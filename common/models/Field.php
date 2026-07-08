<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%field}}".
 *
 * @property int $id
 * @property string $name
 * @property string $attributes
 * @property int $type
 * @property int $filter
 * @property int $extra
 * @property int $filter_type
 * @property string $min
 * @property string $max
 * @property string $step
 * @property int $decimals
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 *
 * @property CategoryField[] $categoryFields
 * @property FieldTranslation[] $fieldTranslations
 * @property FieldTranslation $translation
 * @property Language[] $languages
 * @property FieldValue[] $fieldValues
 * @property Option[] $options
 * @property User $creator
 * @property User $updater
 */
class Field extends UuidActiveRecord
{
	const TYPE_TEXT = 1;
    const TYPE_NUMBER = 2;
    const TYPE_TEXTAREA = 3;
    const TYPE_CHECKBOX = 4;
    const TYPE_RADIO = 5;
    const TYPE_SELECT = 6;
    const TYPE_MULTIPLE_SELECT = 7;
    const TYPE_DATE = 8;
    const TYPE_DATETIME = 9;
    const TYPE_DATE_INTERVAL = 10;
    const TYPE_DATETIME_INTERVAL = 11;

    const FILTER_TYPE_TEXT = 1;
    const FILTER_TYPE_NUMBER = 2;
    const FILTER_TYPE_TEXTAREA = 3;
    const FILTER_TYPE_CHECKBOX = 4;
    const FILTER_TYPE_RADIO = 5;
    const FILTER_TYPE_SELECT = 6;
    const FILTER_TYPE_MULTIPLE_SELECT = 7;
    const FILTER_TYPE_DATE = 8;
    const FILTER_TYPE_DATETIME = 9;
    const FILTER_TYPE_DATE_INTERVAL = 10;
    const FILTER_TYPE_DATETIME_INTERVAL = 11;


	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%field}}';
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
			[['attributes'], 'string'],
			[['name', 'type', 'status'], 'required'],
			[['type', 'filter', 'extra', 'filter_type', 'decimals', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['min', 'max', 'step'], 'number'],
			[['created_at', 'updated_at'], 'safe'],
			[['name'], 'string', 'max' => 255],
			[['name'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'name' => Yii::t('label', 'Name'),
			'attributes' => Yii::t('label', 'Attributes'),
			'type' => Yii::t('label', 'Type'),
            'extra' => Yii::t('label', 'Extra'),
            'filter' => Yii::t('label', 'Filter'),
            'filter_type' => Yii::t('label', 'Filter Type'),
            'min' => Yii::t('label', 'Min'),
            'max' => Yii::t('label', 'Max'),
            'step' => Yii::t('label', 'Step'),
            'decimals' => Yii::t('label', 'Decimals'),
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
    public function getCategoryFields()
    {
        return $this->hasMany(CategoryField::class, ['field_id' => 'id']);
    }


    /**
	 * @return \yii\db\ActiveQuery
	 */
	public function getFieldTranslations()
	{
		return $this->hasMany(FieldTranslation::class, ['field_id' => 'id']);
	}

	/**
	 * Gets the model translation.
	 *
	 * @param string|null $language
	 * @return mixed
	 */
	public function getTranslation($language = null)
	{
		if ($language === null) {
			$language = Yii::$app->language;
		}
		return ArrayHelper::index($this->fieldTranslations, 'language_id')[$language];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%field_translation}}', ['field_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getFieldValues()
	{
		return $this->hasMany(FieldValue::class, ['field_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOptions()
	{
		return $this->hasMany(Option::class, ['field_id' => 'id']);
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
	public static function getTypeLabels()
	{
		return [
			self::TYPE_TEXT => Yii::t('common', 'Text'),
			self::TYPE_NUMBER => Yii::t('common', 'Number'),
			self::TYPE_TEXTAREA => Yii::t('common', 'Textarea'),
			self::TYPE_CHECKBOX => Yii::t('common', 'Checkbox'),
			self::TYPE_RADIO => Yii::t('common', 'Radio'),
			self::TYPE_SELECT => Yii::t('common', 'Select'),
			self::TYPE_MULTIPLE_SELECT => Yii::t('common', 'Multiple Select'),
			self::TYPE_DATE => Yii::t('common', 'Date'),
			self::TYPE_DATETIME => Yii::t('common', 'Datetime'),
			self::TYPE_DATE_INTERVAL => Yii::t('common', 'Date Interval'),
			self::TYPE_DATETIME_INTERVAL => Yii::t('common', 'Datetime Interval'),
		];
	}

    /**
     * Model statuses array.
     *
     * @return array
     */
    public static function getFilterTypeLabels()
    {
        return [
            self::FILTER_TYPE_TEXT => Yii::t('common', 'Text'),
            self::FILTER_TYPE_NUMBER => Yii::t('common', 'Number'),
            self::FILTER_TYPE_TEXTAREA => Yii::t('common', 'Textarea'),
            self::FILTER_TYPE_CHECKBOX => Yii::t('common', 'Checkbox'),
            self::FILTER_TYPE_RADIO => Yii::t('common', 'Radio'),
            self::FILTER_TYPE_SELECT => Yii::t('common', 'Select'),
            self::FILTER_TYPE_MULTIPLE_SELECT => Yii::t('common', 'Multiple Select'),
            self::FILTER_TYPE_DATE => Yii::t('common', 'Date'),
            self::FILTER_TYPE_DATETIME => Yii::t('common', 'Datetime'),
            self::FILTER_TYPE_DATE_INTERVAL => Yii::t('common', 'Date Interval'),
            self::FILTER_TYPE_DATETIME_INTERVAL => Yii::t('common', 'Datetime Interval'),
        ];
    }

	/**
	 * Finds all models
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllFields()
	{
		return static::find()
			->alias('f')
			->joinWith([
				'fieldTranslations ft',
			])
			->andWhere([
				'f.status' => static::STATUS_ACTIVE,
				'f.deleted' => static::NO,
			])
			->all();
	}

	/**
	 * Finds all models by Category model ID(s)
	 * @param int|array $category_id
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllByCategoryId($category_id, $action_id = null)
	{
		$query = static::find()
			->alias('f')
			->joinWith([
				'fieldTranslations ft',
				'categoryFields cf',
			])
			->andWhere([
				'f.status' => static::STATUS_ACTIVE,
				'f.deleted' => static::NO,
			])
			->andWhere([
				'AND',
				['IS NOT', 'cf.category_id', null],
				['>=', 'cf.category_id', 1],
			])
			->andWhere([
				'cf.category_id' => $category_id,
			]);
		if ($action_id) {
			$query->where([
				'cf.action_id' => $action_id
			]);
		}
		return $query->all();
	}

	/**
	 * Finds all models by Category model ID(s)
	 * @param int|array $category_id
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllByCategoryAction($category_id, $action_id)
	{
		$query = static::find()
			->alias('f')
			->joinWith([
				'fieldTranslations ft',
				'categoryFields cf',
			])
			->andWhere([
				'f.status' => static::STATUS_ACTIVE,
				'f.deleted' => static::NO,
			])
			->andWhere([
				'AND',
				['IS NOT', 'cf.category_id', null],
				['IS NOT', 'cf.action_id', null],
				['>=', 'cf.category_id', 1],
				['>=', 'cf.action_id', 1],
				['=', 'cf.category_id', $category_id],
				['=', 'cf.action_id', $action_id],
			]);
		return $query->all();
	}
}
