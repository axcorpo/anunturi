<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\helpers\Html;
use tws\helpers\Url;

class ActivateAccountForm extends Model
{
	/**
	 * @var string The account activation token.
	 */
	public $token;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;

    /**
	 * @var User The User model.
	 */
	private $_user;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['token'], 'required'],
			[['token'], 'trim'],
			['token', 'validateToken'],
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
			'token' => Yii::t('label', 'Activation Code'),
		];
	}

	/**
	 * Validates the token attribute.
	 *
	 * @return bool
	 */
	public function validateToken()
	{
		if (!$this->getUser()) {
			$this->addError('token', Yii::t('yii', '{attribute} is invalid.', [
				'attribute' => $this->getAttributeLabel('token'),
			]));
			return false;
		}
		return true;
	}

	/**
	 * Finds user by signup token.
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$this->_user = User::findBySignupToken($this->token);
		}
		return $this->_user;
	}

	/**
	 * Activates Subscriber model.
	 *
	 * @return bool
	 */
	public function activateSubscriber()
	{
		try {
			/** @var Subscriber $subscriber */
			if ($subscriber = $this->getUser()->getSubscriber()->active(false)->one()) {
				$subscriber->status = Subscriber::STATUS_ACTIVE;
				if (!$subscriber->save()) {
					throw new \Exception('Cannot activate the Subscriber.');
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Activates Broker model.
	 *
	 * @return bool
	 */
	public function activateBroker()
	{
		try {
			/** @var Broker $broker */
			if ($broker = $this->getUser()->getBroker()->active(false)->one()) {
				$broker->status = Broker::STATUS_ACTIVE;
				if (!$broker->save()) {
					throw new \Exception('Cannot activate the Broker.');
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Activates the only Subscription model chosen at signup.
	 *
	 * @return bool
	 */
	public function activateSubscription()
	{
		try {
			if ($subscriber = $this->getUser()->subscriber) {
				/** @var Subscription $subscription */
				if ($subscription = $subscriber->getSubscriptions()->one()) {
					if (!$subscription->activate('now', false)) {
						throw new \Exception('Cannot activate the Subscription.');
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sends welcome email.
	 *
	 * @return bool
	 */
	public function sendWelcomeEmail()
	{
		try {
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_WELCOME);
			if (!$template || !($templateTranslation = $template->getTranslation())) {
				throw new \Exception();
			}
			$user = $this->getUser();
			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
				'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
				'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
				'{{FIRST_NAME}}' => $user->first_name,
				'{{MIDDLE_NAME}}' => $user->middle_name,
				'{{LAST_NAME}}' => $user->last_name,
				'{{EMAIL}}' => $user->email,
				'{{PHONE}}' => $user->phone,
                '{{ACCOUNT_PAGE_URL}}' => Url::to(['account/profile/index'], true, '@frontend'),
			];

			return Yii::$app->mailer->compose()
				->setTo([$user->email => $user->fullName])
				->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
				->send();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Activates user account.
	 *
	 * @return bool if account was activated.
	 */
	public function activate()
	{
		if (!empty($this->workEmail)) {
			return false;
		}
        if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')) {
            if (!empty($this->captchaResponse)) {
                $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . Yii::$app->settings->get('reCaptchaSecretKey', 'general') .'&response=' . $this->captchaResponse);
                $response = json_decode($result);
                if (empty($response->success)) {
                    return false;
                }
            }
        }
		try {
			if (!($user = $this->getUser())) {
				throw new \Exception();
			}
			$user->signup_token = null;
			$user->status = User::STATUS_ACTIVE;
			if (!$user->save(false)) {
				throw new \Exception();
			}
			if (!$this->activateSubscriber()) {
				throw new \Exception();
			}

			if (!$this->activateBroker()) {
				throw new \Exception();
			}

//			if (!$this->activateSubscription()) {
//				throw new \Exception();
//			}

			$this->sendWelcomeEmail();

			Notification::create([
				'title' => Yii::t('notification', 'Account activation'),
				'message' => Yii::t('notification', '{0} became an active member.', [
					Html::a($user->fullName, ['/admin/subscriber-manager/subscriber/view', 'id' => $user->subscriber->id]),
				]),
				'icon' => 'fa fa-user-plus',
				'color' => '#00ff00',
			]);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}
