<?php

namespace backend\modules\announcement;

use Yii;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
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
		if ($app instanceof \yii\web\Application) {
			$app->getUrlManager()->addRules([$this->getUrlManagerRules()], false);
		}
	}

	/**
	 * Gets the UrlManager component rules.
	 * Handles the children and submodules UrlManager component rules.
	 *
	 * @return array
	 */
	public function getUrlManagerRules()
	{
		// Children rules
		$rules = array_map(function ($rule) {
			if (isset($rule['class']) && $rule['class'] === 'yii\web\GroupUrlRule') {
				$rule['prefix'] = str_replace('<module>', $this->id, $rule['prefix']);
				$rule['routePrefix'] = str_replace('<module>', $this->id, $rule['routePrefix']);
			}
			return $rule;
		}, $this->urlManager->rules);

		// Submodules rules
		foreach ($this->modules as $moduleId => $module) {
			$module = $this->getModule($moduleId);
			if (method_exists($module, 'getUrlManagerRules')) {
				array_unshift($rules, $module->getUrlManagerRules());
			}
		}

		return [
			'class' => 'yii\web\GroupUrlRule',
			'prefix' => $this->module instanceof \yii\base\Application ? $this->id : "{$this->module->id}/{$this->id}",
			'rules' => $rules,
		];
	}

	/**
	 * Gets the nav menu items of this module.
	 *
	 * @return array
	 */
	public function getNavMenuItems()
	{
		return [
			'roles' => ['viewCategory', 'viewAnnouncement', 'viewPromotional', 'viewRenewal', 'viewReservation'],
			'icon' => 'icon-layers',
			'label' => Yii::t('common', 'Announcements'),
			'url' => '#',
			'items' => [
				[
					'roles' => ['viewCategory'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Categories'),
					'url' => ['/announcement-manager/category/index'],
				],
				[
					'roles' => ['viewField'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Fields'),
					'url' => ['/announcement-manager/field/index'],
				],
				[
					'roles' => ['viewAnnouncement'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Announcements'),
					'url' => ['/announcement-manager/announcement/index'],
				],
				[
					'roles' => ['viewPromotional'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Promotionals'),
					'url' => ['/announcement-manager/promotional/index'],
				],
				[
					'roles' => ['viewRenewal'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Renewals'),
					'url' => ['/announcement-manager/renewal/index'],
				],
                [
                    'roles' => ['viewReservation'],
                    'icon' => 'fa fa-circle-o',
                    'label' => Yii::t('common', 'Reservations'),
                    'url' => ['/announcement-manager/reservation/index'],
                ],
                [
                    'roles' => ['viewReview'],
                    'icon' => 'fa fa-circle-o',
                    'label' => Yii::t('common', 'Reviews'),
                    'url' => ['/announcement-manager/review/index'],
                ],
			],
		];
	}
}
