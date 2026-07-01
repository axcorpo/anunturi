<?php
/* @var $this yii\web\View */

use backend\modules\export\widgets\export\Export;
use backend\modules\marketing\models\MarketingRecipientSearch;
use common\models\MarketingGroup;
use common\models\MarketingRecipient;
use common\widgets\datatable\DataTable;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Marketing Recipients');
$this->params['breadcrumbs'] = [
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == MarketingRecipient::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => MarketingRecipient::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreMarketingRecipient') && $showTrash,
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
				'dt-table' => '#dt-marketing-recipients',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteMarketingRecipient'),
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
				'dt-table' => '#dt-marketing-recipients',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createMarketingRecipient'),
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
    [
        'visible' => Yii::$app->user->can('updateMarketingRecipient'),
        'tag' => 'a',
        'url' => ['bulk-update'],
        'icon' => 'fa fa-edit',
        'options' => [
            'class' => 'action-update-selection btn btn-sm btn-warning hidden',
            'title' => Yii::t('common', 'Bulk Update'),
            'data' => [
                'toggle' => 'tooltip',
                'popup-action' => '',
                'popup-done' => ['redrawDataTable' => '#dt-marketing-recipients'],
                'dt-bulk-operation' => 'update',
                'dt-table' => '#dt-marketing-recipients',
            ],
        ],
    ],
    [
        'visible' => Yii::$app->user->can('importMarketingRecipient'),
        'tag' => 'a',
        'url' => ['import'],
        'icon' => 'fa fa-sign-in fa-rotate-180',
        'options' => [
            'class' => 'btn btn-sm btn-default',
            'title' => Yii::t('common', 'Import'),
            'data' => [
                'toggle' => 'tooltip',
            ],
        ],
    ],
    [
        'visible' => true,
        'content' => Export::widget([
            'buttonOptions' => [
                'class' => 'btn btn-sm btn-default',
                'style' => 'margin: 0 5px;',
                'title' => Yii::t('common', 'Export'),
                'label' => '<span class="fa fa-file-excel-o"></span>',
            ],
            'dropdownOptions' => [
                'class' => 'pull-right',
            ],
            'items' => [
                Export::FORMAT_CSV => [
                    'visible' => false,
                    'label' => 'CSV',
                    'url' => '#',
                    'clientOptions' => [
                        'fieldDelimiter' => ',',
                        'fieldEnclosure' => '"',
                        'shouldAddBom' => true,
                    ],
                ],
                Export::FORMAT_VCF => [
                    'visible' => false,
                    'label' => 'VCF',
                    'url' => '#',
                    'clientOptions' => [
                        'map' => [
                            'name' => 'name',
                            'phone' => 'phone',
                            'email' => 'email',
                        ],
                    ],
                ],
                Export::FORMAT_XLSX => [
                    'visible' => true,
                    'label' => 'XLSX',
                    'url' => '#',
                    'clientOptions' => [
                        'shouldCreateNewSheetsAutomatically' => true,
                        'shouldUseInlineStrings' => true,
                    ],
                ],
                Export::FORMAT_PDF => [
                    'visible' => false,
                    'label' => 'PDF',
                    'url' => '#',
                    'clientOptions' => [
                        'allowHtml' => false,
                    ],
                ],
            ],
            'clientOptions' => [
                'url' => Url::to(['export']),
                'dataTable' => '#dt-marketing-recipients',
                'model' => MarketingRecipientSearch::class,
                'title' => Yii::t('common', 'Marketing Recipients'),
            ],
        ]),
    ],
];
?>

<div class="dt-scroll-x">
<?= DataTable::widget([
	'id' => 'dt-marketing-recipients',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-marketing-recipients']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? MarketingRecipient::YES : null) . ';
			}'),
		],
		'order' => [
			[4, 'desc']
		],
		'pageLength' => Yii::$app->settings->get('itemsPerPage'),
		'lengthMenu' => [
			'autoCreate' => true,
			'displayAll' => Yii::t('common', 'All'),
		],
		'autoWidth' => false,
		'responsive' => false,
		'scrollX' => false,
        'colReorder' => [
            'realtime' => false,
        ],
        'dom' => 'lfBrtip',
        'buttons' => [
            [
                'extend' => 'colvis',
                'text' => '<span class="fa fa-eye"></span>',
            ],
        ],
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
				'data' => 'name',
				'title' => Yii::t('common', 'Name'),
				'filter' => ['text'],
			],
			[
				'data' => 'email',
				'title' => Yii::t('common', 'Email'),
				'filter' => ['text'],
			],
			[
				'data' => 'phone',
				'title' => Yii::t('common', 'Phone'),
				'filter' => ['text'],
			],
			[
				'data' => 'created_at',
				'title' => Yii::t('label', 'Created At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
            [
                'data' => 'group',
                'title' => Yii::t('common', 'Groups'),
                'filter' => Select2::widget([
                    'id' => 'select2-' . rand(0, 9999),
                    'name' => '',
                    'data' => ArrayHelper::map(MarketingGroup::findAllMarketingGroups(), 'id', 'translation.name'),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'multiple' => true,
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Filter'),
                    ],
                ]),
            ],
			[
				'data' => 'status',
				'title' => Yii::t('label', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(MarketingRecipient::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
</div>
