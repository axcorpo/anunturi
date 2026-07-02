<?php

namespace console\controllers;

use common\models\Announcement;
use common\models\Integration;
use common\models\KnowledgeBase;
use common\models\RecordVectorIndex;
use common\services\OpenAiRecordVectorStoreService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Builds / maintains the OpenAI semantic index for announcements.
 *
 * Requires:
 *  - an active default Integration of type OpenAI (API key);
 *  - an active OpenAI KnowledgeBase (linked to the chat Assistant, or the
 *    `announcementVectorKnowledgeBaseId` params override).
 *
 * Usage:
 *   php yii ai-index/setup sk-...    # store the OpenAI API key + create the default KnowledgeBase
 *   php yii ai-index/status          # index coverage stats
 *   php yii ai-index/reindex         # (re)index every displayable announcement
 *   php yii ai-index/reindex 123     # (re)index a single announcement by id
 *   php yii ai-index/withdraw 123    # remove a single announcement from the vector store
 *   php yii ai-index/cleanup         # withdraw announcements no longer displayable
 */
class AiIndexController extends Controller
{
	/**
	 * One-shot provisioning: stores the OpenAI API key as the default Integration (type OpenAI)
	 * and ensures an active OpenAI KnowledgeBase exists (the vector store itself is created lazily
	 * on the first sync). Run `ai-index/reindex` afterwards to backfill.
	 *
	 * @param string|null $apiKey OpenAI API key (sk-...). Omit to keep the existing key and only ensure the KB.
	 * @param string $kbName Name of the knowledge base row.
	 * @return int
	 */
	public function actionSetup($apiKey = null, $kbName = 'Anunturi')
	{
		if ($apiKey !== null && trim($apiKey) !== '') {
			$integration = Integration::find()
				->where([
					'type' => Integration::TYPE_OPENAI,
					'deleted' => Integration::NO,
				])
				->orderBy(['default' => SORT_DESC, 'id' => SORT_ASC])
				->one();
			if ($integration === null) {
				$integration = new Integration();
				$integration->name = 'OpenAI';
				$integration->type = Integration::TYPE_OPENAI;
			}
			$integration->data = json_encode(['api_key' => trim($apiKey)], JSON_UNESCAPED_SLASHES);
			$integration->default = Integration::YES;
			$integration->status = Integration::STATUS_ACTIVE;
			$integration->deleted = Integration::NO;
			if (!$integration->save(false)) {
				$this->stderr("Failed to save the OpenAI integration.\n", Console::FG_RED);
				return ExitCode::UNSPECIFIED_ERROR;
			}
			$this->stdout("OpenAI API key stored (integration #{$integration->id}).\n", Console::FG_GREEN);
		}

		$kb = KnowledgeBase::find()
			->where([
				'provider' => KnowledgeBase::PROVIDER_OPENAI,
				'status' => KnowledgeBase::STATUS_ACTIVE,
				'deleted' => KnowledgeBase::NO,
			])
			->orderBy(['id' => SORT_ASC])
			->one();
		if ($kb === null) {
			$kb = new KnowledgeBase();
			$kb->name = $kbName;
			$kb->description = 'Announcement listing semantic index';
			$kb->provider = KnowledgeBase::PROVIDER_OPENAI;
			$kb->status = KnowledgeBase::STATUS_ACTIVE;
			$kb->deleted = KnowledgeBase::NO;
			if (!$kb->save(false)) {
				$this->stderr("Failed to create the knowledge base.\n", Console::FG_RED);
				return ExitCode::UNSPECIFIED_ERROR;
			}
			$this->stdout("Knowledge base '{$kb->name}' created (#{$kb->id}).\n", Console::FG_GREEN);
		} else {
			$this->stdout("Knowledge base '{$kb->name}' already active (#{$kb->id}).\n", Console::FG_GREEN);
		}

		$this->stdout("Setup complete. Run `php yii ai-index/reindex` to build the vector index.\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * Shows index coverage: how many displayable announcements are actually present in the vector store.
	 *
	 * @return int
	 */
	public function actionStatus()
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured (create an active OpenAI KnowledgeBase in the DB — or set the announcementVectorKnowledgeBaseId override — and configure the OpenAI Integration).\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		$displayableIds = $this->buildDisplayableQuery()->column();
		$displayable = count($displayableIds);

		$indexedIds = RecordVectorIndex::find()
			->where(['deleted' => RecordVectorIndex::NO, 'status' => RecordVectorIndex::STATUS_ACTIVE])
			->select('record_id')
			->column();
		$indexedDisplayable = count(array_intersect(
			array_map('intval', $displayableIds),
			array_map('intval', $indexedIds)
		));
		$errors = (int) RecordVectorIndex::find()
			->where(['status' => RecordVectorIndex::STATUS_ERROR])
			->count();
		$missing = $displayable - $indexedDisplayable;
		$stale = count($indexedIds) - $indexedDisplayable;

		$this->stdout("Displayable announcements:  {$displayable}\n");
		$this->stdout("Indexed (displayable):      {$indexedDisplayable}\n");
		$this->stdout("Missing from the index:     {$missing}\n", $missing > 0 ? Console::FG_RED : Console::FG_GREEN);
		$this->stdout("Stale rows (not listable):  {$stale}\n", $stale > 0 ? Console::FG_YELLOW : Console::FG_GREEN);
		$this->stdout("Index rows in error:        {$errors}\n", $errors > 0 ? Console::FG_YELLOW : Console::FG_GREEN);
		if ($missing > 0) {
			$this->stdout("Run `php yii ai-index/reindex` to index the missing announcements.\n", Console::FG_YELLOW);
		}
		if ($stale > 0) {
			$this->stdout("Run `php yii ai-index/cleanup` to withdraw stale announcements.\n", Console::FG_YELLOW);
		}
		return ExitCode::OK;
	}

	/**
	 * (Re)index displayable announcements (active, with an active subscription) into the OpenAI vector store.
	 *
	 * @param int|null $id Optional single announcement id.
	 * @return int
	 */
	public function actionReindex($id = null)
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured (create an active OpenAI KnowledgeBase in the DB — or set the announcementVectorKnowledgeBaseId override — and configure the OpenAI Integration).\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		if ($id !== null) {
			$ids = [(int) $id];
		} else {
			$ids = array_map('intval', $this->buildDisplayableQuery()->column());
		}

		$total = count($ids);
		if ($total === 0) {
			$this->stdout("No matching announcements to index.\n", Console::FG_YELLOW);
			return ExitCode::OK;
		}

		$this->stdout("Indexing {$total} announcement(s)...\n", Console::FG_GREEN);
		$done = 0;
		$failed = 0;
		foreach ($ids as $announcementId) {
			// syncAnnouncement never throws; success is reflected in the index row status.
			OpenAiRecordVectorStoreService::syncAnnouncement($announcementId);
			$row = RecordVectorIndex::find()->where(['record_id' => $announcementId])->one();
			if ($row !== null && (int) $row->status === RecordVectorIndex::STATUS_ACTIVE) {
				$done++;
				$this->stdout('.', Console::FG_GREEN);
			} else {
				$failed++;
				Yii::error('AiIndexController reindex announcement ' . $announcementId . ': ' . ($row->error_message ?? 'unknown'), __METHOD__);
				$this->stdout('x', Console::FG_RED);
			}
		}

		$this->stdout("\nDone. Indexed: {$done}, failed: {$failed}" . ($failed > 0 ? ' — see record_vector_index.error_message' : '') . ".\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * Remove a single announcement from the vector store.
	 *
	 * @param int $id Announcement id.
	 * @return int
	 */
	public function actionWithdraw($id)
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured.\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		OpenAiRecordVectorStoreService::withdrawFromVectorStore((int) $id);
		$this->stdout("Withdrawn announcement {$id} from the vector store.\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * Withdraw vectors for announcements that are indexed but no longer displayable
	 * (inactive, deleted, or subscription expired).
	 *
	 * @return int
	 */
	public function actionCleanup()
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured.\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		$displayableIds = array_map('intval', $this->buildDisplayableQuery()->column());

		$staleQuery = RecordVectorIndex::find()
			->where(['status' => RecordVectorIndex::STATUS_ACTIVE, 'deleted' => RecordVectorIndex::NO]);
		if ($displayableIds !== []) {
			$staleQuery->andWhere(['not in', 'record_id', $displayableIds]);
		}
		$staleIds = array_map('intval', $staleQuery->select(['record_id'])->column());

		$total = count($staleIds);
		if ($total === 0) {
			$this->stdout("Nothing to clean up.\n", Console::FG_GREEN);
			return ExitCode::OK;
		}

		$this->stdout("Withdrawing {$total} stale announcement vector(s)...\n", Console::FG_GREEN);
		foreach ($staleIds as $announcementId) {
			OpenAiRecordVectorStoreService::withdrawFromVectorStore($announcementId);
			$this->stdout('.', Console::FG_GREEN);
		}

		$this->stdout("\nDone. Withdrawn: {$total}.\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * IDs of announcements displayable in the public listing: active announcement
	 * with an active (non-deleted) subscription — same base rules as
	 * {@see Announcement::provideAnnouncements()}.
	 *
	 * @return \yii\db\ActiveQuery
	 */
	protected function buildDisplayableQuery()
	{
		return Announcement::find()
			->alias('a')
			->joinWith(['subscriptions s'], false)
			->where([
				'a.status' => Announcement::STATUS_ACTIVE,
				'a.deleted' => Announcement::NO,
				's.status' => Announcement::STATUS_ACTIVE,
				's.deleted' => Announcement::NO,
			])
			->select(['a.id'])
			->distinct();
	}
}
