<?php

namespace frontend\modules\account\controllers;

use common\helpers\UploadHelper;
use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\SettingForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class SettingController extends MainController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'actions' => ['index', 'upload-file'],
						'roles' => ['@'],
					],
				],
			],
		];
	}

	/**
	 * Displays index view.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionIndex()
	{
		$model = $this->findModel(Yii::$app->user->id, SettingForm::class);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'Your profile was successfully updated.'));
			return $this->refresh();
		}

		return $this->render('index', [
			'model' => $model,
		]);
	}

	/**
	 * Uploads a new file.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionUploadFile()
	{
		$model = $this->findModel(Yii::$app->user->id);
		$bodyParams = Yii::$app->request->post();
		$attribute = $bodyParams['attribute'];
		$fileName = $bodyParams['fileName'];
		$response = [];

		try {
			if (!$model->hasAttribute($attribute) || !($file = UploadedFile::getInstanceByName($fileName))) {
				throw new \Exception();
			}
			$oldFilePath = Yii::getAlias("@uploads/user/{$model->id}/{$model->oldAttributes['image']}");
			$fileName = UploadHelper::saveFile($file, $model->getFullName(), "@uploads/user/{$model->id}");
			if (!$fileName) {
				throw new \Exception();
			}
			$model->$attribute = $fileName;
			if (!$model->save()) {
				throw new \Exception();
			}
			if (is_file($oldFilePath) && $oldFilePath != Yii::getAlias("@uploads/user/{$model->id}/{$model->$attribute}")) {
				FileHelper::unlink($oldFilePath);
			}
			$response['success'] = true;
		} catch (\Exception $e) {
			$response['success'] = false;
			$response['error'] = Yii::t('common', 'Could not upload the file.');
		}

		return $this->asJson($response);
	}

	/**
	 * Finds the Subscriber model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @return \yii\db\ActiveRecord|SettingForm the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null)
	{
		$modelName = class_exists($modelName) ? $modelName : User::class;
		$model = $modelName::find()
			->where([
				'id' => $id,
				'deleted' => User::NO,
			]);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
