<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%package_module}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $deleted
 *
 * @property PackageFeature[] $packageFeatures
 * @property PackageModule $parent
 * @property PackageModule[] $packageModules
 * @property SubscriptionFeature[] $subscriptionFeatures
 * @property User $creator
 * @property User $updater
 */
class PackageModule extends CommonActiveRecord
{
	const GENERAL = 'General';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%package_module}}';
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
			[['parent_id', 'created_by', 'updated_by', 'deleted'], 'integer'],
			[['name'], 'required'],
			[['created_at', 'updated_at'], 'safe'],
			[['name'], 'string', 'max' => 255],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => PackageModule::class, 'targetAttribute' => ['parent_id' => 'id']],
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
			'name' => Yii::t('label', 'Name'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPackageFeatures()
	{
		return $this->hasMany(PackageFeature::class, ['package_module_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(PackageModule::class, ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPackageModules()
	{
		return $this->hasMany(PackageModule::class, ['parent_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSubscriptionFeatures()
	{
		return $this->hasMany(SubscriptionFeature::class, ['package_module_id' => 'id']);
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
	 * Model module labels.
	 *
	 * @return array
	 */
	public static function getModuleLabels()
	{
		return [
			self::GENERAL => Yii::t('label', 'General'),
		];
	}

	/**
	 * Model module labels.
	 *
	 * @param string $attributeName
	 * @return string|null
	 */
	public static function extractModuleNameFromAttributeName($attributeName)
	{
		$packageModuleName = null;

		if (strpos($attributeName, '_') !== false) {
			$packageModuleName = ucfirst(reset(explode('_', $attributeName)));
			if (array_key_exists($packageModuleName, self::getModuleLabels())) {
				return $packageModuleName;
			}
		}

		return $packageModuleName;
	}
}
