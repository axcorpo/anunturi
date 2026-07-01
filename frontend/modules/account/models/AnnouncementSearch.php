<?php

namespace frontend\modules\account\models;

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

				'image' => function (Announcement $model) {
		            if ($model->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$model->id}/{$model->image}"))) {
                        return $model->imageUrl;
                    }
		            return '&mdash;';
				},
				'title' => function (Announcement $model) {
					return $model->translation->title;
				},
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
						['LIKE', 'cr.first_name', $value],
						['LIKE', 'cr.middle_name', $value],
						['LIKE', 'cr.last_name', $value],
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
