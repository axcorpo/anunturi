<?php

namespace backend\modules\announcement\models;

use borales\extensions\phoneInput\PhoneInputValidator;
use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Category;
use common\models\Picture;
use common\models\Reservation;
use common\models\User;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\web\UploadedFile;

class ReservationForm extends Reservation
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
		return ArrayHelper::merge(parent::rules(), [
			['phone', PhoneInputValidator::class, 'skipOnEmpty' => true],
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
	 * Saves the model.
	 *
	 * @return bool
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
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
