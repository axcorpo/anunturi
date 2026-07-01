<?php

namespace frontend\modules\account\models;

use common\helpers\DateHelper;
use common\models\Commercial;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\bootstrap\Dropdown;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class CommercialSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Set the query
		$this->query = Commercial::find()
			->alias('co')
			->select([
				'co.id',
				'co.bid_id',
				'co.image',
				'co.url',
				'co.created_at',
				'co.updated_at',
				'co.created_by',
				'co.status',
			])
			->joinWith([
				'bid b',
				'bid.broker.user u',
				'creator c' => function (ActiveQuery $query) {
					$query->select([
						'c.id',
						'c.first_name',
						'c.middle_name',
						'c.last_name',
					]);
				},
			])
			->where([
				'co.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Commercial::NO,
			])
			->groupBy(['co.id']);
	}

    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function formatData(ActiveQuery $query, $columns)
    {
        $data = [];

        foreach ($query->all() as $model) {
            $data[] = [
                'id' => (int) $model->id,
                'action' => call_user_func(function () use ($model) {
                    $actions = [];

                    $viewAction = [
                        'label' => '<span class="action-icon fa fa-eye color-info"></span> ' . Yii::t('common', 'View'),
                        'url' => ['view', 'id' => $model->id],
                        'linkOptions' => [
                            'data' => [
                                'popup-action' => '',
                            ],
                        ],
                    ];
                    $updateAction = [
                        'label' => '<span class="action-icon fa fa-edit color-primary"></span> ' . Yii::t('common', 'Update'),
                        'url' => ['update', 'id' => $model->id],
                        'linkOptions' => [
                            'data' => [
                                'popup-action' => '',
                                'popup-done' => ['redrawDataTable' => '#dt-commercials'],
                            ],
                        ],
                    ];
                    $deleteAction = [
                        'label' => '<span class="action-icon fa fa-ban color-danger"></span> ' . Yii::t('common', 'Delete'),
                        'url' => ['delete', 'id' => $model->id],
                        'linkOptions' => [
                            'data' => [
                                'method' => 'POST',
                                'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                            ],
                        ],
                    ];


                    $actions[] = $viewAction;
                    $actions[] = $updateAction;
                    $actions[] = $deleteAction;

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
                }),
                'image' => call_user_func(function () use ($model) {
                    if ($model->imageUrl && is_file(Yii::getAlias("@uploads/commercial/{$model->id}/{$model->image}"))) {
                        return $model->imageUrl;
                    }
                    return '&mdash;';
                }),
                'bid' => call_user_func(function () use ($model) {
                    if ($model->bid) {
                        return $model->bid->bidName;
                    }
                    return '&mdash;';
                }),
                'url',
                'broker' => call_user_func(function () use ($model) {
                    if ($model->bid->broker->user) {
                        return $model->bid->broker->user->fullName;
                    }
                    return '&mdash;';

                }),
                'status' => call_user_func(function () use ($model) {
                    $status = Commercial::getStatusLabels()[$model->status];
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
				case 'bid':
					$query->$filterOperator(['=', 'b.id' . $column['data'], $value]);
					break;
				case 'broker':
					$query->$filterOperator([
						'OR',
						['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.middle_name, u.last_name)') , $value],
						['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.last_name)') , $value],
						['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.last_name)') , $value],
					]);
					break;
				case 'created_by':
					$query->$filterOperator([
						'OR',
						['LIKE', 'co.first_name', $value],
						['LIKE', 'co.middle_name', $value],
						['LIKE', 'co.last_name', $value],
					]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'co.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'co.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					// Apply default filter if column exist in table schema
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'co.' . $column['data'], $value]);
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
				case 'bid':
					$query->addOrderBy(['b.id' => $sort]);
					break;
				case 'broker':
					$query->addOrderBy([
						'u.first_name' => $sort,
						'u.middle_name' => $sort,
						'u.last_name' => $sort,
					]);
					break;
				case 'created_by':
					$query->addOrderBy([
						'co.first_name' => $sort,
						'co.middle_name' => $sort,
						'co.last_name' => $sort,
					]);
					break;
				default:
					// Apply default order
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['co.' . $column['data'] => $sort]);
                    }
					break;
			}
		}
		return $query;
	}
}
