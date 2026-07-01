<?php

namespace backend\modules\nomenclature\models;

use common\models\Feature;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class FeatureForm extends Feature
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->currency = Yii::$app->settings->get('currencyCode');
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['price'], 'required'],
			[['price'], 'number', 'min' => 0],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [

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
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
