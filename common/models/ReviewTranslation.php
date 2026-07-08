<?php

namespace common\models;

use Yii;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%review_translation}}".
 *
 * @property int $review_id
 * @property string $language_id
 * @property string $title
 * @property string $content
 * @property int $deleted
 *
 * @property Language $language
 * @property Review $review
 */
class ReviewTranslation extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%review_translation}}';
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
            [['review_id', 'language_id', 'title'], 'required'],
            [['review_id', 'deleted'], 'integer'],
            [['content'], 'string'],
            [['language_id'], 'string', 'max' => 5],
            [['title'], 'string', 'max' => 255],
            [['review_id', 'language_id'], 'unique', 'targetAttribute' => ['review_id', 'language_id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
            [['review_id'], 'exist', 'skipOnError' => true, 'targetClass' => Review::class, 'targetAttribute' => ['review_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'review_id' => Yii::t('label', 'Review ID'),
            'language_id' => Yii::t('label', 'Language ID'),
            'title' => Yii::t('label', 'Title'),
            'content' => Yii::t('label', 'Content'),
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
    public function getReview()
    {
        return $this->hasOne(Review::class, ['id' => 'review_id']);
    }
}
