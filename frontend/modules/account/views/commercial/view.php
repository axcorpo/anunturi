<?php

/* @var $this yii\web\View */
/* @var $model common\models\Commercial */

use common\models\Commercial;
use common\models\EventLog;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Commercial')]);
?>
<?php if (Yii::$app->request->isAjax): ?>
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
                            'value' => function (Commercial $model) {
                                if ($model->image && is_file(Yii::getAlias("@uploads/commercial/{$model->id}/{$model->image}"))) {
                                return Html::img($model->imageUrl, ['alt' => $model->id, 'class' => 'img-responsive', 'style' => ['max-width' => '100px']]);
                            }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Url'),
                            'value' => function (Commercial $model) {
                                return $model->url ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Bid'),
                            'value' => function (Commercial $model) {
                                return $model->bid ? Html::a($model->bid->bidName, ['/commercial-manager/bid/view', 'id' => $model->bid->id]) : '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Created By'),
                            'value' => function (Commercial $model) {
                                return $model->creator ? Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) : '&mdash;';
                            },
                        ],
                        'created_at:datetime',
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Updated By'),
                            'value' => function (Commercial $model) {
                                return $model->updater ? Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) : '&mdash;';
                            },
                        ],
                        'updated_at:datetime',
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Status'),
                            'value' => function (Commercial $model) {
                                $status = $model::getStatusLabels()[$model->status];

                                return Html::tag('span', $status['label'], ['class' => 'label label-' . $status['color']]);
                            },
                        ],
                    ],
                ]) ?>
            </div>

            <?php if (Yii::$app->request->isAjax): ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal" style="padding: 15.5px 20px;"><?= Yii::t('common', 'Close') ?></button>
        </div>
    </div>
</div>
<?php endif; ?>
