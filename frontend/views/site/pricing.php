<?php
/* @var $this \yii\web\View */

use common\models\Feature;
use common\models\Package;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use common\widgets\datatable\DataTable;
use tws\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
use tws\widgets\carousel\Carousel;

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
			$packages = Package::findPaidPackages();
		?>
		<?php foreach ($packages as $package): ?>
			<?php
				$packageTranslation = $package->getTranslation();
				$packageFeatures = $package->getPackageFeatures()->indexBy('name')->all();
			?>
			<div class="carousel-item swiper-slide">
				<?php if ($package->type == Package::TYPE_STANDARD): ?>
					<div class="priceTableWrapper advancedSupport bg-white" data-pricing-package="true">
						<div class="priceTableTitle">
							<h2><?= $packageTranslation->name ?> <small><?= Yii::t('common', 'Billed') ?>: <?= $package->getFormattedBillingCycle() ?></small></h2>
						</div>
						<div class="priceAmount">
							<h2><?= number_format($package->price, 2, '.', '') ?><small><?= $package->currency ?></small></h2>
						</div>
						<div class="priceInfo">
							<?= $package->translation->content ?>
							<div class="priceBtn">
								<?php if (Yii::$app->user->isGuest): ?>
									<a class="btn btn-primary" href="<?= Url::to(['/site/signup', 'package_id' => Yii::$app->security->maskToken((string) $package->id)]) ?>"><?= Yii::t('common', 'Sign Up') ?></a>
								<?php elseif (Yii::$app->user->identity->subscriber): ?>
									<a class="btn btn-primary" href="<?= Url::to(['/account/payment/package', 'id' => Yii::$app->security->maskToken((string) $package->id)]) ?>"><?= Yii::t('common', 'Buy Now') ?></a>
								<?php else: ?>
									<a class="btn btn-primary" href="<?= Url::to(['/site/subscribe', 'package_id' => Yii::$app->security->maskToken((string) $package->id)]) ?>"><?= Yii::t('common', 'Subscribe') ?></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php elseif ($package->type == Package::TYPE_CUSTOM): ?>
					<!-- <div class="priceTableWrapper advancedSupport bg-white" data-pricing-package="true">
						<div class="priceTableTitle">
							<h2><?= $packageTranslation->name ?> <small><?= Yii::t('common', 'Billed') ?>: <?= Yii::t('common', 'Custom') ?></small></h2>
						</div>
						<div class="priceAmount">
							<h2><?= Yii::t('common', 'Custom') ?></h2>
						</div>
						<div class="priceInfo">
							<?= $package->translation->content ?>
							<div class="priceBtn">
								<a class="btn btn-primary" href="<?= Url::to(['/site/contact', 'request' => 'enterprise']) ?>"><?= Yii::t('common', 'Contact Us') ?></a>
							</div>
						</div>
					</div> -->
				<?php endif; ?>
			</div>
			<?php $i++; ?>
		<?php endforeach; ?>
		<?php Carousel::end(); ?>
	</div>
</section>
