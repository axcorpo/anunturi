<?php

namespace backend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\Page;
use common\models\ReviewTranslation;
use common\models\Company;
use common\models\Review;
use common\models\Subscriber;
use common\models\User;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

class ReviewSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Review::find()
			->alias('r')
			->select([
				'r.id',
				'r.score',
				'r.ip_address',
				'r.type',
				'r.created_by',
				'r.created_at',
				'r.updated_by',
				'r.updated_at',
				'r.status',
                'r.announcement_id',
                'r.company_id',
                'r.subscriber_id',
                'rt.title',
                'rt.content',
			])
			->joinWith([
                'announcement.announcementTranslations at' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        'at.language_id' => Yii::$app->language,
                        'at.deleted' => ReviewTranslation::NO,
                    ]);
                },
                'reviewTranslations rt' => function (ActiveQuery $query){
                    return $query->andOnCondition([
                        'rt.language_id' => Yii::$app->language,
                        'rt.deleted' => ReviewTranslation::NO,
                    ]);
                },
                'company c' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        'c.deleted' => Company::NO,
                    ]);
                },
                'subscriber s' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        's.deleted' => Subscriber::NO,
                    ]);
                },
                'subscriber.user su' => function (ActiveQuery $query) {
                    $query->select([
                        'su.id',
                        'su.first_name',
                        'su.middle_name',
                        'su.last_name',
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
				'r.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Review::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Review::class => [
				'id',
                'action' => function (Review $model) {
                    $actions = [];

                    if ($this->requestParams['deleted'] == Review::YES) {
                        if (Yii::$app->user->can('restoreReview')) {
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
                        if (Yii::$app->user->can('deleteReview')) {
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
                        if (Yii::$app->user->can('viewReview')) {
                            $actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
                                'class' => 'action-view btn btn-xs btn-info',
                                'title' => Yii::t('common', 'View'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('updateReview')) {
                            $actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
                                'class' => 'action-update btn btn-xs btn-primary',
                                'title' => Yii::t('common', 'Update'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                    'popup-done' => ['redrawDataTable' => '#dt-reviews'],
                                ],
                            ]);
                        }
                        if (Yii::$app->user->can('deleteReview')) {
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
                'type' => function (Review $model) {
                    return Review::getReviewTypes()[$model->type];
                },
                'review' => function (Review $model) {
                    if ($model->type == Review::REVIEW_TYPE_ANNOUNCEMENT) {
                        return $model->announcement->translation->title;
                    } elseif ($model->type == Review::REVIEW_TYPE_SUBSCRIBER) {
                        return $model->subscriber->user->fullName;
                    } elseif ($model->type == Review::REVIEW_TYPE_COMPANY) {
                        return $model->company->name;
                    }
                    return '&mdash;';
                },
                'score' => function (Review $model) {
                    return $model->score ?: '&mdash;';
                },
                'title' => function (Review $model) {
                    return $model->translation->title ?: '&mdash;';
                },
                'content' => function (Review $model) {
                    return $model->translation->content ?: '&mdash;';
                },
                'ip_address' => function (Review $model) {
                    return $model->ip_address ?: '&mdash;';
                },
				'created_by' => function (Review $model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function (Review $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Review $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Review $model) {
					$status = Review::getStatusLabels()[$model->status];
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
                case 'type':
                    if (!empty($value) && is_string($value)) {
                        $value = explode(',', $value);
                    }
                    $query->$filterOperator(['r.type' => $value]);
                    break;
                case 'announcement':
                    $query->$filterOperator(['LIKE', 'at.title' , $value]);
                    break;
                case 'subscriber':
                    $query->$filterOperator([
                        'OR',
                        ['LIKE', new Expression('CONCAT_WS(" ", su.first_name, su.middle_name, su.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.first_name, su.last_name, su.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.last_name, su.middle_name, su.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.last_name, su.first_name, su.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.middle_name, su.first_name, su.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.middle_name, su.last_name, su.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.first_name, su.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.first_name, su.middle_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.middle_name, su.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.middle_name, su.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.last_name, su.first_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", su.last_name, su.middle_name)') , $value],
                    ]);
                    break;
                case 'company':
                    $query->$filterOperator(['LIKE', 'c.name' , $value]);
                    break;
                case 'title':
                    $query->$filterOperator(['LIKE', 'rt.title' , $value]);
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
                case 'subscriber':
                    $query->addOrderBy([
                        'su.first_name' => $sort,
                        'su.middle_name' => $sort,
                        'su.last_name' => $sort,
                    ]);
                    break;
                case 'company':
                    $query->addOrderBy(['c.name' => $sort]);
                    break;
                case 'title':
                    $query->addOrderBy(['rt.title' => $sort]);
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
