<?php
/* @var $this yii\web\View */
/* @var $model common\models\LoginForm */

use common\widgets\ActiveForm;
use yii\authclient\widgets\AuthChoice;
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
						<?= $form->field($model, 'username')->input('email') ?>
						<?= $form->field($model, 'password')->passwordInput() ?>
						<div class="clearfix form-group">
							<?= $form->field($model, 'rememberMe', [
								'options' => [
									'class' => 'pull-left',
								],
							])->checkbox() ?>
							<?= Html::a(Yii::t('common', 'Forgot Password?'), ['/site/reset-password'], ['class' => 'pull-right link']) ?>
						</div>
					</div>
                   <?= $form->field($model, 'workEmail', [
                    'options' => [
                        'class' => 'work-email',
                    ],
                    'template' => '{input}',
                ])->textarea(['required' => 'required'])->label(false) ?>
                    <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                        <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                        <?= $form->field($model, 'captchaResponse', [
                            'template' => '{input}',
                        ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                    <?php endif; ?>
					<div class="panel-footer text-center">
						<?= Html::submitButton(Yii::t('common', 'Log In'), ['class' => 'btn btn-block btn-primary btn-slide-right']) ?>
                        <?php $authChoice = AuthChoice::begin(['baseAuthUrl' => ['site/auth'], 'autoRender' => false]); ?>
						<?php if ($clients = $authChoice->getClients()): ?>
	                        <div style="margin: 15px 0;"><?= Yii::t('frontend', 'Or') ?></div>
	                        <?php foreach ($clients as $client): ?>
	                            <?= Html::a( '<i class="fa fa-' . $client->name . '" style="background-color:#fff; color: ' . str_replace(['google', 'facebook'], ['#d93025', '#1877f2'], $client->name) . '; padding: 5px ' . str_replace(['google', 'facebook'], ['8', '10'], $client->name) . 'px; border-radius: 0.1875em; margin-right: 10px;"></i>' . Yii::t('common', 'Log In With') . ' ' . $client->title, ['site/auth', 'authclient'=> $client->name, ], ['class' => 'btn btn-block btn-slide-right btn-'. $client->name]) ?>
	                        <?php endforeach; ?>
						<?php endif; ?>
                        <?php AuthChoice::end(); ?>
                    </div>
					<?php ActiveForm::end(); ?>
				</div>
				<div class="text-center">
					<p><?= Yii::t('common', 'Don\'t have an account yet?') ?> <?= Html::a(Yii::t('common', 'Sign Up'), ['/site/signup'], ['class' => 'link']) ?></p>
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