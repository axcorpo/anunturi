<?php

namespace frontend\modules\account\controllers;

use backend\modules\announcement\models\ReviewForm;
use backend\modules\announcement\models\ReviewSearch;
use common\helpers\UploadHelper;
use common\models\Review;
use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\ProfileForm;
use frontend\modules\account\models\ReviewDeleteForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class PlacedReviewController extends MainController
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
						'actions' => ['index', 'customer', 'dt-reviews', 'view', 'update', 'delete'],
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
        return [
            'dt-reviews' => ReviewSearch::class,
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
        $query = Review::find()
            ->alias('r')
            ->where(['=', 'r.created_by', Yii::$app->user->identity->id])
            ->orderBy('r.id')
            ->andWhere([
                'r.deleted' => Review::NO,
            ]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => 5,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
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
        $model = $this->findModel($id, ReviewForm::class);
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllReviews']));

            $message = Yii::t('common', 'Record has been updated.');
            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['index']);
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
     * Displays a single Review model.
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
     * Deletes an announcement.
     *
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id, ReviewDeleteForm::class);

        if ($model->deleteReview()) {
            Yii::$app->session->setFlash('success', Yii::t('frontend', 'Review deleted successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('frontend', 'Cannot delete the selected Review.'));
        }

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
		$modelName = class_exists($modelName) ? $modelName : Review::class;
		$model = $modelName::find()
			->where([
				'id' => $id,
				'deleted' => Review::NO,
			]);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
