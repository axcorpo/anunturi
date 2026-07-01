<?php

namespace frontend\modules\account\controllers;
use common\models\Announcement;
use common\models\AnnouncementTranslation;
use common\models\UserHasAnnouncement;
use frontend\controllers\MainController;
use frontend\modules\account\models\ProfileForm;
use tws\helpers\Url;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class FavouriteController extends MainController
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
						'actions' => ['index', 'create', 'delete'],
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
	 * Displays index view.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionIndex()
    {
        $query = Announcement::find()
			->alias('a')
				->joinWith([
					'users u',
				])
				->where([
					'u.id' => Yii::$app->user->id,
				])
				->andWhere([
					'a.status' => Announcement::STATUS_ACTIVE,
					'a.deleted' => Announcement::NO,
				]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => 5,
            ],
        ]);

        return $this->render('/announcement/favourite', [
            'dataProvider' => $dataProvider,
        ]);
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
		$model = UserHasAnnouncement::find()
			->where([
				'user_id' => Yii::$app->user->id,
				'announcement_id' => Yii::$app->request->post()['id'],
			])
			->one();
		if (!$model) {
			$model = new UserHasAnnouncement();
			$model->user_id = Yii::$app->user->id;
			$model->announcement_id =  Yii::$app->request->post()['id'];
		}
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The operation was successful.'),
				'body' => [],
			],
		];
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$model->save();
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

		if ($model->announcement->translation->slug) {
			$url = Url::to(['/announcement/default/view', 'slug' => $model->announcement->translation->slug]);
		} else  {
			$url = ['index'];
		}

		return $this->redirect($url);
	}

	/**
	 * Deletes existing Announcement models.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param null|int $id
	 * @return mixed
	 * @throws \Throwable
	 * @throws NotFoundHttpException if no model was found
	 */
	public function actionDelete($id = null)
	{
		$model = UserHasAnnouncement::find()
			->where([
				'user_id' => Yii::$app->user->id,
				'announcement_id' => $id,
			])
			->one();
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The operation was successful.'),
				'body' => [],
			],
		];
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$model->delete();
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

		return $this->redirect(['index']);
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
