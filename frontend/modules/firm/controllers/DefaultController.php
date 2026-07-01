<?php

namespace frontend\modules\firm\controllers;

use common\models\Company;
use common\models\CompanyTranslation;
use common\models\Announcement;
use common\models\Subscription;
use dosamigos\google\maps\LatLng;
use dosamigos\google\maps\Map;
use dosamigos\google\maps\overlays\Marker;
use frontend\controllers\MainController;
use frontend\modules\firm\models\ReviewForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

class DefaultController extends MainController
{
	/**
	 * Displays index view.
	 *
	 * @param null|string $category
	 * @param null|string $tag
	 * @param null|int $year
	 * @return mixed
	 */
	public function actionIndex($tag = null)
	{
		$view = $this->getView();
		$canonicalUrl = Url::canonical();
		$model = null;
		$modelTranslation = null;


        if ($page = \common\models\Page::findPageByRoute(["/{$this->module->id}/{$this->id}/index"])) {
            $modelTranslation = $page->getTranslation();
            $view->title = $modelTranslation->title;
        }

		// Standard meta tags
		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'company'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:title', 'content' => $modelTranslation->title], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');

		$dataProvider = new ActiveDataProvider([
			'query' => Company::provideCompanies(),
			'pagination' => [
				'defaultPageSize' => 24,
			],
		]);

		foreach ($dataProvider->getModels() as $company) {
			$model = Company::findOne(['id' => $company->id]);
			$model->views++;
			$model->save(false, ['views']);
		}

		return $this->render('index', [
			'dataProvider' => $dataProvider,
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
		$model->visits++;
		$model->save(false, ['visits']);

		$modelTranslation = $model->getTranslation();
		$view = $this->getView();
		$canonicalUrl = Url::canonical();

		// Standard meta tags
		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'company'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:name', 'content' => $model->name], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');
		if ($ogLogo = $model->getImageUrl(true)) {
			$view->registerMetaTag(['property' => 'og:image', 'content' => $ogLogo], 'og:image');
		}

        $dataProvider = new ActiveDataProvider([
            'query' => Announcement::findByCompany($model->id),
            'pagination' => [
                'defaultPageSize' => 8,
            ],
        ]);

        $latLng = new LatLng([
            'lat' => $model->latitude,
            'lng' => $model->longitude,
        ]);

        $map = new Map([
            'center' => $latLng,
            'zoom' => 12,
            'width' => '100%',
            'height' => '100%',
            'mapTypeControl' => false,
        ]);
        $marker = new Marker([
            'position' => $latLng,
            'title' => Yii::$app->name,
            'icon' => Yii::getAlias('@web/img/map/marker.png'),
        ]);
        $map->addOverlay($marker);


        $reviewModel = new ReviewForm(['company_id' => $model->id]);

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
            'modelTranslation' => $model->getTranslation(),
            'dataProvider' => $dataProvider,
            'reviewModel' => $reviewModel,
            'map' => $map,

        ]);
	}

	/**
	 * Finds the Announcement model based on its slug value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param string $slug
	 * @return array|\yii\db\ActiveRecord|Company
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($slug)
	{
		$model = Company::find()
			->alias('c')
			->joinWith([
                'companyTranslations ct' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'ct.language_id' => Yii::$app->language,
                        'ct.deleted' => CompanyTranslation::NO,
                    ]);
                },
			])
			->andWhere([
				'ct.slug' => $slug,
			])
			->andWhere([
				'c.status' => Company::STATUS_ACTIVE,
				'c.deleted' => Company::NO,
			])
			->limit(1);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
