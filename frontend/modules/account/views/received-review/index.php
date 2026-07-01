<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Announcement;
use common\models\Review;
use common\widgets\ActiveForm;
use common\widgets\datatable\DataTable;
use kartik\file\FileInput;
use kartik\rating\StarRating;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Breadcrumbs;
use yii\widgets\LinkPager;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<?php if (!empty($this->params['breadcrumbs'])) : ?>
    <!-- Dashboard breadcrumb section -->
    <div class="section dashboard-breadcrumb-section bg-dark">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <h2><?= Html::encode($this->title) ?></h2>
                    <?= Breadcrumbs::widget([
                        'options' => [
                            'class' => 'breadcrumb',
                        ],
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>


<!-- DASHBOARD REVIEWS SECTION -->
<section class="clearfix bg-dark dashboard-review-section">
    <div class="container">
        <div class="row">
			<div class="dashboardBoxBg">
            <?= DataTable::widget([
                'id' => 'dt-customer-reviews',
                'options' => [
                    'class' => 'table table-bordered table-hover',
                ],
                'showColumnFilters' => false,
                'clientOptions' => [
                    'deferRender' => true,
                    'processing' => true,
                    'serverSide' => true,
                    'ajax' => [
                        'url' => Url::to(['received-placed-review/dt-customer-reviews']),
                        'method' => 'POST',
                    ],
                    'order' => [
                        [1, 'desc'],
                    ],
                    'pageLength' => Yii::$app->settings->get('itemsPerPage'),
                    'lengthMenu' => [
                        'autoCreate' => true,
                        'displayAll' => Yii::t('common', 'All'),
                    ],
                    'autoWidth' => false,
                    'responsive' => true,
                    'columns' => [
                        [
                            'data' => 'action',
                            'class' => 'common\widgets\datatable\ActionColumn',
                            'title' => Yii::t('common', 'Action'),
                        ],
                        [
                            'data' => 'created_by',
                            'title' => Yii::t('label', 'Created By'),
                            'filter' => ['text'],
                        ],
                        [
                            'data' => 'placed-review',
                            'title' => Yii::t('label', 'Review'),
                            'filter' => ['text'],
                        ],
                        [
                            'data' => 'type',
                            'title' => Yii::t('common', 'Type'),
                            'filter' => Select2::widget([
                                'name' => '',
                                'data' => Review::getReviewTypes(),
                                'pluginLoading' => false,
                                'pluginOptions' => [
                                    'multiple' => true,
                                    'allowClear' => true,
                                    'placeholder' => Yii::t('common', 'Filter'),
                                ],
                            ]),
                        ],
                        [
                            'data' => 'score',
                            'title' => Yii::t('label', 'Rating'),
                            'filter' => ['text'],
                        ],
                        [
                            'data' => 'created_at',
                            'title' => Yii::t('label', 'Created At'),
                            'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
                        ],
                        [
                            'data' => 'status',
                            'title' => Yii::t('common', 'Status'),
                            'className' => 'col-autowidth',
                            'filter' => ['select', ArrayHelper::getColumn(Review::getStatusLabels(), 'label')],
                        ],
                    ],
                ],
            ]) ?>
        </div>
		</div>
    </div>
</section>