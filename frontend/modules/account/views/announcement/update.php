<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Announcement;
use common\models\Category;
use common\models\Country;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = [
	'label' => \common\models\Page::findPageByRoute(['/account/announcement/index'])->translation->title,
	'url' => ['index'],
];
$this->params['breadcrumbs'][] = Html::encode($this->title);
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


<!-- DASHBOARD PROFILE SECTION -->
<section class="clearfix bg-dark profileSection">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-xs-12 dashboardBoxBg">
				<?= $this->render('_form', [
					'model' => $model,
				]) ?>
			</div>
		</div>
	</div>
</section>
