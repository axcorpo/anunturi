<?php

namespace common\models;

use common\helpers\UrlHelper;
use tws\behaviors\DateTimeBehavior;
use tws\helpers\Url;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use common\helpers\Inflector;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii\db\Exception;

/**
 * This is the model class for table "{{%announcement}}".
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $image
 * @property string $icon
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property int $hide_phone
 * @property string $address
 * @property string $locality
 * @property string $county
 * @property string $country
 * @property string $zip_code
 * @property string $latitude
 * @property string $longitude
 * @property string $renewed_at
 * @property string $displayed_at
 * @property string $price
 * @property string $currency
 * @property string $uom
 * @property string $source
 * @property string $source_url
 * @property string $source_image
 * @property int $quantity
 * @property int $type
 * @property int $sort_order
 * @property int $views
 * @property int $visits
 * @property string $ip_address
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Company $company
 * @property AnnouncementHasAction[] $announcementHasActions
 * @property Action[] $actions
 * @property AnnouncementHasPicture[] $announcementHasPictures
 * @property Picture[] $pictures
 * @property AnnouncementTranslation[] $announcementTranslations
 * @property AnnouncementTranslation $translation
 * @property Language[] $languages
 * @property CategoryHasAnnouncement[] $categoryHasAnnouncements
 * @property Category[] $categories
 * @property Conversation[] $conversations
 * @property ExtraFeatureHasAnnouncement[] $extraFeatureHasAnnouncements
 * @property ExtraFeature[] $extraFeatures
 * @property FieldValue[] $fieldValues
 * @property Message[] $messages
 * @property Promotional[] $promotionals
 * @property Renewal[] $renewals
 * @property Reservation[] $reservations
 * @property Review[] $reviews
 * @property SubscriptionHasAnnouncement[] $subscriptionHasAnnouncements
 * @property Subscription[] $subscriptions
 * @property Unavailability[] $unavailabilities
 * @property UserHasAnnouncement[] $userHasAnnouncements
 * @property User[] $users
 * @property User $creator
 * @property User $updater
 */
