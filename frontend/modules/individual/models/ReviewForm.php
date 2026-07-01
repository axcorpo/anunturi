<?php

namespace frontend\modules\individual\models;

use common\models\Company;
use common\models\Review;
use common\models\ReviewTranslation;
use common\models\Subscriber;
use common\models\Template;
use common\models\User;
use tws\helpers\Url;
use Yii;
use yii\base\Model;

class ReviewForm extends Model
{

	/**
	 * @var string The content.
	 */
	public $subscriber_id;

	/**
	 * @var string The content.
	 */
	public $title;

	/**
	 * @var string The content.
	 */
	public $content;

	/**
	 * @var float The rating.
	 */
	public $score;

	/**
	 * @var string The honeypot field.
	 */
	public $verifyCode;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->score = 5;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['title', 'content', 'score', 'subscriber_id'], 'required'],
			['score', 'number'],
			['verifyCode', 'safe'],
			[['subscriber_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscriber::class, 'targetAttribute' => ['subscriber_id' => 'id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'title' => Yii::t('label', 'Title'),
			'content' => Yii::t('label', 'Content'),
			'subscriber_id' => Yii::t('label', 'Subscriber'),
		];
	}

    /**
     * Sends review received email.
     *
     * @param $announcement
     * @param $client
     * @return bool whether the email was send.
     */
    protected function sendEmail($model)
    {
        try {
            $template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_REVIEW_RECEIVED);
            if (!$template || !($templateTranslation = $template->getTranslation())) {
                return false;
            }

            $subscriber = Subscriber::findOne(['id' => $this->subscriber_id]);
            $user = User::findOne(['id' => $subscriber->user_id]);
            $shortCodeValues = [
                '{{APP_NAME}}' => Yii::$app->name,
                '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
                '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
                '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
                '{{FIRST_NAME}}' => $user->first_name,
                '{{MIDDLE_NAME}}' => $user->middle_name,
                '{{LAST_NAME}}' => $user->last_name,
                '{{CONFIRM_URL}}' => Url::to(['/account/customer-review/confirm', 'id' => $model->id], true, '@frontend'),
                '{{CLIENT_FIRST_NAME}}' => Yii::$app->user->identity->first_name,
                '{{CLIENT_MIDDLE_NAME}}' =>  Yii::$app->user->identity->middle_name,
                '{{CLIENT_LAST_NAME}}' =>  Yii::$app->user->identity->last_name,
                '{{REVIEW}}' => Yii::t('frontend', 'Your profile'),
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
	 * Sends an email to the specified email address using the information collected by this model.
	 *
	 * @return bool whether the email was sent.
	 */
	public function save()
	{
		if (!empty($this->verifyCode)) {
			return false;
		}

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$model = new Review();
			$model->subscriber_id = $this->subscriber_id;
			$model->score = $this->score;
			$model->confirmed = Review::REVIEW_PENDING;
			$model->type = Review::REVIEW_TYPE_SUBSCRIBER;
			$model->ip_address = Yii::$app->request->userIP;
			$model->status = Review::STATUS_INACTIVE;
			if (!$model->save()) {
				$this->addErrors($model->getErrors());
				throw new \Exception();
			}

			$modelTranslation = new ReviewTranslation();
			$modelTranslation->review_id = $model->id;
			$modelTranslation->language_id = Yii::$app->language;
			$modelTranslation->title = strip_tags(trim($this->title));
			$modelTranslation->content = strip_tags(trim($this->content));
			if (!$modelTranslation->save()) {
				$this->addErrors($modelTranslation->getErrors());
				throw new \Exception();
			}
            $this->sendEmail($model);
			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
