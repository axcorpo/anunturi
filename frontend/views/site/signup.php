<?php
/* @var $this yii\web\View */
/* @var $model common\models\SignupForm */

use borales\extensions\phoneInput\PhoneInput;
use common\components\PhoneInputConfig;
use common\models\FeatureModule;
use common\models\Package;
use common\models\Feature;
use common\models\PackageModule;
use common\models\ScheduledTask;
use common\widgets\ActiveForm;
use tws\widgets\carousel\Carousel;
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

<section class="clearfix loginSection">
    <div class="container">
        <div class="row">
            <div class="center-block col-md-12 col-sm-12 col-xs-12">
                <?php $form = ActiveForm::begin([
                    'id' => mb_strtolower($model->formName()),
                    'options' => [
                        'novalidate' => true,
                        'class' => 'panel panel-default package-form',
                    ],
                    'validateOnType' => true,
                    'validateOnBlur' => true,
                ]); ?>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'last_name')->textInput() ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($model, 'first_name')->textInput() ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($model, 'email')->input('email') ?>
                            </div>
                            <div class="col-md-4">
                                <?= $form->field($model, 'phone')->widget(PhoneInput::class, [
                                    'jsOptions' => PhoneInputConfig::jsOptions(),
                                    'options' => PhoneInputConfig::inputClassOptions(),
                                ]) ?>
                            </div>
                            <div class="col-md-4">
                                <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'new-password']) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($model, 'acceptTerms')->checkbox([
                                    'uncheck' => null,
                                    'label' => Yii::t('common', 'I understand and agree to the {0} and {1} of {2}.', [
                                        Html::a(Yii::t('common', 'Terms and Conditions'), ['/site/terms-and-conditions'], ['target' => '_blank']),
                                        Html::a(Yii::t('common', 'Privacy Policy'), ['/site/privacy-policy'], ['target' => '_blank']),
                                        Yii::$app->name
                                    ]),
                                ]) ?>
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
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-center">
                        <?= Html::submitButton(Yii::t('common', 'Sign Up'), ['class' => 'btn btn-block btn-primary btn-slide-right']) ?>
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
                <div class="text-center">
                    <p><?= Yii::t('common', 'Already have an account?') ?> <?= Html::a(Yii::t('common', 'Log In'), ['/site/login'], ['class' => 'link']) ?></p>
                    <p><?= Html::a(Yii::t('common', 'Forgot Password?'), ['/site/reset-password'], ['class' => 'link']) ?></p>
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