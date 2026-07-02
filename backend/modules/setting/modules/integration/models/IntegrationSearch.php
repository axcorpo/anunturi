<?php

namespace backend\modules\setting\modules\integration\models;

use common\models\Integration;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class IntegrationSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Integration::find()->where([
			'deleted' => Integration::NO,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Integration::class => [
				'id',
				'action' => function (Integration $model) {
					$actions = [];

					if (Yii::$app->user->can('viewIntegration')) {
						$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
							'class' => 'action-view btn btn-xs btn-info',
							'title' => Yii::t('common', 'View'),
							'data' => [
								'toggle' => 'tooltip',
							],
						]);
					}

					if (Yii::$app->user->can('updateIntegration')) {
						$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
							'class' => 'action-update btn btn-xs btn-primary',
							'title' => Yii::t('common', 'Update'),
							'data' => [
								'toggle' => 'tooltip',
							],
						]);
					}

					if (Yii::$app->user->can('deleteIntegration')) {
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

					$actions = array_map(function ($actionsChunk) {
						return Html::tag('div', implode('', $actionsChunk));
					}, array_chunk($actions, 1));

					return implode('', $actions);
				},
				'name',
				'type' => function (Integration $model) {
					return Integration::getTypeLabels()[$model->type];
				},
				'expire_at' => function (Integration $model) {
					return $model->expire_at ? Yii::$app->formatter->asDatetime($model->expire_at) : '&mdash;';
				},
				'sandbox',
				'status',
			],
		]);
	}
}
