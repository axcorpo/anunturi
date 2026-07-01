<?php

namespace frontend\modules\account\models;

use common\models\AnnouncementTranslation;
use common\models\Unavailability;
use DateTime;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\helpers\Url;

class UnavailabilitySearch extends Model
{
	/**
	 * @var string The start date of the search interval.
	 */
	public $start_date;

	/**
	 * @var string The end date of the search interval.
	 */
	public $end_date;

	/**
	 * @var array The Announcement model IDs.
	 */
	public $announcement_id;

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public function init()
	{
		parent::init();
		$this->start_date = Yii::$app->formatter->asDateTime(new DateTime('first day of this month'));
		$this->end_date = Yii::$app->formatter->asDateTime(new DateTime('last day of this month'));
	}

	/**
	 * @inheritdoc
	 */
	public function formName() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['start_date', 'end_date'], 'safe'],
			[['announcement_id'], 'each', 'rule' => ['integer']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'start_date' => Yii::t('common', 'Start Date'),
			'end_date' => Yii::t('common', 'End Date'),
			'announcement_id' => Yii::t('common', 'Announcement'),
		];
	}

	/**
	 * Gets the ActiveQuery.
	 *
	 * @return ActiveQuery|\common\models\CommonActiveQuery
	 */
	public function getActiveQuery()
	{
		$query = Unavailability::find()
			->alias('u')
			->joinWith([
				'announcement a',
                'announcement.announcementTranslations at' => function (ActiveQuery $query) {
                    return $query->andOnCondition([
                        'at.language_id' => Yii::$app->language,
                        'at.deleted' => AnnouncementTranslation::NO,
                    ]);
                },
			])
			->andWhere([
				'u.status' => Unavailability::STATUS_ACTIVE,
				'u.deleted' => Unavailability::NO,
			])
			->orderBy(['u.start_at' => SORT_ASC]);
		if (Yii::$app->request->get('announcement_id')) {
			$query->andWhere([
				'u.announcement_id' => Yii::$app->request->get('announcement_id'),
			]);
		} else {
			$query->andWhere([
				'u.announcement_id' => null,
                'u.created_by' => Yii::$app->user->id,
			]);
		}
		return $query;
	}

	/**
	 * Gets the FullCalendar events array.
	 *
	 * @return array
	 */
	public function getEvents()
	{
		try {
			$unavailabilities = $this->getActiveQuery()->select([
				'u.id',
				'u.start_at',
				'u.end_at',
				'at.title',
			]);

			$events = [];

			foreach ($unavailabilities->createCommand()->queryAll() as $unavailability) {
				$actions = [];

					$actions['update'] = Html::tag('button', '<span class="fa fa-edit"></span>', [
						'type' => 'button',
						'class' => 'btn btn-xs btn-info',
						'title' => Yii::t('common', 'Update'),
						'data' => [
							'popup-action' => Url::to(['/account/unavailability/update', 'id' => $unavailability['id']]),
							'popup-css-class' => 'modal-unavailability',
							'toggle' => 'tooltip',
						],
					]);

					$actions['delete'] = Html::tag('button', '<span class="fa fa-trash"></span>', [
						'type' => 'button',
						'class' => 'btn btn-xs btn-danger',
						'title' => Yii::t('common', 'Delete'),
						'data' => [
							'popup-confirm' => Yii::t('common', 'Are you sure you want to perform this action?'),
							'popup-action' => Url::to(['/account/unavailability/delete', 'id' => $unavailability['id']]),
							'popup-method' => 'POST',
							'popup-css-class' => 'modal-unavailability',
							'toggle' => 'tooltip',
						],
					]);

				$events[] = [
					'id' => (int) $unavailability['id'],
					'title' => 'Unavailable' . ' ' . Html::tag('span', implode('', $actions)) ?: Yii::t('frontend', 'Unavailable') . ' ' . Html::tag('span', implode('', $actions), ['class' => 'fc-event-actions']),
					'start' => (new DateTime($unavailability['start_at']))->format(DATE_ATOM),
					'end' => (new DateTime($unavailability['end_at']))->format(DATE_ATOM),
					'color' => '#2e6da4',
				];
			}

			return $events;
		} catch (\Exception $e) {
			return [];
		}
	}
}
