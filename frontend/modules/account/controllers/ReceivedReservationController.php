<?php

namespace frontend\modules\account\controllers;

use backend\modules\announcement\models\ReservationForm;
use common\helpers\UploadHelper;
use common\models\Announcement;
use common\models\Reservation;
use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\AnnouncementCancelForm;
use frontend\modules\account\models\CustomerReservationActivateForm;
use frontend\modules\account\models\ReceivedReservationSearch;
use frontend\modules\account\models\ProfileForm;
use frontend\modules\account\models\ReservationCancelForm;
use frontend\modules\account\models\ReservationSearch;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ReceivedReservationController extends MainController
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
						'actions' => ['index', 'dt-reservations', 'update', 'create'],
						'roles' => ['@'],
					],
                    [
                        'allow' => true,
                        'actions' => ['cancel', 'activate'],
                        'roles' => ['@'],
                        'verbs' => ['POST'],
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
        $query = Reservation::find()
            ->alias('r')
            ->joinWith([
                'announcement a'
            ])
            ->where([
                '=', 'a.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                '!=', 'r.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                'r.deleted' => Reservation::NO,
                'a.status' => Announcement::STATUS_ACTIVE,
                'a.deleted' => Announcement::NO,
            ])
//            ->orderBy(new Expression("GREATEST(COALESCE(a.renewed_at, 0), COALESCE(a.created_at, 0)) DESC"));
        ->orderBy([
            'r.start_at' => SORT_ASC
            ]);


        $totalCount = Reservation::find()
            ->alias('r')
            ->joinWith([
                'announcement a'
            ])
                ->where([
                    '=', 'a.created_by', Yii::$app->user->identity->id,
                ])
                ->andWhere([
                    '!=', 'r.created_by', Yii::$app->user->identity->id,
                ])
            ->andWhere([
                'r.deleted' => Reservation::NO,
            ])
            ->count();

        $activeCount = Reservation::find()
            ->alias('r')
            ->joinWith([
                'announcement a'
            ])
            ->where([
                '=', 'a.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                '!=', 'r.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere(['r.status' => Reservation::STATUS_ACTIVE])
            ->count();

        $pendingCount = Reservation::find()
            ->alias('r')
            ->joinWith([
                'announcement a'
            ])
            ->where([
                '=', 'a.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                '!=', 'r.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere(['r.status' => Reservation::STATUS_PENDING])
            ->count();

        $finishedCount = Reservation::find()
            ->alias('r')
            ->joinWith([
                'announcement a'
            ])
            ->where([
                '=', 'a.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                '!=', 'r.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere(['r.status' => Reservation::STATUS_FINISHED])
            ->count();

        $canceledCount = Reservation::find()
            ->alias('r')
            ->joinWith([
                'announcement a'
            ])
            ->where([
                '=', 'a.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                '!=', 'r.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere(['r.status' => Reservation::STATUS_CANCELED])
            ->count();

        if (Yii::$app->request->get('status') != '') {
            $query
                ->andWhere(['r.status' => Yii::$app->request->get('status')]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => 10,
            ],
        ]);

        $searchModel = new ReceivedReservationSearch();
        $searchModel->load(Yii::$app->request->get());
        $searchModel->load(Yii::$app->request->post());

        if (Yii::$app->request->isAjax) {
            return $this->asJson($searchModel->getEvents());
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'pendingCount' => $pendingCount,
            'finishedCount' => $finishedCount,
            'canceledCount' => $canceledCount,
            'searchModel' => $searchModel,
        ]);
    }
    /**
     * Creates a new Company model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $model = new ReservationForm();
        $model->announcement_id = Yii::$app->request->get('announcement_id');
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllReservations']));

            $message = Yii::t('common', 'Record has been created.');
            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'id' => $model->id,
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
     * Updates an existing Reservation model.
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
        $model = $this->findModel($id, ReservationForm::class);

        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllReservations']));

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
    public function actionCancel($id)
    {
        $model = $this->findModel($id, ReservationCancelForm::class);

        if ($model->cancel()) {
            Yii::$app->session->setFlash('success', Yii::t('frontend', 'Reservation cancelled successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('frontend', 'Cannot cancel the selected placed reservation.'));
        }

        return $this->redirect(['index']);
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
        $model = $this->findModel($id, CustomerReservationActivateForm::class);

        if ($model->activate()) {
            Yii::$app->session->setFlash('success', Yii::t('frontend', 'Reservation activated successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('frontend', 'Cannot activate the selected placed reservation.'));
        }

        return $this->redirect(['index']);
    }

    /**
     *
     * @return array|\yii\db\ActiveRecord[]
     * @throws \Exception
     */
	public function getWaitingReservation()
    {
        $currentDate = (new \DateTime)->format('Y-m-d H:i:s');
        $query = Reservation::find()
            ->alias('r')
            ->select([
            'r.id',
            'r.start_at',
            'r.end_at',
            'r.details',
            'r.price',
            'r.currency',
            'r.period',
            'r.frequency',
            'r.created_at',
            ])
            ->where([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere(new Expression("TIMESTAMPDIFF(SECOND, [[r.start_at]], '{$currentDate}') <= 0"))
            ->orderBy([
                'r.start_at' => SORT_DESC,
            ])
        ->all();

        return $query;
    }

    /**
     *
     * @return array|\yii\db\ActiveRecord[]
     * @throws \Exception
     */
    public function getFinalizedReservation()
    {
        $currentDate = (new \DateTime)->format('Y-m-d H:i:s');
        $query = Reservation::find()
            ->alias('r')
            ->select([
                'r.id',
                'r.start_at',
                'r.end_at',
                'r.details',
                'r.price',
                'r.currency',
                'r.period',
                'r.frequency',
                'r.created_at',
            ])
            ->where([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere(new Expression("TIMESTAMPDIFF(SECOND, [[r.end_at]], '{$currentDate}') <= 0"))
            ->orderBy([
                'r.end_at' => SORT_DESC,
            ])
            ->all();
        return $query;
    }

    public function getCanceledReservation()
    {
        $currentDate = (new \DateTime)->format('Y-m-d H:i:s');
        $query = Reservation::find()
            ->alias('r')
            ->select([
                'r.id',
                'r.start_at',
                'r.end_at',
                'r.details',
                'r.price',
                'r.currency',
                'r.period',
                'r.frequency',
                'r.created_at',
            ])
            ->where([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            ->orderBy([
                'r.end_at' => SORT_DESC,
            ])
            ->all();
        return $query;
    }

    /**
     * Finds the Reservation model(s) based on its primary key value.
     * A 404 HTTP exception will be thrown if no record was found.
     *
     * @param int|array $id
     * @param \yii\db\ActiveRecord|null $modelName
     * @param bool $asActiveQuery
     * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Reservation|ReservationForm
     * @throws NotFoundHttpException if no model was found
     */
    protected function findModel($id, $modelName = null, $asActiveQuery = false)
    {
        $modelName = class_exists($modelName) ? $modelName : Reservation::class;
        $query = $modelName::find()->andWhere([
            'id' => $id,
        ]);

        if ($asActiveQuery) {
            if (!$query->count()) {
                throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
            }
            return $query;
        }

        $query->andWhere(['deleted' => Reservation::NO]);
        if (($model = $query->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
    }
}
