<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\widgets\ActiveForm;
use common\widgets\datatable\DataTable;
use kartik\file\FileInput;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
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

<?php $form = ActiveForm::begin([
    'id' => mb_strtolower($model->formName()),
    'options' => [
        'novalidate' => true,
    ],
    'validateOnType' => true,
]); ?>
<!-- DASHBOARD PROFILE SECTION -->
<section class="clearfix bg-dark profileSection">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="dashboardBoxBg">
                    <div class="profileIntro">
                        <h3><?= Yii::t('frontend', 'Companies') ?></h3>
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
                        <div class="text-center gap-t-xlg form-group">
                            <?= Html::a(Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Company')]), ['/account/company/create'], [
                                'class' => 'btn btn-primary',
                                'data' => [
                                    'popup-action' => '',
                                    'popup-done' => ['redrawDataTable' => '#dt-companies'],
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
                <form>
                    <div class="dashboardBoxBg mt30">
                        <div class="profileIntro">
                            <h3><?= Yii::t('common','Notifications') ?></h3>
                            <div class="row mt30">
                                <div class="form-group col-xs-12">
									<?= $form->field($model, 'announcementNotifications')
										->checkbox([])
										->hint(Yii::t('common', 'Get information about the performance of your ads and tips on how to increase your chances of success'))
									?>
									<?= $form->field($model, 'messageNotifications')
										->checkbox([])
										->hint(Yii::t('common', 'Receive notifications for each new message received or reply to a message'))
									?>
                                </div>
                                <div class="form-group col-xs-12">
                                    <?= Html::submitButton('<span class="fa fa-check"></span> ' . Yii::t('common', 'Save'), [
                                        'class' => 'btn btn-xlg btn-fab btn-success btn-primary',
                                        'title' => Yii::t('common', 'Save'),
                                        'data' => [
//                                            'toggle' => 'tooltip',
                                        ],
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<?php ActiveForm::end(); ?>
