<?php

namespace frontend\models;

use common\models\MarketingRecipient;
use common\models\Template;
use Matrix\Exception;
use Yii;
use yii\base\Model;
use yii\validators\EmailValidator;
use yii\web\NotFoundHttpException;

class NewsletterForm extends Model
{
    /**
     * @var string The name.
     */
    public $name;

    /**
     * @var string The first name.
     */
    public $first_name;

    /**
     * @var string The last name.
     */
    public $last_name;

	/**
	 * @var string The email address.
	 */
	public $email;

    /**
     * @var string The phone.
     */
    public $phone;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

	/**
	 * @var string The honeypot field.
	 */
	public $captchaResponse;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'email'], 'required'],
			['email', 'email'],
			[['first_name', 'last_name', 'name'], 'string'],
			['phone', 'number'],
			['workEmail', 'safe'],
			['captchaResponse', 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
            'name' => Yii::t('label', 'Name'),
            'first_name' => Yii::t('label', 'First Name'),
            'last_name' => Yii::t('label', 'Last Name'),
			'email' => Yii::t('label', 'Email'),
			'phone' => Yii::t('label', 'Phone'),
		];
	}

	/**
	 * Sends an email notification to the host.
	 *
	 * @return bool
	 */
	public function sendSubscriberEmail($model): bool
	{
		$contact = Yii::$app->settings->getCategory('contact');
		try {
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_NEWSLETTER_SUBSCRIPTION);

			if (!$template || !$template->translation->content) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}

			$validator = new EmailValidator();
			if (!$validator->validate($contact['newsletterEmail'], $error)) {
				throw new \Exception($error);
			}

			$shortCodeValues = $model->getShortCodeValues(true);
			$mailer = Yii::$app->mailer->compose()
				->setTo([$contact['newsletterEmail'] => Yii::$app->name])
				->setReplyTo([$model->email => $model->name])
				->setSubject(strtr($template->translation->subject, $shortCodeValues))
				->setHtmlBody(strtr($template->translation->content, $shortCodeValues));

			if (!$mailer->send()) {
				throw new \Exception(Yii::t('common',  'Cannot send {item} notification.', ['item' => Yii::t('common', 'Email')]));
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Sends an email confirmation to the subscriber.
	 *
	 * @return bool
	 */
	public function sendConfirmationEmail($model): bool
	{
		$contact = Yii::$app->settings->getCategory('contact');
		try {
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_NEWSLETTER_CONFIRMATION);

			if (!$template || !$template->translation->content) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}

			$validator = new EmailValidator();
			if (!$validator->validate($model->email, $error)) {
				throw new \Exception($error);
			}

			$shortCodeValues = $model->getShortCodeValues(true);
			$mailer = Yii::$app->mailer->compose()
				->setTo([$model->email => $model->name])
				->setReplyTo([$contact['newsletterEmail'] => Yii::$app->name])
				->setSubject(strtr($template->translation->subject, $shortCodeValues))
				->setHtmlBody(strtr($template->translation->content, $shortCodeValues));

			if (!$mailer->send()) {
				throw new \Exception(Yii::t('common',  'Cannot send {item} notification.', ['item' => Yii::t('common', 'Email')]));
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * @inheritdoc
	 * @throws \Throwable
	 */
	public function save(): bool
	{
	    if (!empty($this->workEmail)) {
			return false;
		}
		unset($this->workEmail);
		if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')) {
			if (!empty($this->captchaResponse)) {
				$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . Yii::$app->settings->get('reCaptchaSecretKey', 'general') .'&response=' . $this->captchaResponse);
				$response = json_decode($result);
				if (empty($response->success)) {
					return false;
				}
			}
		}
		unset($this->captchaResponse);
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$marketingRecipient = MarketingRecipient::findOne(['email' => $this->email, 'phone' => $this->phone]);
			if ($marketingRecipient) {
				if (!$marketingRecipient->status || !$marketingRecipient->deleted) {
					$marketingRecipient->status = MarketingRecipient::STATUS_ACTIVE;
					$marketingRecipient->deleted = MarketingRecipient::NO;
					if (!$marketingRecipient->save()) {
						throw new \Exception();
					} else {
						if (!$this->sendSubscriberEmail($marketingRecipient)){
							throw new \Exception();
						} else {
							if (!$this->sendConfirmationEmail($marketingRecipient)) {
								throw new \Exception();
							}
						}
					}
				}
			} else {
				$marketingRecipient = new MarketingRecipient();
				$marketingRecipient->first_name = $this->first_name;
				$marketingRecipient->last_name = $this->last_name;
				$marketingRecipient->name = implode(' ', array_filter([
                    $this->last_name,
                    $this->first_name,
                ]));
				$marketingRecipient->email = $this->email;
				$marketingRecipient->phone = $this->phone;
				$marketingRecipient->status = MarketingRecipient::STATUS_ACTIVE;
				$marketingRecipient->deleted = MarketingRecipient::NO;
				if (!$marketingRecipient->save()) {
					throw new \Exception();
				} else {
					if (!$this->sendSubscriberEmail($marketingRecipient)){
						throw new \Exception();
					} else {
						if (!$this->sendConfirmationEmail($marketingRecipient)) {
							throw new \Exception();
						}
					}
				}
			}
			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
