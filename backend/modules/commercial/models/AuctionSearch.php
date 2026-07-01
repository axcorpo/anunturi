<?php

namespace backend\modules\commercial\models;

use common\helpers\DateHelper;
use common\models\Auction;
use common\models\Category;
use common\models\CategoryTranslation;
use common\models\County;
use common\models\TemplateTranslation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class AuctionSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Auction::find()
			->alias('a')
			->select([
				'a.id',
				'a.parent_id',
				'a.county_id',
				'a.category_id',
				'a.position',
				'a.period',
				'a.cycle',
				'a.start_at',
				'a.end_at',
				'a.start_price',
				'a.step',
				'a.currency',
				'a.valid_from',
				'a.valid_to',
				'a.created_by',
				'a.updated_by',
				'a.created_at',
				'a.updated_at',
				'a.status',
				'a.deleted',
			])
			->joinWith([
				'parent p' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'p.deleted' => Auction::NO,
					]);
				},
				'county co' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'co.deleted' => County::NO,
					]);
				},
				'category.categoryTranslations cat' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'cat.language_id' => Yii::$app->language,
						'cat.deleted' => CategoryTranslation::NO,
					]);
				},
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
				'a.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Auction::NO,
			])
			->groupBy(['a.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Auction::class => [
				'id',
				'action' => function (Auction $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Auction::YES) {
						if (Yii::$app->user->can('restoreAuction')) {
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
						if (Yii::$app->user->can('deleteAuction')) {
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
						if (Yii::$app->user->can('viewAuction')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateAuction')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
									'popup-done' => ['redrawDataTable' => '#dt-auctions'],
								],
							]);
						}
						if (Yii::$app->user->can('deleteAuction')) {
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
				'parent' => function (Auction $model) {
					if ($model->parent) {
						return implode(' - ', [$model->parent->id, Auction::getPositionLabels()[$model->parent->position]]);
					}
					return '&mdash;';
				},
				'county' => function (Auction $model) {
					return $model->county->name ?: '&mdash;';
				},
				'category' => function (Auction $model) {
					return $model->category->translation->name ?: '&mdash;';
				},
				'position' => function (Auction $model) {
					return Auction::getPositionLabels()[$model->position] ?: '&mdash;';
				},
				'start_at' => function (Auction $model) {
					return Yii::$app->formatter->asDate($model->start_at);
				},
				'end_at' => function (Auction $model) {
					return Yii::$app->formatter->asDate($model->end_at);
				},
				'valid_from' => function (Auction $model) {
					return Yii::$app->formatter->asDate($model->valid_from);
				},
				'valid_to' => function (Auction $model) {
					return Yii::$app->formatter->asDate($model->valid_to);
				},
				'start_price' => function (Auction $model) {
					return $model->start_price ?: '&mdash;';
				},
				'step' => function (Auction $model) {
					return $model->step ?: '&mdash;';
				},
				'currency' => function (Auction $model) {
					return $model->currency ?: '&mdash;';
				},
				'created_by' => function (Auction $model) {
					return $model->creator->fullName ?: '&mdash;';
				},
				'created_at' => function (Auction $model) {
					return Yii::$app->formatter->asDatetime($model->created_at);
				},
				'updated_at' => function (Auction $model) {
					return Yii::$app->formatter->asDatetime($model->updated_at);
				},
				'status' => function (Auction $model) {
					$status = Auction::getStatusLabels()[$model->status];
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
				case 'parent':
					$query->$filterOperator(['LIKE', 't.parent_id', $value]);
					break;
				case 'county':
					$query->$filterOperator(['a.county_id' => array_filter(explode(',', $value))]);
					break;
				case 'category':
					$query->$filterOperator(['a.category_id' => array_filter(explode(',', $value))]);
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
					$query->$filterOperator(['LIKE', 'a.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'a.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'a.' . $column['data'], $value]);
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
				case 'parent':
					$query->addOrderBy([
						'a.parent_id' => $sort,
					]);
					break;
				case 'county':
					$query->addOrderBy([
						'co.name' => $sort,
					]);
					break;
				case 'category':
					$query->addOrderBy([
						'ct.name' => $sort,
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
                        $query->addOrderBy(['a.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
