<?php

/**
 * Horizontal filter bar above the announcement listing grid.
 *
 * No <form> tag of its own — every input is associated with the search form rendered in
 * `index.php` (id="announcement-search-filter-form") via the HTML5 `form` attribute. The single
 * submit point is the search button inside the search form; the filter-bar has no Apply button.
 * The Clear link resets filters while preserving the active search term.
 *
 * Empty fields are stripped before submit by the JS handler registered in `index.php`,
 * so the URL stays clean (`?county=Cluj` instead of `?county=Cluj&locality=&min_price=...`).
 *
 * @var \yii\web\View $this
 * @var \frontend\modules\announcement\models\ListFilterForm $modelListFilterForm
 */

use common\helpers\AnnouncementListSearch;
use common\models\County;
use common\models\Currency;
use frontend\modules\announcement\models\ListFilterForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$searchParam = AnnouncementListSearch::getQueryParam();
$searchValue = trim((string) Yii::$app->request->get($searchParam, ''));

$routeKeys = ['category', 'county', 'tag', 'year'];
$actionParams = ['/announcement/default/index'];
foreach ($routeKeys as $routeKey) {
	$v = Yii::$app->request->get($routeKey);
	if ($v !== null && $v !== '') {
		$actionParams[$routeKey] = $v;
	}
}

$sortData = ListFilterForm::sortOptions();
$currencyData = ArrayHelper::map(Currency::findAllCurrencies(), 'iso_code', 'formattedName');
$countyData = ArrayHelper::map(County::findAllCounties(), 'name', 'name');

$select2Common = [
	'pluginLoading' => false,
	'pluginOptions' => [
		'allowClear' => true,
		'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
	],
];

$clearUrl = $actionParams;
if ($searchValue !== '') {
	$clearUrl[$searchParam] = $searchValue;
}

$activeFilterCount = count($modelListFilterForm->activeFilters());
$initiallyOpen = $activeFilterCount > 0;

$formId = 'announcement-search-filter-form';

/**
 * Render one filter field as a labeled form-group. The input is rendered via $renderInput()
 * (a closure) which gets the resolved id. Setting the `form` attribute on the input ties it
 * to the search form rendered above.
 */
$field = static function (string $name, string $label, callable $renderInput): string {
	$id = 'filter-' . str_replace('_', '-', $name);
	return '<div class="form-group">'
		. '<label class="control-label" for="' . $id . '">' . Html::encode($label) . '</label>'
		. $renderInput($id)
		. '</div>';
};
?>

