<?php

namespace common\models;

use tws\helpers\Url;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $image
 * @property string $icon
 * @property int $type
 * @property int $sort_order
 * @property int $leaf
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Bid[] $bids
 * @property Category $parent
 * @property Category[] $categories
 * @property CategoryField[] $categoryFields
 * @property CategoryHasAction[] $categoryHasActions
 * @property Action[] $actions
 * @property Field[] $fields
 * @property CategoryHasOption[] $categoryHasOptions
 * @property Option[] $options
 * @property CategoryHasAnnouncement[] $categoryHasAnnouncements
 * @property Announcement[] $announcements
 * @property CategoryTranslation[] $categoryTranslations
 * @property CategoryTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */
class Category extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%category}}';
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
			'PositionBehavior' => [
				'class' => PositionBehavior::class,
				'positionAttribute' => 'sort_order',
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
			[['parent_id', 'type', 'sort_order', 'leaf', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['type', 'status'], 'required'],
			[['created_at', 'updated_at'], 'safe'],
			[['image', 'icon'], 'string', 'max' => 255],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['parent_id' => 'id']],
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
			'image' => Yii::t('label', 'Image'),
			'icon' => Yii::t('label', 'Icon'),
			'type' => Yii::t('label', 'Type'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'leaf' => Yii::t('label', 'Leaf'),
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
	public function getBids()
	{
		return $this->hasMany(Bid::class, ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Category::class, ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::class, ['parent_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryFields()
	{
		return $this->hasMany(CategoryField::class, ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryHasActions()
	{
		return $this->hasMany(CategoryHasAction::class, ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getActions()
	{
		return $this->hasMany(Action::class, ['id' => 'action_id'])->viaTable('{{%category_has_action}}', ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryHasOptions()
	{
		return $this->hasMany(CategoryHasOption::class, ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getOptions()
	{
		return $this->hasMany(Option::class, ['id' => 'option_id'])->viaTable('{{%category_has_option}}', ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryHasAnnouncements()
	{
		return $this->hasMany(CategoryHasAnnouncement::class, ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getAnnouncements()
	{
		return $this->hasMany(Announcement::class, ['id' => 'announcement_id'])->viaTable('{{%category_has_announcement}}', ['category_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryTranslations()
	{
		return $this->hasMany(CategoryTranslation::class, ['category_id' => 'id']);
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
		return ArrayHelper::index($this->categoryTranslations, 'language_id')[$language];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%category_translation}}', ['category_id' => 'id']);
	}

	/**
	 * Gets the imageUrl.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getImageUrl($scheme = false)
	{
		return Url::to("@uploads/category/{$this->id}/{$this->image}", $scheme);
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
	 * @param string $glue
	 * @param int $type
	 * @return string
	 * @throws \Throwable
	 */
	public function getTreeName($glue = ' &rsaquo; ')
	{
		return implode($glue, ArrayHelper::getColumn(self::getTree($this->id), 'translation.name'));
	}

	/**
	 * Finds main active records.
	 *
	 * @return self[]|array
	 */
	public static function findMainCategories()
	{
		try {
			return static::getDb()->cache(function ($db) {
				return static::find()
					->alias('c')
					->joinWith([
						'categoryTranslations ct' => function (ActiveQuery $query) {
							$query->andOnCondition([
								'ct.language_id' => Yii::$app->language,
								'ct.deleted' => self::NO,
							]);
						},
					])
					->where([
						'c.status' => self::STATUS_ACTIVE,
						'c.deleted' => self::NO,
						'c.parent_id' => NULL,
					])
					->orderBy(new Expression('[[c.sort_order]] IS NULL'))
					->addOrderBy(['c.sort_order' => SORT_ASC])
					->indexBy('id')
					->all();
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Finds all active records.
	 *
	 * @return self[]|array
	 */
	public static function findAllCategories()
	{
		try {
			return static::getDb()->cache(function ($db) {
				return static::find()
					->alias('c')
					->joinWith([
						'categoryTranslations ct' => function (ActiveQuery $query) {
							$query->andOnCondition([
								'ct.language_id' => Yii::$app->language,
								'ct.deleted' => self::NO,
							]);
						},
					])
					->where([
						'c.status' => self::STATUS_ACTIVE,
						'c.deleted' => self::NO,
					])
					->orderBy(new Expression('[[c.sort_order]] IS NULL'))
					->addOrderBy(['c.sort_order' => SORT_ASC])
					->indexBy('id')
					->all();
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Finds all active records.
	 *
	 * @param int $category_id
	 * @param int $type
	 * @return mixed
	 * @throws \Throwable
	 */
	public static function getTree($category_id, $cacheDuration = 30 * 24 * 3600)
	{
		$tree = [];
		if (!$category_id) {
			return $tree;
		}
		$query = static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => self::NO,
					]);
				},
			])
			->where([
				'c.deleted' => self::NO,
				'c.id' => $category_id,
			])
			->orderBy([
				new Expression('[[c.sort_order]] IS NULL'),
				'c.sort_order' => SORT_ASC
			]);
		$model = static::getDb()->cache(function ($db) use ($query) {
			return $query->one();
		}, $cacheDuration, new TagDependency(['tags' => [__FUNCTION__, 'category_' . $category_id]]));
		if ($model) {
			$tree[$model->id] = $model;
			if ($model->parent_id) {
				$parentTree = self::getTree($model->parent_id);
				$tree = array_merge($parentTree, $tree);
			}
		}
		return $tree;
	}

	/**
	 * Finds all active records.
	 *
	 * @param int $category_id
	 * @param int $type
	 * @return mixed
	 * @throws \Throwable
	 */
	public static function getChildTree($parent_id, $level = 0, $cacheDuration = 30 * 24 * 3600)
	{
		$cacheKey = 'child_tree_' . $parent_id . '_level_' . $level;
		$cachedTree = Yii::$app->cache->get($cacheKey);
		if ($cachedTree !== false) {
			return $cachedTree;
		}
		$tree = [];
		$categories = static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => self::NO,
					]);
				},
			])
			->where([
				'c.deleted' => self::NO,
				'c.parent_id' => $parent_id,
			])
			->orderBy(new Expression('[[c.sort_order]] IS NULL'))
			->addOrderBy(['c.sort_order' => SORT_ASC])
			->all();
		foreach ($categories as $category) {
			if (!isset($tree[$parent_id])) {
				$tree[$parent_id] = [];
			}
			$tree[$parent_id][] = $category->id;
			$tree = array_merge($tree, self::getChildTree($category->id, $level + 1));
		}
		Yii::$app->cache->set($cacheKey, $tree, $cacheDuration, new TagDependency(['tags' => 'category_tree']));
		return $tree;
	}

	/**
	 * Finds all active records.
	 *
	 * @param null $category_id
	 * @param int $type
	 * @return mixed
	 * @throws \Throwable
	 */
	public static function findAllAvailableCategories($category_id)
	{
		$parentIds = ArrayHelper::getColumn(self::getTree($category_id), 'id');
		$parentIds = array_diff($parentIds, [$category_id]);
		return static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => self::NO,
					]);
				},
			])
			->where([
				'c.status' => self::STATUS_ACTIVE,
				'c.deleted' => self::NO,
			])
			->andFilterWhere(['<>', 'c.id', $category_id])
			->andFilterWhere(['IN', 'c.id', $parentIds])
			->orderBy(new Expression('c.sort_order IS NULL'))
			->addOrderBy(['c.sort_order' => SORT_ASC])
			->all();
	}

	/**
	 * Finds model by slug.
	 *
	 * @param string $slug
	 * @return array|\yii\db\ActiveRecord|self|null
	 */
	public static function findCategoryBySlug($slug)
	{
		return static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition(['ct.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'c.status' => self::STATUS_ACTIVE,
				'c.deleted' => self::NO,
				'ct.slug' => $slug,
			])
			->limit(1)
			->one();
	}

	/**
	 * Finds model by slug.
	 *
	 * @param string $criteria
	 * @param bool $asArray
	 * @return array|static[]]
	 */
	public static function findByCriteria($criteria, $asArray = false)
	{
		return static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) use ($criteria) {
					$query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => self::NO,
					]);
					$query->andWhere(['LIKE', 'ct.name', $criteria]);
				},
			])
			->andWhere([
				'c.status' => static::STATUS_ACTIVE,
				'c.deleted' => static::NO,
			])
			->orderBy(new Expression('[[c.sort_order]] IS NULL'))
			->addOrderBy(['c.sort_order' => SORT_ASC])
			->asArray($asArray)
			->indexBy('id')
			->all();
	}

	/**
	 * Finds all active records for sitemap.
	 *
	 * @return self[]|array
	 */
	public static function findSitemapCategories()
	{
		return static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct',
			])
			->where([
				'c.status' => self::STATUS_ACTIVE,
				'c.deleted' => self::NO,
			])
			->orderBy(new Expression('[[c.sort_order]] IS NULL'))
			->addOrderBy(['c.sort_order' => SORT_ASC])
			->indexBy('id')
			->all();
	}

	/**
	 * @param $parent_id
	 * @param int $level
	 * @param null $status
	 * @return array
	 */
	public static function getChildren($parent_id, $level = 0, $status = null, $cacheDuration = 30 * 24 * 3600)
	{
		$cacheKey = [__METHOD__, $parent_id, $level, $status, Yii::$app->language];
		$children = Yii::$app->cache->getOrSet($cacheKey, function () use ($cacheDuration, $parent_id, $level, $status) {
			$children = [];
			/** @var static[] $categories */
			$query = static::find()
				->alias('c')
				->joinWith([
					'categoryTranslations ct' => function (ActiveQuery $query) {
						$query->andOnCondition([
							'ct.language_id' => Yii::$app->language,
							'ct.deleted' => self::NO,
						]);
					},
				])
				->where([
					'c.parent_id' => $parent_id,
					'c.deleted' => self::NO,
				])
				->orderBy(new Expression('[[c.sort_order]] IS NULL'))
				->addOrderBy(['c.sort_order' => SORT_ASC]);
			if ($status) {
				$query->andWhere(['c.status' => $status]);
			}
			$categories = static::getDb()->cache(function ($db) use ($query) {
				return $query->all();
			}, $cacheDuration, new TagDependency(['tags' => [static::class . '_children']]));
			foreach ($categories as $category) {
				$children[] = $category;
				$children = array_merge($children, static::getChildren($category->id, $level + 1, $status));
			}
			return $children;
		}, $cacheDuration, new TagDependency(['tags' => [static::class . '_children']]));
		return $children;
	}

	/**
	 * @param $parent_id
	 * @param int $level
	 * @param null $status
	 * @return array
	 */
	public static function getCategoriesByParent($parent_id, $status = null, $cacheDuration = 30 * 24 * 3600)
	{
		$cacheKey = [__METHOD__, $parent_id, $status, Yii::$app->language];
		return Yii::$app->cache->getOrSet($cacheKey, function () use ($parent_id, $status) {
			$query = static::find()
				->alias('c')
				->joinWith([
					'categoryTranslations ct' => function (ActiveQuery $query) {
						$query->andOnCondition([
							'ct.language_id' => Yii::$app->language,
							'ct.deleted' => self::NO,
						]);
					},
				])
				->where([
					'c.parent_id' => $parent_id,
					'c.deleted' => self::NO,
				])
				->orderBy(new Expression('[[c.sort_order]] IS NULL'))
				->addOrderBy(['c.sort_order' => SORT_ASC]);
			if ($status) {
				$query->andWhere(['c.status' => $status]);
			}
			return $query->all();
		}, $cacheDuration, new TagDependency(['tags' => [static::class . '_categories']]));
	}


	/**
	 * @param $category_id
	 * @param null $status
	 * @return Category[]
	 */
	public static function getParents($category_id, $status = null, $cacheDuration = 30 * 24 * 3600)
	{
		$parents = [];
		$cacheKey = [__METHOD__, $category_id, $status, Yii::$app->language];
		return Yii::$app->cache->getOrSet($cacheKey, function () use ($category_id, $status, &$parents) {
			return static::findParents($category_id, $status, $parents);
		}, $cacheDuration, new TagDependency(['tags' => [static::class . '_parents']]));
	}

	protected static function findParents($category_id, $status, &$parents, $cacheDuration = 30 * 24 * 3600)
	{
		/** @var static $parent */
		$query = static::find()
			->alias('c')
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => self::NO,
					]);
				},
			])
			->where([
				'c.id' => $category_id,
				'c.deleted' => self::NO,
			])
			->orderBy(new Expression('[[c.sort_order]] IS NULL'))
			->addOrderBy(['c.sort_order' => SORT_ASC]);

		if ($status) {
			$query->andWhere(['c.status' => $status]);
		}
		$parent = static::getDb()->cache(function ($db) use ($query) {
			return $query->one();
		}, $cacheDuration, new TagDependency(['tags' => [static::class . '_parents']]));
		if ($parent && $parent->parent_id) {
			$parents[] = $parent;
			static::findParents($parent->parent_id, $status, $parents);
		}
		return $parents;
	}

	/**
	 * Treeview
	 */
	public static function treeview($parent_id, $current = null, $status = null, $params = [])
	{
		$categories = static::getCategoriesByParent($parent_id, $status);
		$output = '';
		if ($categories) {
			foreach ($categories as $category) {
				$url = ['index', 'category' => $category->translation->slug];
				if ($params) {
					foreach ($params as $key => $value) {
						if ($key && $value) {
							$url[$key] = $value;
						}
					}
				}
				$list_class = '';
				$children_output = self::treeview($category->id, $current, $status, $params);
				if ($children_output) {
					$list_class .= 'has-subtree ';
					if ($current && (in_array($category->id, ArrayHelper::getColumn(static::getTree($current), 'id')) || $current == $category->id)) {
						$list_class .= 'subtree-open ';
					}
					$output .= '<li class="' . $list_class . '">';
					$output .= '<div class="subtree-header">';
					$output .= Html::a(ucfirst($category->translation->name), $url);
					$output .= '<button type="button" class="btn btn-subtree-toggle">
                                <span class="fa fa-plus"></span>
                            </button>';
					$output .= '</div>';
					$output .= '<ul class="subtree">' . $children_output . '</ul>';
				} else {
					if ($current == $category->id) {
						$list_class .= 'active ';
					}
					$output .= '<li class="' . $list_class . '">';
					$output .= Html::a(ucfirst($category->translation->name), $url);
				}
				$output .= '</li>';
			}
		}
		return $output;
	}

	public static function invalidateCategoryCache($category_id)
	{
		$tags = [
			'getTree',
			'getChildTree',
			'treeview',               
			'getParents',             
			'getCategoriesByParent', 
			'getChildren',
			'category_' . $category_id
		];
		TagDependency::invalidate(Yii::$app->cache, $tags);
	}
}
