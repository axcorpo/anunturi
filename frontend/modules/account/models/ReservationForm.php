<?php

namespace frontend\modules\account\models;

use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Category;
use common\models\Picture;
use common\models\Reservation;
use common\models\Unavailability;
use common\models\User;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\web\UploadedFile;

class ReservationForm extends Reservation
{

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {

        parent::init();
        $this->start_at = Yii::$app->formatter->asDatetime('now');
        $this->end_at = Yii::$app->formatter->asDatetime('today 23:59:59');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['start_at', 'end_at'], 'required'],
            [['start_at', 'end_at'], 'validateDates', 'skipOnEmpty' => false, 'skipOnError' => false],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'announcement_id' => Yii::t('label', 'Announcement'),
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
                'u.status' => Unavailability::STATUS_ACTIVE,
                'u.deleted' => Unavailability::NO,
            ])
            // both dates in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[u.start_at]], '{$startAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[u.end_at]], '{$endAt}') <= 0 "),
            ]);
        if (!empty($this->id)) {
            $query->andWhere([
                'u.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
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
        if (!empty($this->id)) {
            $query->andWhere([
                'u.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
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

        if (!empty($this->id)) {
            $query->andWhere([
                'u.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
        }

        $query = Unavailability::find()
            ->alias('u')
            ->select('u.*')
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
        if (!empty($this->id)) {
            $query->andWhere([
                'u.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'u.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
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
                'r.status' => Reservation::STATUS_ACTIVE,
                'r.deleted' => Reservation::NO,
            ])
            // both dates in interval
            ->andWhere([
                'AND',
                new Expression("TIMESTAMPDIFF(SECOND, [[r.start_at]], '{$startAt}') >= 0 "),
                new Expression("TIMESTAMPDIFF(SECOND, [[r.end_at]], '{$endAt}') <= 0 "),
            ]);

        if (!empty($this->id)) {
            $query->andWhere([
                '!=', 'r.id', $this->id,
            ])
            ->andWhere([
                'r.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
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

        if (!empty($this->id)) {
            $query->andWhere([
                '!=', 'r.id', $this->id,
            ])
            ->andWhere([
                'r.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('end_at', Yii::t('frontend', 'End date is not available.'));
        }

        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
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

        if (!empty($this->id)) {
            $query->andWhere([
                '!=', 'r.id', $this->id,
            ])
            ->andWhere([
                'r.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
        $models = $query->asArray()->all();
        if (!empty($models)) {
            $this->addError('start_at', Yii::t('frontend', 'Start date is not available.'));
        }

        $query = Reservation::find()
            ->alias('r')
            ->select('r.*')
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

        if (!empty($this->id)) {
            $query->andWhere([
                '!=', 'r.id', $this->id,
            ])
            ->andWhere([
                'r.announcement_id' => $reservation->announcement_id,
            ]);
        } else {
            $query->andWhere([
                'r.announcement_id' => Yii::$app->request->get('announcement_id'),
            ]);
        }
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
            if (!$this->announcement_id) {
                $this->announcement_id = Yii::$app->request->get('announcement_id');
            }
            $this->status = Reservation::STATUS_PENDING;
            if (!$this->code) {
                $this->code = static::generateUniqueCode();
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
