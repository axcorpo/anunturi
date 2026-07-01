<?php

namespace frontend\modules\announcement\models;

use common\helpers\DateHelper;
use common\models\Announcement;
use common\models\AnnouncementTranslation;
use common\models\Company;
use common\models\Message;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\bootstrap\Dropdown;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class MessageSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Message::find()
			->alias('m')
			->select([
				'm.id',
				'm.parent_id',
                'm.announcement_id',
                'm.conversation_id',
				'm.recipient_id',
				'm.subject',
				'm.content',
				'm.seen_at',
				'm.created_by',
				'm.created_at',
				'm.updated_by',
				'm.updated_at',
				'm.status',
			])
			->joinWith([
                'announcement.announcementTranslations at' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        'at.language_id' => Yii::$app->language,
                        'at.deleted' => AnnouncementTranslation::NO,
                    ]);
                },
                'conversation c',
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
				'm.status' => Message::STATUS_ACTIVE,
                'm.deleted' => Message::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Message::class => [
				'id',
                'action' => function (Message $model) {
                    $actions = [];

                    $actions[] = [
                        'label' => '<span class="action-icon fa fa-eye color-info"></span> ' . Yii::t('common', 'View'),
                        'url' => ['view', 'id' => $model->id],
                        'linkOptions' => [
                            'data' => [
                                'popup-action' => '',
                            ],
                        ],
                    ];
                    $actions[] = [
                        'label' => '<span class="action-icon fa fa-edit color-primary"></span> ' . Yii::t('common', 'Update'),
                        'url' => ['update', 'id' => $model->id],
                        'linkOptions' => [
                            'data' => [
                                'popup-action' => '',
                                'popup-done' => ['redrawDataTable' => '#dt-companies'],
                            ],
                        ],
                    ];

                    $content = [];
                    $content[] = Html::beginTag('div', ['class' => 'dropdown']);
                    $content[] = Html::tag('button', '<span class="fa fa-ellipsis-v"></span>', [
                        'class' => 'dropdown-toggle btn btn-primary btn-xs',
                        'style' => ['padding' => '10px 20px 10px 20px'],                        'data' => [
                            'toggle' => 'dropdown',
                        ],
                    ]);
                    $content[] = Dropdown::widget(['items' => $actions, 'encodeLabels' => false]);
                    $content[] = Html::endTag('div');

                    return $actions ? implode('', $content) : '&mdash;';
                },
                'parent' => function (Message $model) {
                    return $model->parent ?: '&mdash;';
                },
                'announcement' => function (Message $model) {
		             if ($model->announcement->translation->title) {
                         return Html::a($model->announcement->translation->title, ['/announcement-manager/announcement/view', 'id' => $model->announcement->id],
                         [
                             'class' => 'btn btn-xs btn-default',
                             'title' => Yii::t('common', 'Update'),
                             'data' => [
                                 'toggle' => 'tooltip',
                                 'popup-action' => '',
                                 'popup-done' => ['redrawDataTable' => '#dt-announcements'],
                             ],
                         ]);
                     }
                    return '&mdash;';
                },
                'recipient' => function (Message $model) {
                    return $model->recipient_id ?: '&mdash;';
                },
                'subject' => function (Message $model) {
                    return $model->subject ?: '&mdash;';
                },
                'content' => function (Message $model) {
                    return $model->content ?: '&mdash;';
                },
                'seen_at' => function (Message $model) {
                    return $model->seen_at ? Yii::$app->formatter->asDatetime($model->seen_at) : '&mdash;';
                },
				'created_by' => function (Message $model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function (Message $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Message $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Message $model) {
					$status = Message::getStatusLabels()[$model->status];
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
