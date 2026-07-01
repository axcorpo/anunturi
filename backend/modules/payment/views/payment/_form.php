<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Payment */

use common\models\Payment;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

?>

<?php $form = ActiveForm::begin([
	'id' => 'payment-form',
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
			<div class="col-sm-4">
				<?= $form->field($model, 'status')->widget(Select2::class, [
					'data' => ArrayHelper::getColumn(Payment::getStatusLabels(), 'label'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'placeholder' => Yii::t('common', 'Choose'),
						'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
					],
				]) ?>
			</div>
			<div class="col-sm-4">
				<?php
					$subscriptions = \common\models\Subscription::find()->active()->deleted(false)->all();
				?>
				<?= $form->field($model, 'subscription_id')->widget(Select2::class, [
					'data' => ArrayHelper::map($subscriptions, 'id', 'code'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Choose'),
						'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
					],
				]) ?>
			</div>
			<div class="col-md-4">
				<?= $form->field($model, 'paid_at')->widget(DateTimePicker::class, [
					'options' => [
						'value' => $model->paid_at ? Yii::$app->formatter->asDatetime($model->paid_at) : null,
						'placeholder' => Yii::$app->settings->get('datetimeFormat'),
					],
					'clientOptions' => [
						'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
						'maxDate' => (new DateTime)->format(DATE_ATOM),
						'ignoreReadonly' => true,
						'showTodayButton' => false,
						'showClear' => true,
						'showClose' => true,
						'allowInputToggle' => true,
						'useCurrent' => false,
					],
				]) ?>
			</div>
		</div>
		<?php if (Yii::$app->request->isAjax) : ?>
			<div class="modal-footer">
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
</div>
<?php ActiveForm::end(); ?>
