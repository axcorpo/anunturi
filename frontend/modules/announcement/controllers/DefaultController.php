<?php

namespace frontend\modules\announcement\controllers;

use common\models\Action;
use common\models\Announcement;
use common\models\Category;
use common\models\County;
use common\models\Subscriber;
use dosamigos\google\maps\LatLng;
use dosamigos\google\maps\Map;
use dosamigos\google\maps\overlays\Marker;
use frontend\controllers\MainController;
use frontend\modules\announcement\models\AnnouncementEmbedForm;
use frontend\modules\announcement\models\ReviewForm;
use frontend\modules\announcement\models\FilterForm;
use kartik\depdrop\DepDropAction;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use common\helpers\Inflector;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

class DefaultController extends MainController
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
						'actions' => [
							'index', 'create', 'view', 'embed', 'category-actions'
						],
						'roles' => ['?', '@'],
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
			'category-actions' => [
				'class' => DepDropAction::class,
				'outputCallback' => function ($selectedId, $params) {
					return Action::queryActionByCategoryId($selectedId);
				},
			],
		];
	}

	/**
	 * Displays index view.
	 *
	 * @param null|string $category
 	 * @param null|string $county
	 * @param null|string $tag
	 * @param null|int $year
	 * @return mixed
	 */
	public function actionIndex($category = null, $county = null, $tag = null, $year = null)
	{
		$view = $this->getView();
		$canonicalUrl = Url::canonical();
		$model = null;
		$modelTranslation = null;
		$countyModel = null;

		if (!empty($category) && ($model = Category::findCategoryBySlug($category))) {
			$modelTranslation = $model->getTranslation();
			$view->title = Yii::t('frontend', 'Announcements from category: {0}', $modelTranslation->name);
			if (Yii::$app->request->post('FilterForm')) {
				Yii::$app->session->set('filter', array_filter(Yii::$app->request->post('FilterForm')));
				$this->refresh();
			}
		} else {
			Yii::$app->session->remove('filter');
			if ($page = \common\models\Page::findPageByRoute(["/{$this->module->id}/{$this->id}/index"])) {
				$modelTranslation = $page->getTranslation();
				$view->title = $modelTranslation->title;
			}
			if (!empty($county)) {
				$countyModel = County::findCountyBySlug($county);
				$view->title = Yii::t('frontend', 'Announcements from county: {0}', $countyModel->name ?: $county);
			}
			if (!empty($tag)) {
				$view->title = Yii::t('frontend', 'Announcements tagged with: {0}', Inflector::humanize($tag));
			} elseif (!empty($year)) {
				$view->title = Yii::t('frontend', 'Announcements from year {0}', $year);
			}
		}

		// Standard meta tags
		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'announcement'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:title', 'content' => !empty($category) && Category::findCategoryBySlug($category) ? $modelTranslation->name : $modelTranslation->title], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');

		$params = [
			'category' => $model->id,
			'county' => $countyModel->name ?: $county,
			'tag' => $tag,
			'year' => $year,
			'promotional' => true,
			'limit' => 3,
			'location' => Yii::$app->request->get(Inflector::slug(Yii::t('label', 'Location'))),
			'search' => Yii::$app->request->get(Inflector::slug(Yii::t('label', 'Search'))),
			'filter' => Yii::$app->session->get('filter'),
		];

		$promotionalData = Announcement::provideAnnouncements($params)->all();

		$auctions = [];
		$auctionListTop = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_TOP , $countyModel->id, $model->id);
		if ($auctionListTop->id) {
			$auctions[] = $auctionListTop->id;
		}
		$auctionListMiddle = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_1030X120_LIST_MIDDLE, $countyModel->id, $model->id);
		if ($auctionListMiddle->id) {
			$auctions[] = $auctionListMiddle->id;
		}
		$auctionListBottom = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_BOTTOM, $countyModel->id, $model->id);
		if ($auctionListBottom->id) {
			$auctions[] = $auctionListBottom->id;
		}

		$params = [
			'category' => $model->id,
			'county' => $countyModel->name ?: $county,
			'tag' => $tag,
			'year' => $year,
			'promotional' => null,
			'limit' => null,
			'location' => Yii::$app->request->get(Inflector::slug(Yii::t('label', 'Location'))),
			'search' => Yii::$app->request->get(Inflector::slug(Yii::t('label', 'Search'))),
			'filter' => Yii::$app->session->get('filter'),
		];

		$dataProvider = new ActiveDataProvider([
			'query' => Announcement::provideAnnouncements($params),
			'pagination' => [
				'defaultPageSize' => 16 - count($promotionalData) + 3 - count($auctions),
			],
		]);

		foreach ($promotionalData as $announcement) {
			$model = Announcement::findOne(['id' => $announcement->id]);
            if ($model->created_by != Yii::$app->user->id) {
                $model->views++;
                $model->displayed_at = (new \DateTime)->format('Y-m-d H:i:s');
                $model->save(false, ['views', 'displayed_at']);
            }
		}

		foreach ($dataProvider->getModels() as $announcement) {
			$model = Announcement::findOne(['id' => $announcement->id]);
            if ($model->created_by != Yii::$app->user->id) {
                $model->views++;
                $model->save(false, ['views']);
            }
		}

		$modelFilterForm = new FilterForm();
		if (!empty(Yii::$app->session->get('filter'))) {
			foreach(Yii::$app->session->get('filter') as $key => $value) {
				$modelFilterForm->$key = $value;
			}
		}

		return $this->render('index', [
			'promotionalData' => $promotionalData,
			'dataProvider' => $dataProvider,
			'auctionListTop' => $auctionListTop,
			'auctionListMiddle' => $auctionListMiddle,
			'auctionListBottom' => $auctionListBottom,
			'modelFilterForm' => $modelFilterForm,
		]);
	}

	/**
	 * Displays a single Announcement model.
	 *
	 * @param string $slug
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionView($slug)
	{
		$model = $this->findModel($slug);
        if ($model->created_by != Yii::$app->user->id) {
            $model->visits++;
            $model->save(false, ['visits']);
        }

		$modelTranslation = $model->getTranslation();
		$view = $this->getView();
		$canonicalUrl = Url::canonical();

		// Standard meta tags
		$actions = [];
		foreach ($model->actions as $action) {
			$actions[] = \common\models\Action::getMyTypes()[$action->type];
		}
		$view->title = ($actions ? implode(', ', $actions) . ' - ' : '') . $modelTranslation->title;
		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'announcement'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:title', 'content' => ($actions ? implode(', ', $actions) . ' - ' : '') . $modelTranslation->title], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');
		if ($ogLogo = $model->getImageUrl()) {
			$view->registerMetaTag(['property' => 'og:image', 'content' => $ogLogo], 'og:image');
		}

		$location = implode(', ', array_filter([
			$model->locality,
			$model->county,
			$model->country ? \common\models\Country::findAllCountries()[$model->country]->name : null,
		]));
		$latLng = new LatLng([
			'lat' => $model->latitude,
			'lng' => $model->longitude,
		]);
		$map = new Map([
			'center' => $latLng,
			'zoom' => 12,
			'width' => '100%',
			'height' => 400,
			'mapTypeControl' => false,
		]);
		$marker = new Marker([
			'position' => $latLng,
			'title' => Yii::$app->name,
			'icon' => Yii::getAlias('@web/img/map/marker.png'),
		]);
		$map->addOverlay($marker);


		$reviewModel = new ReviewForm(['announcement_id' => $model->id]);

		if ($reviewModel->load(Yii::$app->request->post()) && $reviewModel->validate()) {
			if ($reviewModel->save()) {
				Yii::$app->session->setFlash('success', Yii::t('frontend', 'Thank you for your feedback.'));
			} else {
				Yii::$app->session->setFlash('error', Yii::t('common', 'There was an error sending your message.'));
			}
			return $this->refresh();
		}

		return $this->render('view', [
			'model' => $model,
            'reviewModel' => $reviewModel,
			'modelTranslation' => $model->getTranslation(),
			'tags' => array_filter(explode(',', $modelTranslation->keywords)),
			'map' => $map,
			'location' => $location,
		]);
	}


	public function actionCreate()
	{
		$this->layout = 'embed';
		try {
			$code = Yii::$app->security->unmaskToken((string) Yii::$app->request->get('code'));
			$subscriber = Subscriber::findOne(['code' => $code]);
			$user = $subscriber->user;
			$model = new AnnouncementEmbedForm();
			return $this->render('_create-form', [
				'model' => $model,
			]);
		}  catch (\Exception $e) {
			return $this->goHome();
		}
	}


	/**
	 * Finds the Announcement model based on its slug value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param string $slug
	 * @return array|\yii\db\ActiveRecord|Announcement
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($slug)
	{
		$model = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'at.slug' => $slug,
				'a.deleted' => Announcement::NO,
			])
			->andWhere([
				'OR',
				['=', 'a.status', Announcement::STATUS_ACTIVE],
				['=', 'a.created_by', Yii::$app->user->id],
			])
			->limit(1);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}

	/**
	 * Returns the JavaScript API file.
	 *
	 * @return mixed
	 */
	public function actionEmbed()
	{
		return Yii::$app->response->sendFile(Yii::getAlias('@announcement/web/js/embed.js'));
	}
}
