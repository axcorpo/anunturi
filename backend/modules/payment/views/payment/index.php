<?php

/* @var $this yii\web\View */

use common\models\Payment;
use common\widgets\datatable\DataTable;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Payments');
$this->params['breadcrumbs'] = [
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Payment::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Payment::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restorePayment') && $showTrash,
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
				'dt-table' => '#dt-payments',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deletePayment'),
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
				'dt-table' => '#dt-payments',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createPayment'),
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

<div class="row mb-10">
	<div class="col-sm-4 col-lg-3">
		<div class="form-group">
			<label><?= Yii::t('common', 'Date Start') ?></label>
			<?= DateTimePicker::widget([
				'id' => 'dp-date_start',
				'name' => 'date_start',
				'value' => $dtExternalFilters['date_start'],
				'options' => [
					'data' => [
						'dt-external-filter' => '#dt-payments',
					],
				],
				'clientOptions' => [
					'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
					'ignoreReadonly' => true,
					'showTodayButton' => true,
					'showClear' => true,
					'showClose' => true,
					'allowInputToggle' => true,
					'useCurrent' => false,
				],
			]) ?>
		</div>
	</div>
	<div class="col-sm-4 col-lg-3">
		<div class="form-group">
			<label><?= Yii::t('common', 'Date End') ?></label>
			<?= DateTimePicker::widget([
				'id' => 'dp-date_end',
				'name' => 'date_end',
				'value' => $dtExternalFilters['date_end'],
				'options' => [
					'data' => [
						'dt-external-filter' => '#dt-payments',
					],
				],
				'linkedTo' => '#dp-date_start',
				'clientOptions' => [
					'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
					'minDate' => $dtExternalFilters['date_start'] ? (new DateTime($dtExternalFilters['date_start']))->format(DATE_ATOM) : false,
					'ignoreReadonly' => true,
					'showTodayButton' => true,
					'showClear' => true,
					'showClose' => true,
					'allowInputToggle' => true,
					'useCurrent' => false,
				],
			]) ?>
		</div>
	</div>
</div>

<?= DataTable::widget([
	'id' => 'dt-payments',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-payments']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? Payment::YES : null) . ';
			}'),
		],
		'order' => [
			[5, 'asc'],
			[4, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerPage'),
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
				'data' => 'amount',
				'title' => Yii::t('label', 'Amount'),
				'filter' => ['text'],
			],
			[
				'data' => 'split_percentage',
				'title' => Yii::t('label', 'Split Percentage'),
				'filter' => ['text'],
			],
			[
				'data' => 'prize',
				'title' => Yii::t('label', 'Prize'),
				'filter' => ['text'],
			],
			[
				'data' => 'bank_account_holder',
				'title' => Yii::t('label', 'Bank Account Holder'),
				'filter' => ['text'],
			],
			[
				'data' => 'bank_name',
				'title' => Yii::t('label', 'Bank Name'),
				'filter' => ['text'],
			],
			[
				'data' => 'bank_iban',
				'title' => Yii::t('label', 'Bank IBAN'),
				'filter' => ['text'],
			],
			[
				'data' => 'bank_bic',
				'title' => Yii::t('label', 'Bank SWIFT/BIC'),
				'filter' => ['text'],
			],
			[
				'data' => 'created_at',
				'title' => Yii::t('label', 'Created At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'paid_at',
				'title' => Yii::t('label', 'Paid At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'status',
				'title' => Yii::t('common', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Payment::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
