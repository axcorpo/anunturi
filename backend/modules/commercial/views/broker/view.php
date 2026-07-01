<?php

/* @var $this yii\web\View */
/* @var $model common\models\Broker */

use common\models\Broker;
use common\models\EventLog;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Broker')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Brokers'),
		'url' => ['index'],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewBroker'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'Brokers'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('updateBroker'),
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
		'visible' => Yii::$app->user->can('deleteBroker'),
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
		'visible' => Yii::$app->user->can('createBroker'),
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
			'label' => Yii::t('common', 'code'),
			'value' => function (Broker $model) {
				return $model->code ?: '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('common', 'Name'),
			'value' => function (Broker $model) {
				if ($model->user) {
					return Html::a($model->user->fullName, ['/user-manager/user/view', 'id' => $model->user->id], ['target' => '']);
				}
				return '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('common', 'Email'),
			'value' => function (Broker $model) {
				if ($model->user) {
					return $model->user->email;
				}
				return '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('common', 'Phone'),
			'value' => function (Broker $model) {
				if ($model->user) {
					return $model->user->phone;
				}
				return '&mdash;';
			},
		],
		[
			'format' => 'html',
			'label' => Yii::t('common', 'Created By'),
			'value' => function (Broker $model) {
				return $model->creator ? Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) : '&mdash;';
			},
		],
		'created_at:datetime',
		[
			'format' => 'html',
			'label' => Yii::t('common', 'Updated By'),
			'value' => function (Broker $model) {
				return $model->updater ? Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) : '&mdash;';
			},
		],
		'updated_at:datetime',
		[
			'format' => 'html',
			'label' => Yii::t('common', 'Status'),
			'value' => function (Broker $model) {
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
							data.model = ' . json_encode(Broker::class) . '; 
							data.model_key = "' . $model->id . '";
						}'),
						'reloadInterval' => 5 * 60000,
					],
					'order' => [
						[3, 'desc'],
					],
					'pageLength' => (int) Yii::$app->settings->get('itemsPerBroker'),
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
