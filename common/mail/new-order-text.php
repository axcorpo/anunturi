<?php
/* @var $this yii\web\View */
/* @var $model common\models\Order */

use common\models\OptionValue;
use common\models\Order;
use common\models\PaymentMetadata;

$customer = $model->customer;
$user = $customer->user;
?>

<?= Yii::t('label', 'Order') ?>: #<?= $user->fullName ?: '-' ?>,

<?= Yii::t('label', 'Status') ?>: <?= Order::getStatusLabels()[$model->status]['label'] ?: '-' ?>,

<?= Yii::t('label', 'Customer') ?>: <?= $user->fullName ?: '-' ?>,

<?= Yii::t('label', 'Email') ?>: <?= $user->email ?: '-' ?>,

<?= Yii::t('label', 'Phone') ?>: <?= $user->phone ?: '-' ?>,

<?= Yii::t('label', 'Articles') ?>:
<?php foreach ($model->orderProducts as $orderProduct): ?>
	<?php $options = implode(',', array_map(function (OptionValue $optionValue) {
		return "{$optionValue->option->translation->name}: {$optionValue->translation->name}";
	}, $orderProduct->optionValues)) ?>
	<div><?= $orderProduct->quantity ?> x <?= $orderProduct->product->translation->title ?> <?= !empty($options) ? "({$options})" : "" ?></div>
<?php endforeach; ?>,

<?= Yii::t('label', 'Attachments') ?>:
<?php foreach ($model->getAttachments(true, true) as $attachment): ?>
	<?= $attachment['name'] ?>: <?= $attachment['url'] ?>,
<?php endforeach; ?>,

<?= Yii::t('label', 'Shipping Details') ?>: <?= $model->getFormattedShippingDetails() ?: '-' ?>,

<?= Yii::t('label', 'Billing Details') ?>: <?= $model->getFormattedBillingDetails() ?: '-' ?>,

<?= Yii::t('label', 'Notes') ?>: <?= $model->comment ?: '-' ?>,

<?= Yii::t('label', 'Payment Method') ?>: <?= PaymentMetadata::getPaymentMethodLabels()[$model->paymentMetadata->payment_method] ?: '-' ?>,

<?= Yii::t('label', 'Amount') ?>: <?= Yii::$app->formatter->asCurrency($model->amount, $model->currency) ?>,

<?php if ($user->email): ?>
	<?= Yii::t('common', 'You can send an email to the customer by replying to this email.') ?>
<?php endif; ?>
