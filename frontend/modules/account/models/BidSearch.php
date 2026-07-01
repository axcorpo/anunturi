<?php

namespace frontend\modules\account\models;

use common\helpers\DateHelper;
use common\models\Auction;
use common\models\Package;
use common\models\PackageTranslation;
use common\models\Subscriber;
use common\models\Bid;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\bootstrap\Dropdown;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\Html;

class BidSearch extends DataTableAction
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->query = Bid::find()
            ->alias('b')
            ->select([
                'b.id',
                'b.auction_id',
                'b.broker_id',
                'b.price',
                'b.currency',
                'b.created_at',
                'b.updated_at',
                'b.created_by',
                'b.status',
            ])
            ->joinWith([
                'broker br',
                'broker.user u',
                'auction au',
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
                'b.deleted' => Bid::NO,
            ])
            ->andWhere([
                'b.created_by' => Yii::$app->user->identity->id,
            ]);
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
                                'popup-done' => ['redrawDataTable' => '#dt-bids'],
                            ],
                        ],
                    ];
                    $cancelAction = [
                        'label' => '<span class="action-icon fa fa-ban color-danger"></span> ' . Yii::t('common', 'Cancel'),
                        'url' => ['cancel', 'id' => $model->id],
                        'linkOptions' => [
                            'data' => [
                                'method' => 'POST',
                                'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                            ],
                        ],
                    ];

                    $actions[] = $viewAction;
                    $actions[] = $updateAction;
                    $actions[] = $cancelAction;

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
                'auction' => call_user_func(function () use ($model) {
                    if ($model->auction) {
                        return $model->auction->id . ' - ' . Auction::getPositionLabels()[$model->auction->position];
                    }
                    return '&mdash;';
                }),
                'broker' => call_user_func(function () use ($model) {
                    if ($model->broker->user) {
                        return $model->broker->user->fullName;
                    }
                    return '&mdash;';

                }),
                'price' => call_user_func(function () use ($model) {
                    return implode(' ', array_filter([
                        Yii::$app->formatter->asCurrency($model->price, $model->currency),
                    ]));
                }),
				'position' => call_user_func(function () use ($model) {
					$count = Bid::find()
						->where([
							'AND',
							['=', 'auction_id', $model->auction_id],
							['>', 'price', $model->price],
						])
						->count();
					return $count + 1;
				}),
				'created_at' => call_user_func(function () use ($model) {
					return Yii::$app->formatter->asDatetime($model->created_at);
				}),
                'status' => call_user_func(function () use ($model) {
                    $status = Bid::getStatusLabels()[$model->status];
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
                $value = trim($search['value']);
                $filterOperator = 'orFilterWhere';
            } else {
                $value = trim($column['search']['value']);
                $filterOperator = 'andFilterWhere';
            }

            switch ($column['data']) {
                case 'auction':
                    $query->$filterOperator(['LIKE', 'au.id', $value]);
                    break;
                case 'broker':
                    $query->$filterOperator([
                        'OR',
                        ['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.middle_name, u.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.first_name, u.last_name)') , $value],
                        ['LIKE', new Expression('CONCAT_WS(" ", u.middle_name, u.last_name)') , $value],
                    ]);
                    break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'b.created_at', DateHelper::formatAsDate($value)]);
					break;
                default:
                    if (array_key_exists($column['data'], $schema)) {
                        $query->$filterOperator(['LIKE', 'b.' . $column['data'], $value]);
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
                case 'auction':
                    $query->addOrderBy(['au.id' => $sort]);
                    break;
                case 'broker':
                    $query->addOrderBy([
                        'u.first_name' => $sort,
                        'u.middle_name' => $sort,
                        'u.last_name' => $sort,
                    ]);
                    break;
                default:
                    if (array_key_exists($column['data'], $schema)) {
                        $query->addOrderBy(['b.' . $column['data'] => $sort]);
                    }
                    break;
            }
        }
        return $query;
    }
}
