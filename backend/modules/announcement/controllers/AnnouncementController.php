<?php

namespace backend\modules\announcement\controllers;

use backend\controllers\MainController;
use backend\modules\announcement\models\AnnouncementForm;
use backend\modules\announcement\models\AnnouncementSearch;
use common\models\Action;
use frontend\modules\announcement\models\ReviewForm;
use common\helpers\UploadHelper;
use common\models\Announcement;
use common\models\Picture;
use kartik\depdrop\DepDropAction;
use Yii;
use yii\caching\TagDependency;
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
						'actions' => ['index', 'view', 'dt-announcements', 'category-actions'],
						'roles' => ['viewAnnouncement'],
					],
					[
						'allow' => true,
						'actions' => ['create', 'category-fields'],
						'roles' => ['createAnnouncement'],
					],
					[
						'allow' => true,
						'actions' => ['update'],
						'roles' => ['updateAnnouncement', 'category-fields'],
					],
					[
						'allow' => true,
						'actions' => ['delete', 'delete-file', 'delete-picture', 'delete-source-file'],
						'roles' => ['deleteAnnouncement'],
						'verbs' => ['POST'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => ['restoreAnnouncement'],
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
		return [
			'dt-announcements' => AnnouncementSearch::class,
			'category-actions' => [
				'class' => DepDropAction::class,
				'outputCallback' => function ($selectedId, $params) {
					return Action::queryActionByCategoryId($selectedId);
				},
			],
		];
	}

	/**
	 * Lists all Announcement models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		if (Yii::$app->request->get('deleted') == Announcement::YES) {
			if (!Yii::$app->settings->get('enableSoftDelete') || !Yii::$app->user->can('deleteAnnouncement')) {
				return $this->redirect(['index']);
			}
		}
		return $this->render('index');
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
        $reviewModel = new ReviewForm();

        $result = true;
        Yii::$app->eventLog->beginRecord($reviewModel);

        if ($reviewModel->load(Yii::$app->request->post()) && $reviewModel->saveModel()) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllReviews']));

            Yii::$app->eventLog->endRecord();

            $message = Yii::t('common', 'Record has been created.');

            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['view', 'id' => $model->id]);
        }


        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool) $result,
                'data' => $this->renderAjax('view', [
                    'model' => $model,
                    'reviewModel' => $reviewModel,
                ]),
            ]);
        }

        return $this->render('view', [
            'model' => $model,
            'reviewModel' => $reviewModel,
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
		$model = new AnnouncementForm();

		$result = true;

		Yii::$app->eventLog->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllCategories']));

			Yii::$app->eventLog->endRecord();

			$message = Yii::t('common', 'Record has been created.');

			if (Yii::$app->request->isAjax) {
				return $this->asJson([
					'success' => true,
					'message' => $message,
				]);
			}

			Yii::$app->session->setFlash('success', $message);

			return $this->redirect(['view', 'id' => $model->id]);
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => (bool) $result,
				'data' => $this->renderAjax('create', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}

    /**
     * Creates a new Announcement model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCategoryFields()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->session->set('category', Yii::$app->request->post('category'));
            return $this->asJson([
                'category' => Yii::$app->request->post('category'),
            ]);
        }
    }


    /**
	 * Updates an existing Announcement model.
	 * If update is successful, the browser will be redirected to the 'view' category.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 * @throws \yii\db\Exception
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, AnnouncementForm::class);

		$result = true;

		Yii::$app->eventLog->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllCategories']));

			Yii::$app->eventLog->endRecord();

			$message = Yii::t('common', 'Record has been updated.');

			if (Yii::$app->request->isAjax) {
				return $this->asJson([
					'success' => true,
					'message' => $message,
				]);
			}

			Yii::$app->session->setFlash('success', $message);

			return $this->redirect(['view', 'id' => $model->id]);
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => (bool) $result,
				'data' => $this->renderAjax('update', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
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
		$bodyParams = Yii::$app->request->post();
		$isPermanent = !Yii::$app->settings->get('enableSoftDelete') || ($bodyParams['dt_operation'] == 'delete-permanently' || $bodyParams['dt_bulk_operation'] == 'delete-permanently');
		if ($id === null) {
			$id = Yii::$app->request->post('selection');
		}
		$models = $this->findModel($id, null, true);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The delete operation was successful.'),
				'body' => [],
			],
		];
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$deletedModels = [];
			/** @var Announcement $model */
			foreach ($models->each() as $model) {
				Yii::$app->eventLog->beginRecord($model);
				if ($model->delete($isPermanent)) {
					$deletedModels[] = $model->id;
					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}
			if ($isPermanent) {
				foreach ($deletedModels as $deletedModel) {
					FileHelper::removeDirectory(Yii::getAlias("@uploads/{$this->id}/{$deletedModel}"));
				}
			}
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllAnnouncements']));
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'The delete operation was unsuccessful.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}
		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

		return $this->redirect(['index']);
	}

	/**
	 * Restores Announcement models that are marked as deleted.
	 * If restoration is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param null|int $id
	 * @return mixed
	 * @throws NotFoundHttpException if no model was found
	 */
	public function actionRestore($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->post('selection');
		}
		$models = $this->findModel($id, null, true);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The restore operation was successful.'),
				'body' => [],
			],
		];
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			/** @var Announcement $model */
			foreach ($models->each() as $model) {
				Yii::$app->eventLog->beginRecord($model);
				if ($model->restore()) {
					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllAnnouncements']));
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'The restore operation was unsuccessful.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}
		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

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
		$model = $this->findModel($id);
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
