<?php

namespace frontend\modules\account\controllers;

use common\helpers\UploadHelper;
use common\models\Announcement;
use common\models\Commercial;
use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\AnnouncementForm;
use frontend\modules\account\models\CommercialForm;
use frontend\modules\account\models\CommercialSearch;
use frontend\modules\account\models\ProfileForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class CommercialController extends MainController
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
						'actions' => ['index', 'dt-commercials', 'view', 'create', 'update'],
						'roles' => ['@'],
					],
                    [
                        'allow' => true,
                        'actions' => ['delete', 'delete-file'],
                        'roles' => ['@'],
                        'verbs' => ['POST'],
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
        if (!Yii::$app->request->isAjax && !in_array($action->id, ['index', 'delete'])) {
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
            'dt-commercials' => CommercialSearch::class,
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
     * Creates a new Commercial model.
     * If creation is successful, the browser will be redirected to the 'view' category.
     *
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
		$model = new CommercialForm();

		$result = true;

		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllCommercials']));

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
		$model = $this->findModel($id, CommercialForm::class);

		$result = true;

		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllCommercials']));

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
     * Deletes an existing Commercial model.
     * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
     *
     * @param $id
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
    	$model = $this->findModel($id, true);
		if ($model->delete(true)) {
			FileHelper::removeDirectory(Yii::getAlias("@uploads/{$this->id}/{$model->id}"));
			Yii::$app->session->setFlash('success', Yii::t('frontend', 'Record deleted successfully.'));
		} else {
			Yii::$app->session->setFlash('error', Yii::t('frontend', 'Cannot delete the selected record.'));
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
	 * Finds the Subscriber model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
     * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Commercial
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Commercial::class;
		$model = $modelName::find()
			->where([
				'id' => $id,
				'created_by' => Yii::$app->user->id,
			]);


        if ($asActiveQuery) {
            if (!$model->count()) {
                throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
            }
            return $model;
        }

        $model->andWhere(['deleted' => Commercial::NO]);
        if (($model = $model->one()) !== null) {
            return $model;
        }
		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
