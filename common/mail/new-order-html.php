<?php
/* @var $this yii\web\View */
/* @var $model common\models\Order */

use common\models\OptionValue;
use common\models\Order;
use common\models\PaymentMetadata;
use tws\helpers\Url;

$customer = $model->customer;
$user = $customer->user;
?>

<table style="width: 100%; border-collapse: collapse;" border="1" cellspacing="0" cellpadding="5">
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Order') ?></th>
		<td style="width: auto; vertical-align: top;">#<?= $model->code ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Status') ?></th>
		<td style="width: auto; vertical-align: top;"><?= Order::getStatusLabels()[$model->status]['label'] ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Customer') ?></th>
		<td style="width: auto; vertical-align: top;"><?= $user->fullName ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Email') ?></th>
		<td style="width: auto; vertical-align: top;"><?= $user->email ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Phone') ?></th>
		<td style="width: auto; vertical-align: top;"><?= $user->phone ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Articles') ?></th>
		<td style="width: auto; vertical-align: top;">
			<?php foreach ($model->orderProducts as $orderProduct): ?>
				<?php $options = implode(', ', array_map(function (OptionValue $optionValue) {
					return "{$optionValue->option->translation->name}: {$optionValue->translation->name}";
				}, $orderProduct->optionValues)) ?>
				<div><?= $orderProduct->quantity ?> x <?= $orderProduct->product->translation->title ?> <?= !empty($options) ? "({$options})" : "" ?></div>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Attachments') ?></th>
		<td style="width: auto; vertical-align: top;">
			<?php foreach ($model->getAttachments(true, true) as $attachment): ?>
				<div>
					<a href="<?= $attachment['url'] ?>" title="<?= Yii::t('common', 'Download') ?>" target="_blank"><?= $attachment['name'] ?></a>
				</div>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Shipping Details') ?></th>
		<td style="width: auto; vertical-align: top;"><?= $model->getFormattedShippingDetails() ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Billing Details') ?></th>
		<td style="width: auto; vertical-align: top;"><?= $model->getFormattedBillingDetails() ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Notes') ?></th>
		<td style="width: auto; vertical-align: top;"><?= $model->comment ? nl2br($model->comment) : '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Payment Method') ?></th>
		<td style="width: auto; vertical-align: top;"><?= PaymentMetadata::getPaymentMethodLabels()[$model->paymentMetadata->payment_method] ?: '&mdash;' ?></td>
	</tr>
	<tr>
		<th style="width: 150px; vertical-align: top; text-align: left;"><?= Yii::t('label', 'Amount') ?></th>
		<td style="width: auto; vertical-align: top;"><?= Yii::$app->formatter->asCurrency($model->amount, $model->currency) ?></td>
	</tr>
</table>

<p>
	<a href="<?= Url::to(["/admin/store-manager/order/{$model->id}/view"], true) ?>" target="_blank"><?= Yii::t('common', 'View this on {0} dashboard', [Yii::$app->name]) ?></a>
</p>
<?php if ($user->email): ?>
	<p><?= Yii::t('common', 'You can send an email to the customer by replying to this email.') ?></p>
<?php endif; ?>
