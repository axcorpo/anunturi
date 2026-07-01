<?php

/* @var $this yii\web\View */

use common\models\Announcement;
use common\models\Renewal;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\select2\Select2;

$this->title = Yii::t('common', 'Renewals');
$this->params['breadcrumbs'][] = $this->title;

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Renewal::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Renewal::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreRenewal') && $showTrash,
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
				'dt-table' => '#dt-renewals',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteRenewal'),
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
				'dt-table' => '#dt-renewals',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createRenewal'),
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
	'id' => 'dt-renewals',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-renewals']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
   			data.deleted = ' . json_encode($showTrash ? Renewal::YES : null) . ';
			}'),
		],
		'order' => [
			[4, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerRenewal'),
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
				'data' => 'action',
			],
			[
				'data' => 'announcement',
				'title' => Yii::t('label', 'Announcement'),
				'filter' => ['text'],

			],
			[
				'data' => 'price',
				'title' => Yii::t('label', 'Price'),
				'filter' => ['text'],
			],
			[
				'data' => 'currency',
				'title' => Yii::t('label', 'Currency'),
				'filter' => Select2::widget([
					'id' => 'select2-' . rand(0, 9999),
					'name' => '',
					'data' => ArrayHelper::map(\common\models\Currency::findAllCurrencies(), 'iso_code', 'formattedName'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'multiple' => true,
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Filter'),
					],
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
//			[
//				'data' => 'ip_address',
//				'title' => Yii::t('label', 'IP Address'),
//				'filter' => ['text'],
//			],
			[
				'data' => 'status',
				'title' => Yii::t('common', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Renewal::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
