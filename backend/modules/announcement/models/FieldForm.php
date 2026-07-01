<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\models\Category;
use common\models\CategoryField;
use common\models\Field;
use common\models\FieldTranslation;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use common\helpers\Inflector;

class FieldForm extends Field
{
	/**
	 * @var array The multilingual label of the Field.
	 */
	public $label = [];

	/**
	 * @var array The multilingual content of the Field.
	 */
	public $placeholder = [];

	/**
	 * @var array The multilingual content of the Field.
	 */
	public $help_text = [];

	/**
	 * @var array A list of (sub)Category models.
	 */
	public $category = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->type = static::TYPE_TEXT;
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'string', 'min' => 2, 'max' => 255],
			[['label'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[label][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['label', 'placeholder', 'help_text'], 'each', 'rule' => ['trim']],
			[['placeholder', 'help_text'], 'each', 'rule' => ['default', 'value' => null]],
			[['category'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category' => 'id']]],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'label' => Yii::t('label', 'Label'),
			'placeholder' => Yii::t('label', 'Placeholder'),
			'help_text' => Yii::t('label', 'Help Text'),
			'category' => Yii::t('label', 'Categories'),
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

		$this->label = ArrayHelper::map($this->fieldTranslations, 'language_id', 'label');
		$this->placeholder = ArrayHelper::map($this->fieldTranslations, 'language_id', 'placeholder');
		$this->help_text = ArrayHelper::map($this->fieldTranslations, 'language_id', 'help_text');
		$this->category = ArrayHelper::getColumn($this->getCategoryFields()->select(['category_id'])->orderBy(['sort_order' => SORT_ASC])->all(), 'category_id');
	}

	/**
	 * Saves the Field translations.
	 *
	 * @return bool
	 */
	protected function saveFieldTranslations()
	{
		try {
			foreach (\common\models\Language::findAllLanguages() as $language) {
				$fieldTranslation = FieldTranslation::findOne([
					'field_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$fieldTranslation) {
					$fieldTranslation = new FieldTranslation();
					$fieldTranslation->field_id = $this->id;
					$fieldTranslation->language_id = $language->language_id;
				}
				$fieldTranslation->label = ModelHelper::getTranslation($this->label, $language->language_id);
				$fieldTranslation->placeholder = $this->placeholder[$language->language_id];
				$fieldTranslation->help_text = $this->help_text[$language->language_id];

				$this->link('fieldTranslations', $fieldTranslation);
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
	public function linkCategoryFields()
	{
		try {
            if (empty($this->category)) {
                return true;
            }

            $categoryFields = CategoryField::deleteAll([
				'field_id' => $this->id,
				'status' => Category::STATUS_ACTIVE,
				'deleted' => Category::NO,
			]);



            foreach ($this->category as $categoryField) {
                $categoryFieldModel = new CategoryField();
                $categoryFieldModel->field_id = $this->id;
                $categoryFieldModel->category_id = (int) $categoryField;
                $categoryFieldModel->status =  CategoryField::STATUS_ACTIVE;
                if (!$categoryFieldModel->save()) {
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
			$this->name = Inflector::slug($this->name, '_');
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveFieldTranslations()) {
				throw new \Exception();
			}
			if (!$this->linkCategoryFields()) {
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
