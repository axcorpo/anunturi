<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Auction */

use common\models\Category;
use common\models\County;
use common\models\Currency;
use common\models\Bid;
use common\models\Auction;
use common\models\User;
use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => 'auction-form',
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
						'data' => ArrayHelper::getColumn(Auction::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'parent_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(Auction::find()->active(true)->deleted(false)->andWhere(['IS', 'parent_id', null])->all(), 'id', function (Auction $model) {
							return implode(' - ', [$model->id, Auction::getPositionLabels()[$model->position]]);
						}),
						'maintainOrder' => false,
						'showToggleAll' => false,
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'position')->widget(Select2::class, [
						'data' => Auction::getPositionLabels(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'county_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(County::find()->active(true)->deleted(false)->all(), 'id', function (County $model) {
							return $model->name;
						}),
						'maintainOrder' => false,
						'showToggleAll' => false,
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'category_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(Category::find()->active(true)->deleted(false)->all(), 'id', 'treeName'),
						'maintainOrder' => false,
						'showToggleAll' => false,
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'period')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 0,
							'max' => PHP_INT_MAX,
							'step' => 1,
							'decimals' => 0,
							'boostat' => 5,
							'maxboostedstep' => 10,
							'verticalbuttons' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'cycle')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => Auction::getCycleLabels(),
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
				<div class="col-sm-3">
					<?= $form->field($model, 'start_at')->widget(DateTimePicker::class, [
						'id' => 'dp-' . Html::getInputId($model, 'start_at'),
						'options' => [
							'value' => Yii::$app->formatter->asDate($model->start_at),
						],
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
							'ignoreReadonly' => true,
							'showTodayButton' => true,
							'showClear' => true,
							'showClose' => true,
							'allowInputToggle' => true,
							'useCurrent' => false,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'end_at')->widget(DateTimePicker::class, [
						'id' => 'dp-' . Html::getInputId($model, 'end_at'),
						'options' => [
							'value' => Yii::$app->formatter->asDate($model->end_at),
						],
						'linkedTo' => 'dp-' . Html::getInputId($model, 'start_at'),
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
							'minDate' => $model->start_at ? (new DateTime($model->start_at))->format(DATE_ATOM) : false,
							'ignoreReadonly' => true,
							'showTodayButton' => true,
							'showClear' => true,
							'showClose' => true,
							'allowInputToggle' => true,
							'useCurrent' => false,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'valid_from')->widget(DateTimePicker::class, [
						'id' => 'dp-' . Html::getInputId($model, 'valid_from'),
						'options' => [
							'value' => Yii::$app->formatter->asDate($model->valid_from),
						],
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
							'ignoreReadonly' => true,
							'showTodayButton' => true,
							'showClear' => true,
							'showClose' => true,
							'allowInputToggle' => true,
							'useCurrent' => false,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'valid_to')->widget(DateTimePicker::class, [
						'id' => 'dp-' . Html::getInputId($model, 'valid_to'),
						'options' => [
							'value' => Yii::$app->formatter->asDate($model->valid_to),
						],
						'linkedTo' => 'dp-' . Html::getInputId($model, 'valid_from'),
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
							'minDate' => $model->valid_from ? (new DateTime($model->valid_from))->format(DATE_ATOM) : false,
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
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'start_price')->widget(TouchSpin::class, [
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
				<div class="col-sm-4">
					<?= $form->field($model, 'step')->widget(TouchSpin::class, [
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
				<div class="col-sm-4">
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
<?php ActiveForm::end(); ?>
