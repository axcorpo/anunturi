<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%subscription_has_promotional}}".
 *
 * @property int $subscription_id
 * @property int $promotional_id
 *
 * @property Promotional $promotional
 * @property Subscription $subscription
 */
class SubscriptionHasPromotional extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%subscription_has_promotional}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subscription_id', 'promotional_id'], 'required'],
            [['subscription_id', 'promotional_id'], 'integer'],
            [['subscription_id', 'promotional_id'], 'unique', 'targetAttribute' => ['subscription_id', 'promotional_id']],
            [['promotional_id'], 'exist', 'skipOnError' => true, 'targetClass' => Promotional::class, 'targetAttribute' => ['promotional_id' => 'id']],
            [['subscription_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscription::class, 'targetAttribute' => ['subscription_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'subscription_id' => Yii::t('label', 'Subscription ID'),
            'promotional_id' => Yii::t('label', 'Promotional ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPromotional()
    {
        return $this->hasOne(Promotional::class, ['id' => 'promotional_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscription()
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
    }
}
