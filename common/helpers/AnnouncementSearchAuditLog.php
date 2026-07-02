<?php

namespace common\helpers;

use Yii;

/**
 * Replaces the entire file {@see Yii::getAlias()} `@uploads/search.log` on every announcement listing request
 * (full overwrite, no append). Empty search still writes one snapshot so the log always reflects the last page load.
 */
final class AnnouncementSearchAuditLog
{
	/**
	 * $payload['query'] may be empty; the log line shows (no text query) in that case.
	 * $payload['semantic_ids_for_query'] lists vector-mapped IDs used for ordering among LIKE matches (when hybrid).
	 *
	 * @param array{
	 *   query:string,
	 *   semantic_feature_enabled:bool,
	 *   openai_invoked:bool,
	 *   openai_audit:?array,
	 *   semantic_ids_for_query:int[],
	 *   sql_mode:string,
	 *   listing_total_count:int,
	 *   listing_first_page_ids:int[]
	 * } $payload
	 */
	public static function writeOverwrite(array $payload): void
	{
		$dir = Yii::getAlias('@uploads');
		if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
			return;
		}
		$path = $dir . DIRECTORY_SEPARATOR . 'search.log';
		$lines = [];
		$lines[] = '=== Announcement listing search ===';
		$lines[] = 'Time (UTC): ' . gmdate('Y-m-d\TH:i:s\Z');
		$q = (string) ($payload['query'] ?? '');
		$lines[] = 'Query: ' . ($q !== '' ? str_replace(["\r\n", "\r", "\n"], ' ', $q) : '(no text query)');
		$lines[] = 'semanticSearchEnabled (params + KB): ' . ($payload['semantic_feature_enabled'] ? 'yes' : 'no');
		$lines[] = 'OpenAI vector search invoked: ' . ($payload['openai_invoked'] ? 'yes' : 'no');
		$lines[] = 'SQL listing mode: ' . $payload['sql_mode'];
		$ids = $payload['semantic_ids_for_query'];
		$lines[] = 'Announcement IDs from vector (after DB map), used for ordering: ' . ($ids === [] ? '(none)' : implode(', ', $ids));
		$lines[] = 'Listing totalCount (after filters): ' . (int) $payload['listing_total_count'];
		$lines[] = 'First page announcement IDs (actual rows returned): ' . (
			$payload['listing_first_page_ids'] === [] ? '(none)' : implode(', ', $payload['listing_first_page_ids'])
		);
		if (array_key_exists('openai_audit', $payload) && $payload['openai_audit'] !== null) {
			$lines[] = '';
			$lines[] = '--- OpenAI vector audit (JSON, includes snippet previews) ---';
			$json = json_encode(
				$payload['openai_audit'],
				JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
			);
			$lines[] = $json !== false ? $json : '{}';
		}
		$content = implode("\n", $lines) . "\n";
		self::writeFileTruncate($path, $content);
	}

	/**
	 * Replace file contents in full (no append). Uses fopen('wb') + flock so Windows reliably truncates;
	 * falls back to temp file + rename if the handle cannot be opened.
	 */
	private static function writeFileTruncate(string $path, string $content): void
	{
		$handle = @fopen($path, 'wb');
		if ($handle !== false) {
			try {
				@flock($handle, LOCK_EX);
				fwrite($handle, $content);
				fflush($handle);
				@flock($handle, LOCK_UN);
			} finally {
				fclose($handle);
			}

			return;
		}

		Yii::warning('AnnouncementSearchAuditLog: fopen(wb) failed; retry temp+rename for: ' . $path, __METHOD__);

		$dir = dirname($path);
		$tmp = $dir . DIRECTORY_SEPARATOR . '.search-log.' . bin2hex(random_bytes(8)) . '.tmp';
		if (@file_put_contents($tmp, $content) === false) {
			Yii::warning('AnnouncementSearchAuditLog: cannot write temp or target: ' . $path, __METHOD__);

			return;
		}
		if (!@rename($tmp, $path)) {
			@unlink($path);
			if (!@rename($tmp, $path)) {
				@copy($tmp, $path);
			}
			@unlink($tmp);
		}
	}
}
