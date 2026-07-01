<?php

namespace frontend\models;

use common\models\Integration;
use Yii;
use yii\base\Model;
use yii\web\Cookie;

class SpvForm extends Model
{
	/**
	 * @var string Accept Cookies field.
	 */
	public $spv;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['spv'], 'required'],
			['workEmail', 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [];
	}

	/**
	 * Sets accept cookies.
	 *
	 * @return bool whether the email was sent.
	 */
	public function setCookies()
	{
		$cookie = new Cookie([
			'name' => 'spv',
			'value' => $this->spv,
			'expire' => time() + 86400 * 1,
		]);
		Yii::$app->getResponse()->getCookies()->add($cookie);
		return true;
	}

	public function saveModel()
	{
		try {
			$errors = [];
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, 'https://logincert.anaf.ro/anaf-oauth2/v1/token');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
				'grant_type' => 'authorization_code',
				'code' => $this->spv,
				'client_id' => Yii::$app->params['spv.clientId'],
				'client_secret' => Yii::$app->params['spv.clientSecret'],
				'redirect_uri' => Yii::$app->params['spv.redirect'],
				'token_content_type' => 'jwt',
			]));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			if ($response === false) {
				$errors[] = curl_error($curl);
			}
			curl_close($curl);
			$result = json_decode($response, true);

			if (!empty($errors)) {
				throw new \Exception();
			}
			$integration = Integration::findOne(['type' => Integration::TYPE_SPV]);
			if (empty($integration)) {
				$model = new Integration();
			} else {
				$model = Integration::findOne(['id' => $integration->id]);
			}
			if (!empty($result['access_token'])) {
				$model->name = 'Spaţiul Privat Virtual';
				$model->data = $response;
				$model->type = Integration::TYPE_SPV;
				$model->expire_at = date('Y-m-d H:i:s', (time() + $result['expires_in']));
				$model->status = Integration::STATUS_ACTIVE;
				$model->deleted = Integration::NO;
				if (!$model->save()) {
					throw new \Exception();
				} else {
					$cookieCollection = Yii::$app->getResponse()->cookies;
					$cookieCollection->remove('spv');
				}
			}
			return true;
		} catch(\Exception $e) {
			if (!empty($errors)) {
				foreach ($errors as $error) {
					if (!empty($error)) {
						$this->addError('', $error);
					}
				}
			}
			return false;
		}
	}

	/**
	 * Generates a short URL-friendly token from combined tokens.
	 *
	 * @param array $tokens
	 * @return string
	 */
	public function generateShortUrlToken(array $tokens)
	{
		// Combine tokens with a delimiter
		$combinedToken = implode('|', $tokens);

		// Hash the combined token using SHA-256 for security
		$hash = hash('sha256', $combinedToken, true);

		// Encode the hash in a URL-friendly base64 format
		$shortToken = $this->base64UrlEncode($hash);

		return $shortToken;
	}

	/**
	 * Encodes data in a URL-friendly base64 format.
	 *
	 * @param string $data
	 * @return string
	 */
	private function base64UrlEncode($data)
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * Decodes a URL-friendly base64 encoded string.
	 *
	 * @param string $data
	 * @return string
	 */
	private function base64UrlDecode($data)
	{
		return base64_decode(strtr($data, '-_', '+/'));
	}

	/**
	 * Verifies the short URL-friendly token.
	 *
	 * @param string $shortToken
	 * @param array $tokens
	 * @return bool
	 */
	public function verifyShortUrlToken($shortToken, array $tokens)
	{
		// Re-generate the short token from the original tokens
		$expectedToken = $this->generateShortUrlToken($tokens);

		// Compare the provided short token with the expected one
		return hash_equals($shortToken, $expectedToken);
	}
}
