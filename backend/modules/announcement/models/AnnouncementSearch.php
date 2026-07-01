<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\Category;
use common\models\Announcement;
use common\models\AnnouncementTranslation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class AnnouncementSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Announcement::find()
			->alias('a')
			->select([
				'a.id',
				'a.image',
				'a.price',
				'a.currency',
				'a.uom',
				'a.sort_order',
				'a.created_by',
				'a.created_at',
				'a.updated_at',
				'a.status',
				'at.title',
			])
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
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
				'a.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Announcement::NO,
				'a.id' => new Expression('at.announcement_id'),
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Announcement::class => [
				'id',
                'action' => function (Announcement $model) {
                    $actions = [];

                    if ($this->requestParams['deleted'] == Announcement::YES) {
                        if (Yii::$app->user->can('restoreAnnouncement')) {
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
                        if (Yii::$app->user->can('deleteAnnouncement')) {
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
                        if (Yii::$app->user->can('viewAnnouncement')) {
                            $actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
                                'class' => 'action-view btn btn-xs btn-info',
                                'title' => Yii::t('common', 'View'),
                                'data' => [
                                    'toggle' => 'tooltip',
//                                    'popup-action' => '',
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('updateAnnouncement')) {
                            $actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
                                'class' => 'action-update btn btn-xs btn-primary',
                                'title' => Yii::t('common', 'Update'),
                                'data' => [
                                    'toggle' => 'tooltip',
//                                    'popup-action' => '',
//                                    'popup-done' => ['redrawDataTable' => '#dt-announcements'],
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('deleteAnnouncement')) {
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
				'image' => function (Announcement $model) {
		            if ($model->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$model->id}/{$model->image}")))
                    {
                        return $model->imageUrl;
                    }
					return '&mdash;';
				},
				'title' => function (Announcement $model) {
					return $model->translation->title;
				},
                'price' => function (Announcement $model) {
		            if ($model->price) {
                        return Yii::$app->formatter->asCurrency($model->price, $model->currency);
                    }
		            return '&mdash;';
                },
                'uom',
				'sort_order' => function (Announcement $model) {
					return $model->sort_order ?: '&mdash;';
				},
				'created_by' => function (Announcement $model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function (Announcement $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Announcement $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Announcement $model) {
					$status = Announcement::getStatusLabels()[$model->status];
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
				case 'title':
					$query->$filterOperator(['LIKE', 'at.title', $value]);
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
				case 'title':
					$query->addOrderBy(['at.title' => $sort]);
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
                        $query->addOrderBy(['a.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
