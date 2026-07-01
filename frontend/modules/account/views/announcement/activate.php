<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Announcement */

use common\models\Feature;
use backend\widgets\ActiveForm;
use common\models\Subscription;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$subscriptions = Subscription::findAvailableAnnouncementSubscriptions(Yii::$app->user->identity->subscriber->id);
?>

<?php $form = ActiveForm::begin([
	'id' => 'activate-form',
	'options' => [
		'novalidate' => true,
		'class' => Yii::$app->request->isAjax ? 'modal-dialog size-normal' : '',
	],
	'validateOnType' => true,
]); ?>

<div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
	<?php if (Yii::$app->request->isAjax) : ?>
		<div class="modal-header bootstrap-dialog-header btn-warning">
			<div class="bootstrap-dialog-header">
				<div class="bootstrap-dialog-close-button" style="display: none;">
					<button class="btn-modal-close btn btn-warning" data-dismiss="modal" data-bs-dismiss="modal" aria-label="close">✕</button>
				</div>
				<div class="bootstrap-dialog-title" id="application-dialog_title"><?= Yii::t('common', 'Confirmation') ?> <?= $this->title ?></div></div>
		</div>
	<?php endif; ?>
	<div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
		<?php if ($subscriptions): ?>
			<div class="row hidden">
				<div class="col-sm-12">
					<?= $form->field($model, 'subscription_id')->widget(Select2::class, [
						'data' => ArrayHelper::map($subscriptions, 'id', 'formattedName'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= Yii::t('common', 'Are you sure you want to perform this operation?') ?>
				</div>
			</div>
		<?php else: ?>
		<div class="row">
			<div class="col-sm-12">
				<div class="alert alert-warning alert-dismissible">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong><?= Yii::t('frontend', 'You have no subscription that allow you to publish announcements.') ?></strong>
				</div>
			</div>
			<div class="col-sm-12">
				<div class="text-center mt30">
					<?= Html::a(Yii::t('common', 'Buy Packages'), ['/account/payment/package', 'feature' => Feature::ANNOUNCEMENTS, 'announcement' => Yii::$app->request->get('id')], ['class' => 'btn btn-primary', 'style' => 'padding: 25.5px 30px;']) ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>


	<?php if (Yii::$app->request->isAjax) : ?>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" data-dismiss="modal" style="padding: 15.5px 20px;"><span class="glyphicon glyphicon-ban-circle"></span> <?= Yii::t('common', 'No') ?></button>
			<?php if ($subscriptions): ?>
				<?= Html::submitButton('<span class="glyphicon glyphicon-ok"></span> ' . Yii::t('common', 'Yes'), ['class' => 'btn btn-primary', 'style' => 'padding: 15.5px 20px;']) ?>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="form-actions floating">
			<?= Html::submitButton('<span class="fa fa-check"></span>', [
				'class' => 'btn btn-xlg btn-fab btn-success',
				'title' => Yii::t('common', 'Save'),
				'data' => [
					'toggle' => 'tooltip',
				],
			]) ?>
		</div>
	<?php endif; ?>
</div>
<?php ActiveForm::end(); ?>
