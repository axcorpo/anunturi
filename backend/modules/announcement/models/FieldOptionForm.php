<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\models\Option;
use common\models\OptionTranslation;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class FieldOptionForm extends Option
{
	/**
	 * @var array The multilingual label of the Option.
	 */
	public $label = [];

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
			[['label'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[label][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['label'], 'each', 'rule' => ['trim']],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'label' => Yii::t('label', 'Label'),
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
	 * Saves the model.
	 *
	 * @return bool|static
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveOptionTranslations()) {
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
