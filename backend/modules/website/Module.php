<?php

namespace backend\modules\website;

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
			'roles' => ['viewPage', 'viewMenu', 'viewCarousel', 'viewTestimonial', 'viewPartner'],
			'icon' => 'fa fa-sitemap',
			'label' => Yii::t('common', 'Website'),
			'url' => '#',
			'items' => [
				[
					'roles' => ['viewPage'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Pages'),
					'url' => ['/website-manager/page/index'],
				],
				[
					'roles' => ['viewMenu'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Menus'),
					'url' => ['/website-manager/menu/index'],
				],
				[
					'roles' => ['viewCarousel'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Carousels'),
					'url' => ['/website-manager/carousel/index'],
				],
			],
		];
	}
}
