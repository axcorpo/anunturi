<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\widgets\datatable\DataTable;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="section">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->translation->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>

		<?= Html::tag('button', '<span class="fa fa-download"></span>', [
			'class' => 'btn btn-sm btn-primary hidden',
			'title' => Yii::t('common', 'Bulk Download'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => 'download',
				'dt-url' => Url::to(['bulk-download']),
				'dt-table' => '#dt-resources',
			],
		]) ?>
		<?= DataTable::widget([
			'id' => 'dt-resources',
			'options' => [
				'class' => 'table table-bordered table-hover',
			],
			'showColumnFilters' => false,
			'clientOptions' => [
				'deferRender' => true,
				'processing' => true,
				'serverSide' => true,
				'ajax' => [
					'url' => Url::to(['dt-resources']),
					'method' => 'POST',
					'reloadInterval' => 1 * 60000,
				],
				'order' => [
					[4, 'desc'],
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
						'visible' => false,
						'class' => 'common\widgets\datatable\CheckboxColumn',
					],
					[
						'data' => 'action',
						'class' => 'common\widgets\datatable\ActionColumn',
						'title' => Yii::t('common', 'Action'),
						'className' => 'action-column col-autowidth text-center',
					],
					[
						'data' => 'image',
						'title' => Yii::t('label', 'Image'),
						'className' => 'col-autowidth text-center',
						'orderable' => false,
						'filter' => false,
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
				],
			],
		]) ?>
	</div>
</div>
