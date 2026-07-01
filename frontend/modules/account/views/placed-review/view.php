<?php

/* @var $this yii\web\View */
/* @var $model common\models\Review */

use common\models\Announcement;
use common\models\Company;
use common\models\Review;
use common\models\Category;
use common\models\EventLog;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Review')]);

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
                            'label' => Yii::t('label', 'Announcement'),
                            'value' => function (Review $model) {
                                if ($announcements = $model->getAnnouncement()->active(true)->deleted(false)->all()) {
                                    return implode('', array_map(function (Announcement $announcement) {
                                        return Html::a($announcement->translation->title, ['/announcement-manager/announcement/view', 'id' => $announcement->id], ['class' => 'btn btn-xs btn-default btn-deactivate']);
                                    }, $announcements));
                                }
                                return '&mdash;';
                            },
                            'data' => [
                                'toggle' => 'tooltip',
                                'popup-action' => '',
                                'popup-done' => ['redrawDataTable' => '#dt-reviews'],
                            ],
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Title'),
                            'value' => function (Review $model) {
                                return $model->translation->title ?: '&mdash;';
                            },
                        ],
//                        [
//                            'format' => 'html',
//                            'label' => Yii::t('label', 'Company'),
//                            'value' => function (Review $model) {
//                                if ($companies = $model->getCompanies()->active(true)->deleted(false)->all()) {
//                                    return implode('', array_map(function (Company $company) {
//                                        return Html::a($company->name, ['/announcement-manager/announcement/view', 'id' => $company->id], ['class' => 'btn btn-xs btn-default']);
//                                    }, $companies));
//                                }
//                                return '&mdash;';
//                            },
//                            'data' => [
//                                'toggle' => 'tooltip',
//                                'popup-action' => '',
//                                'popup-done' => ['redrawDataTable' => '#dt-reviews'],
//                            ],
//                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Score'),
                            'value' => function (Review $model) {
                                return $model->score ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Content'),
                            'value' => function (Review $model) {
                                return $model->translation->content ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Created By'),
                            'value' => function (Review $model) {
                                return $model->creator ? Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) : '&mdash;';
                            },
                        ],
                        'created_at:datetime',
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Updated By'),
                            'value' => function (Review $model) {
                                return $model->updater ? Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) : '&mdash;';
                            },
                        ],
                        'updated_at:datetime',
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Status'),
                            'value' => function (Review $model) {
                                $status = $model::getStatusLabels()[$model->status];

                                return Html::tag('span', $status['label'], ['class' => 'label label-' . $status['color']]);
                            },
                        ],
                    ],
                ]) ?>
            </div>

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
