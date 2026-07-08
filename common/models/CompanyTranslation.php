<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%company_translation}}".
 *
 * @property int $company_id
 * @property string $language_id
 * @property string $activity
 * @property string $slug
 * @property string $keywords
 * @property string $description
 * @property string $content
 * @property string $schedule
 * @property int $deleted
 *
 * @property Company $company
 * @property Language $language
 */
class CompanyTranslation extends UuidActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%company_translation}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['company_id', 'language_id'], 'required'],
			[['company_id', 'deleted'], 'integer'],
            [['content', 'schedule'], 'string'],
			[['language_id'], 'string', 'max' => 5],
			[['activity',  'slug', 'keywords', 'description'], 'string', 'max' => 255],
			[['company_id', 'language_id'], 'unique', 'targetAttribute' => ['company_id', 'language_id']],
			[['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
			[['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'company_id' => Yii::t('label', 'Company ID'),
			'language_id' => Yii::t('label', 'Language ID'),
			'activity' => Yii::t('label', 'Activity'),
            'slug' => Yii::t('label', 'Slug'),
            'keywords' => Yii::t('label', 'Keywords'),
            'description' => Yii::t('label', 'Description'),
            'content' => Yii::t('label', 'Content'),
            'schedule' => Yii::t('label', 'Schedule'),
            'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCompany()
	{
		return $this->hasOne(Company::class, ['id' => 'company_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getLanguage()
	{
		return $this->hasOne(Language::class, ['language_id' => 'language_id']);
	}

    /**
     * Gets the keywords as array.
     *
     * @param string $delimiter
     * @return array
     */
    public function getKeywordsList($delimiter = ',')
    {
        return $this->keywords ? explode($delimiter, $this->keywords) : [];
    }
}
