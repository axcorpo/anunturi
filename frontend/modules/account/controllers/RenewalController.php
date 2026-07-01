<?php

namespace frontend\modules\account\controllers;
use common\models\Announcement;
use common\models\AnnouncementTranslation;
use common\models\Renewal;
use common\models\UserHasAnnouncement;
use frontend\controllers\MainController;
use frontend\modules\account\models\ProfileForm;
use tws\helpers\Url;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class RenewalController extends MainController
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
						'actions' => ['create'],
						'roles' => ['@'],
					],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [];
	}

	/**
	 * Creates a new Announcement model.
	 * If creation is successful, the browser will be redirected to the 'view' category.
	 *
	 * @return mixed
	 * @throws \yii\db\Exception
	 */
	public function actionCreate()
	{
		$model = new Renewal();
		$model->status = Renewal::STATUS_ACTIVE;
        $model->ip_address = Yii::$app->request->userIP;
		$model->announcement_id = Yii::$app->security->unmaskToken(Yii::$app->request->post()['id']);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The operation was successful.'),
				'body' => [],
			],
		];
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			if (!$model->save()) {
				throw new \Exception();
			}
			$announcement = Announcement::findOne(['id' => $model->announcement_id]);
			$announcement->renewed_at = $model->created_at;
			if (!$announcement->save()) {
				throw new \Exception();
			}
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'The operation was unsuccessful.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}
		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']['title']]);

		return $this->redirect(Yii::$app->request->referrer);
	}

	/**
	 * Finds the Subscriber model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @return \yii\db\ActiveRecord|ProfileForm the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null)
	{
		$modelName = class_exists($modelName) ? $modelName : UserHasAnnouncement::class;
		$model = $modelName::find()
			->alias('f')
			->joinWith([
				'announcement a',
			])
			->where([
				'f.user_id' => Yii::$app->user->id,
				'f.announcement_id' => $id,
			])
			->andWhere([
				'a.status' => Announcement::STATUS_ACTIVE,
				'a.deleted' => Announcement::NO,
			]);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
