<?php

namespace common\models;

use common\helpers\DateHelper;
use tws\helpers\Url;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%commercial}}".
 *
 * @property int $id
 * @property int $bid_id
 * @property string $code
 * @property string $embed
 * @property string $image
 * @property string $video
 * @property string $url
 * @property string $phone
 * @property string $email
 * @property int $width
 * @property int $height
 * @property int $orientation
 * @property int $type
 * @property int $sort_order
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property string $ip_address
 * @property int $status
 * @property int $deleted
 *
 * @property Bid $bid
 * @property User $creator
 * @property User $updater
 */
class Commercial extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%commercial}}';
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
			'PositionBehavior' => [
                'class' => PositionBehavior::class,
				'positionAttribute' => 'sort_order',
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
			[['bid_id', 'code', 'status'], 'required'],
			[['bid_id', 'width', 'height', 'orientation', 'type', 'sort_order', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['embed'], 'string'],
			[['created_at', 'updated_at'], 'safe'],
			[['code', 'image', 'video', 'url', 'phone', 'email', 'ip_address'], 'string', 'max' => 255],
			[['bid_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bid::class, 'targetAttribute' => ['bid_id' => 'id']],
		];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
		return [
			'id' => Yii::t('label', 'ID'),
			'bid_id' => Yii::t('label', 'Bid ID'),
			'code' => Yii::t('label', 'Code'),
			'embed' => Yii::t('label', 'Embed'),
			'image' => Yii::t('label', 'Image'),
			'video' => Yii::t('label', 'Video'),
			'url' => Yii::t('label', 'Url'),
			'phone' => Yii::t('label', 'Phone'),
			'email' => Yii::t('label', 'Email'),
			'width' => Yii::t('label', 'Width'),
			'height' => Yii::t('label', 'Height'),
			'orientation' => Yii::t('label', 'Orientation'),
			'type' => Yii::t('label', 'Type'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'ip_address' => Yii::t('label', 'Ip Address'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBid()
    {
        return $this->hasOne(Bid::class, ['id' => 'bid_id']);
    }

	/**
	 * Gets the imageUrl.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getImageUrl($scheme = false)
	{
		return Url::to("@uploads/commercial/{$this->id}/{$this->image}", $scheme);
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
	 * Generates an unique code.
	 *
	 * @param int $length
	 * @return string
	 * @throws \yii\base\Exception
	 */
	public static function generateUniqueCode($length = 8)
	{
		$code = Yii::$app->security->generateRandomString($length);

		// Ensure that the generated string is alphanumeric
		if (!preg_match('/^[a-zA-Z0-9]*$/', $code)) {
			return static::generateUniqueCode($length);
		}

		// Ensure that the generated string is unique
		if (static::find()->where(['code' => $code])->limit(1)->exists()) {
			return static::generateUniqueCode($length);
		}

		return $code;
	}

	/**
	 * Finds Commercial model by position.
	 *
	 * @param string $module
	 * @return array|\yii\db\ActiveRecord[]|self[]|null
	 */
	public static function findCommercialByPosition($position, $counties = [], $categories = [])
	{
		$query = static::find()
			->alias('c')
			->select([
				'c.id',
				'c.bid_id',
				'c.image',
				'c.url',
				'c.created_at',
				'c.updated_at',
				'c.created_by',
				'c.status',
			])
			->joinWith([
				'bid b',
				'bid.auction au',
			])
			->where([
				'AND',
				['c.status' => Commercial::STATUS_ACTIVE],
				['c.deleted' => Commercial::NO],
				['b.status' => Bid::STATUS_ACTIVE],
				['b.deleted' => Bid::NO],
				['au.status' => Auction::STATUS_ACTIVE],
				['au.deleted' => Auction::NO],
				['au.position' => $position],
				['<=', 'au.valid_from', date('Y-m-d H:i:s')],
				['>=', 'au.valid_to', date('Y-m-d H:i:s')],
			]);
		$query->orderBy([
			'b.price' => SORT_DESC
		]);
		if ($counties) {
			$query->andWhere([
				'au.county_id' => $counties
			]);
		}
		if ($categories) {
			$query->andWhere([
				'au.category_id' => $categories
			]);
		}


		return $query->one();
	}
}
