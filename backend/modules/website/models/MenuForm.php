<?php

namespace backend\modules\website\models;

use common\models\Language;
use common\models\Menu;
use common\models\MenuTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class MenuForm extends Menu
{
	/**
	 * @var array The multilingual name of the menu.
	 */
	public $name = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['name'], 'each', 'rule' => ['trim']],
			[['position'], 'default', 'value' => null],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'name' => Yii::t('label', 'Title'),
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

		$this->name = ArrayHelper::map($this->menuTranslations, 'language_id', 'name');
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveMenuTranslations()
	{
		try {
			foreach (Language::findAllLanguages() as $language) {
				$menuTranslation = MenuTranslation::findOne([
					'menu_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$menuTranslation) {
					$menuTranslation = new MenuTranslation();
					$menuTranslation->language_id = $language->language_id;
				}
				$menuTranslation->name = static::ensureTranslationValue($this->name, $language->language_id);
				$this->link('menuTranslations', $menuTranslation);
			}

			return true;
		} catch (InvalidCallException $e) {
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
			if (!$this->saveMenuTranslations()) {
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
