<?php

namespace backend\modules\commercial\models;

use common\models\Auction;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class AuctionForm extends Auction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		$this->start_at = Yii::$app->formatter->asDate(date('Y-m-05 00:00:00', time()));
		$this->end_at = Yii::$app->formatter->asDate(date('Y-m-20 00:00:00', time()));
		$this->valid_from = Yii::$app->formatter->asDate('first day of next month');
		$this->valid_to = Yii::$app->formatter->asDate('last day of next month');
		$this->valid_to = Yii::$app->formatter->asDate('last day of next month');
		$this->currency = Yii::$app->settings->get('currencyCode');
		$this->period = 1;
		$this->cycle = Auction::CYCLE_MONTHLY;
		$this->start_price = 5;
		$this->step = 5;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'parent_id' => Yii::t('common', 'Parent'),
			'county_id' => Yii::t('common', 'County'),
			'category_id' => Yii::t('common', 'Category'),
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
	}


	/**
	 * Saves the model.
	 *
	 * @return bool
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}
			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();

			return false;
		}
	}
}
