<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\Category;
use common\models\CategoryTranslation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class CategorySearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Category::find()
			->alias('c')
			->select([
				'c.id',
				'c.image',
				'c.sort_order',
				'c.created_by',
				'c.created_at',
				'c.updated_at',
				'c.status',
			])
			->joinWith([
				'categoryTranslations ct' => function (ActiveQuery $query) {
					return $query->andOnCondition([
						'ct.language_id' => Yii::$app->language,
						'ct.deleted' => CategoryTranslation::NO,
					]);
				},
				'creator cr' => function (ActiveQuery $query) {
					$query->select([
						'cr.id',
						'cr.first_name',
						'cr.middle_name',
						'cr.last_name',
					]);
				},
			])
			->where([
				'c.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Category::NO,
			])
			->groupBy(['c.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Category::class => [
				'id',
				'image' => function (Category $model) {
		            if ($model->imageUrl && is_file(Yii::getAlias("@uploads/category/{$model->id}/{$model->image}"))) {
                        return $model->imageUrl;
                    }
					return '&mdash;';
				},
				'name' => function (Category $model) {
					return $model->getTreeName($glue = ' &rsaquo; ');
				},
				'sort_order' => function (Category $model) {
					return $model->sort_order ?: '&mdash;';
				},
				'created_by' => function (Category $model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function (Category $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Category $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Category $model) {
					$status = Category::getStatusLabels()[$model->status];
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
					$query->$filterOperator(['LIKE', 'ct.name', $value]);
					break;
                case 'created_by':
                    $query->$filterOperator([
                        'OR',
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.first_name, cr.middle_name, cr.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.first_name, cr.last_name, cr.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.last_name, cr.middle_name, cr.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.last_name, cr.first_name, cr.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.middle_name, cr.first_name, cr.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.middle_name, cr.last_name, cr.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.first_name, cr.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.first_name, cr.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.middle_name, cr.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.middle_name, cr.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.last_name, cr.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", cr.last_name, cr.middle_name)') , $value],
                    ]);
                    break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'c.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'c.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'c.' . $column['data'], $value]);
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
					$query->addOrderBy(['ct.name' => $sort]);
					break;
				case 'created_by':
					$query->addOrderBy([
						'cr.first_name' => $sort,
						'cr.middle_name' => $sort,
						'cr.last_name' => $sort,
					]);
					break;
				default:
					// Apply default order
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['c.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
