<?php

namespace frontend\modules\announcement\models;

use common\models\Announcement;
use common\models\Review;
use common\models\ReviewTranslation;
use common\models\Template;
use tws\helpers\Url;
use Yii;
use yii\base\Model;

class ReviewForm extends Model
{

	/**
	 * @var string The content.
	 */
	public $announcement_id;

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
			[['title', 'content', 'score', 'announcement_id'], 'required'],
			['score', 'number'],
			['verifyCode', 'safe'],
			[['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
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
			'announcement_id' => Yii::t('label', 'Announcement'),
		];
	}

    /**
     * Sends review received email.
     *
     * @return bool whether the email was send.
     */
    protected function sendEmail($announcement, $model)
    {

        try {
            $template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_REVIEW_RECEIVED);
            if (!$template || !($templateTranslation = $template->getTranslation())) {
                return false;
            }

            $shortCodeValues = [
                '{{APP_NAME}}' => Yii::$app->name,
                '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
                '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
                '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
                '{{FIRST_NAME}}' => $announcement->creator->first_name,
                '{{MIDDLE_NAME}}' => $announcement->creator->middle_name,
                '{{LAST_NAME}}' => $announcement->creator->last_name,
                '{{CONFIRM_URL}}' => Url::to(['/account/customer-review/confirm', 'id' => $model->id], true, '@frontend'),
                '{{CLIENT_FIRST_NAME}}' => $model->creator->first_name,
                '{{CLIENT_MIDDLE_NAME}}' => $model->creator->middle_name,
                '{{CLIENT_LAST_NAME}}' => $model->creator->last_name,
                '{{REVIEW}}' => $announcement->translation->title,
            ];

            return Yii::$app->mailer->compose()
                ->setTo([$announcement->creator->email => $announcement->creator->fullName])
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
			$model->announcement_id = $this->announcement_id;
			$model->confirmed = Review::REVIEW_PENDING;
			$model->type = Review::REVIEW_TYPE_ANNOUNCEMENT;
			$model->score = $this->score;
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
            $announcementModel = Announcement::find()
                ->alias('a')
                ->where([
                    'a.id' => $model->announcement_id,
                 ])
                ->andWhere([
                    'a.status' => Announcement::STATUS_ACTIVE,
                    'a.deleted' => Announcement::NO,
                ])
                ->one();
            $this->sendEmail($announcementModel, $model);
			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
