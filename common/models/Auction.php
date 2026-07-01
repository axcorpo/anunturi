<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%auction}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $county_id
 * @property int $category_id
 * @property int $position
 * @property int $period
 * @property string $cycle
 * @property string $start_at
 * @property string $end_at
 * @property string $start_price
 * @property string $step
 * @property string $currency
 * @property string $valid_from
 * @property string $valid_to
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Auction $parent
 * @property Auction[] $auctions
 * @property Category $category
 * @property County $county
 * @property Bid[] $bids
 * @property User $creator
 * @property User $updater
 */
class Auction extends CommonActiveRecord
{
	const POSITION_HOME_PAGE_300X440_LEFT = 1;
	const POSITION_HOME_PAGE_300X440_RIGHT = 2;
	const POSITION_HOME_PAGE_300X300_CAROUSEL = 3;
	const POSITION_HOME_PAGE_1030X120_BOTTOM = 4;
	const POSITION_ANNOUNCEMENTS_PAGE_300X440_SIDEBAR_TOP = 5;
	const POSITION_ANNOUNCEMENTS_PAGE_300X300_SIDEBAR_BOTTOM = 6;
	const POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_TOP = 7;
	const POSITION_ANNOUNCEMENTS_PAGE_1030X120_LIST_MIDDLE = 8;
	const POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_BOTTOM = 9;

	const CYCLE_DAILY = 1;
	const CYCLE_WEEKLY = 2;
	const CYCLE_MONTHLY = 3;
	const CYCLE_YEARLY = 4;

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auction}}';
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
				'attributes' => ['start_at', 'end_at', 'valid_from', 'valid_to'],
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
			[['parent_id', 'county_id', 'category_id', 'position', 'period', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['position', 'start_at', 'end_at', 'currency', 'valid_from', 'valid_to', 'status'], 'required'],
			[['start_at', 'end_at', 'valid_from', 'valid_to', 'created_at', 'updated_at'], 'safe'],
			[['start_price', 'step'], 'number'],
			[['cycle'], 'string', 'max' => 255],
			[['currency'], 'string', 'max' => 3],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Auction::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
			[['county_id'], 'exist', 'skipOnError' => true, 'targetClass' => County::class, 'targetAttribute' => ['county_id' => 'id']],
		];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
		return [
			'id' => Yii::t('label', 'ID'),
			'parent_id' => Yii::t('label', 'Parent ID'),
			'county_id' => Yii::t('label', 'County ID'),
			'category_id' => Yii::t('label', 'Category ID'),
			'position' => Yii::t('label', 'Position'),
			'period' => Yii::t('label', 'Period'),
			'cycle' => Yii::t('label', 'Cycle'),
			'start_at' => Yii::t('label', 'Start At'),
			'end_at' => Yii::t('label', 'End At'),
			'start_price' => Yii::t('label', 'Start Price'),
			'step' => Yii::t('label', 'Step'),
			'currency' => Yii::t('label', 'Currency'),
			'valid_from' => Yii::t('label', 'Valid From'),
			'valid_to' => Yii::t('label', 'Valid To'),
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
	public function getParent()
	{
		return $this->hasOne(Auction::class, ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAuctions()
	{
		return $this->hasMany(Auction::class, ['parent_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategory()
	{
		return $this->hasOne(Category::class, ['id' => 'category_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCounty()
	{
		return $this->hasOne(County::class, ['id' => 'county_id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBids()
    {
        return $this->hasMany(Bid::class, ['auction_id' => 'id']);
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
	 * @return string
	 */
	public function getAuctionName()
	{
		return $this->id . ' - ' . Auction::getPositionLabels()[$this->position];
	}

	/**
	 * Model statuses array.
	 *
	 * @return array
	 */
	public static function getPositionLabels()
	{
		return [
			self::POSITION_HOME_PAGE_300X440_LEFT => Yii::t('common', 'Home Page 300x440 Left'),
			self::POSITION_HOME_PAGE_300X440_RIGHT => Yii::t('common', 'Home Page 300x440 Right'),
			self::POSITION_HOME_PAGE_300X300_CAROUSEL => Yii::t('common', 'Home Page 300x300 Carousel'),
			self::POSITION_HOME_PAGE_1030X120_BOTTOM => Yii::t('common', 'Home Page 1030x120 Bottom'),
			self::POSITION_ANNOUNCEMENTS_PAGE_300X440_SIDEBAR_TOP => Yii::t('common', 'Announcements Page 300x440 Sidebar Top'),
			self::POSITION_ANNOUNCEMENTS_PAGE_300X300_SIDEBAR_BOTTOM => Yii::t('common', 'Announcements Page 300x300 Sidebar Bottom'),
			self::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_TOP => Yii::t('common', 'Announcements Page 300x440 List Top'),
			self::POSITION_ANNOUNCEMENTS_PAGE_1030X120_LIST_MIDDLE => Yii::t('common', 'Announcements Page 1030x120 List Middle'),
			self::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_BOTTOM => Yii::t('common', 'Announcements Page 300x440 List Bottom'),
		];
	}

	/**
	 * Model Cycle labels.
	 *
	 * @return array
	 */
	public static function getCycleLabels()
	{
		return [
			self::CYCLE_DAILY => Yii::t('label', 'Daily'),
			self::CYCLE_WEEKLY => Yii::t('label', 'Weekly'),
			self::CYCLE_MONTHLY => Yii::t('label', 'Monthly'),
			self::CYCLE_YEARLY => Yii::t('label', 'Yearly'),
		];
	}

	/**
	 * Finds Auction model by position.
	 *
	 * @param string $module
	 * @return array|\yii\db\ActiveRecord[]|self[]|null
	 */
	public static function findAuctionByPosition($position, $counties = null, $categories = null)
	{
		$query = static::find()
			->alias('c')
			->select([
				'c.id',
				'c.parent_id',
				'c.county_id',
				'c.category_id',
				'c.position',
				'c.period',
				'c.cycle',
				'c.start_at',
				'c.end_at',
				'c.start_price',
				'c.step',
				'c.currency',
				'c.valid_from',
				'c.valid_to',
				'c.created_by',
				'c.updated_by',
				'c.created_at',
				'c.updated_at',
				'c.status',
				'c.deleted',
			])
			->where([
				'AND',
				['c.status' => Commercial::STATUS_ACTIVE],
				['c.deleted' => Commercial::NO],
				['c.position' => $position],
				['YEAR(c.start_at)' => date('Y')],
				['MONTH(c.start_at)' => date('m')],
			]);
		if ($counties) {
			$query->andWhere([
				'c.county_id' => $counties
			]);
		}
		if ($categories) {
			$query->andWhere([
				'c.category_id' => $categories
			]);
		}


		return $query->one();
	}
}
