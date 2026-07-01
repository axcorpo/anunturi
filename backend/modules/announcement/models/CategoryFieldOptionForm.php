<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\models\Category;
use common\models\CategoryHasOption;
use common\models\OptionTranslation;
use common\models\Option;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class CategoryFieldOptionForm extends Option
{
	/**
	 * @var array The multilingual label of the Option.
	 */
	public $label = [];

	/**
	 * @var int The Category model ID.
	 */
	public $category_id;

	/**
	 * @var array A list of (sub)Category models.
	 */
	public $category = [];

	/**
	 * @var int The option sort order.
	 */
	public $sort_order;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		if (!$this->category_id) {
			$this->category_id = Yii::$app->request->get('category_id');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['label'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[label][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['sort_order'], 'integer', 'min' => 1],
			[['label',], 'each', 'rule' => ['trim']],
			[['category'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category' => 'id']]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'field_id' => Yii::t('label', 'Field'),
			'label' => Yii::t('label', 'Label'),
			'sort_order' => Yii::t('label', 'Sort Order'),
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

		$this->label = ArrayHelper::map($this->optionTranslations, 'language_id', 'label');
		$this->category = ArrayHelper::getColumn($this->getCategoryHasOptions()->select(['category_id'])->orderBy(['sort_order' => SORT_ASC])->all(), 'category_id');
		$this->sort_order = $this->getCategoryHasOptions()->select(['sort_order'])->where(['category_id' => $this->category_id])->one()->sort_order;
	}

	/**
	 * Saves the Option translations.
	 *
	 * @return bool
	 */
	protected function saveOptionTranslations()
	{
		try {
			foreach (\common\models\Language::findAllLanguages() as $language) {
				$optionTranslation = OptionTranslation::findOne([
					'option_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$optionTranslation) {
					$optionTranslation = new OptionTranslation();
					$optionTranslation->option_id = $this->id;
					$optionTranslation->language_id = $language->language_id;
				}
				$optionTranslation->label = ModelHelper::getTranslation($this->label, $language->language_id);

				$this->link('optionTranslations', $optionTranslation);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Link the Category.
	 *
	 * @return bool
	 */
	public function linkCategory()
	{
		try {
			CategoryHasOption::deleteAll([
				'option_id' => $this->id,
				'category_id' => array_merge([$this->category_id], (array) $this->category),
			]);

			$categories = Category::findAll([
				'id' => array_merge([$this->category_id], (array) $this->category),
				'status' => Category::STATUS_ACTIVE,
				'deleted' => Category::NO,
			]);

			foreach ($categories as $category) {
				$categoryOption = new CategoryHasOption();
				$categoryOption->category_id = $category->id;
				$categoryOption->option_id = $this->id;
				$categoryOption->sort_order = $this->sort_order;
				if (!$categoryOption->save()) {
					throw new \Exception();
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|static
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!($option = static::findOne(['field_id' => $this->field_id, 'value' => $this->value, 'deleted' => static::NO]))) {
				if (!$this->save()) {
					throw new \Exception();
				}
				if (!$this->saveOptionTranslations()) {
					throw new \Exception();
				}
			} else {
				$this->id = $option->id;
			}
			if (!$this->linkCategory()) {
				throw new \Exception();
			}
			$transaction->commit();
			return $this;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
