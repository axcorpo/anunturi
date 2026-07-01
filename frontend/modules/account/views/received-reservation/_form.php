<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Subscription */

use common\models\Currency;
use common\models\Reservation;
use common\widgets\ActiveForm;
use common\widgets\fullcalendar\FullCalendar;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = Html::encode($this->title);

?>


<section class="clearfix bg-dark profileSection">
    <div class="container">
        <?php $form = ActiveForm::begin([
            'id' => mb_strtolower($model->formName()),
            'options' => [
                'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
                'novalidate' => true,
            ],
            'validateOnType' => true,
            'validateOnBlur' => false,
        ]); ?>

        <div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
            <?php if (Yii::$app->request->isAjax) : ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <div class="modal-title"><?= $this->title ?></div>
                </div>
            <?php endif; ?>

            <div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
        <div class="row">
            <div class="col-md-12 col-sm-7 col-xs-12 dashboardBoxBg">
                <?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
                    <div class="dashboardBoxBg mb30">
                        <div class="profileIntro">
                            <?= $form->errorSummary($model, [
                                'header' => false,
                                'class' => 'alert alert-danger alert-icon',
                            ]) ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="dashboardBoxBg">
                        <div class="row">
                            <div class="col-sm-4">
                                <?= $form->field($model, 'status')->widget(Select2::class, [
                                    'data' => ArrayHelper::getColumn(Reservation::getStatusLabels(), 'label'),
                                    'pluginLoading' => false,
                                    'pluginOptions' => [
                                        'placeholder' => Yii::t('common', 'Choose'),
                                    ],
                                ]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($model, 'start_at')->widget(DateTimePicker::class, [
                                    'id' => 'dp-' . Html::getInputId($model, 'start_at'),
                                    'options' => [
                                        'value' => Yii::$app->formatter->asDatetime($model->start_at),
                                    ],
                                    'clientOptions' => [
                                        'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
                                        'ignoreReadonly' => false,
                                        'showTodayButton' => true,
                                        'showClear' => true,
                                        'showClose' => true,
                                        'allowInputToggle' => true,
                                        'useCurrent' => false,
                                    ],
                                ]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($model, 'end_at')->widget(DateTimePicker::class, [
                                    'id' => 'dp-' . Html::getInputId($model, 'end_at'),
                                    'options' => [
                                        'value' => Yii::$app->formatter->asDatetime($model->end_at),
                                    ],
                                    'clientOptions' => [
                                        'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
                                        'ignoreReadonly' => false,
                                        'showTodayButton' => true,
                                        'showClear' => true,
                                        'showClose' => true,
                                        'allowInputToggle' => true,
                                        'useCurrent' => false,
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'price')->widget(TouchSpin::class, [
                                    'pluginOptions' => [
                                        'min' => 0,
                                        'max' => PHP_INT_MAX,
                                        'step' => 0.01,
                                        'decimals' => 2,
                                        'boostat' => 5,
                                        'maxboostedstep' => 10,
                                        'verticalbuttons' => true,
                                    ],
                                ]) ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'currency')->widget(Select2::class, [
                                    'options' => [
                                        'multiple' => false,
                                    ],
                                    'data' => ArrayHelper::map(Currency::find()->active(true)->deleted(false)->all(), 'iso_code', 'formattedName'),
                                    'maintainOrder' => false,
                                    'showToggleAll' => false,
                                    'pluginLoading' => false,
                                    'pluginOptions' => [
                                        'allowClear' => true,
                                        'placeholder' => Yii::t('common', 'Choose'),
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'period')->widget(TouchSpin::class, [
                                    'pluginOptions' => [
                                        'min' => 0,
                                        'max' => PHP_INT_MAX,
                                        'step' => 1,
                                        'boostat' => 5,
                                        'maxboostedstep' => 10,
                                        'verticalbuttons' => true,
                                    ],
                                ]) ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'frequency')->widget(TouchSpin::class, [
                                    'pluginOptions' => [
                                        'min' => 0,
                                        'max' => PHP_INT_MAX,
                                        'step' => 0.01,
                                        'decimals' => 2,
                                        'boostat' => 5,
                                        'maxboostedstep' => 10,
                                        'verticalbuttons' => true,
                                    ],
                                ]) ?>
                            </div>
                        </div>
                    <?= FullCalendar::widget([
                        'id' => 'activity-calendar',
                        'clientOptions' => [
                            'header' => [
                                'left' => 'prev,next today',
                                'center' => 'title',
                                'right' => 'month,agendaWeek,agendaDay',
                            ],
                            'navLinks' => true,
                            'weekNumbers' => true,
                            'editable' => false,
                            'fixedWeekCount' => false,
                            'showNonCurrentDates' => false,
                            'eventLimit' => true,
                            'events' => [
                                'method' => 'POST',
                                'url' => Url::current(),
                                'startParam' => 'start_date',
                                'endParam' => 'end_date',
                            ],
                            'lazyFetching' => true,
                            'loading' => new JsExpression('function (isLoading) {
			$(this).overlay(isLoading ? "" : "remove");
		}'),
                            'eventRender' => new JsExpression('function (event, $element, e) {
			$element.find(".fc-title").html(event.title);
		}'),
                        ],
                    ]) ?>

                    <?php $this->registerJs('
	$(document).on("done.popupAction", function (e, $element, $modal, response) {
		if ($modal.hasClass("modal-activity")) {
			$("#activity-calendar").fullCalendar("refetchEvents");
		}
	});
'); ?>
                    </div>
            </div>
        </div>
            </div>
            <?php if (Yii::$app->request->isAjax) : ?>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-sm-12 form-group text-left">
                            <span class="fa fa-info-circle"></span>
                            <?= sprintf(Yii::t('common', 'Fields marked with %s are required'), '<span class="mark-required">*</span>'); ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
                    <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success']) ?>
                </div>
            <?php else : ?>
                <div class="form-actions floating">
                    <?= Html::submitButton('<span class="fa fa-check"></span>', [
                        'class' => 'btn btn-xlg btn-fab btn-success',
                        'title' => Yii::t('common', 'Save'),
                        'data' => [
                            'toggle' => 'tooltip',
                        ],
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</section>

