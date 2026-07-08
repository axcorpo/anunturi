<?php

namespace common\models;

use Yii;
use yii\base\BaseObject;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%marketing_group}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $sort_order
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property MarketingGroup $parent
 * @property MarketingGroup[] $marketingGroups
 * @property MarketingGroupHasRecipient[] $marketingGroupHasRecipients
 * @property MarketingRecipient[] $marketingRecipients
 * @property MarketingGroupTranslation[] $marketingGroupTranslations
 * @property MarketingGroupTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */

class MarketingGroup extends UuidActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%marketing_group}}';
    }

    /**
     * @inheritdoc
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
            [['parent_id', 'sort_order', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['status'], 'required'],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => MarketingGroup::class, 'targetAttribute' => ['parent_id' => 'id']],
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
            'sort_order' => Yii::t('label', 'Sort Order'),
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
    public function getParent()
    {
        return $this->hasOne(MarketingGroup::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroups()
    {
        return $this->hasMany(MarketingGroup::class, ['parent_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroupHasRecipients()
    {
        return $this->hasMany(MarketingGroupHasRecipient::class, ['marketing_group_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingRecipients()
    {
        return $this->hasMany(MarketingRecipient::class, ['id' => 'marketing_recipient_id'])->viaTable('{{%marketing_group_has_recipient}}', ['marketing_group_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroupTranslations()
    {
        return $this->hasMany(MarketingGroupTranslation::class, ['marketing_group_id' => 'id']);
    }

    /**
     * Gets the model translation.
     *
     * @param null|string $language
     * @return null|ArticleCategoryTranslation
     */
    public function getTranslation($language = null)
    {
        if ($language === null) {
            $language = Yii::$app->language;
        }
        return ArrayHelper::index($this->marketingGroupTranslations, 'language_id')[$language];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguages()
    {
        return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%marketing_group_translation}}', ['marketing_group_id' => 'id']);
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
     * Finds all active records.
     *
     * @return static[]|array
     */
    public static function findAllMarketingGroups()
    {
        try {
            return static::getDb()->cache(function ($db) {
                return static::find()
                    ->alias('mg')
                    ->joinWith([
                        'marketingGroupTranslations mgt' => function (ActiveQuery $query) {
                            $query->andOnCondition(['mgt.language_id' => Yii::$app->language]);
                        },
                    ])
                    ->where([
                        'mg.status' => static::STATUS_ACTIVE,
                        'mg.deleted' => static::NO,
                    ])
                    ->orderBy(['mgt.name' => SORT_ASC])
                    ->indexBy('id')
                    ->all();
            }, 0, new TagDependency(['tags' => __FUNCTION__]));
        } catch (\Throwable $e) {
            return [];
        }
    }
}
