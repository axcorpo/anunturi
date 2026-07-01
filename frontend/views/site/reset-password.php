<?php
/* @var $this yii\web\View */
/* @var $resetPasswordRequestModel common\models\ResetPasswordRequestForm */
/* @var $resetPasswordModel common\models\ResetPasswordForm */

use common\models\ResetPasswordForm;
use common\widgets\ActiveForm;
use yii\helpers\Html;
?>

<?php if ($title = $this->context->currentPage->translation->title): ?>
	<section class="clearfix pageTitleSection" style="background-image: url();">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<div class="pageTitle">
						<h1><?= $title ?></h1>
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

<section class="clearfix loginSection">
	<div class="container">
		<?php if ($resetPasswordModel->scenario == ResetPasswordForm::SCENARIO_PASSWORD): ?>
			<div class="row">
				<div class="center-block col-md-6">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($resetPasswordModel->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'panel panel-default',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
						<div class="panel-heading text-center">
							<div class="panel-title"><?= Yii::t('common', 'Set your new password') ?></div>
						</div>
						<div class="panel-body">
							<?= $form->field($resetPasswordModel, 'password')->passwordInput() ?>
							<?= $form->field($resetPasswordModel, 'workEmail', [
								'options' => [
									'class' => 'work-email',
								],
								'template' => '{input}',
                            ])->textarea(['required' => 'required'])->label(false) ?>
						</div>
                        <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                            <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                            <?= $form->field($resetPasswordModel, 'captchaResponse', [
                                'template' => '{input}',
                            ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                        <?php endif; ?>
						<div class="panel-footer text-center">
							<?= Html::submitButton(Yii::t('common', 'Reset Password'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
						</div>
					<?php ActiveForm::end(); ?>
				</div>
			</div>
		<?php else: ?>
			<div class="row">
				<div class="col-md-6">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($resetPasswordRequestModel->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'panel panel-default',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
						<div class="panel-heading text-center">
							<div class="panel-title"><?= Yii::t('common', 'Request a password reset code') ?></div>
						</div>
						<div class="panel-body">
							<?= $form->field($resetPasswordRequestModel, 'username')->textInput() ?>
                            <?= $form->field($resetPasswordRequestModel, 'workEmail', [
                                'options' => [
                                    'class' => 'work-email',
                                ],
                                'template' => '{input}',
                            ])->textarea(['required' => 'required'])->label(false) ?>
						</div>
                        <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                            <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                            <?= $form->field($resetPasswordRequestModel, 'captchaResponse', [
                                'template' => '{input}',
                            ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                        <?php endif; ?>
						<div class="panel-footer text-center">
							<?= Html::submitButton(Yii::t('common', 'Request Code'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
						</div>
					<?php ActiveForm::end(); ?>
				</div>
				<div class="col-md-6">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($resetPasswordModel->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'panel panel-default',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
						<div class="panel-heading text-center">
							<div class="panel-title"><?= Yii::t('common', 'Already have a password reset code?') ?></div>
						</div>
						<div class="panel-body">
							<?= $form->field($resetPasswordModel, 'token')->textInput() ?>
                            <?= $form->field($resetPasswordModel, 'workEmail', [
                                'options' => [
                                    'class' => 'work-email',
                                ],
                                'template' => '{input}',
                            ])->textarea(['required' => 'required'])->label(false) ?>
						</div>
                        <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                            <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                            <?= $form->field($resetPasswordModel, 'captchaResponse', [
                                'template' => '{input}',
                            ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                        <?php endif; ?>
						<div class="panel-footer text-center">
							<?= Html::submitButton(Yii::t('common', 'Validate Code'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
						</div>
					<?php ActiveForm::end(); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="text-center gap-t-sm">
			<p><?= Yii::t('common', 'Already have an account?') ?> <?= Html::a(Yii::t('common', 'Log In'), ['/site/login']) ?></p>
		</div>
	</div>
</section>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($resetPasswordModel, 'workEmail') . '").removeAttr("required");
	');
?>
<?php
$this->registerJs('
		$( "#' . Html::getInputId($resetPasswordRequestModel, 'workEmail') . '").removeAttr("required");
	');
?>
