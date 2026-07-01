<?php

namespace backend\modules\marketing;

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
			'roles' => ['viewDirectMarketingCampaign', 'viewMarketingRecipient'],
			'icon' => 'fa fa-bullhorn',
			'label' => Yii::t('common', 'Marketing'),
			'url' => '#',
			'items' => [
				[
					'roles' => ['viewDirectMarketingCampaign'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Campaigns'),
					'url' => ['/marketing-manager/direct-email-campaign/index'],
				],
				[
					'roles' => ['viewMarketingRecipient'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('common', 'Recipients'),
					'url' => ['/marketing-manager/marketing-recipient/index'],
				],
                [
                    'roles' => ['viewMarketingGroup'],
                    'icon' => 'fa fa-circle-o',
                    'label' => Yii::t('common', 'Groups'),
                    'url' => ['/marketing-manager/marketing-group/index'],
                ],
			],
		];
	}
}
