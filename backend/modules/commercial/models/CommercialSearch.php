<?php

namespace backend\modules\commercial\models;

use common\helpers\DateHelper;
use common\models\Commercial;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class CommercialSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Commercial::find()
			->alias('co')
			->select([
				'co.id',
				'co.bid_id',
				'co.image',
				'co.url',
				'co.created_at',
				'co.updated_at',
				'co.created_by',
				'co.status',
			])
			->joinWith([
				'bid b',
				'bid.broker.user u',
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
				'co.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Commercial::NO,
			])
			->groupBy(['co.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Commercial::class => [
				'id',
				'action' => function (Commercial $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Commercial::YES) {
						if (Yii::$app->user->can('restoreCommercial')) {
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
						if (Yii::$app->user->can('deleteCommercial')) {
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
						if (Yii::$app->user->can('viewCommercial')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateCommercial')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteCommercial')) {
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
				'image' => function (Commercial $model) {
                    if ($model->imageUrl && is_file(Yii::getAlias("@uploads/commercial/{$model->id}/{$model->image}")))
                    {
                        return $model->imageUrl;
                    }
                    return '&mdash;';
				},
				'url',
				'bid'=> function (Commercial $model) {
					if ($model->bid) {
						return $model->bid->bidName;
					}
					return '&mdash;';
				},
				'broker' => function (Commercial $model) {
					if ($model->bid->broker->user) {
						return $model->bid->broker->user->fullName;
					}
					return '&mdash;';
				},
				'created_by' => function (Commercial $model) {
					return $model->creator->fullName;
				},
				'created_at' => function (Commercial $model) {
					return Yii::$app->formatter->asDatetime($model->created_at);
				},
				'updated_at' => function (Commercial $model) {
					return Yii::$app->formatter->asDatetime($model->updated_at);
				},
				'status' => function (Commercial $model) {
					$status = Commercial::getStatusLabels()[$model->status];
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
				case 'bid':
					$query->$filterOperator(['=', 'b.id' . $column['data'], $value]);
					break;
				case 'broker':
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
					$query->$filterOperator(['LIKE', 'co.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'co.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'co.' . $column['data'], $value]);
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
				case 'bid':
					$query->addOrderBy(['b.id' => $sort]);
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
						'co.first_name' => $sort,
						'co.middle_name' => $sort,
						'co.last_name' => $sort,
					]);
					break;
				default:
					// Apply default order
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['co.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
