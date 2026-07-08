<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%extra_feature_has_announcement}}".
 *
 * @property int $extra_feature_id
 * @property int $announcement_id
 *
 * @property Announcement $announcement
 * @property ExtraFeature $extraFeature
 */
class ExtraFeatureHasAnnouncement extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%extra_feature_has_announcement}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['extra_feature_id', 'announcement_id'], 'required'],
            [['extra_feature_id', 'announcement_id'], 'integer'],
            [['extra_feature_id', 'announcement_id'], 'unique', 'targetAttribute' => ['extra_feature_id', 'announcement_id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['extra_feature_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExtraFeature::class, 'targetAttribute' => ['extra_feature_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'extra_feature_id' => Yii::t('label', 'Extra Feature ID'),
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
    public function getExtraFeature()
    {
        return $this->hasOne(ExtraFeature::class, ['id' => 'extra_feature_id']);
    }
}
