<?php

namespace common\services;

use common\models\Announcement;
use common\models\KnowledgeBase;
use common\models\AnnouncementTranslation;
use common\models\RecordVectorIndex;
use Yii;
use yii\db\Expression;

/**
 * Indexes announcements into a dedicated OpenAI vector store (Knowledge Base) for semantic search.
 * Failures are logged only; they never throw out of {@see syncAnnouncement} so business operations are unaffected.
 *
 * Index text is generic listing data only ({@see buildIndexPlainText()}): fields like title, locality, content—any category.
 * There is no fixed domain prompt (e.g. rentals); the visitor’s phrase is sent to the vector search API as the query.
 * Vector hits are not post-filtered in PHP—relevance is whatever OpenAI retrieval returns; tune API params only.
 *
 * Configure {@see \Yii::$app->params}:
 * - `announcementVectorKnowledgeBaseId` — optional PK override of an active OpenAI Knowledge Base. When 0/missing, the KB
 *   linked to the default chat Assistant is used, then any active OpenAI KB ({@see resolveKnowledgeBase()}). If none resolves,
 *   no indexing and no `record_vector_index` writes occur.
 * - `announcementVectorSemanticSearch` — when true, listing search uses vector retrieval when possible
 * - `announcementVectorSemanticSearchScoreThreshold` — optional 0..1 (default in params; higher = stricter)
 * - `announcementVectorSemanticSearchRewriteQuery` — optional bool, default true (OpenAI may rewrite the query string for retrieval only; not a custom site prompt). Set false to keep the raw visitor phrase.
 * - `announcementVectorSemanticSearchRanker` — optional `auto` | `none` | `default-2024-11-15` (OpenAI vector ranker)
 *
 * Temp index files are always written to `@backend/runtime/record_vector` (same path from frontend, backend, or console).
 */
class OpenAiRecordVectorStoreService
{
	/** @var bool */
	private static $knowledgeBaseLookupDone = false;

	/** @var KnowledgeBase|null Cached result of {@see resolveKnowledgeBase()} for this PHP process */
	private static $cachedKnowledgeBase;

	/**
	 * Defer work until after the HTTP response (non-blocking for the user).
	 */
	public static function scheduleSync(int $announcementId): void
	{
		if (!self::isConfigured()) {
			return;
		}
		register_shutdown_function(static function () use ($announcementId): void {
		try {
			self::syncAnnouncement($announcementId);
		} catch (\Throwable $e) {
			// syncAnnouncement should not throw; kept as a safety net so shutdown never breaks the app.
			Yii::error('OpenAiRecordVectorStoreService::syncAnnouncement failed: ' . $e->getMessage(), __METHOD__);
		}
		});
	}

