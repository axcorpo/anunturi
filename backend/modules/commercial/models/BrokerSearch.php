<?php

namespace backend\modules\commercial\models;

use common\helpers\DateHelper;
use common\models\Broker;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class BrokerSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Broker::find()
			->alias('b')
			->select([
				'b.id',
				'b.user_id',
				'b.code',
				'b.created_at',
				'b.updated_at',
				'b.created_by',
				'b.status',
			])
			->joinWith([
				'user u',
				'creator c' => function (ActiveQuery $query) {
					$query->select([
						'c.id',
						'c.first_name',
						'c.middle_name',
						'c.last_name',
					]);
				},
			])
			->where([
				'b.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Broker::NO,
			])
			->groupBy(['b.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Broker::class => [
				'id',
				'action' => function (Broker $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Broker::YES) {
						if (Yii::$app->user->can('restoreBroker')) {
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
						if (Yii::$app->user->can('deleteBroker')) {
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
						if (Yii::$app->user->can('viewBroker')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateBroker')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteBroker')) {
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
				'code' => function (Broker $model) {
					return $model->code ?: '&mdash;';
				},
				'name' => function (Broker $model) {
					return $model->user->fullName ?: '&mdash;';
				},
				'email' => function (Broker $model) {
					return $model->user->email ?: '&mdash;';
				},
				'phone' => function (Broker $model) {
					return $model->user->phone ?: '&mdash;';
				},
				'created_by' => function (Broker $model) {
					return $model->creator->fullName ?: '&mdash;';
				},
				'created_at' => function (Broker $model) {
					return Yii::$app->formatter->asDatetime($model->created_at);
				},
				'updated_at' => function (Broker $model) {
					return Yii::$app->formatter->asDatetime($model->updated_at);
				},
				'status' => function (Broker $model) {
					$status = Broker::getStatusLabels()[$model->status];
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
                case 'name':
                    $query->$filterOperator([
                        'OR',
                        ['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.middle_name, u.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.last_name, u.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.last_name, u.middle_name, u.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.last_name, u.first_name, u.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.first_name, u.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.last_name, u.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.last_name, u.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.last_name, u.middle_name)') , $value],
                    ]);
                    break;
				case 'email':
					$query->$filterOperator(['LIKE', 'u.email', $value]);
					break;
				case 'phone':
					$query->$filterOperator(['LIKE', 'u.phone', $value]);
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
				case 'name':
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
