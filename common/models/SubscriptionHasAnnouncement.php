<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%subscription_has_announcement}}".
 *
 * @property int $subscription_id
 * @property int $announcement_id
 *
 * @property Announcement $announcement
 * @property Subscription $subscription
 */
class SubscriptionHasAnnouncement extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%subscription_has_announcement}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subscription_id', 'announcement_id'], 'required'],
            [['subscription_id', 'announcement_id'], 'integer'],
            [['subscription_id', 'announcement_id'], 'unique', 'targetAttribute' => ['subscription_id', 'announcement_id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
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
            'announcement_id' => Yii::t('label', 'Announcement ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnnouncement()
    {
        return $this->hasOne(Announcement::class, ['id' => 'announcement_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscription()
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
    }
}
