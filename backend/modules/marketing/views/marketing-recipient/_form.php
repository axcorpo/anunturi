<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\MarketingRecipient */

use borales\extensions\phoneInput\PhoneInput;
use common\components\PhoneInputConfig;
use common\models\MarketingGroup;
use common\models\MarketingRecipient;
use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use tws\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

?>

<?php $form = ActiveForm::begin([
	'id' => 'marketing-recipient-form',
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
                <?= $form->field($model, 'status')->widget(Select2::class, [
                    'data' => ArrayHelper::getColumn(MarketingRecipient::getStatusLabels(), 'label'),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'name')->textInput() ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'last_name')->textInput() ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'first_name')->textInput() ?>
            </div>
        </div>
		<div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'email')->input('email') ?>
            </div>
			<div class="col-sm-6">
				<?= $form->field($model, 'phone')->widget(PhoneInput::class, [
					'jsOptions' => PhoneInputConfig::jsOptions(),
					'options' => PhoneInputConfig::inputClassOptions(),
				]) ?>
			</div>
		</div>
        <div class="row">
            <div class="col-sm-12">
                <?= $form->field($model, 'marketing_group_id')->widget(Select2::class, [
                    'options' => [
                        'multiple' => true,
                    ],
                    'data' => ArrayHelper::map(MarketingGroup::findAllMarketingGroups(), 'id', 'translation.name'),
                    'maintainOrder' => false,
                    'showToggleAll' => true,
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
