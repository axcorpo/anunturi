<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Subscription */

use common\widgets\ActiveForm;
use yii\helpers\Html;

$shouldRenderModal = Yii::$app->request->isAjax;
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
		'class' => $shouldRenderModal ? 'modal-dialog modal-lg' : '',
	],
	'validateOnType' => true,
]); ?>
	<div class="form-body <?= $shouldRenderModal ? 'modal-content' : '' ?>">
		<?php if ($shouldRenderModal): ?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div class="modal-title"><?= $this->title ?></div>
			</div>
		<?php endif; ?>

		<div class="form-fields <?= $shouldRenderModal ? 'modal-body' : '' ?>">
			<?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
				<?= $form->errorSummary($model, [
					'header' => false,
					'class' => 'alert alert-danger alert-icon',
				]) ?>
			<?php endif; ?>

			<?php if (Yii::$app->settings->get('enableRecurringPayment', 'payment')): ?>
				<?= $form->field($model, 'enable_recurring_payment')->checkbox() ?>
			<?php endif; ?>
		</div>

		<?php if ($shouldRenderModal): ?>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal" style="padding: 15.5px 20px;"><?= Yii::t('common', 'Cancel') ?></button>
				<button type="submit" class="btn btn-primary" style="padding: 15.5px 20px;"><?= Yii::t('common', 'Save') ?></button>
			</div>
		<?php else: ?>
			<div class="form-actions floating">
				<?= Html::submitButton('<span class="fa fa-check"></span>', [
					'class' => 'btn btn-xlg btn-fab btn-default',
					'title' => Yii::t('common', 'Save'),
					'data' => [
						'toggle' => 'tooltip',
					],
				]) ?>
			</div>
		<?php endif; ?>
	</div>
<?php ActiveForm::end(); ?>
