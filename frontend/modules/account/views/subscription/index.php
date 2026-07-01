<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Subscription;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Breadcrumbs;

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


<!-- DASHBOARD REVIEWS SECTION -->
<section class="clearfix bg-dark dashboard-review-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-xs-12 dashboardBoxBg">
				<?= DataTable::widget([
					'id' => 'dt-package-subscriptions',
					'options' => [
						'class' => 'table table-bordered table-hover',
					],
					'showColumnFilters' => false,
					'clientOptions' => [
						'deferRender' => true,
						'processing' => true,
						'serverSide' => true,
						'ajax' => [
							'url' => Url::to(['dt-package-subscriptions']),
							'method' => 'POST',
							'reloadInterval' => 5 * 60000,
						],
						'order' => [
							[2, 'asc'],
							[3, 'desc'],
						],
						'pageLength' => Yii::$app->settings->get('itemsPerPage'),
						'lengthMenu' => [
							'autoCreate' => true,
							'displayAll' => Yii::t('common', 'All'),
						],
						'autoWidth' => false,
						'responsive' => true,
						'columns' => [
							[
								'data' => 'action',
								'class' => 'common\widgets\datatable\ActionColumn',
								'title' => Yii::t('common', 'Action'),
							],
							[
								'data' => 'code',
								'title' => Yii::t('label', 'Code'),
								'filter' => ['text'],
							],
							[
								'data' => 'package',
								'title' => Yii::t('label', 'Package'),
								'filter' => ['text'],
							],
							[
								'data' => 'price',
								'title' => Yii::t('label', 'Price'),
								'filter' => ['text'],
							],
							[
								'data' => 'end_at',
								'title' => Yii::t('label', 'Next Due At'),
								'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
							],
							[
								'data' => 'status',
								'title' => Yii::t('label', 'Status'),
								'className' => 'col-autowidth',
								'filter' => ['select', ArrayHelper::getColumn(Subscription::getStatusLabels(), 'label')],
							],
						],
					],
				]) ?>

				<div class="text-center mt30">
					<?= Html::a(Yii::t('common', 'Buy Packages'), ['/account/payment/index'], ['class' => 'btn btn-primary', 'style' => 'padding: 25.5px 30px;']) ?>
				</div>
			</div>
		</div>
	</div>
</section>
