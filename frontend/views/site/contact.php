<?php
/* @var $this yii\web\View */
/* @var $model frontend\models\ContactForm */
/* @var $contact array */
/* @var $address string */
/* @var $map dosamigos\google\maps\Map */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<?php if ($title = $this->context->currentPage->translation->title): ?>
	<!-- PAGE TITLE SECTION -->
	<section class="clearfix pageTitleSection bg-image" style="background-image: url('<?= Yii::getAlias('@web/img/background/bg-page-title.jpg') ?>');">
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

<?php if ($breadcrumbs = $this->params['breadcrumbs']): ?>
	<section class="clearfix">
		<div class="container">
			<div class="section dashboard-breadcrumb-section">
				<div class="row">
					<div class="col-xs-12">
						<?php
						if (!empty($breadcrumbs)) {
							$lastBreadcrumb = array_pop($breadcrumbs);
							$lastBreadcrumb = Html::tag('h1', $lastBreadcrumb, ['class' => 'page-title']);
							$breadcrumbs[] = $lastBreadcrumb;
						}
						?>
						<?= Breadcrumbs::widget([
							'options' => [
								'class' => 'breadcrumb breadcrumb-list',
							],
							'encodeLabels' => false,
							'links' => !empty($breadcrumbs) ? $breadcrumbs : [],
						]) ?>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- CONTACT SECTION -->
<section class="clearfix">
	<div class="container">
		<div class="row contact-page-content">
			<div class="col-sm-8 col-xs-12">
				<?php if ($content = $this->context->currentPage->translation->content): ?>
					<div class="priceTableTitle">
						<?= $content ?>
					</div>
				<?php endif; ?>
				<?php $form = ActiveForm::begin([
					'id' => mb_strtolower($model->formName()),
					'options' => [
						'class' => 'contact-form',
					],
				]); ?>
				<?= $form->field($model, 'name')->textInput() ?>
				<div class="row">
					<div class="col-md-6">
						<?= $form->field($model, 'email')->input('email') ?>
					</div>
					<div class="col-md-6">
						<?= $form->field($model, 'phone')->input('tel') ?>
					</div>
				</div>
				<?= $form->field($model, 'subject')->textInput() ?>
				<?= $form->field($model, 'message')->textarea(['rows' => 7]) ?>
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
				<div>
					<span class="pull-right gap-t-xs gap-b-xs"><span class="color-danger">*</span> <?= Yii::t('common', 'Required Fields') ?></span>
					<?= Html::submitButton(Yii::t('common', 'Submit'), [
						'class' => 'btn btn-primary',
						'name' => 'contact-button',
					]) ?>
				</div>
				<?php ActiveForm::end(); ?>
			</div>
			<div class="col-sm-4 col-xs-12">
				<div class="contactInfo">
					<ul class="list-unstyled list-address">
						<?php if ($contact['company']): ?>
							<li>
								<i class="fa fa-building" aria-hidden="true"></i>
								<?= $contact['company'] ?>
								<br>
								<?php if ($contact['tin']): ?>
									<?= Yii::t('label', 'Taxpayer Identification Number') ?>:
									<?= $contact['tin'] ?>
									<br>
								<?php endif; ?>
								<?php if ($contact['registrationNumber']): ?>
									<?= Yii::t('label', 'Registration Number') ?>:
									<?= $contact['registrationNumber'] ?>
								<?php endif; ?>
							</li>
						<?php endif; ?>
						<?php if ($address): ?>
							<li>
								<i class="fa fa-map-signs" aria-hidden="true"></i>
								<?php if ($contact['latitude'] && $contact['longitude']): ?>
									<a href="//www.google.com/maps/place/<?= $contact['latitude'] ?>,<?= $contact['longitude'] ?>" target="_blank" aria-hidden="true"><?= $address ?></a>
								<?php else: ?>
									<?= $address ?>
								<?php endif; ?>
							</li>
						<?php endif; ?>
						<?php if ($contact['schedule'][Yii::$app->language]): ?>
							<li>
								<i class="fa fa-clock-o" aria-hidden="true"></i>
								<?= nl2br($contact['schedule'][Yii::$app->language]) ?>
							</li>
						<?php endif; ?>
						<?php if ($contact['mobilePhone'] || $contact['fixedPhone']): ?>
							<li>
								<i class="fa fa-phone" aria-hidden="true"></i>
								<?= implode(', ', array_filter([
									$contact['mobilePhone'] ? Html::a($contact['mobilePhone'], "tel:{$contact['mobilePhone']}", ['class' => 'link-underline', 'data-prevent-page-overlay' => 'true']) : null,
									$contact['fixedPhone'] ? Html::a($contact['fixedPhone'], "tel:{$contact['fixedPhone']}", ['class' => 'link-underline', 'data-prevent-page-overlay' => 'true']) : null,
								])) ?>
							</li>
						<?php endif; ?>
						<?php if ($contact['email']): ?>
							<li>
								<i class="fa fa-envelope" aria-hidden="true"></i>
								<a href="mailto:<?= $contact['email'] ?>" aria-hidden="true"><i class="fa fa-envelope"></i> <?= $contact['email'] ?></a>
							</li>
						<?php endif; ?>
					</ul>
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

<?php if ($map): ?>
	<?= $map->display() ?>
<?php endif; ?>
