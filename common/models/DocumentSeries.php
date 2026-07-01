<?php

namespace common\models;

use common\behaviors\DocumentSeriesAndNumberBehavior;
use tws\behaviors\DefaultBehavior;
use Yii;
use yii\base\BaseObject;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%document_series}}".
 *
 * @property int $id
 * @property string $name
 * @property int $type
 * @property int $first_number
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property User $creator
 * @property User $updater
 */
class DocumentSeries extends CommonActiveRecord
{

	CONST TYPE_INVOICE = 1;
    CONST TYPE_PROFORM =  2;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%document_series}}';
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
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
                'groupAttributes' => ['type'],
                'ensureDefaultValue' => true,
			],
            'DocumentSeriesAndNumberBehavior' => [
                'class' => DocumentSeriesAndNumberBehavior::class,
                'documentSeriesModel' => DocumentSeries::class,
                'documentSeriesDefaultValue' => function () {
                    return DocumentSeries::findDefaultDocumentSeries()->name;
                },
                'documentSeriesAttribute' => 'name',
                'documentNumberAttribute' => 'first_number',
                'documentStatusAttribute' => 'status',
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
			[['name', 'status'], 'required'],
			[['type', 'first_number', 'default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['created_at', 'updated_at'], 'default'],
			[['name'], 'string', 'max' => 255],
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
			'type' => Yii::t('label', 'Type'),
			'first_number' => Yii::t('label', 'First Number'),
			'default' => Yii::t('label', 'Default'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
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
     * Model type labels.
     *
     * @return array
     */
    public static function getTypeLabels()
    {
        return [
            self::TYPE_PROFORM => Yii::t('label', 'Proform'),
			self::TYPE_INVOICE => Yii::t('label', 'Invoice'),
        ];
    }

	/**
	 * Finds all active records.
	 *
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public static function findAllDocumentSeries()
	{
		return static::getDb()->cache(function ($db) {
			return static::find()
				->where([
					'status' => self::STATUS_ACTIVE,
					'deleted' => self::NO,
				])
				->orderBy(['name' => SORT_ASC])
				->indexBy('id')
				->all();
		}, 0, new TagDependency(['tags' => __FUNCTION__]));
	}

	/**
	 * Finds the default record or fallback to the first record.
	 *
	 * @param bool $fallbackToFirst Indicates if the first record should be returned when a default one does not exist.
	 * @return array|\yii\db\ActiveRecord|self|null
	 */
	public static function findDefaultDocumentSeries($fallbackToFirst = true, $type = null)
	{
		$query = static::find()
			->andWhere([
				'status' => self::STATUS_ACTIVE,
				'deleted' => self::NO,
			])
			->limit(1);
		if ($type) {
			$query->andWhere([
				'type' => $type,
			]);
		}

		if (!($model = $query->andWhere(['default' => self::YES])->one())) {
			if ($fallbackToFirst === true) {
				$model = $query->one();
			}
		}

		return $model;
	}

	/**
	 * Gets the next document number based on the document series.
	 *
	 * @param mixed $documentSeries
	 * @return int
	 */
	public static function getNextDocumentNumber($documentSeries = null, $type = null, $status = null)
	{
		$documentSeriesQuery = static::find()
			->andWhere([
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->limit(1);

		if ($documentSeries === null) {
			// Get the first DocumentSeries model
			$documentSeriesModel = $documentSeriesQuery->one();
			$firstNumber = $documentSeriesModel->first_number ?: 1;
			$documentSeries = $documentSeriesModel->name;
		} else {
			// Get the DocumentSeries model by name
			$documentSeriesModel = $documentSeriesQuery->andWhere(['name' => $documentSeries])->one();
			$firstNumber = $documentSeriesModel->first_number ?: 1;
		}


		switch ($type) {
			case DocumentSeries::TYPE_INVOICE:
			case DocumentSeries::TYPE_PROFORM:
				$model = new Invoice();
			break;
			default:
				$model = new Invoice();
			break;
		}

		$maxNumber = $model::find()
			->where(['document_series' => $documentSeries])
			->andFilterWhere(['type' => $type])
			->andFilterWhere(['status' => $status])
			->max('document_number');

		if (empty($maxNumber) || ($firstNumber > $maxNumber)) {
			$maxNumber = $firstNumber - 1;
		}

		return $maxNumber + 1;
	}
}
