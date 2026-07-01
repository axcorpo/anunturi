<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\AnnouncementTranslation;
use common\models\Reservation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class ReservationSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Reservation::find()
			->alias('r')
			->select([
				'r.id',
				'r.start_at',
				'r.end_at',
				'r.phone',
				'r.details',
				'r.price',
				'r.currency',
				'r.period',
				'r.frequency',
				'r.ip_address',
				'r.created_by',
				'r.created_at',
				'r.updated_by',
				'r.updated_at',
				'r.status',
                'r.announcement_id',
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
				'r.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Reservation::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Reservation::class => [
				'id',
                'action' => function (Reservation $model) {
                    $actions = [];

                    if ($this->requestParams['deleted'] == Reservation::YES) {
                        if (Yii::$app->user->can('restoreReservation')) {
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
                        if (Yii::$app->user->can('deleteReservation')) {
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
                        if (Yii::$app->user->can('viewReservation')) {
                            $actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
                                'class' => 'action-view btn btn-xs btn-info',
                                'title' => Yii::t('common', 'View'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('updateReservation')) {
                            $actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
                                'class' => 'action-update btn btn-xs btn-primary',
                                'title' => Yii::t('common', 'Update'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                    'popup-done' => ['redrawDataTable' => '#dt-reservations'],
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('deleteReservation')) {
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
                'announcement' => function (Reservation $model) {
                    if ($model->announcement->translation->title) {
                        return Html::a($model->announcement->translation->title, ['/announcement-manager/announcement/view', 'id' => $model->announcement->id],
                            [
                                'class' => 'btn btn-xs btn-default',
                                'title' => Yii::t('common', 'Update'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                    'popup-done' => ['redrawDataTable' => '#dt-reservations'],
                                ],
                            ]);
                    }
                    return '&mdash;';
                },
				'start_at' => function (Reservation $model) {
                    return $model->start_at ?: '&mdash;';
                },
                'end_at' => function (Reservation $model) {
                    return $model->end_at ?: '&mdash;';
                },
                'phone' => function (Reservation $model) {
                    return $model->phone ?: '&mdash;';
                },
                'details' => function (Reservation $model) {
                    return $model->details ?: '&mdash;';
                },
                'price' => function (Reservation $model) {
                    return $model->price ?: '&mdash;';
                },
                'currency' => function (Reservation $model) {
                    return $model->currency ?: '&mdash;';
                },
                'period' => function (Reservation $model) {
                    return $model->period ?: '&mdash;';
                },
                'frequency' => function (Reservation $model) {
                    return $model->frequency ?: '&mdash;';
                },
                'ip_address' => function (Reservation $model) {
                    return $model->ip_address ?: '&mdash;';
                },
				'created_by' => function (Reservation $model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function (Reservation $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Reservation $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Reservation $model) {
					$status = Reservation::getStatusLabels()[$model->status];
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
                case 'phone':
                    $query->$filterOperator(['LIKE', 'r.phone' , $value]);
                    break;
                case 'currency':
                    $query->$filterOperator(['r.currency' => array_filter(explode(',', $value))]);
                    break;
                case 'period':
                    $query->$filterOperator(['LIKE', 'r.period' , $value]);
                    break;
                case 'frequency':
                    $query->$filterOperator(['LIKE', 'r.frequency' , $value]);
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
					$query->$filterOperator(['LIKE', 'r.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'r.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'r.' . $column['data'], $value]);
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
                        $query->addOrderBy(['r.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
