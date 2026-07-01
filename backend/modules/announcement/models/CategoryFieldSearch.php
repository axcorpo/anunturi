<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\Action;
use common\models\Category;
use common\models\CategoryField;
use common\models\CategoryTranslation;
use common\models\Field;
use common\models\FieldTranslation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class CategoryFieldSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = CategoryField::find()
			->alias('cf')
			->select([
				'cf.id',
				'cf.category_id',
				'cf.field_id',
				'cf.action_id',
				'cf.created_at',
				'cf.updated_at',
				'cf.status',
				'cf.sort_order',
				'cf.status',
				'CONCAT_WS(" ", [[cr.first_name]], [[cr.middle_name]], [[cr.last_name]]) AS [[creator_name]]',
			])
			->joinWith([
				'field.fieldTranslations ft' => function (ActiveQuery $query) {
					return $query->andOnCondition([
						'ft.language_id' => Yii::$app->language,
						'ft.deleted' => FieldTranslation::NO,
					]);
				},
                'action a',
                'category.categoryTranslations ct' => function (ActiveQuery $query) {
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
			->andWhere([
				'cf.category_id' => $this->requestParams['category_id'],
				'cf.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Field::NO,
			])
			->groupBy(['cf.id']);
	}

    /**
     * @inheritdoc
     */
    public function formatData(ActiveQuery $query, $columns)
    {
        return ArrayHelper::toArray($query->all(), [
            CategoryField::class => [
                'id',
                'action' => function (CategoryField $model) {
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
                },
                'field' => function (CategoryField $model) {
                    return $model->field->translation->label ?: '&mdash;';
                },
                'actions' => function (CategoryField $model){
                    return Action::getTypes()[$model->action->type] ?: '&mdash;';
                },
                'sort_order' => function (CategoryField $model){
                    return $model->sort_order ?: '&mdash;';
                },
                'created_by' => function (CategoryField $model) {
                    return $model->creator->fullName;
                },
                'created_at' => function (CategoryField $model) {
                    return Yii::$app->formatter->asDatetime($model->created_at);
                },
                'updated_at' => function (CategoryField $model) {
                    return Yii::$app->formatter->asDatetime($model->updated_at);
                },
                'status' => function (CategoryField $model) {
                    $status = CategoryField::getStatusLabels()[$model->status];
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
				case 'label':
					$query->$filterOperator(['LIKE', 'ft.label', $value]);
					break;
				case 'sort_order':
					$query->$filterOperator(['LIKE', 'cf.sort_order', $value]);
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
					$query->$filterOperator(['LIKE', 'f.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'f.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'f.' . $column['data'], $value]);
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
				case 'label':
					$query->addOrderBy(['ft.label' => $sort]);
					break;
				case 'sort_order':
					$query->addOrderBy(['cf.sort_order' => $sort]);
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
                        $query->addOrderBy(['f.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
