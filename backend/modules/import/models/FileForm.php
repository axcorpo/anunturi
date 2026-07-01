<?php

namespace backend\modules\import\models;

use common\models\ImportFile;
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use tws\helpers\StringHelper;
use yii\web\UploadedFile;

class FileForm extends ImportFile
{
	/**
	 * @var int The existing File model id.
	 */
	public $file_id;

	/**
	 * @var UploadedFile The importFile.
	 */
	public $importFile;


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
		return [
			[['status'], 'required'],
			[['created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['name', 'file', 'model'], 'string', 'max' => 255],
			[['name', 'model'], 'trim'],
			[['importFile'], 'file', 'extensions' => ['xlsx', 'ods', 'csv'], 'maxSize' => 100 * 1024 *1024, 'skipOnEmpty' => true],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * Saves the files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
		try {
			if (!($file = UploadedFile::getInstance($this, 'file'))) {
				$this->addError('importFile', Yii::t('yii', '{attribute} cannot be blank.', [
					'attribute' => $this->getAttributeLabel('file'),
				]));
				return false;
			}

			$dirPath = Yii::getAlias("@uploads/import/file/{$this->id}");
			$oldFilePath = "{$dirPath}/{$this->oldAttributes['file']}";
			$fileName = StringHelper::truncate(implode('_', array_filter([
				Inflector::slug($this->name ?: $file->baseName),
			])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);
			if (!$file->saveAs($filePath)) {
				throw new \Exception();
			}
			if (!$this->updateAttributes(['file' => $fileName])) {
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
	 * @return bool|\yii\db\ActiveRecord|static
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveFiles()) {
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
