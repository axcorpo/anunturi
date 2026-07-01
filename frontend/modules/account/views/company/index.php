<?php
/* @var $this yii\web\View */

use common\widgets\datatable\DataTable;
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
                            'id' => 'dt-companies',
                            'options' => [
                                'class' => 'table table-bordered table-hover',
                            ],
                            'showColumnFilters' => false,
                            'clientOptions' => [
                                'deferRender' => true,
                                'processing' => true,
                                'serverSide' => true,
                                'ajax' => [
                                    'url' => Url::to(['company/dt-companies']),
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
                                        'data' => 'name',
                                        'title' => Yii::t('label', 'Name'),
                                        'filter' => ['text'],
                                    ],
                                    [
                                        'data' => 'schedule',
                                        'title' => Yii::t('common', 'Schedule'),
                                        'filter' => ['text'],
                                    ],
                                    [
                                        'data' => 'email',
                                        'title' => Yii::t('label', 'Email'),
                                        'filter' => ['text'],
                                    ],
                                    [
                                        'data' => 'phone',
                                        'title' => Yii::t('label', 'Phone'),
                                        'filter' => ['text'],
                                    ],
                                    [
                                        'data' => 'created_at',
                                        'title' => Yii::t('label', 'Created At'),
                                        'className' => 'col-autowidth',
                                        'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
                                    ],
                                ],
                            ],
                        ]) ?>

                        <div class="text-center gap-t-xlg">
                            <?= Html::a(Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Company')]), ['create'], [
                                'class' => 'btn btn-primary',
                                'data' => [
                                    'popup-action' => '',
                                    'popup-done' => ['redrawDataTable' => '#dt-companies'],
                                ],
                            ]) ?>
                        </div>

                    </div>
                </div>
            </div>
        </section>

	</div>
</div>
