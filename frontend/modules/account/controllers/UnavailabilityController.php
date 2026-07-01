<?php

namespace frontend\modules\account\controllers;

use common\models\Unavailability;
use frontend\controllers\MainController;
use frontend\modules\account\models\AnnouncementReservationSearch;
use frontend\modules\account\models\AnnouncementUnavailabilitySearch;
use frontend\modules\account\models\CustomerAnnouncementReservationSearch;
use frontend\modules\account\models\CustomerAnnouncementUnavailabilitySearch;
use frontend\modules\account\models\ReservationSearch;
use frontend\modules\account\models\UnavailabilityForm;
use frontend\modules\account\models\UnavailabilitySearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class UnavailabilityController extends MainController
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
						'actions' => ['index', 'view', 'list', 'create', 'update', 'delete'],
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
		$searchModel = new UnavailabilitySearch();
		$searchModel->load(Yii::$app->request->get());
		$searchModel->load(Yii::$app->request->post());

		if (Yii::$app->request->get('announcement_id')) {
            $reservationSearchModel = new AnnouncementReservationSearch();
            $reservationSearchModel->load(Yii::$app->request->get());
            $reservationSearchModel->load(Yii::$app->request->post());
        }

		if (Yii::$app->request->isAjax) {
			return $this->asJson(array_merge($searchModel->getEvents(), $reservationSearchModel->getEvents()));
		}
		return $this->render('index');
	}

	/**
	 * Displays a single Subscription model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView()
	{
        $searchModel = new AnnouncementUnavailabilitySearch();
        $searchModel->load(Yii::$app->request->get());
        $searchModel->load(Yii::$app->request->post());

        $reservationSearchModel = new AnnouncementReservationSearch();
        $reservationSearchModel->load(Yii::$app->request->get());
        $reservationSearchModel->load(Yii::$app->request->post());

        if (Yii::$app->request->isAjax) {
            return $this->asJson(array_merge($searchModel->getEvents(), $reservationSearchModel->getEvents()));
        }
        return $this->render('index');
	}
    /**
     * Displays a single Subscription model.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionList()
    {
        $searchModel = new CustomerAnnouncementUnavailabilitySearch();
        $searchModel->load(Yii::$app->request->get());
        $searchModel->load(Yii::$app->request->post());

        $reservationSearchModel = new CustomerAnnouncementReservationSearch();
        $reservationSearchModel->load(Yii::$app->request->get());
        $reservationSearchModel->load(Yii::$app->request->post());

        if (Yii::$app->request->isAjax) {
            return $this->asJson(array_merge($searchModel->getEvents(), $reservationSearchModel->getEvents()));
        }
        return $this->render('index');
    }

	/**
	 * Creates a new Unavailability model.
	 * If creation is successful, the browser will be redirected to the 'view' category.
	 *
	 * @return mixed
	 * @throws \yii\db\Exception
	 */
	public function actionCreate()
	{
		$model = new UnavailabilityForm();

		$result = true;

		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllUnavailabilities']));

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
				'data' => $this->renderAjax('update', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}


	/**
	 * Updates an existing Subscription model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, UnavailabilityForm::class);
		$result = true;

		if ($model->load(Yii::$app->request->post()) && ($result = $model->save())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllUnavailabilities']));

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
	 * Cancels an announcement.
	 *
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionDelete($id)
	{
		$model = $this->findModel($id, null, false);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The delete operation was successful.'),
				'body' => [],
			],
		];
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$model->delete(true);
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllUnavailabilities']));
			$dbTransaction->commit();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
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
	 * Finds the Subscriber model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Unavailability
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Unavailability::class;
		$model = $modelName::find()
			->alias('u')
			->where([
				'u.id' => $id,
				'u.deleted' => Unavailability::NO,
			]);


		if ($asActiveQuery) {
			if (!$model->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $model;
		}

		$model->andWhere(['u.deleted' => Unavailability::NO]);
		if (($model = $model->one()) !== null) {
			return $model;
		}
		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
