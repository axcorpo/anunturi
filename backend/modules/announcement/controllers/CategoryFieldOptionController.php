<?php

namespace backend\modules\announcement\controllers;

use backend\controllers\MainController;
use backend\modules\announcement\models\CategoryFieldOptionSearch;
use backend\modules\announcement\models\CategoryFieldOptionForm;
use common\models\CategoryHasField;
use common\models\Field;
use common\models\Option;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class CategoryFieldOptionController extends MainController
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
						'actions' => ['view', 'dt-category-field-options', 'search'],
						'roles' => ['viewField'],
					],
					[
						'allow' => true,
						'actions' => ['create'],
						'roles' => ['createField'],
					],
					[
						'allow' => true,
						'actions' => ['update'],
						'roles' => ['updateField'],
					],
					[
						'allow' => true,
						'actions' => ['delete', 'delete-file'],
						'roles' => ['deleteField'],
						'verbs' => ['POST'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => ['restoreField'],
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
			'dt-category-field-options' => CategoryFieldOptionSearch::class,
		];
	}

	/**
	 * Displays a single Field model.
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
	 * Creates a new Field model.
	 * If creation is successful, the browser will be redirected to the 'view' category.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new CategoryFieldOptionForm();
		$model->category_id = Yii::$app->request->get('category_id');
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
	 * Updates an existing Field model.
	 * If update is successful, the browser will be redirected to the 'view' category.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, CategoryFieldOptionForm::class);
		$model->category_id = Yii::$app->request->get('category_id');
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
	 * Deletes existing Field models.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param null|int $id
	 * @return mixed
	 */
	public function actionDelete($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->post('selection');
		}
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The delete operation was successful.'),
				'body' => [],
			],
		];
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$models = CategoryHasField::deleteAll([
				'category_id' => Yii::$app->request->get('category_id'),
				'field_id' => $id,
			]);
			if (!$models) {
				throw new \Exception();
			}
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllFields']));
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
	 * Search models.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionSearch()
	{
		if (!Yii::$app->request->isAjax) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		$data = [];
		$bodyParams = Yii::$app->request->post();
		$query = Option::find()
			->alias('o')
			->select([
				'o.id',
				'o.value',
			])
			->joinWith([
				'optionTranslations ot',
			])
			->where([
				'o.field_id' => $bodyParams['field_id'],
				'o.status' => Field::STATUS_ACTIVE,
				'o.deleted' => Field::NO,
			])
			->groupBy(['o.id']);

		if ($bodyParams['attribute'] == 'value') {
			$query->andFilterWhere([
				'OR',
				['LIKE', 'o.value', $bodyParams['query']],
				['LIKE', 'ot.label', $bodyParams['query']],
			]);
		}

		foreach ($query->asArray()->all() as $model) {
			$translations = ArrayHelper::index($model['optionTranslations'], 'language_id');
			$data[] = [
				'id' => $model['id'],
				'value' => $model['value'],
				'label' => $translations[Yii::$app->language]['label'],
				'translations' => $translations,
			];
		}

		return $this->asJson($data);
	}

	/**
	 * Finds the Field model(s) based on its primary key value.
	 * A 404 HTTP exception will be thrown if no record was found.
	 *
	 * @param int|array $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Option|CategoryFieldOptionForm
	 * @throws NotFoundHttpException if no model was found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Option::class;
		$query = $modelName::find()
			->alias('o')
			->joinWith([
				'categoryHasOptions cho',
			])
			->andWhere([
				'o.id' => $id,
				'cho.category_id' => Yii::$app->request->get('category_id'),
			]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['o.deleted' => Field::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
