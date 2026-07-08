<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%action}}".
 *
 * @property int $id
 * @property int $sort_order
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $type
 * @property int $status
 * @property int $deleted
 *
 *
 * @property CategoryHasAction[] $categoryHasActions
 * @property Category[] $categories
 * @property CategoryField[] $categoryFields
 * @property AnnouncementHasAction[] $announcementHasActions
 * @property Announcement[] $announcements
 * @property User $creator
 * @property User $updater
 */
class Action extends UuidActiveRecord
{
	const TYPE_EMPLOY = 1;
	const TYPE_SEARCH_EMPLOYMENT = 2;
	const TYPE_SELL = 3;
	const TYPE_BUY = 4;
	const TYPE_RENT = 5;
	const TYPE_SEARCH_RENT = 6;
	const TYPE_TRANSPORT = 7;
	const TYPE_SEARCH_TRANSPORT = 8;
	const TYPE_EXECUTE = 9;
	const TYPE_SEARCH_EXECUTION = 10;
	const TYPE_OFFER = 11;
	const TYPE_SEARCH_OFFER = 12;
	const TYPE_PROVIDE = 13;
	const TYPE_SEARCH_PROVIDER = 14;
	const TYPE_BID = 15;
	const TYPE_SEARCH_BID = 16;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%action}}';
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
			[['sort_order', 'created_by', 'updated_by', 'type', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['type', 'status'], 'required'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'type' => Yii::t('label', 'Type'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryHasActions()
	{
		return $this->hasMany(CategoryHasAction::class, ['action_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::class, ['id' => 'category_id'])->viaTable('{{%category_has_action}}', ['action_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryFields()
	{
		return $this->hasMany(CategoryField::class, ['action_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAnnouncementHasActions()
	{
		return $this->hasMany(AnnouncementHasAction::class, ['action_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getAnnouncements()
	{
		return $this->hasMany(Announcement::class, ['id' => 'announcement_id'])->viaTable('{{%announcement_has_action}}', ['action_id' => 'id']);
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
	 * Model statuses array.
	 *
	 * @return array
	 */
	public static function getTypes()
	{
		return [
			self::TYPE_EMPLOY => Yii::t('common', 'Employ'),
			self::TYPE_SEARCH_EMPLOYMENT => Yii::t('common', 'Search For Employment'),
			self::TYPE_SELL => Yii::t('common', 'Sell'),
			self::TYPE_BUY => Yii::t('common', 'Buy'),
			self::TYPE_RENT => Yii::t('common', 'Rent'),
			self::TYPE_SEARCH_RENT => Yii::t('common', 'Search To Rent'),
			self::TYPE_TRANSPORT => Yii::t('common', 'Transport'),
			self::TYPE_SEARCH_TRANSPORT => Yii::t('common', 'Search To Transport'),
			self::TYPE_EXECUTE => Yii::t('common', 'Execute'),
			self::TYPE_SEARCH_EXECUTION => Yii::t('common', 'Search For Execution'),
			self::TYPE_OFFER => Yii::t('common', 'Offer'),
			self::TYPE_SEARCH_OFFER => Yii::t('common', 'Search For Offer'),
			self::TYPE_PROVIDE => Yii::t('common', 'Provide'),
			self::TYPE_SEARCH_PROVIDER => Yii::t('common', 'Search For Provider'),
			self::TYPE_BID => Yii::t('common', 'Bid'),
			self::TYPE_SEARCH_BID => Yii::t('common', 'Search To Bid'),
		];
	}

	/**
	 * Model statuses array.
	 *
	 * @return array
	 */
	public static function getMyTypes()
	{
		return [
			self::TYPE_EMPLOY => Yii::t('common', 'I Employ'),
			self::TYPE_SEARCH_EMPLOYMENT => Yii::t('common', 'I Search For Employment'),
			self::TYPE_SELL => Yii::t('common', 'I Sell'),
			self::TYPE_BUY => Yii::t('common', 'I Buy'),
			self::TYPE_RENT => Yii::t('common', 'I Rent'),
			self::TYPE_SEARCH_RENT => Yii::t('common', 'I Search To Rent'),
			self::TYPE_TRANSPORT => Yii::t('common', 'I am Transporting'),
			self::TYPE_SEARCH_TRANSPORT => Yii::t('common', 'I Search To Transport'),
			self::TYPE_EXECUTE => Yii::t('common', 'I Execute'),
			self::TYPE_SEARCH_EXECUTION => Yii::t('common', 'I Search For Execution'),
			self::TYPE_OFFER => Yii::t('common', 'I Offer'),
			self::TYPE_SEARCH_OFFER => Yii::t('common', 'I Search For Offer'),
			self::TYPE_PROVIDE => Yii::t('common', 'I Provide'),
			self::TYPE_SEARCH_PROVIDER => Yii::t('common', 'I Search For Provider'),
			self::TYPE_BID => Yii::t('common', 'I Bid'),
			self::TYPE_SEARCH_BID => Yii::t('common', 'I Search To Bid'),
		];
	}

	/**
	 * Query all records by Category model id.
	 *
	 * @param $defect_category_id
	 * @return array
	 * @throws \yii\db\Exception
	 */
	public static function queryActionByCategoryId($category_id)
	{
		$actions = [];
		$query = static::find()
			->alias('a')
			->select([
				'a.*',
			])
			->joinWith([
				'categories c',
			], false)
			->where([
				'a.status' => self::STATUS_ACTIVE,
				'a.deleted' => self::NO,
				'c.id' => $category_id,
			]);
		foreach ($query->createCommand()->queryAll() as $action) {
			$actions[] = ['id' => $action['id'], 'name' => Action::getMyTypes()[$action['type']]];
		}
		return $actions;
	}
}
