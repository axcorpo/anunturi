<?php

namespace frontend\modules\account;

use Yii;
use common\helpers\Inflector;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public $layout = 'main';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		Yii::configure($this, require __DIR__ . '/config/main.php');
	}

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		Yii::setAlias("@{$this->id}", __DIR__);

		$app->i18n->translations[$this->id] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'basePath' => "@{$this->id}/messages",
			'forceTranslation' => true,
			'fileMap' => [
				$this->id => 'i18n.php',
			],
		];

		if ($app instanceof \yii\web\Application) {
			if ($urlRules = $this->buildUrlRules()) {
				$app->getUrlManager()->addRules([$urlRules], false);
			}
		}
	}

	/**
	 * Builds the rules for all pages of this module.
	 *
	 * @return array
	 */
	protected function buildUrlRules()
	{
		$pages = \common\models\Page::findPagesByModule($this->id);
		$urlRules = [
			[
				'pattern' => '',
				'route' => 'default/index',
			],
		];

		foreach ($pages as $page) {
			$pageTranslation = $page->getTranslation();

			// Custom routes
			if ($page->controller == 'profile') {
				$urlRules = array_merge($urlRules, $this->buildProfileUrlRules($page, $pageTranslation));
				continue;
			} elseif ($page->controller == 'announcement') {
				$urlRules = array_merge($urlRules, $this->buildAnnouncementUrlRules($page, $pageTranslation));
				continue;
			} elseif ($page->controller == 'placed-review' && $page->controller == 'received-placed-review') {
				$urlRules = array_merge($urlRules, $this->buildReviewUrlRules($page, $pageTranslation));
				continue;
			} elseif ($page->controller == 'placed-reservation' && $page->controller == 'received-placed-reservation') {
				$urlRules = array_merge($urlRules, $this->buildReservationUrlRules($page, $pageTranslation));
				continue;
			} elseif ($page->controller == 'payment') {
                $urlRules = array_merge($urlRules, $this->buildPaymentUrlRules($page, $pageTranslation));
                continue;
            } elseif ($page->controller == 'unavailability') {
                $urlRules = array_merge($urlRules, $this->buildUnavailabilityUrlRules($page, $pageTranslation));
                continue;
            }
			// Default routes
			$urlRules[] = [
				'pattern' => $pageTranslation->slug,
				'route' => "{$page->controller}/{$page->action}",
			];
			$urlRules[] = [
				'pattern' => "{$pageTranslation->slug}/<id>/<action>",
				'route' => "{$page->controller}/<action>",
			];
			$urlRules[] = [
				'pattern' => "{$pageTranslation->slug}/<action>",
				'route' => "{$page->controller}/<action>",
			];
		}

		return [
			'class' => 'yii\web\GroupUrlRule',
			'routePrefix' => $this->id,
			'prefix' => Inflector::slug(Yii::t($this->id, 'Account')),
			'rules' => $urlRules,
		];
	}

    /**
     * Builds URL Rules for ProfileController.
     *
     * @param \common\models\Page $page
     * @param \common\models\PageTranslation $pageTranslation
     * @return array
     */
    protected function buildProfileUrlRules($page, $pageTranslation)
    {
        $urlRules[] = [
            'pattern' => $pageTranslation->slug,
            'route' => "{$page->controller}/{$page->action}",
        ];
        $urlRules[] = [
            'pattern' => "{$pageTranslation->slug}/upload-file",
            'route' => "{$page->controller}/upload-file",
        ];

        return $urlRules;
    }

	/**
	 * Builds URL Rules for AnnouncementController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildAnnouncementUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Category Actions')),
			'route' => "{$page->controller}/category-actions",
		];

		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Category Fields')),
			'route' => "{$page->controller}/category-fields",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for RenewalController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildRenewalUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for PromotionalController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildPromotionalUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for PlacedReservationController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildReservationUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for PlacedReviewController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildReviewUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for PaymentController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildPaymentUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Package')),
			'route' => "{$page->controller}/package",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Subscription')),
			'route' => "{$page->controller}/subscription",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Invoice')),
			'route' => "{$page->controller}/invoice",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Features')),
			'route' => "{$page->controller}/features",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Result')),
			'route' => "{$page->controller}/result",
		];

		return $urlRules;
	}

    /**
     * Builds URL Rules for PromotionalController.
     *
     * @param \common\models\Page $page
     * @param \common\models\PageTranslation $pageTranslation
     * @return array
     */
    protected function buildUnavailabilityUrlRules($page, $pageTranslation)
    {
		// Default routes
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<action>",
			'route' => "{$page->controller}/<action>",
		];

        return $urlRules;
    }
}
