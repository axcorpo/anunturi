<?php
/* @var $this yii\web\View */
/* @var $model \frontend\modules\account\models\PaymentRequest */

use common\models\PaymentMetadata;
use tws\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

$this->title = Yii::t('frontend', 'Payment Request');
$this->params['breadcrumbs'][] = Html::encode($this->title);

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
				<?php if ($model->hasErrors()): ?>
					<header class="section-header text-center">
						<h1 class="section-heading color-danger font-md"><?= Yii::t('frontend', 'Payment cannot be initiated.') ?></h1>
					</header>
					<div class="gap-b-md">
						<p class="color-grey text-center"><?= Yii::t('frontend', 'What happened?') ?></p>
						<div class="row">
							<div class="col-md-offset-1 col-md-10 col-lg-offset-2 col-lg-8">
								<?= Html::errorSummary($model, [
									'header' => false,
									'class' => 'error-summary alert alert-danger alert-icon',
								]) ?>
							</div>
						</div>
					</div>
					<div class="text-center">
						<p class="color-grey gap-b-md"><?= Yii::t('frontend', 'What can you do now?') ?></p>
						<ul class="list-inline">
							<li class="inline-block-sm">
								<a class="btn btn-default btn-outline btn-slide-right" href="<?= Url::canonical() ?>"><?= Yii::t('frontend', 'Try Again') ?></a>
							</li>
							<li><?= mb_strtolower(Yii::t('common', 'Or')) ?></li>
							<li class="inline-block-sm">
								<a class="btn btn-default btn-outline btn-slide-right" href="<?= Url::to(['/site/contact']) ?>"><?= Yii::t('frontend', 'Contact Us') ?></a>
							</li>
						</ul>
					</div>
				<?php else: ?>
					<header class="section-header text-center gap-b-0">
						<h1 class="section-heading color-primary font-md"><?= Yii::t('frontend', 'Payment via {0}', [$activePaymentProcessors[$model->payment_processor]]) ?></h1>
                        <span class="loader"><span class="loader-inner"></span></span>
					</header>
					<?= $model->getPaymentForm() ?>
					<?php $this->registerJs('$("#payment-request-form").submit();'); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
