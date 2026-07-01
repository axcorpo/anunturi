<?php

namespace common\models;

use Mpdf\Tag\Sub;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%review}}".
 *
 * @property int $id
 * @property int $announcement_id
 * @property int $subscriber_id
 * @property int $company_id
 * @property int $score
 * @property int $confirmed
 * @property int $type
 * @property string $ip_address
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Announcement $announcement
 * @property Subscriber $subscriber
 * @property Company $company
 * @property ReviewTranslation[] $reviewTranslations
 * @property ReviewTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */
class Review extends CommonActiveRecord
{
    const REVIEW_PENDING = 0;
    const REVIEW_CONFIRMED = 1;
    const REVIEW_REJECTED = 2;

    const REVIEW_TYPE_ANNOUNCEMENT = 0;
    const REVIEW_TYPE_SUBSCRIBER = 1;
    const REVIEW_TYPE_COMPANY = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%review}}';
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
			[['announcement_id', 'subscriber_id', 'company_id', 'score', 'confirmed', 'type', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['confirmed', 'status'], 'required'],
			[['ip_address'], 'string', 'max' => 255],
			[['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
			[['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
            [['subscriber_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscriber::class, 'targetAttribute' => ['subscriber_id' => 'id']],
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
            'subscriber_id' => Yii::t('label', 'Subscriber ID'),
            'company_id' => Yii::t('label', 'Company ID'),
			'score' => Yii::t('label', 'Score'),
            'confirmed' => Yii::t('label', 'Confirmed'),
            'type' => Yii::t('label', 'Type'),
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
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriber()
    {
        return $this->hasOne(Subscriber::class, ['id' => 'subscriber_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCompany()
	{
		return $this->hasOne(Company::class, ['id' => 'company_id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReviewTranslations()
    {
        return $this->hasMany(ReviewTranslation::class, ['review_id' => 'id']);
    }

	/**
	 * Gets the model translation.
	 *
	 * @param string|null $language
	 * @return mixed
	 */
	public function getTranslation($language = null)
	{
		if ($language === null) {
			$language = Yii::$app->language;
		}
		return ArrayHelper::index($this->reviewTranslations, 'language_id')[$language];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
    public function getLanguages()
    {
        return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%review_translation}}', ['review_id' => 'id']);
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
    public static function getConfirmationLabels()
    {
        return [
            static::REVIEW_PENDING => [
                'label' => Yii::t('label', 'Pending'),
                'color' => 'warning',
            ],
            static::REVIEW_CONFIRMED => [
                'label' => Yii::t('label', 'Confirmed'),
                'color' => 'success',
            ],
            static::REVIEW_REJECTED => [
                'label' => Yii::t('label', 'Rejected'),
                'color' => 'danger',
            ],
        ];
    }


    /**
     * Model reviewTypes array.
     *
     * @return array
     */
    public static function getReviewTypes()
    {
        return [
            self::REVIEW_TYPE_ANNOUNCEMENT => Yii::t('common', 'Announcement'),
            self::REVIEW_TYPE_SUBSCRIBER => Yii::t('common', 'Subscriber'),
            self::REVIEW_TYPE_COMPANY => Yii::t('common', 'Company'),
        ];
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getCompanies()
    {
        $company = Company::find()
            ->alias('c')
            ->joinWith([
                'companyTranslations ct' => function (ActiveQuery $query) {
                    $query->andOnCondition(['ct.language_id' => Yii::$app->language]);
                },
            ])
            ->where([
                'c.status' => Company::STATUS_ACTIVE,
                'c.deleted' => Company::NO,
            ])
            ->all();


        return $company;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getSubscribers()
    {
        $subscriber = Subscriber::find()
            ->alias('s')
            ->joinWith([
                'user u' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'u.status' => User::STATUS_ACTIVE,
                        'u.deleted' => User::NO,
                    ]);
                },
                'user.companies c' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'c.status' => Company::STATUS_ACTIVE,
                        'c.deleted' => Company::NO,
                    ]);
                },
            ])
            ->andWhere([
                's.status' => static::STATUS_ACTIVE,
                's.deleted' => static::NO,
            ])
            ->andWhere([
                'IS', 'c.id', null,
            ])
            ->all();

        return $subscriber;
    }

    /**
     * delete the review.
     *
     * @return bool
     */
    public function deleteReview()
    {
        try {
            $this->deleted = self::YES;
            $this->status = self::STATUS_INACTIVE;

            if (!$this->save()) {
                throw new \Exception();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

	/**
	 * @param $announcement_id
	 * @param int $level
	 * @param null $status
	 * @return array
	 */
	public static function findAllReviews($announcement_id, $status = null)
	{
		$query = static::find()
			->alias('r')
			->joinWith([
				'reviewTranslations rt' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'rt.language_id' => Yii::$app->language,
						'rt.deleted' => ReviewTranslation::NO,
					]);
				},
			])
			->where([
				'r.deleted' => self::NO,
			])
			->addOrderBy(['r.created_at' => SORT_DESC]);
		if ($announcement_id) {
			$query->andWhere([
				'r.announcement_id' => $announcement_id
			]);
		}
		if ($status) {
			$query->andWhere([
				'r.status' => $status
			]);
		}
		return $query->all();
	}

    /**
     * @param $announcement_id
     * @param int $level
     * @param null $status
     * @return array
     */
    public static function findCompanyReviews($company_id, $status = null)
    {
        $query = static::find()
            ->alias('r')
            ->joinWith([
                'reviewTranslations rt' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'rt.language_id' => Yii::$app->language,
                        'rt.deleted' => ReviewTranslation::NO,
                    ]);
                },
            ])
            ->where([
                'r.deleted' => self::NO,
            ])
            ->addOrderBy(['r.created_at' => SORT_DESC]);
        if ($company_id) {
            $query->andWhere([
                'r.company_id' => $company_id
            ]);
        }
        if ($status) {
            $query->andWhere([
                'r.status' => $status
            ]);
        }
        return $query->all();
    }

    /**
     * @param $announcement_id
     * @param int $level
     * @param null $status
     * @return array
     */
    public static function findSubscriberReviews($subscriber_id, $status = null)
    {
        $query = static::find()
            ->alias('r')
            ->joinWith([
                'reviewTranslations rt' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'rt.language_id' => Yii::$app->language,
                        'rt.deleted' => ReviewTranslation::NO,
                    ]);
                },
            ])
            ->where([
                'r.deleted' => self::NO,
            ])
            ->addOrderBy(['r.created_at' => SORT_DESC]);
        if ($subscriber_id) {
            $query->andWhere([
                'r.subscriber_id' => $subscriber_id,
            ]);
        }
        if ($status) {
            $query->andWhere([
                'r.status' => $status
            ]);
        }
        return $query->all();
    }

}
