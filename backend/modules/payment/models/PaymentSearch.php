<?php

namespace backend\modules\payment\models;

use common\helpers\DateHelper;
use common\models\Payment;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class PaymentSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		// Set the query
		$this->query = Payment::find()
			->alias('p')
			->select([
				'p.id',
				'p.subscription_id',
				'p.payment_id',
				'p.amount',
				'p.currency',
				'p.split_percentage',
				'p.paid_at',
				'p.bank_account_holder',
				'p.bank_name',
				'p.bank_iban',
				'p.bank_bic',
				'p.created_at' ,
				'p.status',
			])
			->andWhere([
				'p.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Payment::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Payment::class => [
				'id',
				'action' => function (Payment $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Payment::YES) {
						if (Yii::$app->user->can('restorePayment')) {
							$actions[] = Html::a('<span class="fa fa-undo"></span>', ['restore', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-success',
								'title' => Yii::t('common', 'Restore'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'restore',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
						if (Yii::$app->user->can('deletePayment')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model->id], [
								'class' => 'action-delete btn btn-xs btn-danger',
								'title' => Yii::t('common', 'Delete Permanently'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'delete-permanently',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
					} else {
						if (Yii::$app->user->can('viewPayment')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updatePayment')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deletePayment')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model->id], [
								'class' => 'action-delete btn btn-xs btn-danger',
								'title' => Yii::t('common', 'Delete'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'delete',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
					}

					$actions = array_map(function ($actionsChunk) {
						return Html::tag('div', implode('', $actionsChunk));
					}, array_chunk($actions, 3));

					return implode('', $actions);
				},
				'amount' => function (Payment $model) {
					return $model->amount ? Yii::$app->formatter->asCurrency($model->amount, $model->currency) : '&mdash;';
				},
				'split_percentage' => function (Payment $model) {
					return $model->split_percentage ? Yii::$app->formatter->asPercent($model->split_percentage / 100) : '&mdash;';
				},
				'prize' => function (Payment $model) {
					return Yii::$app->formatter->asCurrency(round($model->amount * $model->split_percentage / 100, 2), $model->currency);
				},
				'paid_at' => function (Payment $model) {
					return $model->paid_at ?: '&mdash;';
				},
				'bank_account_holder' => function (Payment $model) {
					return $model->bank_account_holder ?: '&mdash;';
				},
				'bank_name' => function (Payment $model) {
					return $model->bank_name ?: '&mdash;';
				},
				'bank_iban' => function (Payment $model) {
					return $model->bank_iban ?: '&mdash;';
				},
				'bank_bic' => function (Payment $model) {
					return $model->bank_iban ?: '&mdash;';
				},
				'created_at' => function (Payment $model) {
					return $model->created_at ?: '&mdash;';
				},
				'status' => function (Payment $model) {
					$status = Payment::getStatusLabels()[$model->status];
					return Html::tag('span', $status['label'], ['class' => 'label label-block label-' . $status['color']]);
				},
			],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function applyFilter(ActiveQuery $query, $columns, $search)
	{
		/** @var \yii\db\ActiveRecord $modelClass */
		$modelClass = $query->modelClass;
		$schema = $modelClass::getTableSchema()->columns;

		// Filtering conditions
		$query->andFilterWhere([
			'AND',
			['>=', 'p.created_at', DateHelper::formatAsDateTime($this->externalFilters['date_start'])],
			['<', 'p.created_at', DateHelper::formatAsDateTime($this->externalFilters['date_end'], '+1 day')],
		]);

		// Loop through the DataTable columns
		foreach ($columns as $column) {
			// Continue if the column is not searchable
			if ($column['searchable'] == 'false') {
				continue;
			}
			// Get the filter value
			if (!empty($search['value'])) {
				$value = $search['value'];
				$filterOperator = 'orFilterWhere';
			} else {
				$value = $column['search']['value'];
				$filterOperator = 'andFilterWhere';
			}
			// Handle custom column filter
			switch ($column['data']) {
				case 'created_at':
					$query->$filterOperator(['LIKE', 'p.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'paid_at':
					$query->$filterOperator(['LIKE', 'p.last_activity', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'p.' . $column['data'], $value]);
					}
					break;
			}
		}
		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function applyOrder(ActiveQuery $query, $columns, $order)
	{
        /** @var \yii\db\ActiveRecord $modelClass */
        $modelClass = $query->modelClass;
        $schema = $modelClass::getTableSchema()->columns;

        // Loop through the DataTable order items
		foreach ($order as $key => $item) {
			// Get the order targeted column
			$column = $columns[$item['column']];
			// Continue if the column is not orderable
			if (array_key_exists('orderable', $column) && $column['orderable'] === 'false') {
				continue;
			}
			// Get the order value
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;
			// Handle custom column filter
			switch ($column['data']) {
				default:
					// Apply default order
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['p.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
