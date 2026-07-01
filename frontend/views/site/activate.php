<?php
/* @var $this yii\web\View */
/* @var $model common\models\ActivateAccountForm */

use common\widgets\ActiveForm;
use yii\helpers\Html;
?>

<?php if ($title = $this->context->currentPage->translation->title): ?>
	<section class="clearfix pageTitleSection" style="background-image: url();">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<div class="pageTitle">
						<h2><?= $title ?></h2>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ($content = $this->context->currentPage->content): ?>
	<section class="clearfix">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<?= $content ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- LOGIN SECTION -->
<section class="clearfix loginSection">
	<div class="container">
		<div class="row">
			<div class="center-block col-md-5 col-sm-6 col-xs-12">
				<div class="panel panel-default">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($model->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'loginForm',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
					<div class="panel-body">
						<?= $form->field($model, 'token')->textInput() ?>
						<?= $form->field($model, 'workEmail', [
							'options' => [
								'class' => 'work-email',
							],
							'template' => '{input}',
                        ])->textarea(['required' => 'required'])->label(false) ?>
					</div>
                    <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                        <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                        <?= $form->field($model, 'captchaResponse', [
                            'template' => '{input}',
                        ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                    <?php endif; ?>
					<div class="panel-footer text-center">
						<?= Html::submitButton(Yii::t('common', 'Activate'), ['class' => 'btn btn-block btn-primary btn-slide-right']) ?>
					</div>
					<?php ActiveForm::end(); ?>
				</div>
				<div class="text-center">
					<p><?= Yii::t('common', 'Already have an account?') ?> <?= Html::a(Yii::t('common', 'Log In'), ['/site/login'], ['class' => 'link']) ?></p>
				</div>
			</div>
		</div>
	</div>
</section>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>
