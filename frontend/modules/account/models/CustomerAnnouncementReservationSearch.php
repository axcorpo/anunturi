<?php

namespace frontend\modules\account\models;

use common\models\AnnouncementTranslation;
use common\models\Reservation;
use common\models\Unavailability;
use DateTime;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\helpers\Url;

class CustomerAnnouncementReservationSearch extends Model
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
        $query = Reservation::find()
            ->alias('r')
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
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            ->andWhere([
                'a.created_by' => Yii::$app->user->identity->id,
            ])
            ->orderBy(['r.start_at' => SORT_ASC]);
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
            $reservations = $this->getActiveQuery()->select([
                'r.id',
                'r.start_at',
                'r.end_at',
                'at.title',
            ]);

            $events = [];

            foreach ($reservations->createCommand()->queryAll() as $reservation) {

                $events[] = [
                    'id' => (int) $reservation['id'],
                    'title' => 'Reserved' . ' ' . $reservation['title'] ?: Yii::t('frontend', 'Unavailable'),
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
