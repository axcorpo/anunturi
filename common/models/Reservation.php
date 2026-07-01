<?php

namespace common\models;

use common\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%reservation}}".
 *
 * @property int $id
 * @property int $announcement_id
 * @property string $code
 * @property string $start_at
 * @property string $end_at
 * @property string $phone
 * @property string $details
 * @property string $price
 * @property string $currency
 * @property int $period
 * @property string $frequency
 * @property string $ip_address
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Announcement $announcement
 * @property User $creator
 * @property User $updater
 */
class Reservation extends CommonActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 2;
    const STATUS_FINISHED = 3;
    const STATUS_CANCELED = 4;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%reservation}}';
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
			[['announcement_id', 'period', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['start_at', 'end_at', 'created_at', 'updated_at'], 'safe'],
			[['details'], 'string'],
			[['price', 'frequency'], 'number'],
			[['code', 'phone', 'ip_address'], 'string', 'max' => 255],
			[['currency'], 'string', 'max' => 3],
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
			'phone' => Yii::t('label', 'Phone'),
			'details' => Yii::t('label', 'Details'),
			'price' => Yii::t('label', 'Price'),
			'currency' => Yii::t('label', 'Currency'),
			'period' => Yii::t('label', 'Period'),
			'frequency' => Yii::t('label', 'Frequency'),
			'ip_address' => Yii::t('label', 'Ip Address'),
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
     * Model status labels.
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_CANCELED => [
                'label' => Yii::t('label', 'Canceled'),
                'color' => 'danger',
            ],
            self::STATUS_ACTIVE => [
                'label' => Yii::t('label', 'Active'),
                'color' => 'success',
            ],
            self::STATUS_PENDING => [
                'label' => Yii::t('label', 'Pending'),
                'color' => 'warning',
            ],
            self::STATUS_FINISHED => [
                'label' => Yii::t('label', 'Finished'),
                'color' => 'info',
            ],
        ];
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
     * Activates the reservation.
     *
     * @return bool
     */
    public function activate()
    {
        try {
            $this->status = self::STATUS_ACTIVE;

            if (!$this->save()) {
                throw new \Exception();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cancels the reservation.
     *
     * @return bool
     */
    public function cancel()
    {
        try {
            $this->status = self::STATUS_CANCELED;

            if (!$this->save()) {
                throw new \Exception();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cancels the reservation.
     *
     * @return bool
     */
    public function deleteReservation()
    {
        try {
            $this->deleted = self::YES;

            if (!$this->save()) {
                throw new \Exception();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
