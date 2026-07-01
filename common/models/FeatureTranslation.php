<?php

namespace common\models;

use Yii;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%feature_translation}}".
 *
 * @property int $feature_id
 * @property string $language_id
 * @property string $name
 * @property int $deleted
 *
 * @property Feature $feature
 * @property Language $language
 */
class FeatureTranslation extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%feature_translation}}';
    }

	/**
	 * @inheritdoc
	 * @throws \Exception
	 */
	public function behaviors()
	{
		return [
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
            [['feature_id', 'language_id', 'name'], 'required'],
            [['feature_id', 'deleted'], 'integer'],
            [['language_id'], 'string', 'max' => 5],
            [['name'], 'string', 'max' => 255],
            [['feature_id', 'language_id'], 'unique', 'targetAttribute' => ['feature_id', 'language_id']],
            [['feature_id'], 'exist', 'skipOnError' => true, 'targetClass' => Feature::class, 'targetAttribute' => ['feature_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'feature_id' => Yii::t('label', 'Feature ID'),
            'language_id' => Yii::t('label', 'Language ID'),
            'name' => Yii::t('label', 'Name'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFeature()
    {
        return $this->hasOne(Feature::class, ['id' => 'feature_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['language_id' => 'language_id']);
    }
}
