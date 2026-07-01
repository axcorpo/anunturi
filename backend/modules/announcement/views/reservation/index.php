<?php

/* @var $this yii\web\View */

use common\models\Announcement;
use common\models\Reservation;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\select2\Select2;

$this->title = Yii::t('common', 'Reservations');
$this->params['breadcrumbs'][] = $this->title;

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Reservation::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Reservation::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreReservation') && $showTrash,
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
				'dt-table' => '#dt-reservations',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteReservation'),
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
				'dt-table' => '#dt-reservations',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createReservation'),
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
	'id' => 'dt-reservations',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-reservations']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
   			data.deleted = ' . json_encode($showTrash ? Reservation::YES : null) . ';
			}'),
		],
		'order' => [
			[4, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerReservation'),
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
				'data' => 'phone',
				'title' => Yii::t('label', 'Phone'),
				'filter' => ['text'],
			],
			[
				'data' => 'details',
				'title' => Yii::t('label', 'Details'),
				'filter' => ['text'],
			],
			[
				'data' => 'period',
				'title' => Yii::t('label', 'Period'),
				'filter' => ['text'],
			],
			[
				'data' => 'frequency',
				'title' => Yii::t('label', 'Frequency'),
				'filter' => ['text'],
			],
//			[
//				'data' => 'ip_address',
//				'title' => Yii::t('label', 'IP Address'),
//				'filter' => ['text'],
//			],
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
				'filter' => ['select', ArrayHelper::getColumn(Reservation::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
