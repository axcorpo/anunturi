<?php
/* @var $this yii\web\View */

use common\models\Invoice;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
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

<div class="section section-md">
    <div class="container-fluid">
        <?php if ($content = $this->context->currentPage->translation->content): ?>
            <header class="section-header">
                <?= $content ?>
            </header>
        <?php endif; ?>


        <section class="clearfix bg-dark dashboard-review-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-xs-12 dashboardBoxBg">
                        <?= DataTable::widget([
                            'id' => 'dt-invoices',
                            'options' => [
                                'class' => 'table table-bordered table-hover',
                            ],
                            'showColumnFilters' => false,
                            'clientOptions' => [
                                'deferRender' => true,
                                'processing' => true,
                                'serverSide' => true,
                                'ajax' => [
                                    'url' => Url::to(['dt-invoices']),
                                    'method' => 'POST',
                                    'reloadInterval' => 5 * 60000,
                                ],
                                'order' => [
                                    [5, 'asc'],
                                    [3, 'desc'],
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
                                        'data' => 'document_number',
                                        'title' => '#',
                                        'className' => 'col-autowidth',
                                        'filter' => ['text'],
                                    ],
                                    [
                                        'data' => 'amount',
                                        'title' => Yii::t('label', 'Amount'),
                                        'filter' => ['text'],
                                    ],
                                    [
                                        'data' => 'issued_at',
                                        'title' => Yii::t('label', 'Issued At'),
                                        'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
                                    ],
                                    [
                                        'data' => 'paid_at',
                                        'title' => Yii::t('label', 'Paid At'),
                                        'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
                                    ],
                                    [
                                        'data' => 'status',
                                        'title' => Yii::t('label', 'Status'),
                                        'className' => 'col-autowidth',
                                        'filter' => ['select', ArrayHelper::getColumn(Invoice::getStatusLabels(), 'label')],
                                    ],
                                ],
                            ],
                        ]) ?>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

