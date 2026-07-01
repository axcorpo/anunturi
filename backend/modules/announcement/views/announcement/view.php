<?php

/* @var $this yii\web\View */
/* @var $model common\models\Announcement */

use common\models\Action;
use common\models\Announcement;
use common\models\Category;
use common\models\EventLog;
use common\models\Field;
use common\models\FieldValue;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Announcement')]);
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('common', 'Announcements'),
        'url' => ['index'],
    ],
    $this->title,
];
$this->params['actions'] = [
    [
        'visible' => Yii::$app->user->can('viewAnnouncement'),
        'tag' => 'a',
        'url' => ['index'],
        'icon' => 'fa fa-list',
        'options' => [
            'class' => 'btn btn-sm btn-default',
            'title' => Yii::t('common', 'Announcements'),
            'data' => [
                'toggle' => 'tooltip',
                'popup-action' => '',
                'popup-done' => ['redrawDataTable' => '#dt-announcements'],
            ],
        ],
    ],
    [
        'visible' => Yii::$app->user->can('updateAnnouncement'),
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
        'visible' => Yii::$app->user->can('deleteAnnouncement'),
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
        'visible' => Yii::$app->user->can('createAnnouncement'),
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
$renderAjaxModal = (Yii::$app->request->isAjax && strpos(Yii::$app->request->pathInfo, 'eventlog-manager') === false);
?>
<?php if ($renderAjaxModal) : ?>
<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <div class="modal-title"><?= $this->title ?></div>
        </div>
        <div class="modal-body">
            <?php endif; ?>
            <div class="table-responsive">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => [
                        'class' => 'table table-striped table-bordered detail-view detail-view-fixed',
                    ],
                    'attributes' => [
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Image'),
                            'value' => function (Announcement $model) {
                                if ($model->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$model->id}/{$model->image}"))) {
                                    return Html::img($model->imageUrl, ['alt' => $model->translation->title, 'class' => 'img-responsive', 'style' => ['max-width' => '100px']]);
                                }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Title'),
                            'value' => function (Announcement $model) {
                                return $model->translation->title ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Categories'),
                            'value' => function (Announcement $model) {
                                if ($categories = $model->categories) {
                                    return implode('', array_map(function (Category $category) {
                                        return Html::a($category->translation->name, ['/announcement-manager/category/view', 'id' => $category->id], ['class' => 'btn btn-xs btn-default']);
                                    }, $categories));
                                }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Price'),
                            'value' => function (Announcement $model) {
                                if ($model->price) {
                                    return Yii::$app->formatter->asCurrency($model->price, $model->currency);
                                }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Uom'),
                            'value' => function (Announcement $model) {
                                    return $model->uom ? $model->uom : '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Keywords'),
                            'value' => function (Announcement $model) {
                                return $model->translation->keywords ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Description'),
                            'value' => function (Announcement $model) {
                                return $model->translation->description ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Content'),
                            'value' => function (Announcement $model) {
                                return $model->translation->content ? nl2br($model->translation->content): '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Sort Order'),
                            'value' => function (Announcement $model) {
                                return $model->sort_order ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Actions'),
                            'value' => function (Announcement $model) {
                                if ($actions = $model->actions) {
                                    return implode('', array_map(function (Action $action) {
                                        return Html::tag('span', Action::getTypes()[$action->type], ['class' => 'btn btn-xs btn-default']);
                                    }, $actions));
                                }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'raw',
                            'label' => Yii::t('common', 'Fields'),
                            'value' => function (Announcement $model) {
                                if ($model->fieldValues) {
                                    return $this->render('_fields-list', [
                                        'model' => $model,
                                    ]);
                                }
                                return Html::tag('div', '&mdash;', ['class' => 'p8']);
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Created By'),
                            'value' => function (Announcement $model) {
                                return $model->creator ? Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) : '&mdash;';
                            },
                        ],
                        'created_at:datetime',
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Updated By'),
                            'value' => function (Announcement $model) {
                                return $model->updater ? Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) : '&mdash;';
                            },
                        ],
                        'updated_at:datetime',
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Status'),
                            'value' => function (Announcement $model) {
                                $status = $model::getStatusLabels()[$model->status];

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
							data.model = ' . json_encode(Announcement::class) . '; 
							data.model_key = "' . $model->id . '";
						}'),
                                    'reloadInterval' => 5 * 60000,
                                ],
                                'order' => [
                                    [3, 'desc'],
                                ],
                                'pageLength' => (int) Yii::$app->settings->get('itemsPerAnnouncement'),
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
            <?php if ($renderAjaxModal) : ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light btn-slide-center-v" data-dismiss="modal"><?= Yii::t('common', 'Close') ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->registerJs('
	$(document).on("done.popupAction", function(e, $element, $modal, response){
		if (response.success && response.returnUrl) {
			mainApp.preventPageOverlay = true;
			window.location.href = response.returnUrl;
		}
	});
') ?>
