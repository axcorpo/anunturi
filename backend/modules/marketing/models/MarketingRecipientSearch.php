<?php

namespace backend\modules\marketing\models;

use common\helpers\DateHelper;
use common\models\MarketingRecipient;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class MarketingRecipientSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = MarketingRecipient::find()
			->alias('mr')
			->select([
				'mr.id',
				'mr.name',
				'mr.email',
				'mr.phone',
				'mr.created_at',
				'mr.status',
			])
            ->joinWith([
                'marketingGroups.marketingGroupTranslations mgt' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'mgt.language_id' => Yii::$app->language,
                        'mgt.deleted' => MarketingRecipient::NO,
                    ]);
                },
            ])
			->andWhere([
				'mr.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : MarketingRecipient::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			MarketingRecipient::class => [
				'id',
				'action' => function (MarketingRecipient $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == MarketingRecipient::YES) {
						if (Yii::$app->user->can('restoreMarketingRecipient')) {
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
						if (Yii::$app->user->can('deleteMarketingRecipient')) {
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
						if (Yii::$app->user->can('viewMarketingRecipient')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateMarketingRecipient')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteMarketingRecipient')) {
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
				'name' => function (MarketingRecipient $model) {
					return $model->name ?: '&mdash;';
				},
				'email' => function (MarketingRecipient $model) {
					return $model->email ?: '&mdash;';
				},
				'phone' => function (MarketingRecipient $model) {
					return $model->phone ?: '&mdash;';
				},
                'group' => function (MarketingRecipient $model) {
                    if ($model->marketingGroups) {
                        return implode(', ', ArrayHelper::getColumn($model->marketingGroups, 'translation.name'));
                    }
                    return '&mdash;';
                },
				'created_at' => function (MarketingRecipient $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'status' => function (MarketingRecipient $model) {
					$status = MarketingRecipient::getStatusLabels()[$model->status];
					return Html::tag('span', $status['label'], ['class' => 'label label-block label-' . $status['color']]);
				},
			],
		]);
	}

    /**
     * @inheritdoc
     */
    public function exportFormatData(ActiveQuery $query, $columns)
    {
        return ArrayHelper::toArray($query->all(), [
            MarketingRecipient::class => [
                'name' => function (MarketingRecipient $model) {
                    return $model->name;
                },
                'email' => function (MarketingRecipient $model) {
                    return $model->email;
                },
                'phone' => function (MarketingRecipient $model) {
                    return $model->phone;
                },
                'created_at' => function (MarketingRecipient $model) {
                    return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '';
                },
                'status' => function (MarketingRecipient $model) {
                    return MarketingRecipient::getStatusLabels()[$model->status]['label'];
                },
            ],
        ]);
    }

    /**
     * Gets data for the export action.
     *
     * @param ActiveQuery $query
     * @param array $columns
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getExportData(ActiveQuery $query, $columns = []): array
    {
        $data = $this->exportFormatData($query, $columns);
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
				$value = trim($search['value']);
				$filterOperator = 'orFilterWhere';
			} else {
				$value = trim($column['search']['value']);
				$filterOperator = 'andFilterWhere';
			}

			switch ($column['data']) {
                case 'group':
                    $query->$filterOperator(['mgt.marketing_group_id' => array_filter(explode(',', $value))]);
                    break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'mr.created_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'mr.' . $column['data'], $value]);
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
                case 'group':
                    $query->addOrderBy([
                        'mgt.name' => $sort,
                    ]);
                    break;
				default:
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['mr.' . $column['data'] => $sort]);
                    }
                break;
			}
		}
		return $query;
	}
}
