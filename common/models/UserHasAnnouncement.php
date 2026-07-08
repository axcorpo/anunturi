<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%user_has_announcement}}".
 *
 * @property int $user_id
 * @property int $announcement_id
 *
 * @property Announcement $announcement
 * @property User $user
 */
class UserHasAnnouncement extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_has_announcement}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'announcement_id'], 'required'],
            [['user_id', 'announcement_id'], 'integer'],
            [['user_id', 'announcement_id'], 'unique', 'targetAttribute' => ['user_id', 'announcement_id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('label', 'User ID'),
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
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * delete the announcement.
     *
     * @return bool
     */
    public function deleteFavouriteAnnouncement()
    {
        try {
            $this->announcement_id = 0;
            $this->user_id = 0;

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
