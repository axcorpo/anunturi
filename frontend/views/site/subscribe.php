<?php
/* @var $this yii\web\View */
/* @var $model common\models\SubscribeForm */

use common\models\Feature;
use common\models\Package;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use common\widgets\ActiveForm;
use tws\widgets\carousel\Carousel;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<section class="clearfix pageTitleSection" style="background-image: url();">
	<div class="container">
		<div class="row">
			<div class="col-xs-12">
				<div class="pageTitle">
					<h2><?= $this->context->currentPage->translation->title ?></h2>
				</div>
			</div>
		</div>
	</div>
</section>

<?php if (!empty($this->params['breadcrumbs'])) : ?>
	<section class="clearfix">
		<div class="container">
			<div class="section dashboard-breadcrumb-section">
				<div class="row">
					<div class="col-xs-12">
						<?= Breadcrumbs::widget([
							'options' => [
								'class' => 'breadcrumb breadcrumb-list',
							],
							'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
						]) ?>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ($content = $this->context->currentPage->translation->content): ?>
	<section class="clearfix">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<?= $this->context->currentPage->translation->content ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="clearfix loginSection">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-xs-12">

				<?php $form = ActiveForm::begin([
					'id' => mb_strtolower($model->formName()),
					'options' => [
						'novalidate' => true,
					],
					'validateOnType' => true,
					'validateOnBlur' => true,
				]); ?>
				<div class="form-group field-<?= Html::getInputId($model, 'package_id') ?> required" role="radiogroup" aria-required="true">
					<label class="control-label"><?= Yii::t('common', 'Choose a package') ?></label>
					<?php Carousel::begin([
						'options' => [
							'class' => 'carousel-pricing',
						],
						'pagination' => false,
						'navigation' => false,
						'scrollbar' => false,
						'clientOptions' => [
							'autoplay' => [
								'delay' => 7000,
							],
							'speed' => 1000,
							'effect' => 'slide',
							'slidesPerView' => 1,
							'spaceBetween' => 15,
							'breakpointsInverse' => true,
							'breakpoints' => [
								650 => [
									'slidesPerView' => 2,
								],
								992 => [
									'slidesPerView' => 3,
								],
								1200 => [
									'slidesPerView' => 3,
								],
							],
						],
					]); ?>
					<?php
					$i = 0;
					$featureModuleLabels = FeatureModule::getModuleLabels();
					$featureLabels = Feature::getFeatureLabels();
					$packages = Package::findPackagesByType([Package::TYPE_STANDARD]);
					?>
					<?php foreach ($packages as $package): ?>
				<?php
				$packageTranslation = $package->getTranslation();
				/** @var PackageFeature[] $packageFeatures */
				$packageFeatures = $package->getPackageFeatures()->indexBy('name')->all();
				?>
					<div class="carousel-item swiper-slide">
						<div class="priceTableWrapper advancedSupport bg-white <?= $package->id == $model->package_id ? 'active' : '' ?>" data-pricing-package="true">
							<?php if ($package->type == Package::TYPE_STANDARD): ?>
							<div class="priceTableTitle">
								<h2><?= $packageTranslation->name ?> <small><?= Yii::t('common', 'Billed') ?>: <?= $package->getFormattedBillingCycle() ?></small></h2>
							</div>
							<div class="priceAmount">
								<h2><?= number_format($package->price, 2, '.', '') ?><small><?= $package->currency ?></small></h2>
							</div>
							<div class="priceInfo">
								<?= $packageTranslation->content ?>
								<?php elseif ($package->type == Package::TYPE_CUSTOM): ?>
								<div class="priceTableTitle">
									<h2><?= $packageTranslation->name ?> <small><?= Yii::t('common', 'Billed') ?>: <?= Yii::t('common', 'Custom') ?></small></h2>
								</div>
								<div class="priceAmount">
									<h2><?= Yii::t('common', 'Custom') ?></h2>
								</div>
								<div class="priceInfo">
									<?= $packageTranslation->content ?>
									<ul class="priceShorting">
										<?php if (Yii::$app->user->isGuest && $package->trial_period): ?>
											<li class="active"><i class="fa fa-check-square"></i><p><?= Yii::t('common', 'Trial Period') ?>: <?= $package->getFormattedTrialPeriod() ?></p></li>
										<?php endif; ?>
										<li class="active"><i class="fa fa-check-square"></i><p><?= $featureLabels[Feature::ANNOUNCEMENTS] ?>: <?= Yii::t('common', 'Custom') ?></p></li>
									</ul>
									<?php endif; ?>
									<div class="priceBtn">
										<span class="btn btn-primary"><?= Yii::t('common', 'Choose') ?></span>
										<div class="hidden">
											<?= $form->field($model, 'package_id', [
												'options' => [
													'tag' => false,
												],
												'selectors' => [
													'container' => '.field-' . Html::getInputId($model, 'package_id'),
													'input' => '.input-' . Html::getInputId($model, 'package_id'),
													'error' => '.error-' . Html::getInputId($model, 'package_id'),
												],
											])->radio([
												'id' => null,
												'class' => 'input-' . Html::getInputId($model, 'package_id'),
												'value' => $package->id,
												'data' => [
													'custom' => $package->type == Package::TYPE_CUSTOM ? 'true' : 'false',
												],
												'uncheck' => null,
												'label' => '',
												'labelOptions' => [
													'class' => 'hidden',
												],
											]) ?>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php $i++; ?>
						<?php endforeach; ?>
						<?php Carousel::end(); ?>
						<div class="error-<?= Html::getInputId($model, 'package_id') ?> help-block help-block-error gap-t-sm"></div>
					</div>
					<?= $form->field($model, 'custom_package_requirements', [
						'options' => [
							'id' => 'custom-package-fields-container',
							'class' => 'required gap-b-lg' . ($model->getPackage()->type == Package::TYPE_CUSTOM ? '' : ' hidden'),
						],
					])->textarea([
						'rows' => 3,
						'placeholder' => Yii::t('common', 'Example') . ': 15 ' . mb_strtolower(Yii::t('common', 'Announcements'))
					]) ?>
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
					<div class="text-center">
						<?= Html::submitButton(Yii::t('common', 'Subscribe'), ['class' => 'btn btn-primary']) ?>
					</div>
				<?php ActiveForm::end(); ?>
			</div>
		</div>
	</div>
</section>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>
