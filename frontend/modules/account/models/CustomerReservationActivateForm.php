<?php

namespace frontend\modules\account\models;

use common\models\Announcement;
use common\models\Reservation;
use common\models\Template;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use tws\helpers\Url;

class CustomerReservationActivateForm extends Reservation
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [

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
     * Sends reset password email.
     *
     * @return bool whether the email was send.
     */
    public function sendEmail()
    {
        try {
            $template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_RESERVATION_APPROVED);
            if (!$template || !($templateTranslation = $template->getTranslation())) {
                throw new \Exception();
            }
            $reservation = Reservation::findOne(['id' => Yii::$app->request->get('id')]);
            $announcement = Announcement::findOne(['id' => $reservation->announcement_id]);
            $shortCodeValues = [
                '{{APP_NAME}}' => Yii::$app->name,
                '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
                '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
                '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
                '{{ANNOUNCEMENT}}' => $announcement->translation->title,
                '{{ANNOUNCEMENT_OWNER_DETAILS}}' => $announcement->creator->fullName . ', ' . $announcement->creator->phone . ', ' . $announcement->creator->email,
                '{{RESERVATION_CODE}}' => $reservation->code,
                '{{RESERVATION_START_DATE}}' => Yii::$app->formatter->asDate($reservation->start_at),
                '{{RESERVATION_END_DATE}}' => Yii::$app->formatter->asDate($reservation->end_at),
                '{{CLIENT_FIRST_NAME}}' => $reservation->creator->first_name,
                '{{CLIENT_MIDDLE_NAME}}' => $reservation->creator->middle_name,
                '{{CLIENT_LAST_NAME}}' => $reservation->creator->last_name,
                '{{RESERVATION_PAGE_URL}}' => Url::to(['/account/placed-reservation/index'], true, '@frontend'),
            ];

            return Yii::$app->mailer->compose()
                ->setTo([$reservation->creator->email => $reservation->creator->fullName])
                ->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
                ->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
                ->send();
        } catch (\Exception $e) {
            return false;
        }
    }



	/**
	 * Activates the placed-reservation.
	 *
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function activate()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::activate()) {
				throw new \Exception();
			}
			$this->sendEmail();
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
