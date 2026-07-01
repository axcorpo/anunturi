<?php

namespace common\models;

use Yii;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%extra_feature_translation}}".
 *
 * @property int $extra_feature_id
 * @property string $language_id
 * @property string $name
 * @property int $deleted
 *
 * @property ExtraFeature $extraFeature
 * @property Language $language
 */
class ExtraFeatureTranslation extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%extra_feature_translation}}';
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
            [['extra_feature_id', 'language_id', 'name'], 'required'],
            [['extra_feature_id', 'deleted'], 'integer'],
            [['language_id'], 'string', 'max' => 5],
            [['name'], 'string', 'max' => 255],
            [['extra_feature_id', 'language_id'], 'unique', 'targetAttribute' => ['extra_feature_id', 'language_id']],
            [['extra_feature_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExtraFeature::class, 'targetAttribute' => ['extra_feature_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'extra_feature_id' => Yii::t('label', 'Extra Feature ID'),
            'language_id' => Yii::t('label', 'Language ID'),
            'name' => Yii::t('label', 'Name'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExtraFeature()
    {
        return $this->hasOne(ExtraFeature::class, ['id' => 'extra_feature_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['language_id' => 'language_id']);
    }
}
