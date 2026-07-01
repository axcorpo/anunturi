<?php

/* @var $this yii\web\View */

use common\models\Auction;
use common\models\Category;
use common\models\County;
use common\models\Currency;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\select2\Select2;

$this->title = Yii::t('common', 'Auctions');
$this->params['breadcrumbs'][] = $this->title;
if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Auction::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Auction::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreAuction') && $showTrash,
		'tag' => 'button',
		'icon' => 'fa fa-undo',
		'options' => [
			'class' => 'btn btn-sm btn-success hidden',
			'title' => Yii::t('common', 'Restore'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => 'restore',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['restore']),
				'dt-table' => '#dt-auctions',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteAuction'),
		'tag' => 'button',
		'icon' => 'fa fa-trash',
		'options' => [
			'class' => 'btn btn-sm btn-danger hidden',
			'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['delete']),
				'dt-table' => '#dt-auctions',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createAuction'),
		'tag' => 'a',
		'url' => ['create'],
		'icon' => 'fa fa-plus',
		'options' => [
			'class' => 'btn btn-sm btn-success',
			'title' => Yii::t('common', 'Create'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
];
?>

<?= DataTable::widget([
	'id' => 'dt-auctions',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-auctions']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? Auction::YES : null) . ';
			}'),
		],
		'order' => [
			[2, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerAuction'),
		'lengthMenu' => [
			[25, 50, 100, -1],
			[25, 50, 100, Yii::t('common', 'All')],
		],
		'autoWidth' => false,
		'responsive' => true,
		'columns' => [
			[
				'class' => 'common\widgets\datatable\CheckboxColumn',
			],
			[
				'class' => 'common\widgets\datatable\ActionColumn',
				'data' => 'action',
				'title' => Yii::t('common', 'Action'),
				'className' => 'action-column col-autowidth',
			],
			[
				'data' => 'id',
				'title' => Yii::t('label', 'ID'),
				'filter' => ['text'],
			],
			[
				'data' => 'position',
				'title' => Yii::t('common', 'Position'),
				'className' => 'col-autowidth',
				'filter' => Select2::widget([
					'id' => 'select2-' . rand(0, 9999),
					'name' => '',
					'data' => Auction::getPositionLabels(),
					'pluginLoading' => false,
					'pluginOptions' => [
						'multiple' => false,
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Filter'),
					],
				]),
			],
			[
				'data' => 'parent',
				'title' => Yii::t('common', 'Parent'),
				'className' => 'col-autowidth',
				'filter' => Select2::widget([
					'id' => 'select2-' . rand(0, 9999),
					'name' => '',
					'data' => ArrayHelper::map(Auction::find()->active(true)->deleted(false)->andWhere(['IS', 'parent_id', null])->all(), 'id', function (Auction $model) {
						return implode(' - ', [$model->id, Auction::getPositionLabels()[$model->position]]);
					}),
					'pluginLoading' => false,
					'pluginOptions' => [
						'multiple' => false,
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Filter'),
					],
				]),
			],
			[
				'data' => 'county',
				'title' => Yii::t('label', 'County'),
				'filter' => Select2::widget([
					'id' => 'select2-' . rand(0, 9999),
					'name' => '',
					'data' => ArrayHelper::map(County::find()->active(true)->deleted(false)->all(), 'id', function (County $model) {
						return $model->name;
					}),
					'pluginLoading' => false,
					'pluginOptions' => [
						'multiple' => true,
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Filter'),
					],
				]),
			],
			[
				'data' => 'category',
				'title' => Yii::t('label', 'Category'),
				'filter' => Select2::widget([
					'id' => 'select2-' . rand(0, 9999),
					'name' => '',
					'data' => ArrayHelper::map(Category::find()->active(true)->deleted(false)->all(), 'id', 'treeName'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'multiple' => true,
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Filter'),
					],
				]),
			],
			[
				'data' => 'start_at',
				'title' => Yii::t('label', 'Start At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'end_at',
				'title' => Yii::t('label', 'End At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'valid_from',
				'title' => Yii::t('label', 'Valid From'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'valid_to',
				'title' => Yii::t('label', 'Valid To'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'start_price',
				'title' => Yii::t('label', 'Start Price'),
				'filter' => ['text'],
			],
			[
				'data' => 'step',
				'title' => Yii::t('label', 'Step'),
				'filter' => ['text'],
			],
			[
				'data' => 'currency',
				'title' => Yii::t('label', 'Currency'),
				'filter' => Select2::widget([
					'id' => 'select2-' . rand(0, 9999),
					'name' => '',
					'data' => ArrayHelper::map(Currency::find()->active(true)->deleted(false)->all(), 'iso_code', 'formattedName'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'multiple' => true,
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Filter'),
					],
				]),
			],
//			[
//				'data' => 'created_by',
//				'title' => Yii::t('label', 'Created By'),
//				'filter' => ['text'],
//			],
//			[
//				'data' => 'created_at',
//				'title' => Yii::t('label', 'Created At'),
//				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
//			],
//			[
//				'data' => 'updated_at',
//				'title' => Yii::t('label', 'Updated At'),
//				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
//			],
			[
				'data' => 'status',
				'title' => Yii::t('common', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Auction::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
