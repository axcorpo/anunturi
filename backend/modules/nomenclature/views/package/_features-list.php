<?php
/* @var $this yii\web\View */
/* @var $model common\models\Package */

use common\models\Feature;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use yii\helpers\ArrayHelper;

/** @var PackageFeature[] $packageFeatures */
$packageFeatures = ArrayHelper::index($model->packageFeatures, 'name');
$featureModuleLabels = FeatureModule::getModuleLabels();
$featureLabels = Feature::getFeatureLabels();
?>

<table class="table table-bordered table-condensed">
	<tbody>
	<tr>
		<td class="col-autowidth"><?= $featureLabels[Feature::ANNOUNCEMENTS] ?></td>
		<td><?= $packageFeatures[Feature::ANNOUNCEMENTS]->value ?: 0 ?></td>
	</tr>
	</tbody>
</table>
<fieldset class="fieldset margin-top-10">
	<legend><?= Yii::t('label', 'Promotions') ?></legend>
	<div class="row">
		<div class="col-sm-6">
			<table class="table table-bordered table-condensed">
				<tbody>
				<tr>
					<td class="col-autowidth"><?= Yii::t('label', 'Announcements') ?></td>
					<td>
						<?= $packageFeatures[Feature::PROMOTIONS]->value ?: 0 ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<div class="col-sm-6">
			<table class="table table-bordered table-condensed">
				<tbody>
				<tr>
					<td class="col-autowidth"><?= Yii::t('label', 'Days') ?></td>
					<td>
						<?= $packageFeatures[Feature::PROMOTION_DAYS]->value ?: 0 ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</fieldset>

