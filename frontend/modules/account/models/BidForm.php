<?php

namespace frontend\modules\account\models;

use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Bid;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\web\UploadedFile;

class BidForm extends Bid
{

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
        $this->status = self::STATUS_ACTIVE;
        $this->currency = Yii::$app->settings->get('currencyCode');
        $this->broker_id = Yii::$app->user->identity->broker->id;
       	$bid = Bid::findLastBid($this->auction_id);
       	$this->price = (double)$bid->price + (double)$this->auction->step;
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
