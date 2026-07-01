<?php

namespace frontend\modules\account\models;

use common\models\Renewal;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class RenewalForm extends Renewal
{

    /**
     * @var renewal to set announcement renewal
     */
    public $renewal_at;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->status = static::STATUS_ACTIVE;
        $this->ip_address = Yii::$app->request->userIP;
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
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
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
