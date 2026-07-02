<?php

namespace common\components;

use richweber\google\translate\Translation as BaseTranslation;

class Translation extends BaseTranslation
{
	/**
	 * @inheritdoc
	 * Overrides parent to use POST (avoids URL length limits) and returns translated text directly.
	 *
	 * @param string $source Source language code
	 * @param string $target Target language code
	 * @param string $text Content to translate (plain text or HTML)
	 * @return string|null Translated content or null on failure
	 */
	public function translate($source, $target, $text)
	{
		$params = http_build_query([
			'key' => $this->key,
			'source' => $source,
			'target' => $target,
			'format' => 'html',
			'q' => $text,
		]);
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
				'content' => $params,
				'timeout' => 30,
			],
		]);
		$response = @file_get_contents(self::API_URL, false, $context);
		if ($response === false) {
			return null;
		}
		$data = json_decode($response, true);
		return $data['data']['translations'][0]['translatedText'] ?? null;
	}
}
