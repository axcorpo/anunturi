<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Bid;
use common\models\Commercial;
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
                    'id' => 'dt-commercials',
                    'options' => [
                        'class' => 'table table-bordered table-hover',
                    ],
                    'showColumnFilters' => false,
                    'clientOptions' => [
                        'deferRender' => true,
                        'processing' => true,
                        'serverSide' => true,
                        'ajax' => [
                            'url' => Url::to(['dt-commercials']),
                            'method' => 'POST',
                        ],
                        'order' => [
                            [3, 'desc'],
                            [4, 'asc'],
                        ],
                        'pageLength' => (int) Yii::$app->settings->get('itemsPerCommercial'),
                        'lengthMenu' => [
                            [25, 50, 100, -1],
                            [25, 50, 100, Yii::t('common', 'All')],
                        ],
                        'autoWidth' => false,
                        'responsive' => true,
                        'columns' => [
                            [
                                'class' => 'common\widgets\datatable\ActionColumn',
                                'data' => 'action',
                                'title' => Yii::t('common', 'Action'),
                            ],
                            [
                                'class' => 'common\widgets\datatable\ImageColumn',
                                'config' => [
                                    'altAttribute' => 'id',
                                ],
                                'data' => 'image',
                                'title' => Yii::t('common', 'Image'),
                            ],
                            [
                                'data' => 'url',
                                'title' => Yii::t('common', 'Url'),
                                'filter' => ['text'],
                            ],
                            [
                                'data' => 'bid',
                                'title' => Yii::t('common', 'Bid'),
                                'filter' => ['select', ArrayHelper::map(Bid::find()->active()->deleted(false)->all(), 'id', 'bidName')],
                            ],
                            [
                                'data' => 'broker',
                                'title' => Yii::t('common', 'Broker'),
                                'filter' => ['text'],
                            ],
                            [
                                'data' => 'status',
                                'title' => Yii::t('common', 'Status'),
                                'className' => 'col-autowidth',
                                'filter' => ['select', ArrayHelper::getColumn(Commercial::getStatusLabels(), 'label')],
                            ],
                        ],
                    ],
                ]) ?>
				<div class="text-center gap-t-xlg">
					<?= Html::a(Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Commercial')]), ['create'], [
						'class' => 'btn btn-primary',
						'data' => [
							'popup-action' => '',
							'popup-done' => ['redrawDataTable' => '#dt-commercials'],
						],
					]) ?>
				</div>
            </div>
        </div>
    </div>
</section>
