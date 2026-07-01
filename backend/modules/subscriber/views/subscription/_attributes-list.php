<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use common\models\Feature;
use common\models\PackageFeature;
use common\models\PackageModule;
use common\models\ScheduledTask;
use yii\helpers\ArrayHelper;

/** @var \common\models\SubscriptionFeature[] $subscriptionFeatures */
$subscriptionFeatures = ArrayHelper::index($model->subscriptionFeatures, 'name');
$packageModuleLabels = PackageModule::getModuleLabels();
$featureLabels = Feature::getFeatureLabels();
?>

<table class="table table-bordered table-condensed">
	<tbody>
		<tr>
			<td class="col-autowidth"><?= $featureLabels[Feature::ANNOUNCEMENTS] ?></td>
			<td><?= $subscriptionFeatures[Feature::ANNOUNCEMENTS]->value ?: 0 ?></td>
		</tr>
		<tr>
			<td class="col-autowidth"><?= $featureLabels[Feature::PROMOTIONS] ?></td>
			<td><?= $subscriptionFeatures[Feature::PROMOTIONS]->value ?: 0 ?></td>
		</tr>
	</tbody>
</table>
