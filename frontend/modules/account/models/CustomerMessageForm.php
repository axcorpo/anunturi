<?php

namespace frontend\modules\account\models;

use common\models\Announcement;
use common\models\Conversation;
use common\models\Message;
use common\models\Template;
use common\models\User;
use tws\helpers\Url;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;


class CustomerMessageForm extends Message
{

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ([
            [['parent_id', 'announcement_id', 'conversation_id', 'recipient_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['status'], 'required'],
            [['content'], 'string'],
            [['seen_at', 'created_at', 'updated_at'], 'safe'],
            [['subject'], 'string', 'max' => 255],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
            [['conversation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Conversation::class, 'targetAttribute' => ['conversation_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Message::class, 'targetAttribute' => ['parent_id' => 'id']],
            [['content'], 'required'],
            [['content'], 'string'],
            [['subject'], 'string', 'max' => 255],
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

	public function saveConversation()
	{
		try {
			$conversation = Conversation::findOne([
				'id' => $this->conversation_id,
				'announcement_id' => $this->announcement_id,
			]);
			if (!$conversation) {
				$conversation = new Conversation();
				$conversation->announcement_id = $this->announcement_id;
				$conversation->status = self::STATUS_ACTIVE;
			}
			if (!$conversation->save()) {
				throw new \Exception();
			}
			$this->conversation_id = $conversation->id;
			return true;
		} catch(\Exception $e) {
			return false;
		}
	}

    /**
     * Sends placed-review received email.
     *
     * @param $announcement
     * @param $client
     * @return bool whether the email was send.
     */
    protected function sendEmail($announcement)
    {
        try {
            $template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_MESSAGE_RECEIVED);
            if (!$template || !($templateTranslation = $template->getTranslation())) {
                return false;
            }

                $user = User::findOne(['id' => $this->recipient_id]);
                $shortCodeValues = [
                    '{{APP_NAME}}' => Yii::$app->name,
                    '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
                    '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
                    '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
                    '{{FIRST_NAME}}' => $user->first_name,
                    '{{MIDDLE_NAME}}' => $user->middle_name,
                    '{{LAST_NAME}}' => $user->last_name,
                    '{{MESSAGE_PAGE_URL}}' => Url::to(['/account/message/view', 'id' => $this->id], true, '@frontend'),
                    '{{CLIENT_FIRST_NAME}}' => Yii::$app->user->identity->first_name,
                    '{{CLIENT_MIDDLE_NAME}}' =>  Yii::$app->user->identity->middle_name,
                    '{{CLIENT_LAST_NAME}}' =>  Yii::$app->user->identity->last_name,
                    '{{ANNOUNCEMENT}}' => $announcement->translation->title,
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
	 * Saves the model.
	 *
	 * @return bool
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!$this->saveConversation()) {
				throw new \Exception();
			}
            $announcement = Announcement::findOne(['id' => Yii::$app->request->get('announcement_id')]);
			if (Yii::$app->request->get('announcement_id')) {
			    $this->recipient_id = $announcement->created_by;
            }
            if (!$this->save()) {
				throw new \Exception();
			}
			$this->sendEmail($announcement);

			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
