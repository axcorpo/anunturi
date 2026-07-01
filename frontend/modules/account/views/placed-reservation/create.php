<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use common\widgets\ActiveForm;
use common\widgets\fullcalendar\FullCalendar;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Reservation')]);
?>

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
                    <div class="col-md-12 col-sm-7 col-xs-12">
                        <?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
                                <div class="profileIntro">
                                    <?= $form->errorSummary($model, [
                                        'header' => false,
                                        'class' => 'alert alert-danger alert-icon',
                                    ]) ?>
                                </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-sm-6">
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
                                        'minDate' => (new DateTime('now'))->format(DATE_ATOM),
                                    ],
                                ]) ?>
                            </div>
                            <div class="col-sm-6">
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
                                        'minDate' => $model->start_at ? (new DateTime($model->start_at))->format(DATE_ATOM) : false,
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <?= FullCalendar::widget([
                            'id' => 'announcement-calendar',
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
                                    'method' => 'GET',
                                    'url' => Url::to(['/account/unavailability/view', 'announcement_id' => Yii::$app->request->get('announcement_id')]),
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
                    <button type="button" class="btn btn-default btn-primary" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
                    <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success btn-primary']) ?>
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
<?php $this->registerJs('
	$(document).on("done.popupAction", function (e, $element, $modal, response) {
		if ($modal.hasClass("modal-placed-reservation")) {
			$("#placed-reservation-calendar").fullCalendar("refetchEvents");
		}
	});
'); ?>

