<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%message}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $announcement_id
 * @property int $conversation_id
 * @property int $recipient_id
 * @property string $subject
 * @property string $content
 * @property string $seen_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Announcement $announcement
 * @property Conversation $conversation
 * @property Message $parent
 * @property Message[] $messages
 */
class Message extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%message}}';
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function behaviors()
    {
        return [
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
            ],
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'value' => (new \DateTime)->format('Y-m-d H:i:s'),
            ],
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
            [['parent_id', 'announcement_id', 'conversation_id', 'recipient_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['conversation_id', 'recipient_id', 'status'], 'required'],
            [['content'], 'string'],
            [['seen_at', 'created_at', 'updated_at'], 'safe'],
            [['subject'], 'string', 'max' => 255],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['conversation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Conversation::class, 'targetAttribute' => ['conversation_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Message::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'parent_id' => Yii::t('label', 'Parent ID'),
            'announcement_id' => Yii::t('label', 'Announcement ID'),
            'conversation_id' => Yii::t('label', 'Conversation ID'),
            'recipient_id' => Yii::t('label', 'Recipient ID'),
            'subject' => Yii::t('label', 'Subject'),
            'content' => Yii::t('label', 'Content'),
            'seen_at' => Yii::t('label', 'Seen At'),
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
    public function getAnnouncement()
    {
        return $this->hasOne(Announcement::class, ['id' => 'announcement_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConversation()
    {
        return $this->hasOne(Conversation::class, ['id' => 'conversation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Message::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Message::class, ['parent_id' => 'id']);
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

    public static function findUnseenMessages()
    {
        $query = self::find()
            ->alias('m')
            ->where([
                'm.recipient_id' => Yii::$app->user->identity,
            ])
            ->andWhere([
                'IS', 'm.seen_at', null,
            ]);

        return $query->all();
    }
}
