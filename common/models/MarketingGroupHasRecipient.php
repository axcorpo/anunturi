<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%marketing_group_has_recipient}}".
 *
 * @property int $marketing_group_id
 * @property int $marketing_recipient_id
 *
 * @property MarketingGroup $marketingGroup
 * @property MarketingRecipient $marketingRecipient
 */
class MarketingGroupHasRecipient extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%marketing_group_has_recipient}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['marketing_group_id', 'marketing_recipient_id'], 'required'],
            [['marketing_group_id', 'marketing_recipient_id'], 'integer'],
            [['marketing_group_id', 'marketing_recipient_id'], 'unique', 'targetAttribute' => ['marketing_group_id', 'marketing_recipient_id']],
            [['marketing_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => MarketingGroup::class, 'targetAttribute' => ['marketing_group_id' => 'id']],
            [['marketing_recipient_id'], 'exist', 'skipOnError' => true, 'targetClass' => MarketingRecipient::class, 'targetAttribute' => ['marketing_recipient_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'marketing_group_id' => Yii::t('label', 'Marketing Group ID'),
            'marketing_recipient_id' => Yii::t('label', 'Marketing Recipient ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroup()
    {
        return $this->hasOne(MarketingGroup::class, ['id' => 'marketing_group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingRecipient()
    {
        return $this->hasOne(MarketingRecipient::class, ['id' => 'marketing_recipient_id']);
    }
}
