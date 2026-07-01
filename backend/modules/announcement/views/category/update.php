<?php

/* @var $this yii\web\View */
/* @var $model common\models\Category */

use common\models\Field;
use common\models\Option;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Update {item}', ['item' => Yii::t('common', 'Category')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Categories'),
		'url' => ['index'],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewCategory'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'Categories'),
			'data' => [
				'toggle' => 'tooltip',
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

<?= $this->render('_form', [
	'model' => $model,
]) ?>

<div class="portlet box blue-hoki margin-top-10">
	<div class="portlet-title">
		<div class="caption"><?= Yii::t('common', 'Fields') ?></span></div>
		<div class="actions">
			<?= Html::button('<span class="fa fa-trash"></span>', [
				'type' => 'button',
				'class' => 'btn btn-sm btn-danger hidden',
				'title' => Yii::t('common', 'Bulk Delete'),
				'data' => [
					'toggle' => 'tooltip',
					'dt-bulk-operation' => 'delete',
					'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
					'dt-url' => Url::to(['category-field/delete', 'category_id' => $model->id]),
					'dt-table' => '#dt-category-fields',
				],
			]) ?>
			<?= Html::a('<span class="fa fa-plus"></span>', ['category-field/create', 'category_id' => $model->id], [
				'type' => 'button',
				'class' => 'btn btn-sm btn-success',
				'title' => Yii::t('common', 'Create'),
				'data' => [
					'toggle' => 'tooltip',
					'popup-action' => '',
					'popup-done' => ['redrawDataTable' => '#dt-category-fields'],
				],
			]) ?>
		</div>
	</div>
	<div class="portlet-body">
		<?= DataTable::widget([
			'id' => 'dt-category-fields',
			'options' => [
				'class' => 'table table-bordered table-hover',
			],
			'showColumnFilters' => true,
			'clientOptions' => [
				'deferRender' => true,
				'processing' => true,
				'serverSide' => true,
				'ajax' => [
					'url' => Url::to(['category-field/dt-category-fields', 'category_id' => $model->id]),
					'method' => 'POST',
					'data' => new JsExpression('function (data) {
					 	data.category_id = "' . $model->id . '"; 
					}'),
				],
				'order' => [
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
						'data' => 'action',
						'class' => 'common\widgets\datatable\ActionColumn',
						'title' => Yii::t('common', 'Action'),
					],
					[
                        'data' => 'field',
                        'title' => Yii::t('label', 'Field'),
                        'filter' => ['text'],
                    ],
                    [
                        'data' => 'actions',
                        'title' => Yii::t('label', 'Actions'),
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
						'filter' => ['select', ArrayHelper::getColumn(Field::getStatusLabels(), 'label')],
					],
				],
			],
		]) ?>
	</div>
</div>

<div class="portlet box blue-hoki margin-top-10">
	<div class="portlet-title">
		<div class="caption"><?= Yii::t('common', 'Options') ?></span></div>
		<div class="actions">
			<?= Html::button('<span class="fa fa-trash"></span>', [
				'type' => 'button',
				'class' => 'btn btn-sm btn-danger hidden',
				'title' => Yii::t('common', 'Bulk Delete'),
				'data' => [
					'toggle' => 'tooltip',
					'dt-bulk-operation' => 'delete',
					'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
					'dt-url' => Url::to(['category-field-option/delete', 'category_id' => $model->id]),
					'dt-table' => '#dt-category-field-options',
				],
			]) ?>
			<?= Html::a('<span class="fa fa-plus"></span>', ['category-field-option/create', 'category_id' => $model->id], [
				'type' => 'button',
				'class' => 'btn btn-sm btn-success',
				'title' => Yii::t('common', 'Create'),
				'data' => [
					'toggle' => 'tooltip',
					'popup-action' => '',
					'popup-done' => ['redrawDataTable' => '#dt-category-field-options'],
				],
			]) ?>
		</div>
	</div>
	<div class="portlet-body">
		<?= DataTable::widget([
			'id' => 'dt-category-field-options',
			'options' => [
				'class' => 'table table-bordered table-hover',
			],
			'showColumnFilters' => true,
			'clientOptions' => [
				'deferRender' => true,
				'processing' => true,
				'serverSide' => true,
				'ajax' => [
					'url' => Url::to(['category-field-option/dt-category-field-options', 'category_id' => $model->id]),
					'method' => 'POST',
					'data' => new JsExpression('function (data) {
					 	data.category_id = "' . $model->id . '"; 
					}'),
				],
				'order' => [
					[5, 'asc'],
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
						'data' => 'field_label',
						'title' => Yii::t('label', 'Field'),
						'filter' => ['text'],
					],
					[
						'data' => 'label',
						'title' => Yii::t('label', 'Label'),
						'filter' => ['text'],
					],
					[
						'data' => 'value',
						'title' => Yii::t('label', 'Value'),
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
