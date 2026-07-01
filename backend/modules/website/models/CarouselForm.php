<?php

namespace backend\modules\website\models;

use common\models\Language;
use common\models\Carousel;
use common\models\CarouselTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class CarouselForm extends Carousel
{
	/**
	 * @var array The multilingual name of the carousel.
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
			[['type'], 'required'],
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['name'], 'each', 'rule' => ['trim']],
			[['config'], 'default', 'value' => null],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'name' => Yii::t('label', 'Name'),
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

		$this->name = ArrayHelper::map($this->carouselTranslations, 'language_id', 'name');
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveCarouselTranslations()
	{
		try {
			foreach (Language::findAllLanguages() as $language) {
				$carouselTranslation = CarouselTranslation::findOne([
					'carousel_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$carouselTranslation) {
					$carouselTranslation = new CarouselTranslation();
					$carouselTranslation->language_id = $language->language_id;
				}
				$carouselTranslation->name = static::ensureTranslationValue($this->name, $language->language_id);
				$this->link('carouselTranslations', $carouselTranslation);
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
			if (!$this->saveCarouselTranslations()) {
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
