<?php

/* @var $this yii\web\View */

use common\models\Category;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\select2\Select2;

$this->title = Yii::t('common', 'Categories');
$this->params['breadcrumbs'][] = $this->title;
if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Category::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Category::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreCategory') && $showTrash,
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
				'dt-table' => '#dt-categories',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteCategory'),
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
				'dt-table' => '#dt-categories',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createCategory'),
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
	'id' => 'dt-categories',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-categories']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
   			data.deleted = ' . json_encode($showTrash ? Category::YES : null) . ';
			}'),
		],
		'order' => [
			[4, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerCategory'),
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
				'title' => Yii::t('common', 'Action'),
				'buttons' => [
					'view' => Yii::$app->user->can('viewCategory'),
					'update' => Yii::$app->user->can('updateCategory'),
					'delete' => Yii::$app->user->can('deleteCategory'),
				],
			],
			[
				'class' => 'common\widgets\datatable\ImageColumn',
				'config' => [
					'altAttribute' => 'name',
				],
				'data' => 'image',
				'title' => Yii::t('common', 'Image'),
			],
			[
				'data' => 'name',
				'title' => Yii::t('common', 'Name'),
				'filter' => Html::tag('input', null, [
					'type' => 'text',
					'class' => 'form-control',
					'placeholder' => Yii::t('common', 'Filter'),
				]),
			],
			[
				'data' => 'sort_order',
				'title' => Yii::t('common', 'Sort Order'),
				'className' => 'col-autowidth',
				'filter' => Html::tag('input', null, [
					'type' => 'text',
					'class' => 'form-control',
					'placeholder' => Yii::t('common', 'Filter'),
				]),
			],
			[
				'data' => 'created_by',
				'title' => Yii::t('label', 'Created By'),
				'filter' => ['text'],
			],
			[
				'data' => 'created_at',
				'title' => Yii::t('label', 'Created At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'updated_at',
				'title' => Yii::t('label', 'Updated At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'status',
				'title' => Yii::t('common', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Category::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
