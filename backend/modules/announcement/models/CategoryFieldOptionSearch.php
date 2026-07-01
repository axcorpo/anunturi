<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\Field;
use common\models\FieldTranslation;
use common\models\Option;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\Html;

class CategoryFieldOptionSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Option::find()
			->alias('o')
			->select([
				'o.id',
				'o.value',
				'o.created_at',
				'o.updated_at',
				'o.status',
				'ot.label',
				'cho.sort_order',
				'cho.category_id',
				'ft.label AS field_label',
				'CONCAT_WS(" ", [[cr.first_name]], [[cr.middle_name]], [[cr.last_name]]) AS [[creator_name]]',
			])
			->joinWith([
				'optionTranslations ot' => function (ActiveQuery $query) {
					return $query->andOnCondition([
						'ot.language_id' => Yii::$app->language,
						'ot.deleted' => FieldTranslation::NO,
					]);
				},
				'categoryHasOptions cho',
				'field.fieldTranslations ft' => function (ActiveQuery $query) {
					return $query->andOnCondition([
						'ft.language_id' => Yii::$app->language,
						'ft.deleted' => FieldTranslation::NO,
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
			->andWhere([
				'cho.category_id' => $this->requestParams['category_id'],
				'o.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Field::NO,
			])
			->groupBy(['o.id']);
	}

	/**
	 * @inheritdoc
	 * @throws \yii\db\Exception
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		$data = [];

		foreach ($query->createCommand()->queryAll() as $model) {
			$data[] = [
				'id' => (int) $model['id'],
				'action' => call_user_func(function () use ($model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Field::YES) {
						if (Yii::$app->user->can('deleteField')) {
							$actions[] = Html::a('<span class="fa fa-undo"></span>', ['restore', 'id' => $model['id'], 'category_id' => $model['category_id']], [
								'class' => 'action-view btn btn-xs btn-success',
								'title' => Yii::t('common', 'Restore'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'restore',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
						if (Yii::$app->user->can('deleteField')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model['id'], 'category_id' => $model['category_id']], [
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
						if (Yii::$app->user->can('viewField')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model['id'], 'category_id' => $model['category_id']], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
									'popup-action' => '',
									'popup-done' => ['redrawDataTable' => '#dt-category-fields'],
								],
							]);
						}
						if (Yii::$app->user->can('updateField')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model['id'], 'category_id' => $model['category_id']], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
									'popup-action' => '',
									'popup-done' => ['redrawDataTable' => '#dt-category-fields'],
								],
							]);
						}
						if (Yii::$app->user->can('deleteField')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model['id'], 'category_id' => $model['category_id']], [
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
				}),
				'label' => call_user_func(function () use ($model) {
					return $model['label'] ?: '&mdash;';
				}),
				'value' => call_user_func(function () use ($model) {
					return $model['value'] ?: '&mdash;';
				}),
				'sort_order' => call_user_func(function () use ($model) {
					return $model['sort_order'] ?: '&mdash;';
				}),
				'field_label' => call_user_func(function () use ($model) {
					return $model['field_label'] ?: '&mdash;';
				}),
				'created_by' => call_user_func(function () use ($model) {
					return $model['creator_name'] ?: '&mdash;';
				}),
				'created_at' => call_user_func(function () use ($model) {
					return $model['created_at'] ? Yii::$app->formatter->asDatetime($model['created_at']) : '&mdash;';
				}),
				'updated_at' => call_user_func(function () use ($model) {
					return $model['updated_at'] ? Yii::$app->formatter->asDatetime($model['updated_at']) : '&mdash;';
				}),
				'status' => call_user_func(function () use ($model) {
					$status = Field::getStatusLabels()[$model['status']];
					return Html::tag('span', $status['label'], ['class' => 'label label-block label-' . $status['color']]);
				}),
			];
		}

		return $data;
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
				$value = $search['value'];
				$filterOperator = 'orFilterWhere';
			} else {
				$value = $column['search']['value'];
				$filterOperator = 'andFilterWhere';
			}

			switch ($column['data']) {
				case 'field_label':
					$query->$filterOperator(['LIKE', 'ft.label', $value]);
					break;
				case 'label':
					$query->$filterOperator(['LIKE', 'ot.label', $value]);
					break;
				case 'sort_order':
					$query->$filterOperator(['LIKE', 'cho.sort_order', $value]);
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
					$query->$filterOperator(['LIKE', 'o.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'o.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'o.' . $column['data'], $value]);
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
				case 'field_label':
					$query->addOrderBy(['ft.label' => $sort]);
					break;
				case 'label':
					$query->addOrderBy(['ot.label' => $sort]);
					break;
				case 'sort_order':
					$query->addOrderBy(['cho.sort_order' => $sort]);
					break;
				case 'created_by':
					$query->addOrderBy([
						'cr.first_name' => $sort,
						'cr.middle_name' => $sort,
						'cr.last_name' => $sort,
					]);
					break;
				default:
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['o.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