	/**
	 * Remove vectors for an announcement after it is deactivated / cancelled.
	 */
	public static function scheduleWithdraw(int $announcementId): void
	{
		if (!self::isConfigured()) {
			return;
		}
		register_shutdown_function(static function () use ($announcementId): void {
			try {
				self::withdrawFromVectorStore($announcementId);
			} catch (\Throwable $e) {
				Yii::error('OpenAiRecordVectorStoreService::withdrawFromVectorStore failed: ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * Before hard-deleting an announcement row, drop index rows; after response, purge OpenAI objects.
	 *
	 * @return array{0:string,1:?string,2:?string}|null vector_store_id, openai_file_id, vector_store_file_id
	 */
	public static function detachAnnouncementIndexRow(int $announcementId): ?array
	{
		$row = RecordVectorIndex::find()->where(['record_id' => $announcementId])->one();
		if ($row === null) {
			return null;
		}
		$meta = [
			(string) $row->vector_store_id,
			(string) $row->openai_file_id,
			$row->vector_store_file_id !== null && $row->vector_store_file_id !== '' ? (string) $row->vector_store_file_id : null,
		];
		RecordVectorIndex::deleteAll(['record_id' => $announcementId]);
		self::bumpSearchCacheGeneration();
		return $meta;
	}

	public static function scheduleRemotePurge(?array $meta): void
	{
		if ($meta === null || $meta[0] === '' || $meta[1] === '' || !str_starts_with($meta[1], 'file-')) {
			return;
		}
		register_shutdown_function(static function () use ($meta): void {
			try {
				self::tryRemoveRemote($meta[0], $meta[1], $meta[2]);
			} catch (\Throwable $e) {
				Yii::warning('OpenAI vector purge (shutdown): ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * True only when an active OpenAI {@see KnowledgeBase} resolves in DB.
	 * No vector/OpenAI work runs when this is false — including writes to `record_vector_index`.
	 */
	public static function isConfigured(): bool
	{
		return self::resolveKnowledgeBase() !== null;
	}

	public static function semanticSearchEnabled(): bool
	{
		return self::isConfigured() && !empty(Yii::$app->params['announcementVectorSemanticSearch']);
	}

	/**
	 * Bumped on every sync / withdraw / hard-detach so cached `query → record_ids` entries from before
	 * the change become unreachable (the generation is part of the cache key).
	 * Caller-side: callers should never read this directly — the gen is opaque, only its identity matters.
	 */
	public static function bumpSearchCacheGeneration(): void
	{
		if (!Yii::$app->has('cache')) {
			return;
		}
		try {
			Yii::$app->cache->set('avs:gen', microtime(true) . '_' . bin2hex(random_bytes(4)), 0);
		} catch (\Throwable $e) {
			Yii::warning('bumpSearchCacheGeneration: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Stable per-process generation token. Lazy-creates on first read so a cold cache still produces a key.
	 * Returns a short string included in cache keys.
	 */
	public static function currentSearchCacheGeneration(): string
	{
		if (!Yii::$app->has('cache')) {
			return '0';
		}
		$gen = Yii::$app->cache->get('avs:gen');
		if (is_string($gen) && $gen !== '') {
			return $gen;
		}
		try {
			$seed = microtime(true) . '_' . bin2hex(random_bytes(4));
			Yii::$app->cache->set('avs:gen', $seed, 0);
			return $seed;
		} catch (\Throwable $e) {
			return '0';
		}
	}

	/**
	 * Ordered announcement IDs from vector search (best-effort), mapped via {@see RecordVectorIndex}.
	 * Uses several OpenAI search calls (max 50 hits each) with attribute filter `record_id nin …`
	 * to collect up to {@see OpenAIResponsesService::VECTOR_SEARCH_MAX_UNIQUE_CAP} distinct files.
	 *
	 * @param int|null $maxResults Cap 1..250; null uses {@see \Yii::$app->params} `announcementVectorSemanticSearchMaxResults` or 250.
	 * @param array<string,mixed>|null $audit When a variable is passed by reference, it is replaced with diagnostic details (rounds, snippets, mapping).
	 * @return int[]
	 */
	public static function searchAnnouncementIds(string $query, ?int $maxResults = null, &$audit = null, ?float $scoreThresholdOverride = null): array
	{
		$auditActive = func_num_args() > 2;
		if ($auditActive) {
			$audit = [
				'query' => trim($query),
				'configured' => self::isConfigured(),
				'skipped_reason' => null,
				'vector_store_id' => null,
				'max_results_target' => null,
				'per_request' => null,
				'rounds' => [],
				'file_ids_ordered' => [],
				'mapped_record_ids' => [],
				'unmapped_file_ids' => [],
			];
		}

		$query = trim($query);
		if ($query === '') {
			if ($auditActive) {
				$audit['skipped_reason'] = 'empty_query';
			}
			return [];
		}
		if (!self::isConfigured()) {
			if ($auditActive) {
				$audit['skipped_reason'] = 'not_configured';
			}
			return [];
		}
		$kb = self::resolveKnowledgeBase();
		if ($kb === null || empty($kb->vector_store_id)) {
			if ($auditActive) {
				$audit['skipped_reason'] = $kb === null ? 'knowledge_base_missing' : 'vector_store_id_empty';
			}
			return [];
		}
		$vsId = (string) $kb->vector_store_id;
		if ($auditActive) {
			$audit['vector_store_id'] = $vsId;
		}
		$scoreThreshold = max(
			0.0,
			min(1.0, $scoreThresholdOverride ?? (float) (Yii::$app->params['announcementVectorSemanticSearchScoreThreshold'] ?? 0.7))
		);
		$rewriteQuery = !array_key_exists('announcementVectorSemanticSearchRewriteQuery', Yii::$app->params)
			|| !empty(Yii::$app->params['announcementVectorSemanticSearchRewriteQuery']);
		$allowedRankers = ['auto', 'none', 'default-2024-11-15'];
		$ranker = isset(Yii::$app->params['announcementVectorSemanticSearchRanker'])
			? (string) Yii::$app->params['announcementVectorSemanticSearchRanker']
			: 'default-2024-11-15';
		if (!in_array($ranker, $allowedRankers, true)) {
			$ranker = 'auto';
		}
		if ($auditActive) {
			$audit['score_threshold'] = sprintf('%.4f', $scoreThreshold);
			$audit['rewrite_query'] = $rewriteQuery;
			$audit['ranker'] = $ranker;
		}
		$target = $maxResults ?? (int) (Yii::$app->params['announcementVectorSemanticSearchMaxResults'] ?? 250);
		$target = max(1, min(OpenAIResponsesService::vectorSearchMaxUniqueCap(), $target));
		$perCall = OpenAIResponsesService::vectorSearchMaxPerRequest();
		if ($auditActive) {
			$audit['max_results_target'] = $target;
			$audit['per_request'] = $perCall;
		}

		// Cache lookup — same query / parameters reuse previous result for a short TTL.
		$cacheTtl = (int) (Yii::$app->params['announcementVectorSemanticSearchCacheTtl'] ?? 1800);
		$cache = null;
		$cacheKey = null;
		if (!$auditActive && $cacheTtl > 0 && Yii::$app->has('cache')) {
			$cache = Yii::$app->cache;
			$cacheKey = 'avs:' . sha1(implode('|', [
				self::currentSearchCacheGeneration(),
				$vsId,
				$query,
				$target,
				sprintf('%.4f', $scoreThreshold),
				$ranker,
				$rewriteQuery ? '1' : '0',
			]));
			$cached = $cache->get($cacheKey);
			if (is_array($cached)) {
				return $cached;
			}
		}

		$excludeRecordIds = [];
		$fileOrder = [];
		$seenFile = [];
		// Default rounds capped low — most queries plateau within 1–2 rounds; previously up to 25 rounds × 50 hits = 1250 API calls.
		$maxRoundsParam = (int) (Yii::$app->params['announcementVectorSemanticSearchMaxRounds'] ?? 2);
		$maxRoundsParam = max(1, min(25, $maxRoundsParam));
		$maxRounds = min($maxRoundsParam, (int) ceil($target / $perCall) + 1);

		for ($round = 0; $round < $maxRounds && count($fileOrder) < $target; $round++) {
			$filters = null;
			if ($excludeRecordIds !== []) {
				$filters = [
					'type' => 'and',
					'filters' => [
						[
							'key' => 'record_id',
							'type' => 'nin',
							'value' => array_values(array_unique(array_map('intval', $excludeRecordIds))),
						],
					],
				];
			}
			$roundInfo = [
				'round' => $round,
				'filters' => $filters,
				'raw_snippet_count' => 0,
				'new_unique_file_ids_this_round' => [],
				'snippets' => [],
				'api_error' => null,
			];
			try {
				$snippets = OpenAIResponsesService::searchVectorStore(
					$vsId,
					$query,
					$perCall,
					$scoreThreshold,
					$filters,
					$rewriteQuery,
					$ranker
				);
			} catch (\Throwable $e) {
				Yii::warning('Vector search failed (round ' . $round . '): ' . $e->getMessage(), __METHOD__);
				$roundInfo['api_error'] = $e->getMessage();
				if ($auditActive) {
					$audit['rounds'][] = $roundInfo;
				}
				break;
			}
			$roundInfo['raw_snippet_count'] = count($snippets);
			if ($snippets === []) {
				if ($auditActive) {
					$audit['rounds'][] = $roundInfo;
				}
				break;
			}
			$newFileIds = [];
			$snippetLogCap = 30;
			$snippetLogged = 0;
			foreach ($snippets as $s) {
				if (!is_array($s)) {
					continue;
				}
				$fid = (string) ($s['file_id'] ?? '');
				$preview = (string) ($s['text'] ?? '');
				if (function_exists('mb_substr')) {
					$preview = mb_substr($preview, 0, 500);
				} else {
					$preview = substr($preview, 0, 500);
				}
				if ($auditActive && $snippetLogged < $snippetLogCap) {
					$roundInfo['snippets'][] = [
						'file_id' => $fid,
						'filename' => (string) ($s['filename'] ?? ''),
						'text_preview' => $preview,
					];
					$snippetLogged++;
				}
				if ($fid === '' || isset($seenFile[$fid])) {
					continue;
				}
				$seenFile[$fid] = true;
				$fileOrder[] = $fid;
				$newFileIds[] = $fid;
				if (count($fileOrder) >= $target) {
					if ($auditActive) {
						if (count($snippets) > $snippetLogged) {
							$roundInfo['snippets_omitted'] = count($snippets) - $snippetLogged;
						}
						$roundInfo['new_unique_file_ids_this_round'] = $newFileIds;
						$audit['rounds'][] = $roundInfo;
					}
					break 2;
				}
			}
			if ($auditActive && count($snippets) > $snippetLogged) {
				$roundInfo['snippets_omitted'] = count($snippets) - $snippetLogged;
			}
			$roundInfo['new_unique_file_ids_this_round'] = $newFileIds;
			if ($auditActive) {
				$audit['rounds'][] = $roundInfo;
			}
			if ($newFileIds === []) {
				break;
			}
			$rows = RecordVectorIndex::find()
				->where([
					'openai_file_id' => $newFileIds,
					'deleted' => RecordVectorIndex::NO,
					'status' => RecordVectorIndex::STATUS_ACTIVE,
				])
				->all();
			foreach ($rows as $row) {
				$excludeRecordIds[] = (int) $row->record_id;
			}
		}

		if ($fileOrder === []) {
			if ($auditActive) {
				$audit['skipped_reason'] = $audit['skipped_reason'] ?? 'no_file_ids_from_api';
				$audit['file_ids_ordered'] = [];
				$audit['mapped_record_ids'] = [];
				$audit['unmapped_file_ids'] = [];
			}
			if ($cache !== null && $cacheKey !== null) {
				$cache->set($cacheKey, [], $cacheTtl);
			}
			return [];
		}
		$rows = RecordVectorIndex::find()
			->where([
				'openai_file_id' => $fileOrder,
				'deleted' => RecordVectorIndex::NO,
				'status' => RecordVectorIndex::STATUS_ACTIVE,
			])
			->indexBy('openai_file_id')
			->all();
		$ids = [];
		foreach ($fileOrder as $fid) {
			if (!isset($rows[$fid])) {
				continue;
			}
			$ids[] = (int) $rows[$fid]->record_id;
		}
		if ($auditActive) {
			$audit['file_ids_ordered'] = $fileOrder;
			$audit['mapped_record_ids'] = $ids;
			$unmapped = [];
			foreach ($fileOrder as $fid) {
				if (!isset($rows[$fid])) {
					$unmapped[] = $fid;
				}
			}
			$audit['unmapped_file_ids'] = $unmapped;
			$audit['skipped_reason'] = $ids === [] ? 'no_db_rows_for_openai_files' : null;
		}
		if ($cache !== null && $cacheKey !== null) {
			$cache->set($cacheKey, $ids, $cacheTtl);
		}
		return $ids;
	}

	/**
	 * Apply relevance ordering for announcement IDs (MySQL FIELD / PostgreSQL CASE).
	 */
	public static function orderByAnnouncementIdsExpression(string $columnSql, array $ids): Expression
	{
		$ids = array_values(array_unique(array_map('intval', array_filter($ids))));
		if ($ids === []) {
			return new Expression('1');
		}
		$driver = Yii::$app->db->driverName;
		if ($driver === 'mysql' || $driver === 'mysqli') {
			return new Expression('FIELD(' . $columnSql . ', ' . implode(',', $ids) . ')');
		}
		$parts = [];
		foreach ($ids as $i => $id) {
			$parts[] = 'WHEN ' . $id . ' THEN ' . $i;
		}
		return new Expression('CASE ' . $columnSql . ' ' . implode(' ', $parts) . ' ELSE 999999 END');
	}

	/**
	 * Order rows so semantic hits (IDs in $semanticOrderedIds) come first in that order, then others by created_at.
	 *
	 * @param string $idColumnSql Unquoted column reference, e.g. a.id.
	 * @param int[] $semanticOrderedIds
	 * @return array<string, int|\yii\db\Expression> Yii {@see ActiveQuery::orderBy()} payload
	 */
	public static function orderSemanticFirstThenDate(string $idColumnSql, array $semanticOrderedIds): array
	{
		$ids = array_values(array_unique(array_map('intval', array_filter($semanticOrderedIds))));
		if ($ids === []) {
			return [
				'a.created_at' => SORT_DESC,
				'a.id' => SORT_DESC,
			];
		}
		$list = implode(',', $ids);
		$driver = Yii::$app->db->driverName;
		if ($driver === 'mysql' || $driver === 'mysqli') {
			return [
				new Expression("(FIND_IN_SET({$idColumnSql}, '" . $list . "') > 0) DESC"),
				new Expression("FIND_IN_SET({$idColumnSql}, '" . $list . "') ASC"),
				'a.created_at' => SORT_DESC,
				'a.id' => SORT_DESC,
			];
		}
		return [
			new Expression("({$idColumnSql} = ANY(ARRAY[" . $list . "]::int[])) DESC"),
			new Expression("array_position(ARRAY[" . $list . "]::int[], {$idColumnSql}) ASC NULLS LAST"),
			'a.created_at' => SORT_DESC,
			'a.id' => SORT_DESC,
		];
	}

	public static function syncAnnouncement(int $announcementId): void
	{
		if (!self::isConfigured()) {
			return;
		}
		$kb = self::resolveKnowledgeBase();
		$announcement = Announcement::find()
			->where(['id' => $announcementId, 'deleted' => Announcement::NO])
			->with(['announcementTranslations', 'categories', 'announcementHasActions'])
			->one();
		if ($announcement === null) {
			return;
		}

		$row = RecordVectorIndex::find()->where(['record_id' => $announcementId])->one();
		if ($row === null) {
			$row = new RecordVectorIndex();
			$row->record_id = $announcementId;
		}

		$tmpFile = null;
		$openaiFileId = null;
		try {
			OpenAIResponsesService::ensureVectorStore($kb);
			$vsId = (string) $kb->vector_store_id;
			$row->vector_store_id = $vsId;

			if (!empty($row->vector_store_id) && self::isOpenAiFileId($row->openai_file_id)) {
				self::tryRemoveRemote($row->vector_store_id, $row->openai_file_id, $row->vector_store_file_id);
			}

			$text = self::buildIndexPlainText($announcement);
			$tmpDir = self::getRecordVectorTempDirectory();
			if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
				throw new \RuntimeException('Cannot create temp directory: ' . $tmpDir);
			}
			$tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'record_' . $announcementId . '_' . bin2hex(random_bytes(4)) . '.txt';
			if (file_put_contents($tmpFile, $text) === false) {
				throw new \RuntimeException('Failed to write temp index file');
			}

			$openaiFileId = OpenAIResponsesService::uploadAssistantFile($tmpFile, 'record_' . $announcementId . '.txt');
			$attach = OpenAIResponsesService::attachFileToVectorStore(
				$vsId,
				$openaiFileId,
				self::vectorStoreFileAttributes($announcement),
				null
			);
			OpenAIResponsesService::waitForVectorStoreFileReady($vsId, $openaiFileId);

			@unlink($tmpFile);
			$tmpFile = null;

			$row->openai_file_id = $openaiFileId;
			$row->vector_store_file_id = $attach['vector_store_file_id'] !== '' ? $attach['vector_store_file_id'] : null;
			$row->status = RecordVectorIndex::STATUS_ACTIVE;
			$row->deleted = RecordVectorIndex::NO;
			$row->indexed_at = (new \DateTime())->format('Y-m-d H:i:s');
			$row->error_message = null;
			if (!$row->save(false)) {
				throw new \RuntimeException('Failed to save RecordVectorIndex');
			}
			self::bumpSearchCacheGeneration();
		} catch (\Throwable $e) {
			if ($tmpFile !== null && is_file($tmpFile)) {
				@unlink($tmpFile);
			}
			if (self::isOpenAiFileId($openaiFileId)) {
				try {
					OpenAIResponsesService::deleteFile($openaiFileId);
				} catch (\Throwable $ignored) {
				}
			}
			if (self::isConfigured()) {
				if (!self::isOpenAiFileId($row->openai_file_id ?? null)) {
					$row->openai_file_id = '__withdrawn__';
				}
				$row->status = RecordVectorIndex::STATUS_ERROR;
				$row->error_message = mb_substr($e->getMessage(), 0, 65000);
				try {
					$row->save(false);
				} catch (\Throwable $saveEx) {
					Yii::error(
						'OpenAiRecordVectorStoreService: could not persist error state for announcement ' . $announcementId . ': ' . $saveEx->getMessage(),
						__METHOD__
					);
				}
			}
			Yii::warning(
				'OpenAiRecordVectorStoreService::syncAnnouncement (non-fatal) announcement=' . $announcementId . ': ' . $e->getMessage(),
				__METHOD__
			);
		}
	}

	public static function withdrawFromVectorStore(int $announcementId): void
	{
		if (!self::isConfigured()) {
			return;
		}
		$row = RecordVectorIndex::find()->where(['record_id' => $announcementId])->one();
		if ($row === null) {
			return;
		}
		$kb = self::resolveKnowledgeBase();
		try {
			if (!empty($row->vector_store_id) && self::isOpenAiFileId($row->openai_file_id)) {
				self::tryRemoveRemote($row->vector_store_id, $row->openai_file_id, $row->vector_store_file_id);
			}
		} catch (\Throwable $e) {
			Yii::warning('withdrawFromVectorStore remote: ' . $e->getMessage(), __METHOD__);
		}
		$row->status = RecordVectorIndex::STATUS_INACTIVE;
		$row->deleted = RecordVectorIndex::YES;
		$row->openai_file_id = '__withdrawn__';
		$row->vector_store_file_id = null;
		if ($kb !== null && !empty($kb->vector_store_id)) {
			$row->vector_store_id = (string) $kb->vector_store_id;
		} elseif ($row->vector_store_id === null || $row->vector_store_id === '') {
			$row->vector_store_id = '__unknown__';
		}
		$row->indexed_at = null;
		$row->error_message = null;
		try {
			$row->save(false);
			self::bumpSearchCacheGeneration();
		} catch (\Throwable $e) {
			Yii::warning('withdrawFromVectorStore save: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Attributes on the vector store file (OpenAI) — same keys/values are written first in {@see buildIndexPlainText()}.
	 *
	 * @return array<string, int|string|bool>
	 */
	protected static function vectorStoreFileAttributes(Announcement $a): array
	{
		$categoryId = 0;
		$cats = $a->categories;
		if (!empty($cats)) {
			$categoryId = (int) ($cats[0]->id ?? 0);
		}
		return [
			'record_id' => (int) $a->id,
			'status' => (int) $a->status,
			'category' => $categoryId,
			'locality' => trim((string) $a->locality),
			'county' => trim((string) $a->county),
		];
	}

	/**
	 * Plain-text payload for the vector index file: one line per field, always `property: value`.
	 * Values are single-line (newlines collapsed to spaces) for stable chunking.
	 * Starts with the same attributes as the vector store file attachment.
	 */
	protected static function buildIndexPlainText(Announcement $a): string
	{
		$lines = [];
		foreach (self::vectorStoreFileAttributes($a) as $key => $value) {
			if (is_bool($value)) {
				$value = $value ? '1' : '0';
			}
			$lines[] = self::indexPropertyLine((string) $key, (string) $value);
		}
		$lines[] = self::indexPropertyLine('country', trim((string) $a->country));
		$lines[] = self::indexPropertyLine('price', trim((string) $a->price) . ' ' . trim((string) $a->currency));
		// Per-action prices (sell / rent / etc.) — the anunturi listing shows the price from
		// announcement_has_action, so the index must carry it too.
		foreach ($a->announcementHasActions as $aha) {
			if ((int) $aha->deleted === Announcement::YES) {
				continue;
			}
			$actionLabel = '';
			if ($aha->action !== null) {
				$actionLabel = (string) (\common\models\Action::getMyTypes()[$aha->action->type] ?? $aha->action->type);
			}
			$lines[] = self::indexPropertyLine(
				'action_price',
				trim($actionLabel . ' ' . trim((string) $aha->price) . ' ' . trim((string) $aha->currency) . ' ' . trim((string) $aha->uom))
			);
		}
		foreach ($a->announcementTranslations as $tr) {
			if ((int) $tr->deleted === AnnouncementTranslation::YES) {
				continue;
			}
			$lines[] = self::indexPropertyLine('translation_language_id', (string) $tr->language_id);
			$lines[] = self::indexPropertyLine('title', trim((string) $tr->title));
			$lines[] = self::indexPropertyLine('keywords', trim((string) $tr->keywords));
			$lines[] = self::indexPropertyLine('description', trim(preg_replace('/\s+/u', ' ', strip_tags((string) $tr->description))));
			$lines[] = self::indexPropertyLine('content', trim((string) $tr->search_text));
		}
		if (!empty($a->categories)) {
			$lines[] = self::indexPropertyLine('category_ids', implode(', ', array_map(static function ($c) {
				return (string) (int) $c->id;
			}, $a->categories)));
			$categoryNames = [];
			foreach ($a->categories as $c) {
				$ct = $c->getTranslation();
				if ($ct !== null && trim((string) $ct->name) !== '') {
					$categoryNames[] = trim((string) $ct->name);
				}
			}
			if ($categoryNames !== []) {
				$lines[] = self::indexPropertyLine('category_names', implode(', ', $categoryNames));
			}
		}
		return implode("\n", $lines) . "\n";
	}

	/**
	 * Single line `property: value` for index .txt files (vector store ingestion).
	 */
	protected static function indexPropertyLine(string $property, string $value): string
	{
		$property = trim($property);
		$value = trim(preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\r", "\n"], ' ', $value)));
		return $property . ': ' . $value;
	}

	/**
	 * Resolves the KnowledgeBase that hosts the announcement vector index.
	 *
	 * Order: `announcementVectorKnowledgeBaseId` param (optional PK override) → first OpenAI KB
	 * linked to the chat Assistant ({@see \common\models\Assistant::findChatAssistant()}) → oldest
	 * active OpenAI KB in DB. Cached per PHP process.
	 */
	public static function resolveKnowledgeBase(): ?KnowledgeBase
	{
		if (self::$knowledgeBaseLookupDone) {
			return self::$cachedKnowledgeBase;
		}
		self::$knowledgeBaseLookupDone = true;
		self::$cachedKnowledgeBase = null;

		$id = (int) (Yii::$app->params['announcementVectorKnowledgeBaseId'] ?? 0);
		if ($id > 0) {
			$kb = KnowledgeBase::find()
				->where([
					'id' => $id,
					'status' => KnowledgeBase::STATUS_ACTIVE,
					'deleted' => KnowledgeBase::NO,
				])
				->one();
			if ($kb !== null && (int) $kb->provider === KnowledgeBase::PROVIDER_OPENAI) {
				self::$cachedKnowledgeBase = $kb;
				return $kb;
			}
		}

		// Prefer the KB(s) linked to the chat assistant configured in the backend.
		$assistant = \common\models\Assistant::findChatAssistant();
		if ($assistant !== null) {
			foreach ($assistant->knowledgeBases as $kb) {
				if ((int) $kb->provider === KnowledgeBase::PROVIDER_OPENAI
					&& (int) $kb->status === KnowledgeBase::STATUS_ACTIVE
					&& (int) $kb->deleted === KnowledgeBase::NO
				) {
					self::$cachedKnowledgeBase = $kb;
					return $kb;
				}
			}
		}

		// Last resort: any active OpenAI KB (oldest first, deterministic).
		$kb = KnowledgeBase::find()
			->where([
				'provider' => KnowledgeBase::PROVIDER_OPENAI,
				'status' => KnowledgeBase::STATUS_ACTIVE,
				'deleted' => KnowledgeBase::NO,
			])
			->orderBy(['id' => SORT_ASC])
			->one();
		self::$cachedKnowledgeBase = $kb;
		return $kb;
	}

	protected static function isOpenAiFileId(?string $id): bool
	{
		return is_string($id) && $id !== '' && str_starts_with($id, 'file-');
	}

	/**
	 * Local scratch directory for announcement .txt payloads before OpenAI upload.
	 * Always under backend app runtime: {@see \Yii::getAlias()} `@backend/runtime/record_vector`.
	 */
	protected static function getRecordVectorTempDirectory(): string
	{
		return rtrim(Yii::getAlias('@backend'), '/\\') . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'record_vector';
	}

	protected static function tryRemoveRemote(string $vectorStoreId, string $openaiFileId, ?string $vectorStoreFileId): void
	{
		if ($vectorStoreId === '' || !self::isOpenAiFileId($openaiFileId)) {
			return;
		}
		try {
			OpenAIResponsesService::removeFileFromVectorStore($vectorStoreId, $openaiFileId);
		} catch (\Throwable $e) {
			if ($vectorStoreFileId) {
				try {
					OpenAIResponsesService::removeFileFromVectorStore($vectorStoreId, $vectorStoreFileId);
				} catch (\Throwable $e2) {
					Yii::warning('removeFileFromVectorStore: ' . $e->getMessage() . ' / ' . $e2->getMessage(), __METHOD__);
				}
			} else {
				Yii::warning('removeFileFromVectorStore: ' . $e->getMessage(), __METHOD__);
			}
		}
		try {
			OpenAIResponsesService::deleteFile($openaiFileId);
		} catch (\Throwable $e) {
			Yii::warning('deleteFile: ' . $e->getMessage(), __METHOD__);
		}
	}
}
