<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%bid}}".
 *
 * @property int $id
 * @property int $broker_id
 * @property int $auction_id
 * @property string $price
 * @property string $currency
 * @property string $sent_at
 * @property string $expired_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Commercial[] $commercial
 * @property Broker $broker
 * @property Auction $auction
 * @property User $creator
 * @property User $updater
 */
class Bid extends UuidActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%bid}}';
	}

	/**
	 * @inheritdoc
	 * @throws \Exception
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
				'attributes' => ['sent_at', 'expired_at',],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => self::YES,
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
			[['broker_id', 'auction_id', 'currency', 'status'], 'required'],
			[['broker_id', 'auction_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['price'], 'number'],
			[['sent_at', 'expired_at', 'created_at', 'updated_at'], 'safe'],
			[['currency'], 'string', 'max' => 3],
			[['broker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Broker::class, 'targetAttribute' => ['broker_id' => 'id']],
			[['auction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Auction::class, 'targetAttribute' => ['auction_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'broker_id' => Yii::t('label', 'Broker ID'),
			'auction_id' => Yii::t('label', 'Auction ID'),
			'price' => Yii::t('label', 'Price'),
			'currency' => Yii::t('label', 'Currency'),
			'sent_at' => Yii::t('label', 'Sent At'),
			'expired_at' => Yii::t('label', 'Expired At'),
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
	public function getCommercial()
	{
		return $this->hasOne(Commercial::class, ['bid_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getBroker()
	{
		return $this->hasOne(Broker::class, ['id' => 'broker_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAuction()
	{
		return $this->hasOne(Auction::class, ['id' => 'auction_id']);
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
	 * @return \yii\db\ActiveQuery
	 */
	public function getBidName()
	{
		return $this->id . ' - ' . $this->auction->id . ' - ' .Auction::getPositionLabels()[$this->auction->position];
	}

	/**
	 * Finds Auction model by position.
	 *
	 * @param string $module
	 * @return array|\yii\db\ActiveRecord[]|self[]|null
	 */
	public static function findLastBid($auction_id = null)
	{
		$query = static::find()
			->alias('b')
			->select([
				'b.*',
			])
			->where([
				'AND',
				['b.status' => self::STATUS_ACTIVE],
				['b.deleted' => self::NO],
				['b.auction_id' => $auction_id],
			])
			->orderBy([
				'b.price' => SORT_DESC,
			]);


		return $query->one();
	}
}
