<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Unavailability */

use backend\widgets\ActiveForm;
use common\models\Unavailability;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => 'unavailability-form',
	'options' => [
		'novalidate' => true,
		'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
	],
	'validateOnType' => true,
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
				<div class="col-sm-6">
					<?= $form->field($model, 'start_at')->widget(DateTimePicker::class, [
						'id' => 'dp-start_at',
						'options' => [
							'value' => Yii::$app->formatter->asDatetime($model->start_at),
						],
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
							'ignoreReadonly' => true,
							'showTodayButton' => true,
							'showClear' => true,
							'showClose' => true,
							'allowInputToggle' => true,
							'useCurrent' => false,
						],
					]) ?>
				</div>
				<div class="col-sm-6">
					<?= $form->field($model, 'end_at')->widget(DateTimePicker::class, [
						'id' => 'dp-end_at',
						'options' => [
							'value' => Yii::$app->formatter->asDatetime($model->end_at),
						],
						'linkedTo' => '#dp-start_at',
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
							'ignoreReadonly' => true,
							'showTodayButton' => true,
							'showClear' => true,
							'showClose' => true,
							'allowInputToggle' => true,
							'useCurrent' => false,
						],
					]) ?>
				</div>
			</div>
        </div>

		<?php if (Yii::$app->request->isAjax) : ?>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary btn-deactivate" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
				<?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-primary']) ?>
			</div>
		<?php else : ?>
			<div class="form-actions floating">
				<?= Html::submitButton('<span class="fa fa-check"></span>', [
					'class' => 'btn btn-primary',
					'title' => Yii::t('common', 'Save'),
					'data' => [
						'toggle' => 'tooltip',
					],
				]) ?>
			</div>
		<?php endif; ?>
	</div>
<?php ActiveForm::end(); ?>