<section class="clearfix announcement-filter-section" id="announcement-filter-bar"
	data-active-filters="<?= $activeFilterCount ?>"
	<?= $initiallyOpen ? '' : 'style="display:none"' ?>>
	<div class="container">

		<div class="row filter-row">
			<div class="col-md-3 col-sm-6 col-xs-12">
				<?= $field('sort_by', Yii::t('frontend', 'Sort by'), function ($id) use ($modelListFilterForm, $sortData, $select2Common, $formId) {
					return Select2::widget(array_merge($select2Common, [
						'name' => 'sort_by',
						'value' => $modelListFilterForm->sort_by,
						'data' => $sortData,
						'options' => [
							'id' => $id,
							'form' => $formId,
							'placeholder' => Yii::t('frontend', 'Sort by'),
						],
					]));
				}) ?>
			</div>

			<div class="col-md-3 col-sm-6 col-xs-12">
				<?= $field('county', Yii::t('label', 'County'), function ($id) use ($modelListFilterForm, $countyData, $select2Common, $formId) {
					return Select2::widget(array_merge($select2Common, [
						'name' => 'county',
						'value' => $modelListFilterForm->county,
						'data' => $countyData,
						'options' => [
							'id' => $id,
							'form' => $formId,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]));
				}) ?>
			</div>

			<div class="col-md-3 col-sm-6 col-xs-12">
				<?= $field('locality', Yii::t('label', 'Locality'), function ($id) use ($modelListFilterForm, $formId) {
					return Html::textInput('locality', $modelListFilterForm->locality, [
						'id' => $id,
						'form' => $formId,
						'class' => 'form-control',
						'placeholder' => Yii::t('label', 'Locality'),
					]);
				}) ?>
			</div>

			<div class="col-md-3 col-sm-6 col-xs-12">
				<?= $field('currency', Yii::t('label', 'Currency'), function ($id) use ($modelListFilterForm, $currencyData, $select2Common, $formId) {
					return Select2::widget(array_merge($select2Common, [
						'name' => 'currency[]',
						'value' => (array) $modelListFilterForm->currency,
						'data' => $currencyData,
						'options' => [
							'id' => $id,
							'form' => $formId,
							'multiple' => true,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]));
				}) ?>
			</div>
		</div>

		<div class="row filter-row">
			<div class="col-md-3 col-sm-6 col-xs-12">
				<?= $field('min_price', Yii::t('label', 'Price') . ' — ' . Yii::t('frontend', 'Min'), function ($id) use ($modelListFilterForm, $formId) {
					return Html::input('number', 'min_price', $modelListFilterForm->min_price, [
						'id' => $id,
						'form' => $formId,
						'class' => 'form-control',
						'min' => 0,
						'step' => 'any',
						'placeholder' => Yii::t('frontend', 'Min'),
					]);
				}) ?>
			</div>

			<div class="col-md-3 col-sm-6 col-xs-12">
				<?= $field('max_price', Yii::t('label', 'Price') . ' — ' . Yii::t('frontend', 'Max'), function ($id) use ($modelListFilterForm, $formId) {
					return Html::input('number', 'max_price', $modelListFilterForm->max_price, [
						'id' => $id,
						'form' => $formId,
						'class' => 'form-control',
						'min' => 0,
						'step' => 'any',
						'placeholder' => Yii::t('frontend', 'Max'),
					]);
				}) ?>
			</div>

			<div class="col-md-6 col-sm-12 col-xs-12 filter-actions">
				<label>&nbsp;</label>
				<?= Html::a(
					'<i class="fa fa-ban" aria-hidden="true"></i> ' . Html::encode(Yii::t('frontend', 'Clear')),
					Url::to($clearUrl),
					['class' => 'btn btn-default btn-block']
				) ?>
			</div>
		</div>
	</div>
</section>

<?php
$this->registerCss(<<<'CSS'
.announcement-filter-section {
	padding: 15px 0 5px 0;
	background-color: #f7f7f7;
	border-bottom: 1px solid #e5e5e5;
}
.announcement-filter-section .filter-row {
	margin-bottom: 5px;
}
.announcement-filter-section .filter-row .form-group {
	margin-bottom: 10px;
}
.announcement-filter-section label {
	font-weight: 600;
	font-size: 12px;
	margin-bottom: 3px;
	color: #555;
}
/* All inputs/selects/buttons aligned to the theme's `.btn` height (44px) with matching border.
   `!important` to win over kartik vendor rules (`.select2-container--krajee-bs3 .select2-selection--single { height: 34px }`). */
.announcement-filter-section .form-control,
.announcement-filter-section .select2-selection.select2-selection--single,
.announcement-filter-section .select2-selection.select2-selection--multiple {
	height: 44px !important;
	min-height: 44px !important;
	box-sizing: border-box !important;
	font-size: 14px !important;
	border: 1px solid #e5e5e5 !important;
	background-color: #fff !important;
	border-radius: 4px !important;
}
.announcement-filter-section .form-control,
.announcement-filter-section .select2-selection.select2-selection--single {
	line-height: 14px !important;
	padding: 14px 12px !important;
}
.announcement-filter-section .select2-selection.select2-selection--single .select2-selection__rendered {
	line-height: 14px !important;
	padding-left: 0 !important;
	padding-right: 20px !important;
}
.announcement-filter-section .select2-selection.select2-selection--single .select2-selection__arrow {
	height: 42px !important;
	top: 1px !important;
}
.announcement-filter-section .select2-selection.select2-selection--multiple {
	padding: 0 !important;
}
.announcement-filter-section .select2-selection.select2-selection--multiple .select2-selection__rendered {
	display: flex !important;
	flex-wrap: wrap !important;
	align-items: center !important;
	height: 42px !important;
	min-height: 42px !important;
	line-height: 1 !important;
	padding: 0 10px !important;
	margin: 0 !important;
	overflow: hidden !important;
}
.announcement-filter-section .select2-selection.select2-selection--multiple .select2-selection__choice {
	margin: 0 4px 0 0 !important;
	line-height: 1.2 !important;
}
.announcement-filter-section .select2-selection.select2-selection--multiple .select2-search--inline {
	display: inline-flex !important;
	align-items: center !important;
}
.announcement-filter-section .select2-selection.select2-selection--multiple .select2-search--inline .select2-search__field {
	margin: 0 !important;
	height: auto !important;
	line-height: 1 !important;
}
.announcement-filter-section .filter-actions .btn {
	height: 44px;
	line-height: 14px;
	padding: 13px 15px;
	margin-top: 0;
}
CSS
);