class Announcement extends UuidActiveRecord
{
    public const STATUS_PENDING = 2;
    public const STATUS_REJECTED = 3;

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%announcement}}';
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
				'attributes' => ['renewed_at', 'displayed_at'],
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
			[['company_id', 'hide_phone', 'quantity', 'type', 'sort_order', 'views', 'visits', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['latitude', 'longitude', 'price'], 'number'],
			[['renewed_at', 'displayed_at', 'created_at', 'updated_at'], 'safe'],
			[['status'], 'required'],
			[['code', 'image', 'icon', 'first_name', 'middle_name', 'last_name', 'email', 'phone', 'address', 'locality', 'county', 'country', 'zip_code', 'uom', 'source', 'source_url', 'source_image', 'ip_address'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
		];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'company_id' => Yii::t('label', 'Company ID'),
			'code' => Yii::t('label', 'Code'),
			'image' => Yii::t('label', 'Image'),
			'icon' => Yii::t('label', 'Icon'),
			'first_name' => Yii::t('label', 'First Name'),
			'middle_name' => Yii::t('label', 'Middle Name'),
			'last_name' => Yii::t('label', 'Last Name'),
			'email' => Yii::t('label', 'Email'),
			'phone' => Yii::t('label', 'Phone'),
            'hide_phone' => Yii::t('label', 'Hide Phone'),
            'address' => Yii::t('label', 'Address'),
            'locality' => Yii::t('label', 'Locality'),
			'county' => Yii::t('label', 'County'),
			'country' => Yii::t('label', 'Country'),
			'zip_code' => Yii::t('label', 'Zip Code'),
			'latitude' => Yii::t('label', 'Latitude'),
			'longitude' => Yii::t('label', 'Longitude'),
			'renewed_at' => Yii::t('label', 'Renewed At'),
			'displayed_at' => Yii::t('label', 'Displayed At'),
            'price' => Yii::t('label', 'Price'),
            'currency' => Yii::t('label', 'Currency'),
            'uom' => Yii::t('label', 'UM'),
            'source' => Yii::t('label', 'Source'),
            'source_url' => Yii::t('label', 'Source Url'),
            'source_image' => Yii::t('label', 'Source Image'),
            'quantity' => Yii::t('label', 'Quantity'),
            'type' => Yii::t('label', 'Type'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'views' => Yii::t('label', 'Views'),
			'visits' => Yii::t('label', 'Visits'),
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
	public function getCompany()
	{
		return $this->hasOne(Company::class, ['id' => 'company_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAnnouncementHasActions()
	{
		return $this->hasMany(AnnouncementHasAction::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getActions()
	{
		return $this->hasMany(Action::class, ['id' => 'action_id'])->viaTable('{{%announcement_has_action}}', ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAnnouncementHasPictures()
	{
		return $this->hasMany(AnnouncementHasPicture::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getPictures()
	{
		return $this->hasMany(Picture::class, ['id' => 'picture_id'])->viaTable('{{%announcement_has_picture}}', ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAnnouncementTranslations()
	{
		return $this->hasMany(AnnouncementTranslation::class, ['announcement_id' => 'id']);
	}

	/**
	 * Gets the model translation.
	 *
	 * @param null|string $language
	 * @return null|PageTranslation
	 */
	public function getTranslation($language = null)
	{
		if ($language === null) {
			$language = Yii::$app->language;
		}
		return ArrayHelper::index($this->announcementTranslations, 'language_id')[$language];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%announcement_translation}}', ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryHasAnnouncements()
	{
		return $this->hasMany(CategoryHasAnnouncement::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::class, ['id' => 'category_id'])->viaTable('{{%category_has_announcement}}', ['announcement_id' => 'id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConversations()
    {
        return $this->hasMany(Conversation::class, ['announcement_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getFieldValues()
	{
		return $this->hasMany(FieldValue::class, ['announcement_id' => 'id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExtraFeatureHasAnnouncements()
    {
        return $this->hasMany(ExtraFeatureHasAnnouncement::class, ['announcement_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExtraFeatures()
    {
        return $this->hasMany(ExtraFeature::class, ['id' => 'extra_feature_id'])->viaTable('{{%extra_feature_has_announcement}}', ['announcement_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Message::class, ['announcement_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPromotionals()
	{
		return $this->hasMany(Promotional::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getRenewals()
	{
		return $this->hasMany(Renewal::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getReservations()
	{
		return $this->hasMany(Reservation::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getReviews()
	{
		return $this->hasMany(Review::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSubscriptionHasAnnouncements()
	{
		return $this->hasMany(SubscriptionHasAnnouncement::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getSubscriptions()
	{
		return $this->hasMany(Subscription::class, ['id' => 'subscription_id'])->viaTable('{{%subscription_has_announcement}}', ['announcement_id' => 'id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnavailabilities()
    {
        return $this->hasMany(Unavailability::class, ['announcement_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUserHasAnnouncements()
	{
		return $this->hasMany(UserHasAnnouncement::class, ['announcement_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getUsers()
	{
		return $this->hasMany(User::class, ['id' => 'user_id'])->viaTable('{{%user_has_announcement}}', ['announcement_id' => 'id']);
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
	 * Gets the imageUrl.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getImageUrl($scheme = false)
	{
		return Url::to("@uploads/announcement/{$this->id}/{$this->image}", $scheme);
	}

    /**
     * Gets the sourceImageUrl.
     *
     * @param bool $scheme
     * @return string
     */
    public function getSourceImageUrl($scheme = false)
    {
        return Url::to("@uploads/announcement/{$this->id}/{$this->source_image}", $scheme);
    }

	/**
	 * Gets the previous article.
	 *
	 * @return array|self|\yii\db\ActiveRecord|null
	 */
	public function getPrevAnnouncement()
	{
		return self::findSiblingAnnouncement($this, 'prev');
	}

	/**
	 * Gets the next article.
	 *
	 * @return array|self|\yii\db\ActiveRecord|null
	 */
	public function getNextAnnouncement()
	{
		return self::findSiblingAnnouncement($this, 'next');
	}

	/**
	 * Activates the announcement.
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
     * Cancels the announcement.
     *
     * @return bool
     */
    public function cancel()
    {
        try {
            $this->status = self::STATUS_INACTIVE;

            if (!$this->save()) {
                throw new \Exception();
            } else {
                $this->unlinkAll('subscriptions', true);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * delete the announcement.
     *
     * @return bool
     */
    public function deleteAnnouncement($announcementModel = null)
    {
        try {
			$aid = (int) $announcementModel->id;
			$meta = \common\services\OpenAiRecordVectorStoreService::detachAnnouncementIndexRow($aid);
			Announcement::deleteAll(['id' => $aid]);
			\common\services\OpenAiRecordVectorStoreService::scheduleRemotePurge($meta);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

	/**
	 * Finds the next article starting from a specific model.
	 *
	 * @param self $model
	 * @param string $direction
	 * @return array|\yii\db\ActiveRecord|self|null
	 */
	public static function findSiblingAnnouncement($model, $direction = 'next')
	{
		$query = static::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
                'subscriptions s',
			])
			->andWhere([
				'a.status' => self::STATUS_ACTIVE,
				'a.deleted' => self::NO,
                's.status' => self::STATUS_ACTIVE,
                's.deleted' => self::NO,
			])
			->orderBy([
				'created_at' => SORT_DESC,
			]);

		if ($direction === 'prev') {
			$query->andFilterWhere(['<', 'a.created_at', $model->created_at]);
		} elseif ($direction === 'next') {
			$query->andFilterWhere(['>', 'a.created_at', $model->created_at]);
		}

		return $query->limit(1)->one();
	}

	/**
	 * Finds the latest articles.
	 *
	 * @param int $limit The limit of the results.
	 * @return array|\yii\db\ActiveRecord|self[]|null
	 */
	public static function findLatestAnnouncements($limit = 10)
	{
		return static::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'at.language_id' => Yii::$app->language,
						'at.deleted' => AnnouncementTranslation::NO,
					]);
				},
				'categories.categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => CategoryTranslation::NO,
					]);
				},
                'subscriptions s',
			])
			->andWhere([
				'a.status' => self::STATUS_ACTIVE,
				'a.deleted' => self::NO,
                's.status' => self::STATUS_ACTIVE,
                's.deleted' => self::NO,
			])
			->orderBy([
				'a.renewed_at' => SORT_DESC,
				'a.created_at' => SORT_DESC,
			])
			->limit($limit)
			->all();
	}

	/**
	 * Finds all active records for sitemap.
	 *
	 * @return self[]|array
	 */
	public static function findSitemapAnnouncements()
	{
		return static::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at',
                'subscriptions s',
			])
			->where([
				'a.status' => self::STATUS_ACTIVE,
				'a.deleted' => self::NO,
                's.status' => self::STATUS_ACTIVE,
                's.deleted' => self::NO,
			])
			->indexBy('id')
			->all();
	}

	/**
	 * Provides active records for RSS Feed.
	 *
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public static function provideRssAnnouncements()
	{
		return static::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			    'subscriptions s',
            ])
            ->where([
                'a.status' => self::STATUS_ACTIVE,
                'a.deleted' => self::NO,
                's.status' => self::STATUS_ACTIVE,
                's.deleted' => self::NO,
            ])
			->orderBy([
				'a.created_at' => SORT_DESC,
			]);
	}

	/**
	 * Two-tier search condition for the public listing.
	 *
	 * The main haystack is the denormalized `announcement_translation.search_text` column
	 * (title + description + keywords + content, HTML-stripped + diacritic-folded — see
	 * {@see AnnouncementTranslation::beforeSave()}). The user query is normalized via the same
	 * pipeline ({@see \common\helpers\AnnouncementListSearch::normalize()}) so "gradina" matches
	 * "grădină" and HTML tags / entities in the source body don't pollute matches.
	 *
	 * `search_text` is matched through the FULLTEXT index (`ftx_announcement_translation_search_text`)
	 * with `MATCH ... AGAINST (... IN BOOLEAN MODE)` — every token required, as a word prefix —
	 * instead of full-scan `LIKE '%term%'`. Tokens the index cannot serve (shorter than
	 * `innodb_ft_min_token_size` or InnoDB built-in stopwords) are ANDed in as LIKE fallbacks,
	 * and a search with no indexable token keeps the original all-LIKE behaviour. The only
	 * semantic difference vs. LIKE: tokens match at word starts, no longer inside words
	 * ("beton" still matches "betoniera", but no longer "autobeton").
	 *
	 * The remaining columns (ct.name / a.locality / a.county) are matched on the raw user input —
	 * those fields are short and rarely contain HTML, but diacritics there are NOT folded.
	 *
	 * Tokens stay column-local: all tokens must hit the same column to count. Cross-column AND
	 * would be more permissive but produces noisy results where unrelated rows match because of one
	 * common word in each column.
	 *
	 * @return array Yii where condition (nested OR / AND).
	 */
	public static function listSearchSqlLikeOr(string $search): array
	{
		$normalized = \common\helpers\AnnouncementListSearch::normalize($search);
		$tokens = \common\helpers\AnnouncementListSearch::tokenize($search);

		$conds = ['OR'];

		// search_text tier — FULLTEXT boolean mode, LIKE only as fallback
		list($indexable, $unindexable) = \common\helpers\AnnouncementListSearch::partitionFulltextTokens($tokens);
		if ($indexable) {
			$searchTextCond = ['AND', new Expression(
				'MATCH ([[at]].[[search_text]]) AGAINST (:announcementFtQuery IN BOOLEAN MODE)',
				[':announcementFtQuery' => \common\helpers\AnnouncementListSearch::booleanFulltextQuery($indexable)]
			)];
			foreach ($unindexable as $token) {
				$searchTextCond[] = ['LIKE', 'at.search_text', $token];
			}
			$conds[] = count($searchTextCond) === 2 ? $searchTextCond[1] : $searchTextCond;
		} elseif ($normalized !== '') {
			// No FULLTEXT-indexable token (all too short / stopwords): original LIKE behaviour
			$conds[] = ['LIKE', 'at.search_text', $normalized];
			if (count($tokens) > 1) {
				$andSearchText = ['AND'];
				foreach ($tokens as $token) {
					$andSearchText[] = ['LIKE', 'at.search_text', $token];
				}
				$conds[] = $andSearchText;
			}
		}

		// Raw-column tier — unchanged
		$rawColumns = ['ct.name', 'a.locality', 'a.county'];
		foreach ($rawColumns as $col) {
			$conds[] = ['LIKE', $col, $search];
		}
		if (count($tokens) > 1) {
			foreach ($rawColumns as $col) {
				$and = ['AND'];
				foreach ($tokens as $token) {
					$and[] = ['LIKE', $col, $token];
				}
				$conds[] = $and;
			}
		}

		return $conds;
	}

	/**
	 * Provides active records by filter criteria.
	 *
	 * @param array $params
	 * @param int[]|null $semanticOrderedIds When non-empty with text search: same SQL LIKE as without vector, but rows whose IDs are listed here are ordered first (vector relevance order among LIKE matches). Without text search: the listing is restricted to these IDs (pure AI mode).
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public static function provideAnnouncements($params = [], ?array $semanticOrderedIds = null)
	{
		$query = static::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
				'categories c',
				'categories.categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition(['ct.language_id' => Yii::$app->language]);
				},
				'subscriptions s',
			])
			->where([
				'a.status' => self::STATUS_ACTIVE,
				'a.deleted' => self::NO,
				's.status' => self::STATUS_ACTIVE,
				's.deleted' => self::NO,
			]);

		if (!empty($params['category'])) {
			$categories = array_merge([$params['category']], ArrayHelper::getColumn(Category::getChildren($params['category']), 'id'));
			$query
				->andWhere(['c.id' => $categories]);
			if (!empty($params['filter'])) {
				$query
					->joinWith([
						'actions ac' => function (ActiveQuery $query) {
							$query->andOnCondition([
								'ac.status' => static::STATUS_ACTIVE,
								'ac.deleted' => static::NO,
							]);
						},
						'fieldValues fv' => function (ActiveQuery $query) {
							$query->andOnCondition([
								'fv.status' => static::STATUS_ACTIVE,
								'fv.deleted' => static::NO,
							]);
						}
					]);
				$fieldIds = []; $conditions = [];
				foreach ($params['filter'] as $key => $value) {
					if ($key == 'action') {
						$query
							->andWhere([
								'ac.id' => $value
							]);
					} elseif ($key == 'min_price') {
						$query
							->andWhere([
								'>=', 'a.price', (float)$value
							]);
					} elseif ($key == 'max_price') {
						$query
							->andWhere([
								'<=', 'a.price', (float)$value
							]);
					} elseif ($key == 'currency') {
						$query
							->andWhere([
								'IN', 'a.currency', $value
							]);
					} else {
						$field = Field::findOne(['id' => end(explode('_', $key))]);
						$fieldIds[] = $field->id;
						$conditions[end(explode('_', $key))][0] = 'AND';
						$conditions[end(explode('_', $key))][1] = ['=', 'fv.field_id', $field->id];
						if ($field->type == Field::TYPE_NUMBER) {
							if ((explode('_', $key))[0] == 'min') {
								$conditions[end(explode('_', $key))][] = ['>=', 'fv.value', (float)$value];
							}
							if ((explode('_', $key))[0] == 'max') {
								$conditions[end(explode('_', $key))][] = ['<=', 'fv.value', (float)$value];
							}
						} elseif ($field->type == Field::TYPE_DATE || $field->type == Field::TYPE_DATETIME) {
							if ((explode('_', $key))[0] == 'from') {
								$conditions[end(explode('_', $key))][] = new Expression("TIMESTAMPDIFF(SECOND, [[fv.value]], '{$value}') <= 0");
							}
							if ((explode('_', $key))[0] == 'to') {
								$conditions[end(explode('_', $key))][] = new Expression("TIMESTAMPDIFF(SECOND, [[fv.value]], '{$value}') >= 0");
							}
						} else {
							$conditions[end(explode('_', $key))][] = ['IN', 'fv.value', $value];
						}
					}
				}
				$conditions = array_values($conditions);
				$where = ['OR'];
				foreach ($conditions as $condition) {
					$where[] = $condition;
				}
				$subquery = FieldValue::find()
					->alias('fv')
					->select([
						'fv.announcement_id',
						'COUNT(fv.id) AS counter'
					])
					->where([
						'fv.status' => static::STATUS_ACTIVE,
						'fv.deleted' => static::NO,
					])
					->groupBy([
						'fv.announcement_id',
					]);
				if (!empty($fieldIds)) {
					$subquery->andWhere([
						'fv.field_id' => $fieldIds,
					]);
				}
				if (!empty($conditions)) {
					$subquery->andWhere($where);
				}
				$query->leftJoin(['sq' => $subquery], '[[a.id]] = [[sq.announcement_id]]');
				$query->andWhere(['>=', 'sq.counter', count($fieldIds)]);
			}
		}
		if (!empty($params['county'])) {
			$query
				->andWhere(['a.county' => $params['county']]);
		}
		if (!empty($params['tag'])) {
			$query
				->andWhere(new Expression('FIND_IN_SET(:tag, [[at.keywords]])'))
				->addParams(['tag' => $params['tag']]);
		}
		if (!empty($params['year'])) {
			$query
				->andWhere(['LIKE', 'a.created_at', $params['year']]);
		}
		if (!empty($params['location'])) {
			$query
				->andWhere([
				'OR',
				['LIKE', 'a.locality', $params['location']],
				['LIKE', 'a.county', $params['location']],
			]);
		}
		if (!empty($params['search'])) {
			$query->andWhere(self::listSearchSqlLikeOr((string) $params['search']));
		}
		// Filter-bar params (GET, always available — unlike the category-scoped session `filter`).
		if (isset($params['min_price']) && $params['min_price'] !== '' && $params['min_price'] !== null) {
			$query->andWhere(['>=', 'a.price', (float) $params['min_price']]);
		}
		if (isset($params['max_price']) && $params['max_price'] !== '' && $params['max_price'] !== null) {
			$query->andWhere(['<=', 'a.price', (float) $params['max_price']]);
		}
		if (!empty($params['currency'])) {
			$query->andWhere(['a.currency' => (array) $params['currency']]);
		}
		if (!empty($params['locality'])) {
			$query->andWhere(['LIKE', 'a.locality', (string) $params['locality']]);
		}

		if (!empty($params['promotional'])) {
			$query
			->joinWith([
				'promotionals p' => function (ActiveQuery $query) {
					$query
						->andOnCondition([
						'p.status' => self::STATUS_ACTIVE,
						'p.deleted' => self::NO,
					]);
				},
			])
			->andWhere(new Expression("TIMESTAMPDIFF(SECOND, [[p.start_at]], NOW()) >= 0"))
			->andWhere(new Expression("TIMESTAMPDIFF(SECOND, [[p.end_at]], NOW()) < 0"))
			->orderBy(new Expression('a.displayed_at ASC, rand()'));
		} else {
			$query
				->joinWith([
					'promotionals p' => function (ActiveQuery $query) {
						$query
							->andOnCondition([
							'p.status' => self::STATUS_ACTIVE,
							'p.deleted' => self::NO,
						])
						->andOnCondition(new Expression("TIMESTAMPDIFF(SECOND, [[p.start_at]], NOW()) >= 0"))
						->andOnCondition(new Expression("TIMESTAMPDIFF(SECOND, [[p.end_at]], NOW()) < 0"));
					},
				])
			->andWhere(['IS', 'p.id', null])
			->orderBy(new Expression("GREATEST(COALESCE(a.renewed_at, 0), COALESCE(a.created_at, 0)) DESC"));

			$semanticIds = array_values(array_unique(array_map('intval', array_filter($semanticOrderedIds ?? []))));
			if ($semanticIds !== []) {
				// Two modes:
				//   - hybrid (search + IDs): LIKE narrows, semantic IDs only reorder; non-AI matches still appear after AI ones
				//   - pure AI (no search):   restrict the listing to AI IDs and rank by AI; nothing else shows
				// Base listing rules (a.status / a.deleted + active subscription) always apply on top.
				if (empty($params['search'])) {
					$query->andWhere(['a.id' => $semanticIds]);
				}
				$query->orderBy(\common\services\OpenAiRecordVectorStoreService::orderSemanticFirstThenDate('a.id', $semanticIds));
			} elseif (!empty($params['sort_by'])) {
				// Semantic ordering wins when present; otherwise honor the user's choice from the filter bar.
				switch ($params['sort_by']) {
					case 'price_asc':
						$query->orderBy(['a.price' => SORT_ASC]);
						break;
					case 'price_desc':
						$query->orderBy(['a.price' => SORT_DESC]);
						break;
					case 'most_viewed':
						$query->orderBy(['a.views' => SORT_DESC, 'a.created_at' => SORT_DESC]);
						break;
					case 'newest':
					default:
						$query->orderBy(['a.created_at' => SORT_DESC]);
						break;
				}
			}
		}

		if ($params['limit']) {
			$query->limit($params['limit']);
		}

		$query->groupBy([
			'a.id',
		]);
		return $query;
	}

	/**
	 * Counts articles by year.
	 *
	 * @return array|null
	 */
	public static function countAnnouncementsByYear()
	{
		try {
			return static::find()
				->select([
					"DATE_FORMAT([[created_at]], '%Y') AS [[year]]",
					"COUNT([[id]]) AS [[total]]",
				])
				->groupBy(new Expression("DATE_FORMAT([[created_at]], '%Y')"))
				->createCommand()
				->queryAll();
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * Announcement rating active records by filter criteria.
	 *
	 * @param null|int $announcement_id
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public static function getAnnouncementRating($announcement_id)
    {
        $rating = static::find()
            ->alias('a')
            ->select([
                'AVG(r.score) AS rating',
            ])
            ->joinWith([
                'reviews r',
            ])
            ->where([
                'r.status' => Review::STATUS_ACTIVE,
                'r.deleted' => Review::NO,
                'a.status' => Announcement::STATUS_ACTIVE,
                'a.deleted' => Announcement::NO,
                'a.id' => $announcement_id,
            ])
            ->groupBy(['a.id'])
            ->average('rating');
        return $rating;
    }


//TODO: get review score for every announcement not average
    /**
     * Announcement rating active records by filter criteria.
     *
     * @param null|int $announcement_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAnnouncementSimpleRating($announcement_id)
    {
        $rating = static::find()
            ->alias('a')
            ->select([
                'r.score',
            ])
            ->joinWith([
                'reviews r',
            ])
            ->where([
                'r.status' => Review::STATUS_ACTIVE,
                'r.deleted' => Review::NO,
                'a.status' => Announcement::STATUS_ACTIVE,
                'a.deleted' => Announcement::NO,
                'a.id' => $announcement_id,
            ])
        ->all();

        return $rating;
    }

	/**
	 * Model status labels.
	 *
	 * @return array
	 */
	public static function getStatusLabels()
	{
		return [
			self::STATUS_INACTIVE => [
				'label' => Yii::t('label', 'Inactive'),
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
			self::STATUS_REJECTED => [
				'label' => Yii::t('label', 'Rejected'),
				'color' => 'danger',
			],
		];
	}

	/**
	 * Gets the picture files as array for the FileInput widget.
	 *
	 * @return array
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getPictureFiles()
	{
		$files = [];

		foreach ($this->pictures as $picture) {
			$filePath = "announcement/{$this->id}/{$picture->image}";

			if (!is_file(Yii::getAlias('@uploads') . '/' . $filePath)) {
				continue;
			}

			$files[] = [
				'caption' => $picture->image,
				'size' => filesize(Yii::getAlias("@uploads/{$filePath}")),
				'key' => $picture->id,
				'downloadUrl' => UrlHelper::getUploadsUrl() . "/{$filePath}",
			];
		}

		return $files;
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

	public static function findByCompany($id)
	{
		$query = static::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'at.language_id' => Yii::$app->language,
						'at.deleted' => static::NO,
					]);
				},
                'subscriptions s',
			])
			->andWhere([
				'a.status' => static::STATUS_ACTIVE,
				'a.deleted' => static::NO,
				'a.company_id' => $id,
                's.status' => self::STATUS_ACTIVE,
                's.deleted' => self::NO,
			])
			->orderBy(new Expression("GREATEST(COALESCE(a.renewed_at, 0), COALESCE(a.created_at, 0)) DESC"));
		return $query;
	}

    public static function findBySubscriber($id)
    {
        $query = static::find()
            ->alias('a')
            ->joinWith([
                'announcementTranslations at' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'at.language_id' => Yii::$app->language,
                        'at.deleted' => static::NO,
                    ]);
                },
            ])
            ->andWhere([
                'a.status' => static::STATUS_ACTIVE,
                'a.deleted' => static::NO,
                'a.created_by' => $id,
            ])
			->orderBy(new Expression("GREATEST(COALESCE(a.renewed_at, 0), COALESCE(a.created_at, 0)) DESC"));
        return $query;
    }
}
