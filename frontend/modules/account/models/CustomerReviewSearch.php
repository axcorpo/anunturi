<?php

namespace frontend\modules\account\models;

use common\helpers\DateHelper;
use common\models\ReviewTranslation;
use common\models\Company;
use common\models\Review;
use common\models\Subscriber;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\bootstrap\Dropdown;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class CustomerReviewSearch extends DataTableAction
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
			    'announcement a',
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
                'OR',
                ['=', 'a.created_by', Yii::$app->user->identity->id],
                ['=', 's.user_id', Yii::$app->user->identity->id],
                ['=', 'c.user_id', Yii::$app->user->identity->id],
            ])
            ->andWhere([
                'r.deleted' => Review::NO,
            ])
            ->andWhere([
                '!=', 'r.confirmed', Review::REVIEW_REJECTED,
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
                    if ($model->status == Review::REVIEW_CONFIRMED) {
                        $actions[] = [
                            'label' => '<span class="action-icon fa fa-eye color-info"></span> ' . Yii::t('common', 'View'),
                            'url' => ['view', 'id' => $model->id],
                            'linkOptions' => [
                                'data' => [
                                    'popup-action' => '',
                                ],
                            ],
                        ];
                    }
//                    $actions[] = [
//                        'label' => '<span class="action-icon fa fa-edit color-primary"></span> ' . Yii::t('common', 'Update'),
//                        'url' => ['update', 'id' => $model->id],
//                        'linkOptions' => [
//                            'data' => [
//                                'popup-action' => '',
//                                'popup-done' => ['redrawDataTable' => '#dt-companies'],
//                            ],
//                        ],
//                    ];
                    if ($model->status == Review::REVIEW_PENDING) {
                        $actions[] = [
                            'label' => '<span class="action-icon fa fa-edit color-primary"></span> ' . Yii::t('common', 'Confirm'),
                            'url' => ['confirm', 'id' => $model->id],
                            'linkOptions' => [
                                'data' => [
                                    'popup-action' => '',
                                ],
                            ],
                        ];
                    }

                    $content = [];
                    $content[] = Html::beginTag('div', ['class' => 'dropdown']);
                    $content[] = Html::tag('button', '<span class="fa fa-ellipsis-v"></span>', [
                        'class' => 'dropdown-toggle btn btn-primary btn-xs',
                        'style' => ['padding' => '10px 20px 10px 20px'],
                        'data' => [
                            'toggle' => 'dropdown',
                        ],
                    ]);
                    $content[] = Dropdown::widget(['items' => $actions, 'encodeLabels' => false]);
                    $content[] = Html::endTag('div');

                    return $actions ? implode('', $content) : '&mdash;';
                },
                'type' => function (Review $model) {
                    return Review::getReviewTypes()[$model->type];
                },
                'placed-review' => function (Review $model) {
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
		            if ($model->status == Review::REVIEW_CONFIRMED) {
                        return $model->score;
                    }
                    else {
                        return '&mdash;';
                    }
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
                case 'title':
                    $query->$filterOperator(['LIKE', 'rt.title' , $value]);
                    break;
                case 'currency':
                    $query->$filterOperator(['p.currency' => array_filter(explode(',', $value))]);
                    break;
				case 'created_by':
					$query->$filterOperator([
						'OR',
						['LIKE', 'cr.first_name', $value],
						['LIKE', 'cr.middle_name', $value],
						['LIKE', 'cr.last_name', $value],
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
                case 'placed-review':
                    $query->addOrderBy(['at.title' => $sort]);
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
