<?php

namespace backend\modules\payment\models;

use common\models\Payment;
use common\models\Subscription;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class PaymentForm extends Payment
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_UNPAID;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['status', 'subscription_id',], 'required'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'subscription_id' => Yii::t('common', 'Subscription'),
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
			$subscription = Subscription::findOne(['id' => $this->subscription_id]);
			$subscriber = $subscription->subscriber;
			$paymentMetadata = $subscription->paymentMetadata;

			if (!empty($subscriber->parent) && !empty(Yii::$app->settings->get('splitIncomePercentage'))) {
				$payment = Payment::findOne(['subscription_id' => $subscription->id, 'payment_id' => $paymentMetadata->payment_id]);
				if (empty($payment)) {
					$this->subscription_id = $subscription->id;
					$this->payment_id = $paymentMetadata->payment_id;
					$this->amount = $subscription->price;
					$this->currency = $subscription->currency;
					$this->split_percentage = Yii::$app->settings->get('splitIncomePercentage') ?: 0;
					$this->bank_account_holder = $subscriber->bank_account_holder;
					$this->bank_name = $subscriber->bank_name;
					$this->bank_iban = $subscriber->bank_iban;
					$this->bank_bic = $subscriber->bank_bic;
					$this->deleted = Payment::NO;
				}
				if (!$this->save()) {
					throw new \Exception();
				}
			}
			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();

			return false;
		}
	}
}
