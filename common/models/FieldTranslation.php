<?php

namespace common\models;

use Yii;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%field_translation}}".
 *
 * @property int $field_id
 * @property string $language_id
 * @property string $label
 * @property string $placeholder
 * @property string $help_text
 * @property int $deleted
 *
 * @property Field $field
 * @property Language $language
 */
class FieldTranslation extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%field_translation}}';
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
            [['field_id', 'language_id', 'label'], 'required'],
            [['field_id', 'deleted'], 'integer'],
            [['help_text'], 'string'],
            [['language_id'], 'string', 'max' => 5],
            [['label', 'placeholder'], 'string', 'max' => 255],
            [['field_id', 'language_id'], 'unique', 'targetAttribute' => ['field_id', 'language_id']],
            [['field_id'], 'exist', 'skipOnError' => true, 'targetClass' => Field::class, 'targetAttribute' => ['field_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'field_id' => Yii::t('label', 'Field ID'),
            'language_id' => Yii::t('label', 'Language ID'),
            'label' => Yii::t('label', 'Label'),
            'placeholder' => Yii::t('label', 'Placeholder'),
            'help_text' => Yii::t('label', 'Help Text'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getField()
    {
        return $this->hasOne(Field::class, ['id' => 'field_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['language_id' => 'language_id']);
    }
}
