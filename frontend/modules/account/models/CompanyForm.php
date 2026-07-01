<?php

namespace frontend\modules\account\models;

use borales\extensions\phoneInput\PhoneInputValidator;
use common\models\Company;
use common\models\CompanyTranslation;
use common\models\Language;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class CompanyForm extends Company
{
	/**
	 * @var UploadedFile The imageFile.
	 */
	public $imageFile;
    /**
     * @var array The multilingual keywords of the product category.
     */
    public $keywords = [];

    /**
     * @var array The multilingual description of the product category.
     */
    public $description = [];

    /**
     * @var array The multilingual content of the product category.
     */
    public $content = [];

    /**
     * @var array The multilingual content of the product category.
     */
    public $activity = [];

    public $schedule;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->country = Yii::$app->settings->get('defaultCountry', 'general');
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['registration_number', 'tin', 'name', 'status', 'email', 'phone'], 'required'],
			['phone', PhoneInputValidator::class, 'skipOnEmpty' => true],
            [['activity', 'keywords', 'description', 'content'], 'each', 'rule' => ['trim']],
            [['schedule'], 'each', 'rule' => ['default', 'value' => null]],
        ]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'imageFile' => Yii::t('label', 'Image'),
            'keywords' => Yii::t('label', 'Keywords'),
            'description' => Yii::t('label', 'Description'),
            'content' => Yii::t('label', 'Content'),
            'activity' => Yii::t('label', 'Activity'),
            'schedule' => Yii::t('common', 'Schedule'),
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
        $this->activity = ArrayHelper::map($this->companyTranslations, 'language_id', 'activity');
        $this->keywords = ArrayHelper::map($this->companyTranslations, 'language_id', 'keywordsList');
        $this->description = ArrayHelper::map($this->companyTranslations, 'language_id', 'description');
        $this->content = ArrayHelper::map($this->companyTranslations, 'language_id', 'content');
        $this->schedule = ArrayHelper::map($this->companyTranslations, 'language_id', 'schedule');
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
	 * Saves the company files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
		try {
            if (!($file = $this->imageFile)) {
				return true;
			}

			$dirPath = Yii::getAlias("@uploads/company/{$this->id}");
			$oldFilePath = "{$dirPath}/{$this->oldAttributes['image']}";
			$fileName = StringHelper::truncate(implode('_', array_filter([
				Inflector::slug($this->name),
				Yii::$app->security->generateRandomString(8),
			])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);
			if (!$file->saveAs($filePath)) {
				throw new \Exception();
			}
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
     * Saves the ProductTranslation models.
     *
     * @return bool
     */
    protected function saveCompanyTranslations()
    {
        try {
            foreach (Language::findAllLanguages() as $language) {
                $companyTranslation = CompanyTranslation::findOne([
                    'company_id' => $this->id,
                    'language_id' => $language->language_id,
                ]);
                if (!$companyTranslation) {
                    $companyTranslation = new CompanyTranslation();
                    $companyTranslation->company_id = $this->id;
                    $companyTranslation->language_id = $language->language_id;
                }
                $companyTranslation->slug = Inflector::slug($this->name . '-' . $this->tin);
                $companyTranslation->keywords = $this->keywords[$language->language_id] ? implode(',', (array) $this->keywords[$language->language_id]) : null;
                $companyTranslation->description = $this->description[$language->language_id];
                $companyTranslation->content = $this->content[$language->language_id];
                $companyTranslation->activity = $this->activity[$language->language_id];
                $companyTranslation->schedule = $this->schedule[$language->language_id];
                if (!$companyTranslation->save()) {
                    $this->addErrors($companyTranslation->getErrors());
                }
            }
            return true;
        } catch (\Exception $e) {
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
			$this->address = implode(', ', array_filter([$this->street_name, $this->street_number]));
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveFiles()) {
				throw new \Exception();
			}
            if (!$this->saveCompanyTranslations()) {
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
