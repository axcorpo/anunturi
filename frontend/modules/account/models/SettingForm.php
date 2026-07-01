<?php

namespace frontend\modules\account\models;

use common\models\Setting;
use common\models\User;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

class SettingForm extends User
{
    public $announcementNotifications;

    public $messageNotifications;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
            [['announcementNotifications','messageNotifications'], 'integer'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'announcementNotifications' => Yii::t('label', 'Announcements'),
			'messageNotifications' => Yii::t('label', 'Messages'),
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
        $setting = User::findUserSettings(Yii::$app->user->id)['notifications_' . Yii::$app->user->id];
        $this->announcementNotifications = $setting['announcementNotifications'];
        $this->messageNotifications = $setting['messageNotifications'];
	}

    public function saveUserNotifications()
    {
        try {
            $setting = Setting::find()
                ->alias('s')
                ->select([
                    's.*',
                ])
                ->joinWith([
                    'user u' => function (ActiveQuery $query) {
                        $query->andOnCondition([
                            'u.deleted' => User::NO,
                        ]);
                    },
                ])
                ->where([
                    'u.id' => Yii::$app->user->id,
                ])
                ->andWhere([
                    's.name' => 'notifications_' . Yii::$app->user->id,
                    's.type' => Setting::TYPE_USER,
                    's.deleted' => Setting::NO,
                ])
                ->one();

            if ($setting->id) {
                $setting->setSerializedValue('setting', [
                    'announcementNotifications' => $this->announcementNotifications,
                    'messageNotifications' => $this->messageNotifications,
                ]);
            } else {
                $setting = new Setting();
                $setting->name = 'notifications_' . Yii::$app->user->id;
                $setting->type = Setting::TYPE_USER;
                $setting->user_id = Yii::$app->user->id;
                $setting->status = Setting::STATUS_ACTIVE;
                $setting->setSerializedValue('setting', [
                    'announcementNotifications' => $this->announcementNotifications,
                    'messageNotifications' => $this->messageNotifications,
                ]);
            }
            if (!$setting->save()) {
                throw new \Exception();
            }
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

	/**
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
            if (!$this->saveUserNotifications()) {
                throw new \Exception();
            }
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
