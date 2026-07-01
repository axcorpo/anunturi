<?php

namespace frontend\modules\announcement\models;

use common\models\Announcement;
use common\models\User;
use common\models\UserHasAnnouncement;
use Yii;
use yii\base\Model;

class UserHasAnnouncementForm extends Model
{

	/**
	 * @var string The announcement.
	 */
	public $announcement_id;

    /**
     * @var string The user.
     */
    public $user_id;



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
		return [
			[['user_id', 'announcement_id'], 'required'],
			[['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'announcement_id' => Yii::t('label', 'Announcement'),
			'user_id' => Yii::t('label', 'User'),
		];
	}

	/**
	 * Sends an email to the specified email address using the information collected by this model.
	 *
	 * @return bool whether the email was sent.
	 */
	public function save()
	{

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$model = new UserHasAnnouncement();
			$model->announcement_id = $this->announcement_id;
			$model->user_id = Yii::$app->user->identity;
			if (!$model->save()) {
				$this->addErrors($model->getErrors());
				throw new \Exception();
			}

			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
