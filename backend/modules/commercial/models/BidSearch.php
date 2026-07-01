<?php

namespace backend\modules\commercial\models;

use common\helpers\DateHelper;
use common\models\Auction;
use common\models\Bid;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class BidSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Bid::find()
			->alias('b')
			->select([
				'b.id',
				'b.auction_id',
				'b.broker_id',
				'b.price',
				'b.currency',
				'b.created_at',
				'b.updated_at',
				'b.created_by',
				'b.status',
			])
			->where([
				'b.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Bid::NO,
			])
			->joinWith([
				'broker.user u',
				'auction au',
				'creator c' => function (ActiveQuery $query) {
					$query->select([
						'c.id',
						'c.first_name',
						'c.middle_name',
						'c.last_name',
					]);
				},
			])
			->groupBy(['b.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Bid::class => [
				'id',
				'action' => function (Bid $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Bid::YES) {
						if (Yii::$app->user->can('restoreBid')) {
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
						if (Yii::$app->user->can('deleteBid')) {
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
						if (Yii::$app->user->can('viewBid')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateBid')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteBid')) {
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
				'auction' => function (Bid $model) {
					if ($model->auction) {
						return $model->auction->id . ' - ' . Auction::getPositionLabels()[$model->auction->position];
					}
					return '&mdash;';
				},
				'broker' => function (Bid $model) {
					if ($model->broker->user) {
						return $model->broker->user->fullName;
					}
					return '&mdash;';
				},
				'price',
				'currency' => function (Bid $model) {
					return $model->currency ?: '&mdash;';
				},
				'created_by' => function (Bid $model) {
					return $model->creator->fullName;
				},
				'created_at' => function (Bid $model) {
					return Yii::$app->formatter->asDatetime($model->created_at);
				},
				'updated_at' => function (Bid $model) {
					return Yii::$app->formatter->asDatetime($model->updated_at);
				},
				'status' => function (Bid $model) {
					$status = Bid::getStatusLabels()[$model->status];
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
				case 'auction':
					$query->$filterOperator(['LIKE', 'au.id', $value]);
					break;
				case 'broker':
					$query->$filterOperator([
						'OR',
						['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.middle_name, u.last_name)') , $value],
						['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.last_name)') , $value],
						['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.last_name)') , $value],
					]);
					break;
                case 'created_by':
                    $query->$filterOperator([
                        'OR',
                        ['LIKE', new Expression('CONCAT_WS(" ", c.first_name, c.middle_name, c.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.first_name, c.last_name, c.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.last_name, c.middle_name, c.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.last_name, c.first_name, c.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.middle_name, c.first_name, c.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.middle_name, c.last_name, c.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.first_name, c.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.first_name, c.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.middle_name, c.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.middle_name, c.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.last_name, c.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", c.last_name, c.middle_name)') , $value],
                    ]);
                    break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'b.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'b.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'b.' . $column['data'], $value]);
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
				case 'auction':
					$query->addOrderBy(['au.id' => $sort]);
					break;
				case 'broker':
					$query->addOrderBy([
						'u.first_name' => $sort,
						'u.middle_name' => $sort,
						'u.last_name' => $sort,
					]);
					break;
				case 'created_by':
					$query->addOrderBy([
						'c.first_name' => $sort,
						'c.middle_name' => $sort,
						'c.last_name' => $sort,
					]);
					break;
				default:
					// Apply default order
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['b.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
