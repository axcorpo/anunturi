<?php

namespace backend\modules\import\models;

use common\models\ImportColumn;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class ColumnSearch extends DataTableAction
{
	/**
	 * @var array The target model attribute labels.
	 */
	protected $targetModelAttributeLabels;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$spreadsheetImport = Yii::$app->session->get('SpreadsheetImport');
		$this->targetModelAttributeLabels = (new $spreadsheetImport['model'])->attributeLabels();

		$this->query = ImportColumn::find()
			->alias('c')
			->select([
				'c.*',
			])
			->joinWith([
				'alternativeSources s' => function (ActiveQuery $query) {
					$query->andOnCondition([
						's.deleted' => ImportColumn::NO,
					]);
				},
			])
			->where([
				'c.sheet_id' => $this->requestParams['sheet_id'],
				'c.deleted' => ImportColumn::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			ImportColumn::class => [
				'id',
				'target' => function ($model) {
					return $this->targetModelAttributeLabels[$model->target] ?: Yii::t('common', Inflector::humanize($model->target, true));
				},
				'source' => function ($model) {
					if ($model->source) {
						return $model->source;
					} else {
						return implode(', ', ArrayHelper::getColumn($model->alternativeSources, 'source'));
					}
				},
				'source_index' => function ($model) {
					if ($model->source_index) {
						return $model->source_index;
					} else {
						return implode(', ', ArrayHelper::getColumn($model->alternativeSources, 'source_index'));
					}
				},
				'field_type',
				'sort_order',
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

		foreach ($columns as $column) {
			if ($column['searchable'] == 'false') {
				continue;
			}
			if (!empty($search['value'])) {
				$value = trim($search['value']);
				$filterOperator = 'orFilterWhere';
			} else {
				$value = trim($column['search']['value']);
				$filterOperator = 'andFilterWhere';
			}

			switch ($column['data']) {
				case 'source':
					$query->$filterOperator([
						'OR',
						['LIKE', 'c.source', $value],
						['LIKE', 's.source', $value],
					]);
					break;
				case 'source_index':
					$query->$filterOperator([
						'OR',
						['=', 'c.source_index', $value],
						['=', 's.source_index', $value],
					]);
					break;
				default:
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

        foreach ($order as $key => $item) {
			$column = $columns[$item['column']];
			if (array_key_exists('orderable', $column) && $column['orderable'] === 'false') {
				continue;
			}
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;

			switch ($column['data']) {
				case 'source':
					$query->addOrderBy([
						'c.source' => $sort,
						's.source' => $sort,
					]);
					break;
				case 'source_index':
					$query->addOrderBy([
						'c.source_index' => $sort,
						's.source_index' => $sort,
					]);
					break;
				default:
                    if (array_key_exists($column['data'], $schema)) {
					    $query->addOrderBy([$column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
