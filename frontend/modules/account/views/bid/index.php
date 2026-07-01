<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Bid;
use common\models\Subscription;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Breadcrumbs;

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
            <div class="col-lg-12 col-xs-12 dashboardBoxBg">
                <?= DataTable::widget([
                    'id' => 'dt-bids',
                    'options' => [
                        'class' => 'table table-bordered table-hover',
                    ],
                    'showColumnFilters' => false,
                    'clientOptions' => [
                        'deferRender' => true,
                        'processing' => true,
                        'serverSide' => true,
                        'ajax' => [
                            'url' => Url::to(['dt-bids']),
                            'method' => 'POST',
                            'reloadInterval' => 5 * 60000,
                        ],
                        'order' => [
                            [2, 'asc'],
                            [3, 'desc'],
                        ],
                        'pageLength' => (int) Yii::$app->settings->get('itemsPerBid'),
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
                                'data' => 'auction',
                                'title' => Yii::t('common', 'Auction'),
                                'filter' => ['text'],
                            ],
                            [
                                'data' => 'broker',
                                'title' => Yii::t('common', 'Broker'),
                                'filter' => ['text'],
                            ],
                            [
                                'data' => 'price',
                                'title' => Yii::t('label', 'Price'),
                                'filter' => ['text'],
                            ],
							[
								'data' => 'created_at',
								'title' => Yii::t('label', 'Created At'),
								'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
							],
							[
								'data' => 'position',
								'title' => Yii::t('common', 'Position'),
								'filter' => false,
								'orderable' => false,
							],
                            [
                                'data' => 'status',
                                'title' => Yii::t('label', 'Status'),
                                'className' => 'col-autowidth',
                                'filter' => ['select', ArrayHelper::getColumn(Bid::getStatusLabels(), 'label')],
                            ],
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</section>
