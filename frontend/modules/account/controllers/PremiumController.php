<?php

namespace frontend\modules\account\controllers;

use frontend\modules\announcement\models\PremiumSearch;
use common\models\Promotional;
use frontend\controllers\MainController;
use frontend\modules\account\models\ProfileForm;
use frontend\modules\account\models\PremiumForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class PremiumController extends MainController
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
						'actions' => ['create', 'view', 'dt-premiums'],
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
            'dt-premiums' => PremiumSearch::class,
        ];
    }


    /**
     * Creates a new Premium model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new PremiumForm();
        $model->announcement_id = Yii::$app->security->unmaskToken(Yii::$app->request->get('announcement'));
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllPremiums']));

            $message = Yii::t('common', 'The operation was successful.');

            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['/account/announcement/index']);
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
     * Displays a single Premium model.
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
		$modelName = class_exists($modelName) ? $modelName : Promotional::class;
		$model = $modelName::find()
			->where([
				'id' => $id,
				'deleted' => Promotional::NO,
			]);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
