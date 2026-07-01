<?php

/* @var $this yii\web\View */
/* @var $model common\models\Field */

use common\models\Field;
use common\models\EventLog;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Field')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	[
		'label' => Yii::t('common', 'Fields'),
		'url' => ['index'],
	],
	Yii::t('common', 'View'),
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewField'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'List'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('updateField'),
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
		'visible' => Yii::$app->user->can('deleteField'),
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
		'visible' => Yii::$app->user->can('createField'),
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

$showEventLogs = isset($showEventLogs) ? $showEventLogs : Yii::$app->eventLog->enabled;
?>

<div class="table-responsive">
	<?= DetailView::widget([
		'model' => $model,
		'options' => [
			'class' => 'table table-striped table-bordered detail-view detail-view-fixed',
		],
		'attributes' => [
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Name'),
				'value' => function (Field $model) {
					return $model->name ?: '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Label'),
				'value' => function (Field $model) {
					return $model->translation->label ?: '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Placeholder'),
				'value' => function (Field $model) {
					return $model->translation->placeholder ?: '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Help Text'),
				'value' => function (Field $model) {
					return $model->translation->help_text ?: '&mdash;';
				},
			],
            [
                'format' => 'html',
                'label' => Yii::t('label', 'Filter'),
                'value' => function (Field $model) {
                    return Field::getBooleanLabels()[$model['filter']] ?: '&mdash;';
                },
            ],
            [
                'format' => 'html',
                'label' => Yii::t('label', 'Extra'),
                'value' => function (Field $model) {
                    return Field::getBooleanLabels()[$model['extra']] ?: '&mdash;';
                },
            ],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Type'),
				'value' => function (Field $model) {
					return $model->type ? Field::getTypeLabels()[$model->type] : '&mdash;';
				},
			],
            [
                'format' => 'html',
                'label' => Yii::t('label', 'Filter Type'),
                'value' => function (Field $model) {
                    return $model->filter_type ? Field::getFilterTypeLabels()[$model->filter_type] : '&mdash;';
                },
            ],
            [
                'format' => 'html',
                'label' => Yii::t('label', 'Min'),
                'value' => function (Field $model) {
                    return $model->min ?: '&mdash;';
                },
            ],
            [
                'format' => 'html',
                'label' => Yii::t('label', 'Max'),
                'value' => function (Field $model) {
                    return $model->max ?: '&mdash;';
                },
            ],
            [
                'format' => 'html',
                'label' => Yii::t('label', 'Step'),
                'value' => function (Field $model) {
                    return $model->step ?: '&mdash;';
                },
            ],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Decimals'),
				'value' => function (Field $model) {
					return $model->decimals ?: '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Created By'),
				'value' => function (Field $model) {
					return $model->creator ? Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) : '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Created At'),
				'value' => function (Field $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Updated By'),
				'value' => function (Field $model) {
					return $model->updater ? Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) : '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Updated At'),
				'value' => function (Field $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
			],
			[
				'format' => 'html',
				'label' => Yii::t('label', 'Status'),
				'value' => function (Field $model) {
					$status = Field::getStatusLabels()[$model->status];
					return Html::tag('span', $status['label'], ['class' => 'label label-' . $status['color']]);
				},
			],
		],
	]) ?>
</div>

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
							data.model = ' . json_encode(\common\models\Field::class) . '; 
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
							'title' => Yii::t('label', 'User'),
							'filter' => ['text'],
						],
						[
							'data' => 'operation',
							'title' => Yii::t('label', 'Operation'),
							'filter' => ['select', ArrayHelper::getColumn(EventLog::getActions(), 'label')],
						],
						[
							'data' => 'date',
							'title' => Yii::t('label', 'Date'),
							'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
						],
						[
							'data' => 'ip_address',
							'title' => Yii::t('label', 'IP Address'),
							'filter' => ['text'],
						],
					],
				],
			]) ?>
		</div>
	</div>
<?php endif; ?>
