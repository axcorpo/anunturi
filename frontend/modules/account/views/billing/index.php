<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Invoice;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="section">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->translation->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>

		<?= DataTable::widget([
			'id' => 'dt-invoices',
			'options' => [
				'class' => 'table table-bordered table-hover',
			],
			'showColumnFilters' => false,
			'clientOptions' => [
				'deferRender' => true,
				'processing' => true,
				'serverSide' => true,
				'ajax' => [
					'url' => Url::to(['dt-invoices']),
					'method' => 'POST',
					'reloadInterval' => 1 * 60000,
				],
				'order' => [
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
						'className' => 'action-column col-autowidth text-center',
					],
					[
						'data' => 'name',
						'title' => Yii::t('label', 'Name'),
						'filter' => ['text'],
					],
					[
						'data' => 'created_at',
						'title' => Yii::t('label', 'Created At'),
						'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
					],
					[
						'data' => 'status',
						'title' => Yii::t('label', 'Status'),
						'className' => 'col-autowidth',
						'filter' => ['select', ArrayHelper::getColumn(Invoice::getStatusLabels(), 'label')],
					],
				],
			],
		]) ?>
	</div>
</div>
