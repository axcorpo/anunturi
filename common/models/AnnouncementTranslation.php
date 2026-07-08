<?php

namespace common\models;

use common\helpers\AnnouncementListSearch;
use Yii;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%announcement_translation}}".
 *
 * @property int $announcement_id
 * @property string $language_id
 * @property string $title
 * @property string $slug
 * @property string $keywords
 * @property string $description
 * @property string $content
 * @property string|null $search_text
 * @property int $deleted
 *
 * @property Announcement $announcement
 * @property Language $language
 */
class AnnouncementTranslation extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%announcement_translation}}';
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
            [['announcement_id', 'language_id', 'title'], 'required'],
            [['announcement_id', 'deleted'], 'integer'],
            [['content'], 'string'],
            [['language_id'], 'string', 'max' => 5],
            [['title', 'slug', 'keywords', 'description'], 'string', 'max' => 255],
            [['announcement_id', 'language_id'], 'unique', 'targetAttribute' => ['announcement_id', 'language_id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'announcement_id' => Yii::t('label', 'Announcement ID'),
            'language_id' => Yii::t('label', 'Language ID'),
            'title' => Yii::t('label', 'Title'),
            'slug' => Yii::t('label', 'Slug'),
            'keywords' => Yii::t('label', 'Keywords'),
            'description' => Yii::t('label', 'Description'),
            'content' => Yii::t('label', 'Content'),
            'deleted' => Yii::t('label', 'Deleted'),
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
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['language_id' => 'language_id']);
    }

    /**
     * Keeps the denormalized `search_text` column in sync with the translation fields.
     * The column is the haystack for the listing free-text search — it must be HTML-stripped
     * and diacritic-folded so LIKE matches against the user's normalized query.
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->search_text = AnnouncementListSearch::normalize(implode(' ', [
            (string) $this->title,
            (string) $this->description,
            (string) $this->keywords,
            (string) $this->content,
        ]));
        return true;
    }
}
