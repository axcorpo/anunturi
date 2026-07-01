<?php
namespace console\controllers;

use common\models\Integration;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Expression;

ini_set('memory_limit', '-1');
set_time_limit(0);

class IntegrationController extends Controller
{
	/**
	 * @var \DateTime The current date and time instance used for this process operations.
	 */
	public static $currentDate;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		self::$currentDate = new \DateTime();
	}

	/**
	 * Run the invoice check.
	 *
	 * @return int
	 */
	public function actionRun()
	{
		try {
			$currentDate = self::$currentDate->format('Y-m-d H:i:s');
			$count = 0;
			$integrations = Integration::find()
				->where([
					'type' => Integration::TYPE_SPV,
					'status' => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
				])
				->andWhere(new Expression("TIMESTAMPDIFF(SECOND, '{$currentDate}', [[expire_at]]) < 0"))
				->all();
			foreach ($integrations as $integration) {
				$data = json_decode($integration->data, true);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, 'https://logincert.anaf.ro/anaf-oauth2/v1/token');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
					'grant_type' => 'refresh_token',
					'client_id' => Yii::$app->params['spv.clientId'],
					'client_secret' => Yii::$app->params['spv.clientSecret'],
					'refresh_token' => $data['refresh_token'],
				]));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				if ($response === false) {
					continue;
				}
				curl_close($curl);
				$result = json_decode($response, true);

				if (!empty($result['access_token'])) {
					$integration->data = $response;
					$integration->expire_at = date('Y-m-d H:i:s', (time() + $result['expires_in']));
					if (!$integration->save()) {
						continue;
					}
				}

				$count++;
			}

			$this->stdout("[" . date("Y-m-d H:i:s") . "] Processed {$count} integration(s).");
			return ExitCode::OK;
		} catch (\Exception $e) {
			$this->stderr($e->getMessage());
			return ExitCode::UNSPECIFIED_ERROR;
		} catch (\Throwable $e) {
			$this->stderr($e->getMessage());
			return ExitCode::UNSPECIFIED_ERROR;
		}
	}
}
