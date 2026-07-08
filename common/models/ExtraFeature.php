<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%extra_feature}}".
 *
 * @property int $id
 * @property int $type
 * @property int $sort_order
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property ExtraFeatureHasAnnouncement[] $extraFeatureHasAnnouncements
 * @property Announcement[] $announcements
 * @property ExtraFeatureTranslation[] $extraFeatureTranslations
 * @property ExtraFeatureTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */
class ExtraFeature extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%extra_feature}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'sort_order', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['status'], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'type' => Yii::t('label', 'Type'),
            'sort_order' => Yii::t('label', 'Sort Order'),
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
    public function getExtraFeatureHasAnnouncements()
    {
        return $this->hasMany(ExtraFeatureHasAnnouncement::class, ['extra_feature_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnnouncements()
    {
        return $this->hasMany(Announcement::class, ['id' => 'announcement_id'])->viaTable('{{%extra_feature_has_announcement}}', ['extra_feature_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExtraFeatureTranslations()
    {
        return $this->hasMany(ExtraFeatureTranslation::class, ['extra_feature_id' => 'id']);
    }

    /**
     * Gets the model translation.
     *
     * @param null|string $language
     * @return null|PageTranslation
     */
    public function getTranslation($language = null)
    {
        if ($language === null) {
            $language = Yii::$app->language;
        }
        return ArrayHelper::index($this->extraFeatureTranslations, 'language_id')[$language];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguages()
    {
        return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%extra_feature_translation}}', ['extra_feature_id' => 'id']);
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
}
