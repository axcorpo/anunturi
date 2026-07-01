<?php

/* @var $this yii\web\View */

use common\models\Auction;
use common\models\Bid;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Bids');
$this->params['breadcrumbs'][] = $this->title;
if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Bid::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Bid::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreBid') && $showTrash,
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
				'dt-table' => '#dt-bids',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteBid'),
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
				'dt-table' => '#dt-bids',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createBid'),
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
	'id' => 'dt-bids',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-bids']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? Bid::YES : null) . ';
			}'),
		],
		'order' => [
			[1, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerBid'),
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
			],
			[
				'data' => 'id',
				'title' => Yii::t('common', 'ID'),
				'filter' => ['text'],
			],
			[
				'data' => 'auction',
				'title' => Yii::t('common', 'Auction'),
				'filter' => ['select', ArrayHelper::map(Auction::find()->active()->deleted(false)->all(), 'id', 'auctionName')],
			],
			[
				'data' => 'broker',
				'title' => Yii::t('common', 'Broker'),
				'filter' => ['text'],
			],
			[
				'data' => 'price',
				'title' => Yii::t('common', 'Price'),
				'filter' => Html::tag('input', null, [
					'type' => 'text',
					'class' => 'form-control',
					'placeholder' => Yii::t('common', 'Filter'),
				]),
			],
			[
				'data' => 'currency',
				'title' => Yii::t('common', 'Currency'),
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
				'filter' => ['select', ArrayHelper::getColumn(Bid::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
