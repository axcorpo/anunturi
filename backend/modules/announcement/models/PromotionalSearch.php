<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\AnnouncementTranslation;
use common\models\Promotional;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class PromotionalSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Promotional::find()
			->alias('p')
			->select([
				'p.id',
				'p.start_at',
				'p.end_at',
				'p.price',
				'p.currency',
				'p.created_by',
				'p.created_at',
				'p.updated_by',
				'p.updated_at',
				'p.status',
                'p.announcement_id',
			])
			->joinWith([
                'announcement.announcementTranslations at' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        'at.language_id' => Yii::$app->language,
                        'at.deleted' => AnnouncementTranslation::NO,
                    ]);
                },
				'creator cr' => function (ActiveQuery $query) {
					$query->select([
						'cr.id',
						'cr.first_name',
						'cr.middle_name',
						'cr.last_name',
					]);
				}
			])
			->where([
				'p.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Promotional::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Promotional::class => [
				'id',
                'action' => function (Promotional $model) {
                    $actions = [];

                    if ($this->requestParams['deleted'] == Promotional::YES) {
                        if (Yii::$app->user->can('restorePromotional')) {
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
                        if (Yii::$app->user->can('deletePromotional')) {
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
                        if (Yii::$app->user->can('viewPromotional')) {
                            $actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
                                'class' => 'action-view btn btn-xs btn-info',
                                'title' => Yii::t('common', 'View'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('updatePromotional')) {
                            $actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
                                'class' => 'action-update btn btn-xs btn-primary',
                                'title' => Yii::t('common', 'Update'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                    'popup-done' => ['redrawDataTable' => '#dt-promotionals'],
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('deletePromotional')) {
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
                'announcement' => function (Promotional $model) {
		             if ($model->announcement->translation->title) {
                         return Html::a($model->announcement->translation->title, ['/announcement-manager/announcement/view', 'id' => $model->announcement->id],
                         [
                             'class' => 'btn btn-xs btn-default',
                             'title' => Yii::t('common', 'Update'),
                             'data' => [
                                 'toggle' => 'tooltip',
                                 'popup-action' => '',
                                 'popup-done' => ['redrawDataTable' => '#dt-promotionals'],
                             ],
                         ]);
                     }
                    return '&mdash;';
                },
				'start_at' => function (Promotional $model) {
                    return $model->start_at ?: '&mdash;';
                },
                'end_at' => function (Promotional $model) {
                    return $model->end_at ?: '&mdash;';
                },
                'price' => function (Promotional $model) {
                    return $model->price ?: '&mdash;';
                },
                'currency' => function (Promotional $model) {
                    return $model->currency ?: '&mdash;';
                },
				'created_by' => function (Promotional $model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function (Promotional $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Promotional $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Promotional $model) {
					$status = Promotional::getStatusLabels()[$model->status];
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
                case 'announcement':
                    $query->$filterOperator(['LIKE', 'at.title' , $value]);
                    break;
                case 'currency':
                    $query->$filterOperator(['p.currency' => array_filter(explode(',', $value))]);
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
					$query->$filterOperator(['LIKE', 'p.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'p.updated_at', DateHelper::formatAsDate($value)]);
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
                case 'announcement':
                    $query->addOrderBy(['at.title' => $sort]);
                    break;
                case  'start_at':
                    $query->addOrderBy(['p.start_at' => $sort]);
                    break;
                case  'end_at':
                    $query->addOrderBy(['p.end_at' => $sort]);
                    break;
                case  'price':
                    $query->addOrderBy(['p.price' => $sort]);
                    break;
                case  'currency':
                    $query->addOrderBy(['p.currency' => $sort]);
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
                        $query->addOrderBy(['p.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
