<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%marketing_group_translation}}".
 *
 * @property int $marketing_group_id
 * @property string $language_id
 * @property string $name
 * @property int $deleted
 *
 * @property Language $language
 * @property MarketingGroup $marketingGroup
 */
class MarketingGroupTranslation extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%marketing_group_translation}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['marketing_group_id', 'language_id', 'name'], 'required'],
            [['marketing_group_id', 'deleted'], 'integer'],
            [['language_id'], 'string', 'max' => 5],
            [['name'], 'string', 'max' => 255],
            [['marketing_group_id', 'language_id'], 'unique', 'targetAttribute' => ['marketing_group_id', 'language_id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
            [['marketing_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => MarketingGroup::class, 'targetAttribute' => ['marketing_group_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'marketing_group_id' => Yii::t('label', 'Marketing Group ID'),
            'language_id' => Yii::t('label', 'Language ID'),
            'name' => Yii::t('label', 'Name'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['language_id' => 'language_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroup()
    {
        return $this->hasOne(MarketingGroup::class, ['id' => 'marketing_group_id']);
    }
}
