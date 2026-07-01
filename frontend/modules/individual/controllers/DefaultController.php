<?php

namespace frontend\modules\individual\controllers;

use common\models\Company;
use common\models\CompanyTranslation;
use common\models\Announcement;
use common\models\Subscriber;
use common\models\Subscription;
use dosamigos\google\maps\LatLng;
use dosamigos\google\maps\Map;
use dosamigos\google\maps\overlays\Marker;
use frontend\controllers\MainController;
use frontend\modules\individual\models\ReviewForm;
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
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'individual'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:title', 'content' => $modelTranslation->title], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');

		$dataProvider = new ActiveDataProvider([
			'query' => Subscriber::provideSubscribersWithAnnouncement(),
			'pagination' => [
				'defaultPageSize' => 24,
			],
		]);

		foreach ($dataProvider->getModels() as $subscriber) {
			$model = Subscriber::findOne(['id' => $subscriber->id]);
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


		$view = $this->getView();
		$canonicalUrl = Url::canonical();

		// Standard meta tags
//		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
//		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'individual'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:name', 'content' => $model->user->fullName], 'og:title');
//		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');
		if ($ogLogo = $model->user->getImageUrl(true)) {
			$view->registerMetaTag(['property' => 'og:image', 'content' => $ogLogo], 'og:image');
		}

        $dataProvider = new ActiveDataProvider([
            'query' => Announcement::findBySubscriber($model->user->id),
            'pagination' => [
                'defaultPageSize' => 8,
            ],
        ]);


        $reviewModel = new ReviewForm(['subscriber_id' => $model->id]);

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
            'dataProvider' => $dataProvider,
            'reviewModel' => $reviewModel,

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
		$model = Subscriber::find()
			->alias('s')
			->andWhere([
				's.code' => end(explode('-', $slug)),
			])
			->andWhere([
				's.status' => Subscriber::STATUS_ACTIVE,
				's.deleted' => Subscriber::NO,
			])
			->limit(1);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
