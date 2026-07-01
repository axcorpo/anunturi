<?php

namespace frontend\modules\account\models;

use common\models\Reservation;
use DateTime;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\helpers\Url;

class ReceivedReservationSearch extends Model
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
        $this->announcement_id = Yii::$app->request->get('announcement_id');
		$this->start_date = Yii::$app->formatter->asDate(new DateTime('first day of this month'));
		$this->end_date = Yii::$app->formatter->asDate(new DateTime('last day of this month'));
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
		return Reservation::find()
            ->alias('r')
            ->select(['r.*', 'at.title', 'a.created_by'])
			->joinWith([
				'announcement a',
                'announcement.announcementTranslations at' => function (ActiveQuery $query) {
                    $query->andOnCondition(['at.language_id' => Yii::$app->language]);
                 },
			])
			->andFilterWhere([
				'a.id' => $this->announcement_id,
			])
			->andWhere([
				'AND',
				['>=', 'r.start_at', $this->start_date],
				['<=', 'r.start_at', $this->end_date],
			])
            ->andWhere([
                '!=', 'r.created_by', Yii::$app->user->identity->id,
            ])
            ->andWhere([
                '=', 'a.created_by', Yii::$app->user->identity->id,
            ])
			->andWhere([
				'r.status' => Reservation::STATUS_ACTIVE,
				'r.deleted' => Reservation::NO,
			])
			->orderBy(['r.start_at' => SORT_ASC]);
	}

	/**
	 * Gets the FullCalendar events array.
	 *
	 * @return array
	 */
	public function getEvents()
	{
		try {
			$reservations = $this->getActiveQuery()->select([
				'r.id',
				'r.code',
				'r.start_at',
				'r.end_at',
                'at.title',
			]);
			$events = [];

			/** @var Reservation $reservation */
			foreach ($reservations->createCommand()->queryAll() as $reservation) {
				$actions = [];

				if (Yii::$app->user->can('viewReservation')) {
					$actions['view'] = Html::tag('button', '<span class="fa fa-eye"></span>', [
						'type' => 'button',
						'class' => 'btn btn-xs btn-info',
						'title' => Yii::t('common', 'View'),
						'data' => [
							'popup-action' => Url::to(['view', 'id' => $reservation['id']]),
							'popup-css-class' => 'modal-placed-reservation',
							'toggle' => 'tooltip',
						],
					]);
				}
				if (Yii::$app->user->can('viewReservation')) {
					$actions['update'] = Html::tag('button', '<span class="fa fa-edit"></span>', [
						'type' => 'button',
						'class' => 'btn btn-xs btn-primary',
						'title' => Yii::t('common', 'Update'),
						'data' => [
							'popup-action' => Url::to(['update', 'id' => $reservation['id']]),
							'popup-css-class' => 'modal-placed-reservation',
							'toggle' => 'tooltip',
						],
					]);
				}
				if (Yii::$app->user->can('deleteReservation')) {
					$actions['delete'] = Html::tag('button', '<span class="fa fa-trash"></span>', [
						'type' => 'button',
						'class' => 'btn btn-xs btn-danger',
						'title' => Yii::t('common', 'Delete'),
						'data' => [
							'popup-confirm' => Yii::t('common', 'Are you sure you want to perform this action?'),
							'popup-action' => Url::to(['delete', 'id' => $reservation['id']]),
							'popup-method' => 'POST',
							'popup-css-class' => 'modal-placed-reservation',
							'toggle' => 'tooltip',
						],
					]);
				}
				$events[] = [
					'id' => (int) $reservation['id'],
					'title' =>  $reservation['title'] . ' (' . $reservation['code'] . ') ' . Html::tag('span', implode('', $actions), ['class' => 'fc-event-actions']),
					'start' => (new DateTime($reservation['start_at']))->format(DATE_ATOM),
					'end' => (new DateTime($reservation['end_at']))->format(DATE_ATOM),
					'color' => '#2e6da4',
				];
			}

			return $events;
		} catch (\Exception $e) {
			return [];
		}
	}
}
