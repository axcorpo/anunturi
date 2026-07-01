<?php

namespace backend\modules\nomenclature\models;

use common\models\Template;
use common\models\TemplateTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class InvoiceTemplateForm extends Template
{
	/**
	 * @var array The multilingual name of the Template.
	 */
	public $name;

	/**
	 * @var array The multilingual content of the Template.
	 */
	public $content;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->type = static::TYPE_INVOICE;
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['content'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[content][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['name', 'content'], 'each', 'rule' => ['trim']],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'name' => Yii::t('label', 'Name'),
			'content' => Yii::t('label', 'Content'),
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

		$this->name = ArrayHelper::map($this->templateTranslations, 'language_id', 'name');
		$this->content = ArrayHelper::map($this->templateTranslations, 'language_id', 'content');
	}

	/**
	 * Saves the Template translations.
	 *
	 * @return bool
	 */
	protected function saveTemplateTranslations()
	{
		try {
			foreach (\common\models\Language::findAllLanguages() as $language) {
				$templateTranslation = TemplateTranslation::findOne([
					'template_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$templateTranslation) {
					$templateTranslation = new TemplateTranslation();
					$templateTranslation->template_id = $this->id;
					$templateTranslation->language_id = $language->language_id;
				}
				$templateTranslation->name = static::ensureTranslationValue($this->name, $language->language_id);
				$templateTranslation->content = $this->content[$language->language_id];
				if (!$templateTranslation->save()) {
					throw new \Exception($templateTranslation->getErrorSummary(false)[0]);
				}
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save()) {
				throw new \Exception();
			}
			if (!$this->saveTemplateTranslations()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}

	/**
	 * Gets the short code items.
	 *
	 * @return array
	 */
	public static function getShortCodeItems()
	{
		return [
			'{{APP_NAME}}' => Yii::t('label', 'Application Name'),
			'{{APP_URL}}' => Yii::t('label', 'Application URL'),
			'{{APP_LOGO_URL}}' => Yii::t('label', 'Application Logo URL'),
			'{{APP_LOGO_ALT_URL}}' => Yii::t('label', 'Application Logo Alternative URL'),
			'{{COMPANY_NAME}}' => Yii::t('label', 'Company Name'),
			'{{COMPANY_TIN}}' => Yii::t('label', 'Company Taxpayer Identification Number'),
			'{{COMPANY_REG_NO}}' => Yii::t('label', 'Company Registration Number'),
			'{{COMPANY_LEGAL_REPRESENTATIVE}}' => Yii::t('label', 'Company Legal Representative'),
			'{{COMPANY_EMAIL}}' => Yii::t('label', 'Company Email'),
			'{{COMPANY_PHONE}}' => Yii::t('label', 'Company Phone'),
			'{{COMPANY_FAX}}' => Yii::t('label', 'Company Fax'),
			'{{COMPANY_ADDRESS}}' => Yii::t('label', 'Company Address'),
			'{{DOCUMENT_NUMBER}}' => Yii::t('label', 'Document Number'),
			'{{ISSUED_AT}}' => Yii::t('label', 'Issued At'),
			'{{DUE_AT}}' => Yii::t('label', 'Due At'),
			'{{PAYMENT_METHOD}}' => Yii::t('label', 'Payment Method'),
			'{{PAYMENT_PROCESSOR}}' => Yii::t('label', 'Payment Processor'),
			'{{TOTAL_AMOUNT}}' => Yii::t('label', 'Total Amount'),
			'{{ITEMS_LIST}}' => Yii::t('label', 'Items List'),
			'{{CLIENT_NAME}}' => Yii::t('label', 'Client Name'),
			'{{CLIENT_PIN}}' => Yii::t('label', 'Client Personal Identification Number'),
			'{{CLIENT_TIN}}' => Yii::t('label', 'Client Taxpayer Identification Number'),
			'{{CLIENT_REG_NO}}' => Yii::t('label', 'Client Registration Number'),
			'{{CLIENT_EMAIL}}' => Yii::t('label', 'Client Email'),
			'{{CLIENT_PHONE}}' => Yii::t('label', 'Client Phone'),
			'{{CLIENT_FAX}}' => Yii::t('label', 'Client Fax'),
			'{{CLIENT_ADDRESS}}' => Yii::t('label', 'Client Address'),
		];
	}
}
