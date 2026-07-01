<?php

namespace backend\modules\commercial\models;

use common\helpers\UploadHelper;
use common\models\Broker;
use common\models\Commercial;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class CommercialForm extends Commercial
{
    /**
     * @var UploadedFile The imageFile.
     */
    public $imageFile;

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
			[['url'], 'required'],
			[['imageFile'], 'file', 'extensions' => Yii::$app->params['image.extensions'], 'mimeTypes' => Yii::$app->params['image.mimeTypes'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'maxFiles' => 1, 'skipOnEmpty' => true],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'imageFile' => Yii::t('label', 'Image'),
			'bid_id' => Yii::t('common', 'Bid'),
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
     * {@inheritdoc}
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);
        if (!empty($data)) {
            $this->imageFile = UploadedFile::getInstance($this, 'imageFile');
        }
        return $result;
    }

	/**
	 * Saves the files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
        if (!($file = $this->imageFile)) {
            return true;
        }

		try {
			switch ($this->bid->auction->position) {
				case 1:
					$width = 300; $height = 440;
					break;
				case 2:
					$width = 300; $height = 440;
					break;
				case 3:
					$width = 300; $height = 300;
					break;
				case 4:
					$width = 1030; $height = 120;
					break;
				case 5:
					$width = 300; $height = 440;
					break;
				case 6:
					$width = 300; $height = 300;
					break;
				case 7:
					$width = 300; $height = 440;
					break;
				case 8:
					$width = 1030; $height = 120;
					break;
				case 9:
					$width = 300; $height = 440;
					break;
				default:break;
			}

			$dirPath = Yii::getAlias("@uploads/commercial/{$this->id}");
			$oldFilePath = "{$dirPath}/{$this->oldAttributes['image']}";
			$fileName = UploadHelper::saveImage($file, $this->id, "@uploads/commercial/{$this->id}", $width, $height, false);
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);

			if (!$this->updateAttributes(['image' => $fileName])) {
				throw new \Exception();
			}
			if (is_file($oldFilePath) && $oldFilePath != $filePath) {
				FileHelper::unlink($oldFilePath);
			}

			return true;
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
			// Unset the file attributes since they are treated later
			unset($this->image);
			if (!$this->code) {
				$this->code = static::generateUniqueCode();
			}
			if (!$this->save()) {
				throw new \Exception();
			}
			// Save files
			if (!$this->saveFiles()) {
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
