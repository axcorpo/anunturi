<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Bid */

use common\helpers\UrlHelper;
use common\models\Broker;
use common\models\Auction;
use common\models\Currency;
use common\models\Bid;
use backend\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\number\NumberControl;
use kartik\select2\Select2;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

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
				<div class="col-sm-3">
					<?= $form->field($model, 'status')->widget(Select2::class, [
						'data' => ArrayHelper::getColumn(Bid::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'auction_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(Auction::find()->active()->deleted(false)->all(), 'id', function (Auction $model) {
							return implode(' - ',[$model->id, Auction::getPositionLabels()[$model->position]]);
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
					<?= $form->field($model, 'broker_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(Broker::findAllBrokers(), 'id', function ($model) {
							return $model->user->fullName;
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
					<?php $currencyField = $form->field($model, 'currency')->widget(Select2::class, [
						'data' => ArrayHelper::map(\common\models\Currency::findAllCurrencies(), 'iso_code', 'formattedName'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Currency'),
						],
					])->label(false) ?>
					<?= $form->field($model, 'price', [
						'template' => '{label}<div class="input-group">{input}<div class="input-group-btn with-control" style="min-width: 150px;">' . $currencyField . '</div></div>{hint}{error}',
					])->widget(NumberControl::class, [
						'maskedInputOptions' => [
							'groupSeparator' => '',
							'radixPoint' => '.',
							'allowMinus' => false,
						],
						'displayOptions' => [
							'autocomplete' => 'off',
							'placeholder' => '0.00',
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
