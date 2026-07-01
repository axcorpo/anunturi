<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%announcement_has_picture}}".
 *
 * @property int $announcement_id
 * @property int $picture_id
 *
 * @property Announcement $announcement
 * @property Picture $picture
 */
class AnnouncementHasPicture extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%announcement_has_picture}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['announcement_id', 'picture_id'], 'required'],
            [['announcement_id', 'picture_id'], 'integer'],
            [['announcement_id', 'picture_id'], 'unique', 'targetAttribute' => ['announcement_id', 'picture_id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['picture_id'], 'exist', 'skipOnError' => true, 'targetClass' => Picture::class, 'targetAttribute' => ['picture_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'announcement_id' => Yii::t('label', 'Announcement ID'),
            'picture_id' => Yii::t('label', 'Picture ID'),
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
    public function getPicture()
    {
        return $this->hasOne(Picture::class, ['id' => 'picture_id']);
    }
}
