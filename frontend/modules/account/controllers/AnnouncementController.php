<?php

namespace frontend\modules\account\controllers;

use common\models\Announcement;
use common\models\Picture;
use common\models\Unavailability;
use common\models\UserHasAnnouncement;
use frontend\controllers\MainController;
use frontend\modules\account\models\AnnouncementActivateForm;
use frontend\modules\account\models\AnnouncementCancelForm;
use frontend\modules\account\models\AnnouncementDeleteForm;
use frontend\modules\account\models\AnnouncementForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class AnnouncementController extends MainController
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
						'actions' => ['index', 'view', 'create', 'update', 'activate', 'favourite'],
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'actions' => ['cancel', 'delete',  'delete-file', 'delete-picture', 'delete-source-file', 'delete-favourite'],
						'roles' => ['@'],
						'verbs' => ['POST'],
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
	 * Lists all Announcement models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{

		$query = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'=', 'created_by', Yii::$app->user->id
			])
			->andWhere([
				'a.deleted' => Announcement::NO,
			])
			->orderBy(new Expression("GREATEST(COALESCE(a.renewed_at, 0), COALESCE(a.created_at, 0)) DESC"));
		$counterQuery = $query;
		$totalCount = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'=', 'created_by', Yii::$app->user->id
			])
			->andWhere([
				'a.deleted' => Announcement::NO,
			])
			->count();
		$activeCount = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'=', 'created_by', Yii::$app->user->id
			])
			->andWhere([
				'a.deleted' => Announcement::NO,
			])
			->andWhere(['a.status' => Announcement::STATUS_ACTIVE])
			->count();
		$inactiveCount = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'=', 'created_by', Yii::$app->user->id
			])
			->andWhere([
				'a.deleted' => Announcement::NO,
			])
			->andWhere(['a.status' => Announcement::STATUS_INACTIVE])
			->count();
		$pendingCount = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'=', 'created_by', Yii::$app->user->id
			])
			->andWhere([
				'a.deleted' => Announcement::NO,
			])
			->andWhere(['a.status' => Announcement::STATUS_PENDING])
			->count();
		$rejectedCount = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'=', 'created_by', Yii::$app->user->id
			])
			->andWhere([
				'a.deleted' => Announcement::NO,
			])
			->andWhere(['a.status' => Announcement::STATUS_REJECTED])
			->count();
		if (Yii::$app->request->get('status') != '') {
			$query
				->andWhere(['a.status' => Yii::$app->request->get('status')]);
		}
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'defaultPageSize' => 12,
			],
		]);

        Unavailability::deleteAll([
            'announcement_id' => null,
            'created_by' => Yii::$app->user->id,
        ]);

		return $this->render('index', [
			'dataProvider' => $dataProvider,
			'totalCount' => $totalCount,
			'activeCount' => $activeCount,
			'inactiveCount' => $inactiveCount,
			'pendingCount' => $pendingCount,
			'rejectedCount' => $rejectedCount,
		]);
	}

	/**
	 * Displays a single Announcement model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$model = $this->findModel($id);

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => true,
				'data' => $this->renderAjax('view', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('view', [
			'model' => $model,
		]);
	}

	/**
	 * Creates a new Announcement model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new AnnouncementForm();
		$result = true;

		if (Yii::$app->request->isPost && Yii::$app->request->isAjax && !Yii::$app->request->post('submit')) {
			if (Yii::$app->request->post('category')) {
				Yii::$app->session->set('category', Yii::$app->request->post('category'));
			}
			if (Yii::$app->request->post('action')) {
				Yii::$app->session->set('action', Yii::$app->request->post('action'));
			}
			exit();
		}

		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllAnnouncements']));

			$message = Yii::t('common', 'Record has been created.');

			Yii::$app->session->setFlash('success', $message);

			return $this->redirect(['announcement/index']);
		}

        return $this->render('create', [
            'model' => $model,
        ]);
	}

	/**
	 * Updates an existing Announcement model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel(Yii::$app->security->unmaskToken($id), AnnouncementForm::class);
		$result = true;

		if (Yii::$app->request->isPost && Yii::$app->request->isAjax && !Yii::$app->request->post('submit')) {
			if (Yii::$app->request->post('category')) {
				Yii::$app->session->set('category', Yii::$app->request->post('category'));
			}
			if (Yii::$app->request->post('action')) {
				Yii::$app->session->set('action', Yii::$app->request->post('action'));
			}
			exit();
		}

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllAnnouncements']));

            $message = Yii::t('common', 'Record has been updated.');

            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['announcement/index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

	/**
	 * Activates an announcement.
	 *
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionActivate($id)
	{
        $model = $this->findModel(Yii::$app->security->unmaskToken($id), AnnouncementActivateForm::class);
        $result = true;
        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllAnnouncements']));

            $message = Yii::t('common', 'Announcement activated successfully.');

            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['/account/announcement/index']);
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool) $result,
                'data' => $this->renderAjax('activate', [
                    'model' => $model,
                ]),
            ]);
        }

        return $this->render('activate', [
            'model' => $model,
        ]);
	}

	/**
	 * Cancels an announcement.
	 *
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionCancel($id)
	{
		$model = $this->findModel(Yii::$app->security->unmaskToken($id), AnnouncementCancelForm::class);

		if ($model->cancel()) {
			Yii::$app->session->setFlash('success', Yii::t('frontend', 'Announcement cancelled successfully.'));
		} else {
			Yii::$app->session->setFlash('error', Yii::t('frontend', 'Cannot cancel the selected announcement.'));
		}

		return $this->redirect(['index']);
	}

	/**
	 * Deletes an announcement.
	 *
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionDelete($id)
	{
		$model = $this->findModel(Yii::$app->security->unmaskToken($id), AnnouncementDeleteForm::class);
		$announcementModel = Announcement::findOne(['id' => Yii::$app->security->unmaskToken($id)]);

		if ($model->deleteAnnouncement($announcementModel)) {
			Yii::$app->session->setFlash('success', Yii::t('frontend', 'Announcement deleted successfully.'));
		} else {
			Yii::$app->session->setFlash('error', Yii::t('frontend', 'Cannot delete the selected announcement.'));
		}
		return $this->redirect(['index']);
	}

	/**
	 * Deletes an existing file.
	 *
	 * @param $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionDeleteFile($id)
	{
		$allowedAttributes = ['image'];
        $attribute = Yii::$app->request->post('attribute', 'image');

        if (!in_array($attribute, $allowedAttributes)) {
            throw new NotFoundHttpException(Yii::t('yii', 'The requested page does not exist.'));
        }

        $model = $this->findModel($id);
		$filePath = Yii::getAlias("@uploads/{$this->id}/{$id}/{$model->$attribute}");
		$result = true;

		if (is_file($filePath) && ($result = FileHelper::unlink($filePath))) {
			$model->$attribute = null;
			$model->save(false);
		}
		return $this->asJson([
			'success' => (bool) $result,
			'message' => $result ?
				Yii::t('common', 'Record has been deleted.') :
				Yii::t('common', 'Cannot delete the record.'),
		]);
	}

    /**
     * Deletes an existing source file.
     *
     * @param $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDeleteSourceFile($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
        }
        $model = $this->findModel($id);
        $attribute = Yii::$app->request->post('attribute', 'source_image');
        $filePath = Yii::getAlias("@uploads/{$this->id}/{$id}/{$model->$attribute}");
        $result = true;

        if (is_file($filePath) && ($result = FileHelper::unlink($filePath))) {
            $model->$attribute = null;
            $model->save(false);
        }
        return $this->asJson([
            'success' => (bool) $result,
            'message' => $result ?
                Yii::t('common', 'Record has been deleted.') :
                Yii::t('common', 'Cannot delete the record.'),
        ]);
    }

	/**
	 * Deletes an existing file.
	 *
	 * @param $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionDeletePicture($id)
	{
		if (!Yii::$app->request->isAjax) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}
		$picture = Picture::findOne(['id' => Yii::$app->request->post('key')]);
		$filePath = Yii::getAlias("@uploads/{$this->id}/{$id}/{$picture->image}");
		$result = true;

		if (is_file($filePath) && ($result = FileHelper::unlink($filePath))) {
			$picture->delete(true);
		}
		return $this->asJson([
			'success' => (bool) $result,
			'message' => $result ?
				Yii::t('common', 'Record has been deleted.') :
				Yii::t('common', 'Cannot delete the record.'),
		]);
	}

	/**
	 * Lists all Announcement models.
	 *
	 * @return mixed
	 */
	public function actionFavourite()
	{

		$query = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
				'userHasAnnouncements uha',
			])
			->where([
				'uha.user_id' => Yii::$app->user->id,
				'a.status' => Announcement::STATUS_ACTIVE,
				'a.deleted' => Announcement::NO,
			])
			->orderBy(new Expression("GREATEST(COALESCE(a.renewed_at, 0), COALESCE(a.created_at, 0)) DESC"));
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'defaultPageSize' => 12,
			],
		]);

		return $this->render('favourite', [
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Deletes an announcement.
	 *
	 * @param $announcement_id
	 * @return mixed
	 */
	public function actionDeleteFavourite($announcement_id)
	{
		$model = UserHasAnnouncement::find()
			->alias('uha')
			->andWhere([
				'announcement_id' => $announcement_id,
				'user_id' => Yii::$app->user->identity,
			])
			->one();

		$model->delete();
		return $this->redirect(['favourite']);
	}

	/**
	 * Finds the Announcement model(s) based on its primary key value.
	 * A 404 HTTP exception will be thrown if no record was found.
	 *
	 * @param int|array $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Announcement|AnnouncementForm
	 * @throws NotFoundHttpException if no model was found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Announcement::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
			'created_by' => Yii::$app->user->id,
		]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['deleted' => Announcement::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
