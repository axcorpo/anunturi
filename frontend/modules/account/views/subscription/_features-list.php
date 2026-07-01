<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use common\models\Feature;
use common\models\PackageFeature;
use common\models\ScheduledTask;
use yii\helpers\ArrayHelper;

/** @var \common\models\SubscriptionFeature[] $subscriptionFeatures */
$subscriptionFeatures = ArrayHelper::index($model->subscriptionFeatures, 'name');
$packageFeatureLabels = Feature::getFeatureLabels();
?>

<table class="table table-bordered table-condensed">
	<tbody>
        <?php if ($subscriptionFeatures[Feature::ANNOUNCEMENTS]->value): ?>
		<tr>
			<td class="col-autowidth"><?= $packageFeatureLabels[Feature::ANNOUNCEMENTS] ?></td>
			<td><?= $subscriptionFeatures[Feature::ANNOUNCEMENTS]->value ?: 0 ?></td>
		</tr>
        <?php elseif ($subscriptionFeatures[Feature::PROMOTIONS]->value): ?>
        <tr>
            <td class="col-autowidth"><?= $packageFeatureLabels[Feature::PROMOTIONS] ?></td>
            <td><?= $subscriptionFeatures[Feature::PROMOTIONS]->value ?: 0 ?></td>
        </tr>
        <tr>
            <td class="col-autowidth"><?= $packageFeatureLabels[Feature::PROMOTION_DAYS] ?></td>
            <td><?= $subscriptionFeatures[Feature::PROMOTION_DAYS]->value ?: 0 ?></td>
        </tr>
        <?php endif; ?>
	</tbody>
</table>
