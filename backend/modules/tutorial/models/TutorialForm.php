<?php

namespace backend\modules\tutorial\models;

use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Tutorial;
use common\models\TutorialTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class TutorialForm extends Tutorial
{

	// i18n attributes
	public $title;
	public $content;

	/**
	 * @var \yii\web\UploadedFile the instance of the uploaded image file.
	 */
	public $imageFile;

	/**
	 * @var \yii\web\UploadedFile the instance of the uploaded attachment file.
	 */
	public $attachmentFile;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		$this->type = static::TYPE_VIDEO;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['title'], 'required', 'when' => function ($model) {
				return empty($model->title[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
			return attribute.$form.find("[name*=\"[title][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['title', 'content'], 'each', 'rule' => ['trim']],
			[['imageFile'], 'file', 'extensions' => Yii::$app->params['image.extensions'], 'mimeTypes' => Yii::$app->params['mimeTypes'], 'maxFiles' => 1, 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'skipOnEmpty' => true],
			[['attachmentFile'], 'file', 'extensions' => ['mp4'], 'maxFiles' => 1, 'maxSize' => Yii::$app->settings->get('maxFileSize') * 250, 'skipOnEmpty' => true],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'title' => Yii::t('common', 'Title'),
			'content' => Yii::t('common', 'Content'),
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
		$this->title = ArrayHelper::map($this->tutorialTranslations, 'language_id', 'title');
		$this->content = ArrayHelper::map($this->tutorialTranslations, 'language_id', 'content');
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveTutorialTranslations()
	{
		try {
			foreach (Yii::$app->controller->appLanguages as $language) {
				$tutorialTranslation = TutorialTranslation::findOne([
					'tutorial_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$tutorialTranslation) {
					$tutorialTranslation = new TutorialTranslation();
					$tutorialTranslation->language_id = $language->language_id;
				}
				$tutorialTranslation->title = ModelHelper::getTranslation($this->title, $language->language_id);
				$tutorialTranslation->content = $this->content[$language->language_id];

				$this->link('tutorialTranslations', $tutorialTranslation);
			}

			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());

			return false;
		}
	}

    /**
     * {@inheritdoc}
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);
        if (!empty($data)) {
            $this->imageFile = UploadedFile::getInstance($this, 'imageFile');
            $this->attachmentFile = UploadedFile::getInstance($this, 'attachmentFile');
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
		// Exit if there are no files selected
		if (!$this->imageFile && !$this->attachmentFile) {
			return true;
		}

		try {
			if ($this->imageFile) {
				if ($this->oldAttributes['image']) {
					FileHelper::unlink(Yii::getAlias("@uploads/tutorial/{$this->id}/{$this->oldAttributes['image']}"));
				}
				if ($fileName = UploadHelper::saveFile($this->imageFile, 'image', "@uploads/tutorial/{$this->id}")) {
					$this->image = $fileName;
				} else {
					$this->addError('imageFile', Yii::t('common', 'Cannot upload the file.'));
					throw new \Exception();
				}
			}
			if ($this->attachmentFile) {
				if ($this->oldAttributes['file']) {
					FileHelper::unlink(Yii::getAlias("@uploads/tutorial/{$this->id}/{$this->oldAttributes['file']}"));
				}
				if ($fileName = UploadHelper::saveFile($this->attachmentFile, 'file', "@uploads/tutorial/{$this->id}")) {
					$this->file = $fileName;
				} else {
					$this->addError('attachmentFile', Yii::t('common', 'Cannot upload the file.'));
					throw new \Exception();
				}
			}

			return $this->save(true, ['image', 'file']);
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
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveTutorialTranslations()) {
				throw new \Exception();
			}
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
