<?php
/* @var $this yii\web\View */
/* @var $model common\models\Package */

use common\models\Feature;
use common\models\PackageFeature;
use common\models\PackageModule;
use common\models\ScheduledTask;
use yii\helpers\ArrayHelper;

/** @var PackageFeature[] $packageFeatures */
$packageFeatures = ArrayHelper::index($model->packageFeatures, 'name');
$packageModuleLabels = PackageModule::getModuleLabels();
$packageFeatureLabels = Feature::getFeatureLabels();
?>

<table class="table table-bordered table-condensed">
	<tbody>
	<tr>
		<td class="col-autowidth"><?= $packageFeatureLabels[PackageFeature::ANNOUNCEMENTS] ?></td>
		<td><?= $packageFeatures[Feature::ANNOUNCEMENTS]->value ?: 0 ?></td>
	</tr>
	<tr>
		<td class="col-autowidth"><?= $packageFeatureLabels[PackageFeature::PROMOTIONS] ?></td>
		<td><?= $packageFeatures[Feature::PROMOTIONS]->value ?: 0 ?></td>
	</tr>
	<tr>
		<td class="col-autowidth"><?= $packageFeatureLabels[PackageFeature::PROMOTION_DAYS] ?></td>
		<td><?= $packageFeatures[Feature::PROMOTION_DAYS]->value ?: 0 ?></td>
	</tr>
	</tbody>
</table>
