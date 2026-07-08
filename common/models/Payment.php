<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%payment}}".
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $payment_id
 * @property string $amount
 * @property string $currency
 * @property string $split_percentage
 * @property string $paid_at
 * @property string $bank_account_holder
 * @property string $bank_name
 * @property string $bank_iban
 * @property string $bank_bic
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Subscription $subscription
 * @property User $creator
 * @property User $updater
 */
class Payment extends UuidActiveRecord
{

	const STATUS_UNPAID = 0;
	const STATUS_PAID = 1;

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%payment}}';
    }

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['paid_at'],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subscription_id', 'status'], 'required'],
            [['subscription_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['amount', 'split_percentage'], 'number'],
            [['paid_at', 'created_at', 'updated_at'], 'safe'],
            [['payment_id', 'bank_account_holder', 'bank_name', 'bank_iban', 'bank_bic'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['subscription_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscription::class, 'targetAttribute' => ['subscription_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'subscription_id' => Yii::t('label', 'Subscription ID'),
            'payment_id' => Yii::t('label', 'Payment ID'),
            'amount' => Yii::t('label', 'Amount'),
            'currency' => Yii::t('label', 'Currency'),
            'split_percentage' => Yii::t('label', 'Split Percentage'),
            'paid_at' => Yii::t('label', 'Paid At'),
            'bank_account_holder' => Yii::t('label', 'Bank Account Holder'),
            'bank_name' => Yii::t('label', 'Bank Name'),
            'bank_iban' => Yii::t('label', 'Bank Iban'),
            'bank_bic' => Yii::t('label', 'Bank SWIFT/BIC'),
            'created_by' => Yii::t('label', 'Created By'),
            'updated_by' => Yii::t('label', 'Updated By'),
            'created_at' => Yii::t('label', 'Created At'),
            'updated_at' => Yii::t('label', 'Updated At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscription()
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * Model status labels.
	 *
	 * @return array
	 */
	public static function getStatusLabels()
	{
		return [
			self::STATUS_UNPAID => [
				'label' => Yii::t('label', 'Unpaid'),
				'color' => 'danger',
				'hexColor' => '#f55060',
			],
			self::STATUS_PAID => [
				'label' => Yii::t('label', 'Paid'),
				'color' => 'success',
				'hexColor' => '#5cb85c',
			],
		];
	}
}
