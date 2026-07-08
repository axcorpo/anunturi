<?php

/* @var $this yii\web\View */
/* @var $model common\models\Field */

use common\models\Option;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Update {item}', ['item' => Yii::t('common', 'Field')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	[
		'label' => Yii::t('common', 'Fields'),
		'url' => ['index'],
	],
	Yii::t('common', 'Update'),
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
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>

<div class="portlet box blue-hoki margin-top-20">
	<div class="portlet-title">
		<div class="caption">
			<?= Yii::t('label', 'Options') ?>
			<?php if ($showTrash = Yii::$app->settings->get('enableSoftDelete')): ?>
				<?php $showTrash = Yii::$app->request->get('deleted') == Option::YES; ?>
				<span class="page-links">
					<?= implode('', [
						Html::a(Yii::t('common', 'Current'), ['update', 'id' => $model->id], ['class' => $showTrash ? '' : 'active']),
						Html::a(Yii::t('common', 'Trash'), ['update', 'id' => $model->id, 'deleted' => Option::YES], ['class' => $showTrash ? 'active' : '']),
					]); ?>
				</span>
			<?php endif; ?>
		</div>
		<div class="actions">
			<?php if (Yii::$app->user->can('restoreField') && $showTrash): ?>
				<?= Html::button('<span class="fa fa-undo"></span>', [
					'type' => 'button',
					'class' => 'btn btn-sm btn-success hidden',
					'title' => Yii::t('common', 'Restore'),
					'data' => [
						'toggle' => 'tooltip',
						'dt-bulk-operation' => 'restore',
						'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						'dt-url' => Url::to(['field-option/restore', 'field_id' => $model->id]),
						'dt-table' => '#dt-field-options',
					],
				]) ?>
			<?php endif; ?>
			<?php if (Yii::$app->user->can('deleteField')): ?>
				<?= Html::button('<span class="fa fa-trash"></span>', [
					'type' => 'button',
					'class' => 'btn btn-sm btn-danger hidden',
					'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
					'data' => [
						'toggle' => 'tooltip',
						'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
						'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						'dt-url' => Url::to(['field-option/delete', 'field_id' => $model->id]),
						'dt-table' => '#dt-field-options',
					],
				]) ?>
			<?php endif; ?>
			<?php if (Yii::$app->user->can('createField')): ?>
				<?= Html::a('<span class="fa fa-plus"></span>', ['field-option/create', 'field_id' => $model->id], [
					'type' => 'button',
					'class' => 'btn btn-sm btn-success',
					'title' => Yii::t('common', 'Create'),
					'data' => [
						'toggle' => 'tooltip',
						'popup-action' => '',
						'popup-done' => ['redrawDataTable' => '#dt-field-options'],
					],
				]) ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="portlet-body">
		<?= DataTable::widget([
			'id' => 'dt-field-options',
			'options' => [
				'class' => 'table table-bordered table-hover',
			],
			'showColumnFilters' => true,
			'clientOptions' => [
				'deferRender' => true,
				'processing' => true,
				'serverSide' => true,
				'ajax' => [
					'url' => Url::to(['field-option/dt-field-options', 'field_id' => $model->id]),
					'method' => 'POST',
					'data' => new JsExpression('function (data) {
					 	data.field_id = ' . json_encode($model->uuid) . ';
						data.deleted = ' . json_encode($showTrash ? Option::YES : null) . ';
					}'),
				],
				'order' => [
					[3, 'asc'],
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
						'data' => 'action',
						'class' => 'common\widgets\datatable\ActionColumn',
						'title' => Yii::t('common', 'Action'),
					],
					[
						'data' => 'label',
						'title' => Yii::t('label', 'Label'),
						'filter' => ['text'],
					],
					[
						'data' => 'sort_order',
						'title' => Yii::t('common', 'Sort Order'),
						'filter' => ['text'],
					],
					[
						'data' => 'status',
						'title' => Yii::t('common', 'Status'),
						'className' => 'col-autowidth',
						'filter' => ['select', ArrayHelper::getColumn(Option::getStatusLabels(), 'label')],
					],
				],
			],
		]) ?>
	</div>
</div>
