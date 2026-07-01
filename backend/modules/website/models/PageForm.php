<?php

namespace backend\modules\website\models;

use common\models\Language;
use common\models\MenuItem;
use common\models\Page;
use common\models\PageTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use common\helpers\Inflector;

class PageForm extends Page
{
	/**
	 * @var array The multilingual title of the page.
	 */
	public $title = [];

	/**
	 * @var array The multilingual slug of the page.
	 */
	public $slug = [];

	/**
	 * @var array The multilingual keywords of the page.
	 */
	public $keywords = [];

	/**
	 * @var array The multilingual description of the page.
	 */
	public $description = [];

	/**
	 * @var array The multilingual content of the page.
	 */
	public $content = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->controller = 'site';
		$this->action = 'page';
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['title'], 'required', 'when' => function () {
				return empty($this->title[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[title][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['module'], 'default', 'value' => null],
			[['title', 'slug', 'keywords', 'description', 'content'], 'each', 'rule' => ['trim']],
			[['keywords', 'description', 'content'], 'each', 'rule' => ['default', 'value' => null]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'title' => Yii::t('label', 'Title'),
			'slug' => Yii::t('label', 'Slug'),
			'keywords' => Yii::t('label', 'Keywords'),
			'description' => Yii::t('label', 'Description'),
			'content' => Yii::t('label', 'Content'),
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

		$this->title = ArrayHelper::map($this->pageTranslations, 'language_id', 'title');
		$this->slug = ArrayHelper::map($this->pageTranslations, 'language_id', 'slug');
		$this->keywords = ArrayHelper::map($this->pageTranslations, 'language_id', 'keywordsList');
		$this->description = ArrayHelper::map($this->pageTranslations, 'language_id', 'description');
		$this->content = ArrayHelper::map($this->pageTranslations, 'language_id', 'content');
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function savePageTranslations()
	{
		try {
			foreach (Language::findAllLanguages() as $language) {
				$pageTranslation = PageTranslation::findOne([
					'page_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$pageTranslation) {
					$pageTranslation = new PageTranslation();
					$pageTranslation->language_id = $language->language_id;
				}
				$pageTranslation->title = static::ensureTranslationValue($this->title, $language->language_id);
				if ($this->default == Page::NO) {
					$pageTranslation->slug = Inflector::slug($this->slug[$language->language_id] ?: $pageTranslation->title);
				} else {
					$pageTranslation->slug = '/';
				}
				$pageTranslation->keywords = $this->keywords[$language->language_id] ? implode(',', (array) $this->keywords[$language->language_id]) : null;
				$pageTranslation->description = $this->description[$language->language_id];
				$pageTranslation->content = $this->content[$language->language_id];

				$this->link('pageTranslations', $pageTranslation);
			}
			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Updates the menu item slugs.
	 *
	 * @return bool
	 */
	protected function updateMenuItemsSlugs()
	{
		/** @var MenuItem[] $menuItems */
		$menuItems = MenuItem::find()
			->alias('mi')
			->joinWith([
				'menuItemTranslations mit',
			])
			->andWhere([
				'OR',
				['=', 'mi.page_id', $this->id],
				['=', 'mi.parent_id', $this->id],
				['IS NOT', 'mi.parent_id', null], // TODO: maybe join with subquery that searches for subpages
			])
			->all();

		if (!$menuItems) {
			return true;
		}
$v = [];
		try {
//			foreach ($menuItems as $menuItem) {
//				foreach ($menuItem->menuItemTranslations as $menuItemTranslation) {
//					$v[] = $menuItem->getNestedSlug($menuItemTranslation->language_id);
//					if ($slug = $menuItem->getNestedSlug($menuItemTranslation->language_id)) {
//						$menuItemTranslation->slug = $slug;
//						if (!$menuItemTranslation->save()) {
//							$this->addErrors($menuItemTranslation->getErrors());
//							throw new \Exception($menuItemTranslation->getErrorSummary(false)[0]);
//						}
//					}
//				}
//			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->savePageTranslations()) {
				throw new \Exception();
			}
			if (!$this->updateMenuItemsSlugs()) {
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
