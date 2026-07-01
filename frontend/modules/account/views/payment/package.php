<?php
/* @var $this yii\web\View */
/* @var $model frontend\modules\account\models\PaymentPackageForm */

use common\models\Feature;
use common\models\Package;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\PaymentMetadata;
use common\models\ScheduledTask;
use common\widgets\ActiveForm;
use tws\widgets\carousel\Carousel;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

$this->title = Yii::t('frontend', 'Payment for {item}', ['item' => Yii::t('common', 'Package')]);
$this->params['breadcrumbs'][] = Html::encode($this->title);
\frontend\modules\account\assets\PaymentAsset::register($this);

$featureModuleLabels = FeatureModule::getModuleLabels();
$featureLabels = Feature::getFeatureLabels();
$paymentSettings = Yii::$app->settings->getCategory('payment');
$activePaymentMethods = array_intersect_key(PaymentMetadata::getPaymentMethodLabels(), (array) $paymentSettings['paymentMethods']);
$activePaymentProcessors = array_intersect_key(PaymentMetadata::getPaymentProcessorLabels(), (array) $paymentSettings['paymentProcessors'][PaymentMetadata::PAYMENT_METHOD_CARD]);
?>

<?php if (!empty($this->params['breadcrumbs'])) : ?>
	<!-- Dashboard breadcrumb section -->
	<div class="section dashboard-breadcrumb-section bg-dark">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<h2><?= Html::encode($this->title) ?></h2>
					<?= Breadcrumbs::widget([
						'options' => [
							'class' => 'breadcrumb',
						],
						'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
					]) ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ($content = $this->context->currentPage->translation->content): ?>
	<section class="clearfix bg-dark">
		<div class="container">
			<div class="row">
				<?= $content ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="clearfix bg-dark dashboard-review-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-xs-12 dashboardBoxBg">

				<?php $form = ActiveForm::begin([
					'id' => 'payment-form',
					'options' => [
						'novalidate' => true,
					],
					'validateOnType' => true,
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
					$packages = Package::findPackagesByType([Package::TYPE_STANDARD, Package::TYPE_CUSTOM]);
					?>
					<?php foreach ($packages as $package): ?>
						<?php
                            $packageTranslation = $package->getTranslation();
                            /** @var PackageFeature[] $packageFeatures */
                            $packageFeatures = \yii\helpers\ArrayHelper::getColumn($package->getPackageFeatures()->indexBy('name')->all(), 'value');
						?>
                        <?php if (empty(Yii::$app->request->get('feature')) || ($packageFeatures[Yii::$app->request->get('feature')])): ?>
                            <div class="carousel-item swiper-slide">
                                <?php if ($package->type == Package::TYPE_STANDARD): ?>
                                    <div class="priceTableWrapper advancedSupport bg-white <?= $package->id == $model->package_id ? 'active' : '' ?>" data-package="<?= $package->id ?>">
                                        <div class="priceTableTitle">
                                            <h2><?= $packageTranslation->name ?> <small><?= Yii::t('common', 'Billed') ?>: <?= $package->getFormattedBillingCycle() ?></small></h2>
                                        </div>
                                        <div class="priceAmount">
                                            <h2><?= number_format($package->price, 2, '.', '') ?><small><?= $package->currency ?></small></h2>
                                        </div>
                                        <div class="priceInfo">
                                            <?= $package->translation->content ?>
                                            <div class="priceBtn" data-package-price="<?= Yii::$app->formatter->asCurrency($package->price, $package->currency) ?>">
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
                                                            'name' => $packageTranslation->name,
                                                            'price' => $package->price,
                                                            'currency' => $package->currency,
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
                                <?php elseif ($package->type == Package::TYPE_CUSTOM): ?>
                                    <!-- <div class="priceTableWrapper advancedSupport bg-white <?= $package->id == $model->package_id ? 'active' : '' ?>" data-pricing-package="true">
                                        <div class="priceTableTitle">
                                            <h2><?= $packageTranslation->name ?> <small><?= Yii::t('common', 'Billed') ?>: <?= Yii::t('common', 'Custom') ?></small></h2>
                                        </div>
                                        <div class="priceAmount">
                                            <h2><?= Yii::t('common', 'Custom') ?></h2>
                                        </div>
                                        <div class="priceInfo">
                                            <?= $package->translation->content ?>
                                            <div class="priceBtn">
                                                <?= Html::a(Yii::t('common', 'Contact Us'), ['/site/contact', 'request' => 'enterprise'], ['class' => 'btn btn-block btn-default btn-outline btn-slide-right']) ?>
                                            </div>
                                        </div>
                                    </div> -->
                                <?php endif; ?>
                            </div>
                            <?php $i++; ?>
                        <?php endif; ?>
					<?php endforeach; ?>
					<?php Carousel::end(); ?>
					<div class="error-<?= Html::getInputId($model, 'package_id') ?> help-block help-block-error"></div>
				</div>

				<?= $this->render('_shared-form-fields', [
					'form' => $form,
					'model' => $model,
				]) ?>
				<div class="text-center">
                    <?php if (Yii::$app->request->get('announcement')): ?>
                        <?= $form->field($model, 'announcement')->hiddenInput()->label(false); ?>
                    <?php endif; ?>
                </div>
                <?= $form->field($model, 'acceptTerms')->checkbox([
                    'uncheck' => null,
                    'label' => Yii::t('common', 'I understand and agree to the {0} and {1} of {2}.', [
                        Html::a(Yii::t('common', 'Terms and Conditions'), ['/site/terms-and-conditions'], ['target' => '_blank']),
                        Html::a(Yii::t('common', 'Privacy Policy'), ['/site/privacy-policy'], ['target' => '_blank']),
                        Yii::$app->name
                    ]),
                ]) ?>
                <div class="text-center">
					<button type="submit" class="btn btn-lg btn-primary btn-slide-right" style="padding: 25.5px 30px;" id="btn-submit-payment">
						<span class="btn-submit-label-payment <?= $model->payment_method == PaymentMetadata::PAYMENT_METHOD_CARD ? '' : 'hidden' ?>">
							<?= Yii::t('common', 'Pay') ?>
							<?php if ($package = $model->getPackage()): ?>
								<span class="btn-submit-amount"><?= Yii::$app->formatter->asCurrency($package->price, $package->currency) ?></span>
							<?php endif; ?>
						</span>
						<?php if (array_key_exists(PaymentMetadata::PAYMENT_METHOD_BANK, $activePaymentMethods)): ?>
							<span class="btn-submit-label-submit <?= $model->payment_method != PaymentMetadata::PAYMENT_METHOD_CARD ? '' : 'hidden' ?>"><?= Yii::t('common', 'Submit') ?></span>
						<?php endif; ?>
					</button>
				</div>
			</div>
			<?php ActiveForm::end(); ?>
		</div>
	</div>
</section>
