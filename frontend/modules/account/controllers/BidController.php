<?php

namespace frontend\modules\account\controllers;

use common\helpers\UploadHelper;
use common\models\Announcement;
use common\models\Bid;
use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\AnnouncementForm;
use frontend\modules\account\models\BidForm;
use frontend\modules\account\models\BidSearch;
use frontend\modules\account\models\ProfileForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class BidController extends MainController
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
						'actions' => ['index', 'dt-bids', 'view', 'create', 'update'],
						'roles' => ['@'],
					],
				],
			],
		];
	}


    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function beforeAction($action)
    {
        if (!Yii::$app->request->isAjax && !in_array($action->id, ['index'])) {
            throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
        }
        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'dt-bids' => BidSearch::class,
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
        return $this->render('index');
	}

    /**
     * Displays a single Subscription model.
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
     * Creates a new Bid model.
     * If creation is successful, the browser will be redirected to the 'view' category.
     *
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $model = new BidForm();
        $model->auction_id = Yii::$app->request->get('auction_id');

        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllBids']));

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
     * Updates an existing Subscription model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id, BidForm::class);
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->save())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllBids']));

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
	 * Finds the Subscriber model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
     * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Bid
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Bid::class;
		$model = $modelName::find()
            ->alias('b')
			->where([
				'b.id' => $id,
				'b.deleted' => Bid::NO,
			]);


        if ($asActiveQuery) {
            if (!$model->count()) {
                throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
            }
            return $model;
        }

        $model->andWhere(['b.deleted' => Bid::NO]);
        if (($model = $model->one()) !== null) {
            return $model;
        }
		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
