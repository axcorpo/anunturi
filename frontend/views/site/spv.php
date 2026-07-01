<?php
/* @var $this yii\web\View */

use common\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = Html::encode($this->title);
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

<?php if ($content = $this->context->currentPage->content): ?>
	<section class="clearfix loginSection">
		<div class="container">
			<?php if (Yii::$app->user->identity->authAssignment->item_name === 'superAdmin'): ?>
				<?php if (empty($integration)): ?>
					<div class="row" style="margin-bottom: 30px;">
						<div class="col-md-12">
							<?= $this->context->currentPage->content ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<?php $form = ActiveForm::begin([
								'action' =>['/spv'],
								'id' => 'spv-form-authorize',
								'options' => [
									'novalidate' => true,
									'class' => 'panel panel-default',
								],
								'validateOnType' => true,
								'validateOnBlur' => false,
							]); ?>
							<div class="panel-heading text-center" style="padding: 18px; height: auto;">
								<h2 class="panel-title text-uppercase"><?= Yii::t('frontend', 'I have access with a digital certificate') ?></h2>
							</div>
							<div class="panel-body">
								<p>
									<?= Yii::t('frontend', 'Choose this option if you have a digital certificate and access to S.P.V. for this company.') ?>
								</p>
							</div>
							<div class="panel-footer">
								<?= Html::a($anchor, $link, ['class' => 'btn btn-block btn-primary btn-slide-right', 'style' => 'padding: 18px 30px;']) ?>
							</div>
							<?php ActiveForm::end() ?>
						</div>
						<div class="col-md-6">
							<?php $form = ActiveForm::begin([
								'options' => [
									'novalidate' => true,
									'class' => 'panel panel-default',
								],
								'validateOnType' => true,
								'validateOnBlur' => false,
							]); ?>
							<div class="panel-heading text-center" style="padding: 18px; height: auto;">
								<h2 class="panel-title text-uppercase"><?= Yii::t('frontend', 'Obtain access through the accountant') ?></h2>
							</div>
							<div class="panel-body">
								<p>
									<?= Yii::t('frontend', 'Copy and send the link below to your accountant so they can authorize your {item} account in S.P.V.', ['item' => Yii::$app->name]) ?>
								</p>
							</div>
							<div class="panel-footer">
								<div class="input-group">
								 <span id="copyButton" class="input-group-addon btn-default" style="padding: 15px 20px; color: #000000;" title="<?= Yii::t('common', 'Click To Copy') ?>">
							      <i class="fa fa-clipboard" aria-hidden="true"></i>
							    </span>
									<input type="text" id="copyTarget" class="form-control" value="<?= $link ?>">
									<span class="copied"><?= Yii::t('common', 'Copied') ?></span>
								</div>
							</div>
							<?php ActiveForm::end() ?>
						</div>
					</div>
				<?php else: ?>
					<div class="alert alert-success alert-icon gap-t-md" role="alert"><?= Yii::t('common', 'The authorization of account access for {item} in S.P.V. is completed.', ['item' => Yii::$app->name]) ?></div>
					<div class="text-center">
						<a class="btn btn-lg btn-default btn-slide-up gap-t-lg" href="<?= Url::to(['/site/index']) ?>"><?= Yii::t('frontend', 'Go to Home Page') ?></a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php
$this->registerJs('
	(function() {
		"use strict";
		function copyToClipboard(elem) {
			var target = elem;
			// select the content
			var currentFocus = document.activeElement;
			target.focus();
			target.setSelectionRange(0, target.value.length);
			// copy the selection
			var succeed;
			try {
				succeed = document.execCommand("copy");
			} catch (e) {
				console.warn(e);
				succeed = false;
			}
			// Restore original focus
			if (currentFocus && typeof currentFocus.focus === "function") {
				currentFocus.focus();
			}
			if (succeed) {
				$(".copied").animate({ top: -25, opacity: 1 }, 700, function() {
					$(this).css({ top: 0, opacity: 0 });
				});
			}
			return succeed;
		}
		$("#copyButton, #copyTarget").on("click", function() {
			copyToClipboard(document.getElementById("copyTarget"));
		});
	})();
');
?>
