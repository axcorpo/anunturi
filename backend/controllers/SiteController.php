<?php

namespace backend\controllers;

use backend\models\SearchForm;
use common\helpers\Inflector;
use common\models\Auction;
use common\models\Category;
use common\models\CategoryTranslation;
use common\models\County;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

class SiteController extends MainController
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
						'actions' => ['login'],
						'allow' => true,
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'logout' => ['POST'],
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
			'error' => [
				'class' => 'yii\web\ErrorAction',
				'layout' => 'blank',
			],
		];
	}

	/**
	 * Displays homepage.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
//		$attributes = ['parent_id', 'county_id', 'category_id', 'position', 'period', 'cycle', 'start_at', 'end_at', 'start_price', 'step', 'currency', 'valid_from', 'valid_to', 'created_by', 'updated_by', 'created_at', 'updated_at', 'status', 'deleted'];
//		$rows = [];
//		$auctions = Auction::find()
//			->where([
//				'=', 'county_id', 1
//			])
//			->all();
//		foreach ($auctions as $auction) {
//			$rows[] = [
//				'parent_id' => $auction->parent_id ?: null,
//				'county_id' => $auction->county_id ?: null,
//				'category_id' => $auction->category_id ?: null,
//				'position' => $auction->position,
//				'period' => $auction->period,
//				'cycle' => $auction->cycle,
//				'start_at' => $auction->start_at,
//				'end_at' => $auction->end_at,
//				'start_price' => $auction->start_price,
//				'step' => $auction->step,
//				'currency' => $auction->currency,
//				'valid_from' => $auction->valid_from,
//				'valid_to' => $auction->valid_to,
//				'created_by' => $auction->created_by,
//				'updated_by' => $auction->updated_by,
//				'created_at' => $auction->created_at,
//				'updated_at' => $auction->updated_at,
//				'status' => $auction->status,
//				'deleted' => $auction->deleted,
//			];
//		}
//		Yii::$app->db->createCommand()->batchInsert(Auction::tableName(), $attributes, $rows)->execute();

//		foreach (CategoryTranslation::findAll(['deleted' => 0]) as $translation) {
//			$parentTranslation = CategoryTranslation::findOne([
//				'category_id' => $translation->category->parent_id,
//				'language_id' => $translation->language_id,
//			]);
//			$translation->slug = implode('-', array_filter([$parentTranslation->slug, Inflector::slug($translation->name)]));
//			if ($translation->language_id == 'ro-RO') {
//				$translation->keywords = 'anunțuri,construcții,anunțuri construcții,' . strtolower($translation->name);
//				$translation->description = str_replace('{{name}}', $translation->name, 'Adaugă sau vizualizează anunțuri din categoria: {{name}} pe eConstructii.ro, platformă axată pe domeniul construcțiilor.');
//			}
//			$translation->save();
//		}

		return $this->render('index');
	}

	/**
	 * Displays login page.
	 *
	 * @return mixed
	 */
	public function actionLogin()
	{
		/** @var \yii\web\UrlManager $frontendAppUrlManager */
		$frontendAppUrlManager = Yii::$app->instance->get('@frontend')->getUrlManager();
		return $this->redirect($frontendAppUrlManager->createAbsoluteUrl(['/site/login']), 301);
	}

	/**
	 * Logs out the current user.
	 *
	 * @return mixed
	 */
	public function actionLogout()
	{
		/** @var \yii\web\UrlManager $frontendAppUrlManager */
		$frontendAppUrlManager = Yii::$app->instance->get('@frontend')->getUrlManager();
		return $this->redirect($frontendAppUrlManager->createAbsoluteUrl(['/site/logout']), 301);
	}

	/**
	 * Searches globally in the site.
	 *
	 * @return mixed
	 */
	public function actionSearch()
	{
		$searchModel = new SearchForm();
		$searchModel->load(Yii::$app->request->get());

		$dataProvider = $searchModel->search();

		return $this->render('search', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
}
