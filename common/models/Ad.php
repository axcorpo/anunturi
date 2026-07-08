<?php

namespace common\models;

use common\helpers\UploadHelper;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%ad}}".
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
class Ad extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ad}}';
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
            [['bid_id', 'status'], 'required'],
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
		return UploadHelper::getFileUrl("ad/{$this->id}/{$this->image}", $scheme);
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
	 * Finds Ad model by position.
	 *
	 * @param string $module
	 * @return array|\yii\db\ActiveRecord[]|self[]|null
	 */
	public static function findAdByPosition($position, $categories = [])
	{
		$query = static::find()
			->alias('a')
			->select([
				'a.id',
				'a.bid_id',
				'a.image',
				'a.url',
				'a.created_at',
				'a.updated_at',
				'a.created_by',
				'a.status',
			])
			->joinWith([
				'bid b',
				'bid.auction au',
				'bid.category c',
			])
			->where([
				'AND',
				['a.status' => Ad::STATUS_ACTIVE],
				['a.deleted' => Ad::NO],
				['b.status' => Bid::STATUS_ACTIVE],
				['b.deleted' => Bid::NO],
				['au.status' => Auction::STATUS_ACTIVE],
				['au.deleted' => Auction::NO],
				['au.position' => $position],
				['<=', 'au.valid_from', date('Y-m-d H:i:s')],
				['>', 'au.valid_to', date('Y-m-d H:i:s')],
			]);
		$query->orderBy([
			'b.price' => SORT_DESC
		]);

		return $query->one();
	}
}
