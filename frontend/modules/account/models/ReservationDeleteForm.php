<?php

namespace frontend\modules\account\models;

use common\models\Invoice;
use common\models\Item;
use common\models\PaymentMetadata;
use common\models\Reservation;
use common\models\Template;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use tws\helpers\Url;

class ReservationDeleteForm extends Reservation
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
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
	 * Cancels the subscription.
	 *
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function deleteReservation()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::delete(true)) {
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
