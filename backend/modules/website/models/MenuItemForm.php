<?php

namespace backend\modules\website\models;

use common\models\Language;
use common\models\MenuItem;
use common\models\MenuItemTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class MenuItemForm extends MenuItem
{
	/**
	 * @var array The multilingual title of the menu item.
	 */
	public $title = [];

	/**
	 * @var array The multilingual url of the menu item.
	 */
	public $url = [];


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->options = [];
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['title'], 'required', 'when' => function ($model) {
				return empty($model->page_id) && empty($model->title[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[page_id]\"]").val() == "" && attribute.$form.find("[name*=\"[title][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['excluded'], 'boolean'],
			[['excluded'], 'default', 'value' => 0],
			[['title', 'url'], 'each', 'rule' => ['trim']],
			[['icon', 'target'], 'default', 'value' => null],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'title' => Yii::t('label', 'Title'),
			'url' => Yii::t('label', 'URL'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->title = ArrayHelper::map($this->menuItemTranslations, 'language_id', 'title');
		$this->url = ArrayHelper::map($this->menuItemTranslations, 'language_id', 'url');
		$this->options = $this->getUnserializedValue('options', []);
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveMenuItemTranslations()
	{
		try {
			foreach (Language::findAllLanguages() as $language) {
				$menuItemTranslation = MenuItemTranslation::findOne([
					'menu_item_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$menuItemTranslation) {
					$menuItemTranslation = new MenuItemTranslation();
					$menuItemTranslation->menu_item_id = $this->id;
					$menuItemTranslation->language_id = $language->language_id;
				}
				$menuItemTranslation->title = $this->title[$language->language_id] ?: null;
				$menuItemTranslation->url = $this->url[$language->language_id] ?: null;
				$menuItemTranslation->slug = $menuItemTranslation->menuItem->getNestedSlug($language->language_id);
				$this->link('menuItemTranslations', $menuItemTranslation);
			}

			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$this->setSerializedValue('options', $this->options);
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveMenuItemTranslations()) {
				throw new \Exception();
			}

			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
