<?php

namespace frontend\modules\account\models;

use common\models\Reservation;
use common\models\Unavailability;
use Yii;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class UnavailabilityForm extends Unavailability
{

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
        $this->status = self::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
            [['start_at', 'end_at'], 'validateDates', 'skipOnEmpty' => false, 'skipOnError' => false],
        ]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();
	}

    /**
     * Validates the placed-reservation dates.
     */
    public function validateDates()
    {
        $startAt = date('Y-m-d H:i:s', strtotime($this->start_at));
        $endAt = date('Y-m-d H:i:s', strtotime($this->end_at));
        $reservation = Reservation::findOne(['id' => $this->id]);
        /* FOR UNAVAILABILITIES DATES*/
        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
            ->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'u.status' => Unavailability::STATUS_ACTIVE,
                'u.deleted' => Unavailability::NO,
            ])
            // both dates in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[u.start_at]], '{$startAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[u.end_at]], '{$endAt}') <= 0 "),
            ]);
        $models = $query->asArray()->all();

        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
            ->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'u.status' => Unavailability::STATUS_ACTIVE,
                'u.deleted' => Unavailability::NO,
            ])
            // start date outside, end date in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[u.start_at]], '{$endAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[u.end_at]], '{$endAt}') <= 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
            ->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'u.status' => Unavailability::STATUS_ACTIVE,
                'u.deleted' => Unavailability::NO,
            ])
            // end date in afara, start date in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[u.start_at]], '{$startAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[u.end_at]], '{$startAt}') <= 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
        }

        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
            ->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'u.status' => Unavailability::STATUS_ACTIVE,
                'u.deleted' => Unavailability::NO,
            ])
            // enddate in afara, startdate in afara
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[u.start_at]], '{$startAt}') < 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[u.end_at]], '{$endAt}') > 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        /* FOR RESERVATION PERIODS */
        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
            ->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            // both dates in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[r.start_at]], '{$startAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[r.end_at]], '{$endAt}') <= 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
            ->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            // start date outside, end date in interval -> BUN
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[r.start_at]], '{$endAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[r.end_at]], '{$endAt}') <= 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
            ->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            // end date in afara, start date in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[r.start_at]], '{$startAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[r.end_at]], '{$startAt}') <= 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
        }

        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
            ->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ])
            ->andWhere([
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            // enddate in afara, startdate in afara
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[r.start_at]], '{$startAt}') < 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[r.end_at]], '{$endAt}') > 0 "),
            ]);
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }
    }


	/**
	 * Saves the model.
	 *
	 * @return bool
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
		    if (Yii::$app->request->get('announcement_id')) {
		       $this->announcement_id = Yii::$app->request->get('announcement_id');
            }
			if (!$this->save()) {
				throw new \Exception();
			}
			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
