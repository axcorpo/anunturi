<?php

namespace common\models;

use common\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%promotional}}".
 *
 * @property int $id
 * @property int $announcement_id
 * @property string $code
 * @property string $start_at
 * @property string $end_at
 * @property string $price
 * @property string $currency
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Announcement $announcement
 * @property SubscriptionHasPromotional[] $subscriptionHasPromotionals
 * @property Subscription[] $subscriptions
 * @property User $creator
 * @property User $updater
 */
class Promotional extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%promotional}}';
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
			'DateTimeBehavior' => [
                'class' => DateTimeBehavior::class,
				'attributes' => ['start_at', 'end_at'],
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
            [['announcement_id', 'status'], 'required'],
            [['announcement_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['start_at', 'end_at', 'created_at', 'updated_at'], 'safe'],
            [['price'], 'number'],
            [['currency'], 'string', 'max' => 3],
            [['code'], 'string', 'max' => 255],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'announcement_id' => Yii::t('label', 'Announcement ID'),
            'code' => Yii::t('label', 'Code'),
            'start_at' => Yii::t('label', 'Start At'),
            'end_at' => Yii::t('label', 'End At'),
            'price' => Yii::t('label', 'Price'),
            'currency' => Yii::t('label', 'Currency'),
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
    public function getSubscriptionHasPromotionals()
    {
        return $this->hasMany(SubscriptionHasPromotional::class, ['promotional_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(Subscription::class, ['id' => 'subscription_id'])->viaTable('{{%subscription_has_promotional}}', ['promotional_id' => 'id']);
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

    /**
     * Generates an unique code.
     *
     * @param int $length
     * @return string
     * @throws \yii\base\Exception
     */
    public static function generateUniqueCode($length = 8)
    {
        $code = Yii::$app->security->generateRandomString($length);

        // Ensure that the generated string is alphanumeric
        if (!preg_match('/^[a-zA-Z0-9]*$/', $code)) {
            return static::generateUniqueCode($length);
        }

        // Ensure that the generated string is unique
        if (static::find()->where(['code' => $code])->limit(1)->exists()) {
            return static::generateUniqueCode($length);
        }

        return $code;
    }


    /**
     * @return array|\yii\db\ActiveRecord[]
     */
	public static function getAnnouncements()
    {
        $announcements = Announcement::find()
            ->alias('a')
            ->joinWith([
                'announcementTranslations at' => function (ActiveQuery $query) {
                    $query->andOnCondition(['at.language_id' => Yii::$app->language]);
                },
            ])
            ->where([
                'a.status' => Announcement::STATUS_ACTIVE,
                'a.deleted' => Announcement::NO,
            ])
            ->all();


        return $announcements;
    }


    public static function isPromoted($announcement_id)
    {
        $result = true;
        $announcements = Promotional::find()
            ->alias('p')
            ->select('p.*')
            ->joinWith([
                'announcement a'
            ])
            ->joinWith([
                'announcement.announcementTranslations at' => function (ActiveQuery $query) {
                    $query->andOnCondition(['at.language_id' => Yii::$app->language]);
                },
            ])
            ->where([
                'p.status' => self::STATUS_ACTIVE,
                'p.deleted' => self::NO,
            ])
			->andWhere(new Expression("TIMESTAMPDIFF(SECOND, [[p.start_at]], NOW()) > 0"))
			->andWhere(new Expression("TIMESTAMPDIFF(SECOND, [[p.end_at]], NOW()) <= 0"))
            ->andWhere([
                'a.status' => Announcement::STATUS_ACTIVE,
                'a.deleted' => Announcement::NO,
                'p.announcement_id' => $announcement_id,
            ])->one();

        if (empty($announcements)) {
            return false;
        }
        return true;
    }

}
