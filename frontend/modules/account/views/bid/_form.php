<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Bid */

use backend\widgets\ActiveForm;
use common\models\Bid;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => 'bid-form',
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
				<div class="col-sm-12">
					<?php $bid = Bid::findLastBid($model->auction->id); ?>
					<?php $curencyField = $form->field($model, 'currency', [
						'options' => [
							'class' => 'col-xs-6',
						],
						'template' => '{input}',
					])->widget(Select2::class, [
						'data' => ArrayHelper::map(\common\models\Currency::findAllCurrencies(), 'iso_code', 'formattedName'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
					<?= $form->field($model, 'price', [
						'options' => [
							'class' => 'form-group required',
						],
						'template' => '{label}<div class="row row-no-gutters row-custom"><div class="col-xs-6">{input}</div>' . $curencyField . '</div>{error}{hint}',
					])->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => $bid->price ? ((double)$bid->price + (double)$model->auction->step) : ($model->auction->start_price ? (double)$model->auction->start_price : 1),
							'max' => PHP_INT_MAX,
							'step' => $model->auction->step ?: 1,
							'decimals' => 2,
							'boostat' => 5,
							'maxboostedstep' => 10,
							'verticalbuttons' => true,
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
