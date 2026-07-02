<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%ai_conversation}}".
 *
 * @property int $id
 * @property string|null $summary
 * @property string|null $openai_conversation_id
 * @property int $created_by
 * @property string $created_at
 * @property int $status
 * @property int $deleted
 *
 * @property AiMessage[] $messages
 * @property User $creator
 */
class AiConversation extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ai_conversation}}';
    }

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
				'attributes' => [
					self::EVENT_BEFORE_INSERT => ['created_at'],
				],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
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
            [['created_by', 'status', 'deleted'], 'integer'],
            [['summary'], 'string', 'max' => 255],
            [['openai_conversation_id'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'summary' => Yii::t('label', 'Summary'),
            'created_by' => Yii::t('label', 'Created By'),
            'created_at' => Yii::t('label', 'Created At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(AiMessage::class, ['conversation_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}
}
