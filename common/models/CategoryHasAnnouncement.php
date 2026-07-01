<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%category_has_announcement}}".
 *
 * @property int $category_id
 * @property int $announcement_id
 *
 * @property Announcement $announcement
 * @property Category $category
 */
class CategoryHasAnnouncement extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category_has_announcement}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'announcement_id'], 'required'],
            [['category_id', 'announcement_id'], 'integer'],
            [['category_id', 'announcement_id'], 'unique', 'targetAttribute' => ['category_id', 'announcement_id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'category_id' => Yii::t('label', 'Category ID'),
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
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }
}
