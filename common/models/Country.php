<?php

namespace common\models;

use Yii;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%country}}".
 *
 * @property int $id
 * @property string $iso_alpha2 Two-letter country code (ISO 3166-1 alpha-2)
 * @property string $iso_alpha3 Three-letter country code (ISO 3166-1 alpha-3)
 * @property string $iso_numeric Three-digit country number (ISO 3166-1 numeric)
 * @property string $name English country name
 * @property string $full_name Full English country name
 * @property string $original_name Original language name
 * @property string $continent_code
 * @property string $isd_code International Dialing Code
 * @property int $requires_postcode Is the postcode required when you are shipping parcel(s) to an address in the country
 * @property int $status
 * @property int $deleted
 *
 * @property CountryTranslation[] $countryTranslations
 * @property CountryTranslation $translation
 * @property Language[] $languages
 */
class Country extends UuidActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%country}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'SoftDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'deleted' => static::YES,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['iso_alpha2', 'iso_alpha3', 'iso_numeric', 'name', 'full_name', 'continent_code', 'status'], 'required'],
            [['requires_postcode', 'status', 'deleted'], 'integer'],
            [['iso_alpha2', 'continent_code'], 'string', 'max' => 2],
            [['iso_alpha3', 'iso_numeric'], 'string', 'max' => 3],
            [['name', 'full_name', 'original_name'], 'string', 'max' => 255],
            [['isd_code'], 'string', 'max' => 7],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'iso_alpha2' => Yii::t('label', 'Iso Alpha2'),
            'iso_alpha3' => Yii::t('label', 'Iso Alpha3'),
            'iso_numeric' => Yii::t('label', 'Iso Numeric'),
            'name' => Yii::t('label', 'Name'),
            'full_name' => Yii::t('label', 'Full Name'),
            'original_name' => Yii::t('label', 'Original Name'),
            'continent_code' => Yii::t('label', 'Continent Code'),
            'isd_code' => Yii::t('label', 'Isd Code'),
            'requires_postcode' => Yii::t('label', 'Requires Postcode'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * Finds all active records.
     *
     * @return null|array|self[]
     */
    public static function findAllCountries()
    {
        try {
            return static::getDb()->cache(function ($db) {
                return static::find()
                    ->alias('c')
                    ->joinWith([
                        'countryTranslations ct' => function (ActiveQuery $query) {
                            return $query->andOnCondition([
                                'ct.language_id' => Yii::$app->language,
                                'ct.deleted' => static::NO
                            ]);
                        }
                    ])
                    ->where([
                        'c.status' => static::STATUS_ACTIVE,
                        'c.deleted' => static::NO,
                    ])
                    ->orderBy(new Expression('ct.name IS NULL'))
                    ->addOrderBy(['ct.name' => SORT_ASC])
                    ->addOrderBy(['c.full_name' => SORT_ASC])
                    ->all();
            }, 0, new TagDependency(['tags' => __FUNCTION__]));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountryTranslations()
    {
        return $this->hasMany(CountryTranslation::class, ['country_id' => 'id']);
    }

    /**
     * Gets the model translation.
     *
     * @param null|string $language
     * @return null|CountryTranslation
     */
    public function getTranslation($language = null)
    {
        if ($language === null) {
            $language = Yii::$app->language;
        }
        return ArrayHelper::index($this->countryTranslations, 'language_id')[$language];
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getLanguages()
    {
        return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%country_translation}}', ['country_id' => 'id']);
    }

    /**
     * Query all records by DefectCategory model id.
     *
     * @param $defect_category_id
     * @return array
     * @throws \yii\db\Exception
     */
    public static function queryCountriesByCounty($county)
    {
        if (strlen($county) > 2) {
            $counties = County::find()
                ->andWhere([
                    'status' => static::STATUS_ACTIVE,
                    'deleted' => static::NO,
                ])
                ->andWhere([
                    'LIKE', 'name', $county
                ])
                ->orderBy(['name' => SORT_ASC])
                ->all();
        }
        $query = static::find()
            ->alias('c')
            ->select([
                'c.iso_alpha2 AS id',
                'ct.name',
            ])
            ->joinWith([
                'countryTranslations ct' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        'ct.language_id' => Yii::$app->language,
                        'ct.deleted' => static::NO
                    ]);
                }
            ])
            ->where([
                'c.status' => static::STATUS_ACTIVE,
                'c.deleted' => static::NO,
            ])
            ->orderBy(new Expression('ct.name IS NULL'))
            ->addOrderBy(['ct.name' => SORT_ASC])
            ->addOrderBy(['c.full_name' => SORT_ASC]);
        if ($counties && ArrayHelper::getColumn($counties, 'country_code')) {
            $query->andWhere([
                'c.iso_alpha2' => ArrayHelper::getColumn($counties, 'country_code'),
            ]);
        }
        return $query->createCommand()->queryAll();
    }
}
