<?php

namespace frontend\modules\account\controllers;


use common\models\Review;
use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\ProfileForm;
use frontend\modules\account\models\ReviewConfirmForm;
use frontend\modules\account\models\ReviewDeleteForm;
use frontend\modules\account\models\CustomerReviewSearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class ReceivedReviewController extends MainController
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
						'actions' => ['index', 'dt-customer-reviews', 'view', 'delete', 'confirm'],
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
            'dt-customer-reviews' => CustomerReviewSearch::class,
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
        if (Yii::$app->request->get('deleted') == Review::YES) {
            if (!Yii::$app->settings->get('enableSoftDelete')) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('index');
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
     * Creates a new Company model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionConfirm($id)
    {
        $model = $this->findModel($id, ReviewConfirmForm::class);
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllCompanies']));

            $message = Yii::t('common', 'Record has been created.');
//            if (Yii::$app->request->isAjax) {
//                return $this->asJson([
//                    'id' => $model->id,
//                    'success' => true,
//                    'message' => $message,
//                ]);
//            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool) $result,
                'data' => $this->renderAjax('confirm', [
                    'model' => $model,
                ]),
            ]);
        }

        return $this->render('confirm', [
            'model' => $model,
        ]);
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
