<?php

namespace common\models;

use Yii;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%locality}}".
 *
 * @property int $id
 * @property string $name
 * @property string $county
 * @property string $auto
 * @property string $zip_code
 * @property int $population
 * @property string $diacritics
 * @property string $latitude
 * @property string $longitude
 * @property int $status
 * @property int $deleted
 */
class Locality extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%locality}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
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
			[['name', 'county', 'status'], 'required'],
			[['population', 'status', 'deleted'], 'integer'],
			[['name', 'county', 'zip_code', 'diacritics', 'latitude', 'longitude'], 'string', 'max' => 255],
			[['auto'], 'string', 'max' => 2],
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
			'county' => Yii::t('label', 'County'),
			'auto' => Yii::t('label', 'Auto'),
			'zip_code' => Yii::t('label', 'Zip Code'),
			'population' => Yii::t('label', 'Population'),
			'diacritics' => Yii::t('label', 'Diacritics'),
			'latitude' => Yii::t('label', 'Latitude'),
			'longitude' => Yii::t('label', 'Longitude'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * Finds by criteria.
	 *
	 * @param string $criteria
	 * @param bool $asArray
	 * @return array|static[]]
	 */
	public static function findByCriteria($criteria, $params = [], $asArray = false)
	{
		$query = static::find()
			->andWhere([
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->andWhere(['LIKE', 'name', $criteria . '%', false]);

		if (!empty($params)) {
			$query->andWhere($params);
		}

		return $query->orderBy(['name' => SORT_ASC])
			->asArray($asArray)
			->indexBy('id')
			->all();
	}

	public static function queryLocalitiesByCounty($county)
	{
		try {
			return static::find()
				->alias('l')
				->select([
					"l.name AS id",
					"l.name",
				])
				->andWhere([
					'l.county' => $county,
				])
				->createCommand()
				->queryAll();
		} catch (\Exception $e) {
			return [];
		}
	}
}
