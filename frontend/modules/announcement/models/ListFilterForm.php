<?php

namespace frontend\modules\announcement\models;

use Yii;
use yii\base\Model;

/**
 * Filter form for the public announcement listing.
 *
 * Bound to the horizontal filter-bar rendered above the listing grid. Submitted via GET so the
 * filter state lives in the URL (shareable, back/forward works, pagination preserves filters).
 *
 * `formName()` is empty on purpose so the GET parameters are flat (`?sort_by=...&min_price=...`)
 * rather than nested under `ListFilterForm[...]`.
 *
 * Distinct from {@see FilterForm} — that one drives the category-scoped dynamic-field filter
 * stored in session; this one carries the always-available listing filters from the filter bar.
 */
class ListFilterForm extends Model
{
	const SORT_NEWEST = 'newest';
	const SORT_PRICE_ASC = 'price_asc';
	const SORT_PRICE_DESC = 'price_desc';
	const SORT_MOST_VIEWED = 'most_viewed';

	public $sort_by;
	public $min_price;
	public $max_price;
	public $currency;
	public $county;
	public $locality;

	public function formName()
	{
		return '';
	}

	public function rules()
	{
		return [
			[['sort_by', 'county', 'locality'], 'string'],
			['sort_by', 'in', 'range' => array_keys(self::sortOptions())],
			[['min_price', 'max_price'], 'number', 'min' => 0],
			['currency', 'each', 'rule' => ['string', 'max' => 3]],
		];
	}

	public function attributeLabels()
	{
		return [
			'sort_by' => Yii::t('frontend', 'Sort by'),
			'min_price' => Yii::t('frontend', 'Min'),
			'max_price' => Yii::t('frontend', 'Max'),
			'currency' => Yii::t('label', 'Currency'),
			'county' => Yii::t('label', 'County'),
			'locality' => Yii::t('label', 'Locality'),
		];
	}

	public static function sortOptions()
	{
		return [
			self::SORT_NEWEST => Yii::t('frontend', 'Newest'),
			self::SORT_PRICE_ASC => Yii::t('frontend', 'Price (low to high)'),
			self::SORT_PRICE_DESC => Yii::t('frontend', 'Price (high to low)'),
			self::SORT_MOST_VIEWED => Yii::t('frontend', 'Most viewed'),
		];
	}

	/**
	 * Returns the validated, non-empty filter values keyed by attribute. Empty strings / nulls /
	 * empty arrays are stripped so the caller can pass them straight to the listing query.
	 *
	 * @return array
	 */
	public function activeFilters()
	{
		$out = [];
		foreach ($this->attributes as $name => $value) {
			if ($value === null || $value === '' || $value === []) {
				continue;
			}
			if (is_array($value)) {
				$value = array_values(array_filter($value, static fn($v) => $v !== '' && $v !== null));
				if ($value === []) {
					continue;
				}
			}
			$out[$name] = $value;
		}
		return $out;
	}
}
