<?php

/* @var $this yii\web\View */
/* @var $model common\models\Payment */

use common\models\Payment;
use common\models\EventLog;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Payment')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Payments'),
		'url' => ['index'],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewPayment'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'Payments'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('updatePayment'),
		'tag' => 'a',
		'url' => ['update', 'id' => $model->id],
		'icon' => 'fa fa-edit',
		'options' => [
			'class' => 'btn btn-sm btn-primary',
			'title' => Yii::t('common', 'Update'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deletePayment'),
		'tag' => 'a',
		'url' => ['delete', 'id' => $model->id],
		'icon' => 'fa fa-trash',
		'options' => [
			'class' => 'btn btn-sm btn-danger',
			'title' => Yii::t('common', 'Delete'),
			'data' => [
				'toggle' => 'tooltip',
				'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'method' => 'POST',
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

<?= DetailView::widget([
	'model' => $model,
	'options' => [
		'class' => 'table table-striped table-bordered detail-view detail-view-fixed',
	],
	'attributes' => [
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Subscription ID'),
			'value' => function (Payment $model) {
				return $model->subscription_id ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Payment ID'),
			'value' => function (Payment $model) {
				return $model->payment_id ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Amount'),
			'value' => function (Payment $model) {
				return $model->amount ? Yii::$app->formatter->asCurrency($model->amount, $model->currency) : '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Split Percentage'),
			'value' => function (Payment $model) {
				return $model->split_percentage ? Yii::$app->formatter->asPercent($model->split_percentage / 100) : '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Prize'),
			'value' => function (Payment $model) {
				return Yii::$app->formatter->asCurrency(round($model->amount * $model->split_percentage / 100, 2), $model->currency);
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Paid At'),
			'value' => function (Payment $model) {
				return $model->paid_at ? Yii::$app->formatter->asDatetime($model->paid_at): '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Bank Account Holder'),
			'value' => function (Payment $model) {
				return $model->bank_account_holder ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Bank Name'),
			'value' => function (Payment $model) {
				return $model->bank_name ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Bank Iban'),
			'value' => function (Payment $model) {
				return $model->bank_iban ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Bank SWIFT/BIC'),
			'value' => function (Payment $model) {
				return $model->bank_bic ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Created By'),
			'value' => function (Payment $model) {
				return $model->creator ? Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) : '&mdash;';
			},
		],
		'created_at:datetime',
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Updated By'),
			'value' => function (Payment $model) {
				return $model->updater ? Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) : '&mdash;';
			},
		],
		'updated_at:datetime',
		[
			'format' => 'html',
			'label' => Yii::t('label', 'Status'),
			'value' => function ($model) {
				$status = $model::getStatusLabels()[$model->status];

				return Html::tag('span', $status['label'], ['class' => 'label label-' . $status['color']]);
			},
		],
	],
]) ?>

<?php if ((!isset($showEventLogs) || $showEventLogs === true) && Yii::$app->user->can('viewEventLog')) : ?>
	<div class="portlet box blue-hoki mt-15">
		<div class="portlet-title">
			<div class="caption"><?= Yii::t('common', 'Event Logs') ?></div>
		</div>
		<div class="portlet-body">
			<?= DataTable::widget([
				'id' => 'dt-logs',
				'options' => [
					'class' => 'table table-bordered table-hover',
				],
				'showColumnFilters' => true,
				'clientOptions' => [
					'deferRender' => true,
					'processing' => true,
					'serverSide' => true,
					'ajax' => [
						'url' => Url::to(['/eventlog-manager/event-log/dt-event-logs']),
						'method' => 'POST',
						'data' => new JsExpression('function (data) {
							data.model = ' . json_encode(Payment::class) . '; 
							data.model_key = "' . $model->id . '";
						}'),
						'reloadInterval' => 5 * 60000,
					],
					'order' => [
						[3, 'desc'],
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
							'class' => 'common\widgets\datatable\ActionColumn',
							'title' => Yii::t('common', 'Action'),
							'buttons' => [
								'event-log' => [
									'visible' => Yii::$app->user->can('viewEventLog'),
									'url' => ['/eventlog-manager/event-log/view', 'id' => new JsExpression('id')],
									'content' => '<span class="fa fa-eye"></span>',
									'options' => [
										'class' => 'action-view btn btn-xs btn-info',
										'title' => Yii::t('common', 'View'),
										'data' => [
											'toggle' => 'tooltip',
											'popup-action' => '',
										],
									],
								],
							],
						],
						[
							'data' => 'user',
							'title' => Yii::t('common', 'User'),
							'filter' => ['text'],
						],
						[
							'data' => 'operation',
							'title' => Yii::t('common', 'Operation'),
							'filter' => ['select', ArrayHelper::getColumn(EventLog::getActions(), 'label')],
						],
						[
							'data' => 'date',
							'title' => Yii::t('common', 'Date'),
							'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
						],
						[
							'data' => 'ip_address',
							'title' => Yii::t('common', 'IP Address'),
							'filter' => ['text'],
						],
					],
				],
			]) ?>
		</div>
	</div>
<?php endif; ?>
