<?php

namespace backend\modules\nomenclature\models;

use common\models\Template;
use common\models\TemplateTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class EmailTemplateForm extends Template
{
	/**
	 * @var array The multilingual name of the Template.
	 */
	public $name;

	/**
	 * @var array The multilingual subject of the Template.
	 */
	public $subject;

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

		$this->type = static::TYPE_EMAIL;
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['variant'], 'required'],
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['subject'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[subject][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['content'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[content][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['name', 'subject', 'content'], 'each', 'rule' => ['trim']],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'name' => Yii::t('label', 'Name'),
			'subject' => Yii::t('label', 'Subject'),
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
		$this->subject = ArrayHelper::map($this->templateTranslations, 'language_id', 'subject');
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
				$templateTranslation->subject = $this->subject[$language->language_id];
				$templateTranslation->content = $this->content[$language->language_id];

				$this->link('templateTranslations', $templateTranslation);
			}
			return true;
		} catch (InvalidCallException $e) {
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
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveTemplateTranslations()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}

	/**
	 * Gets the short code items.
	 *
	 * @param null|int $variant
	 * @return array
	 */
	public static function getShortCodeItems($variant = null)
	{
		$shortCodeItems = [
			'{{APP_NAME}}' => Yii::t('label', 'App Name'),
			'{{APP_URL}}' => Yii::t('label', 'App URL'),
			'{{APP_LOGO_URL}}' => Yii::t('label', 'App Logo URL'),
			'{{APP_LOGO_ALT_URL}}' => Yii::t('label', 'App Logo Alternative URL'),
		];

		switch ($variant) {
			case static::EMAIL_VARIANT_ACCOUNT_ACTIVATION:
				$shortCodeItems = array_merge($shortCodeItems, [
					'{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
					'{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
					'{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
					'{{EMAIL}}' => Yii::t('label', 'Email'),
					'{{PHONE}}' => Yii::t('label', 'Phone'),
					'{{ACCOUNT_ACTIVATION_CODE}}' => Yii::t('label', 'Account Activation Code'),
					'{{ACCOUNT_ACTIVATION_PAGE_URL}}' => Yii::t('label', 'Account Activation Page URL'),
					'{{ACCOUNT_ACTIVATION_URL}}' => Yii::t('label', 'Account Activation URL'),
				]);
				break;
			case static::EMAIL_VARIANT_PASSWORD_RESET:
				$shortCodeItems = array_merge($shortCodeItems, [
					'{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
					'{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
					'{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
					'{{EMAIL}}' => Yii::t('label', 'Email'),
					'{{PHONE}}' => Yii::t('label', 'Phone'),
					'{{PASSWORD_RESET_CODE}}' => Yii::t('label', 'Password Reset Code'),
					'{{PASSWORD_RESET_PAGE_URL}}' => Yii::t('label', 'Password Reset Page URL'),
					'{{PASSWORD_RESET_URL}}' => Yii::t('label', 'Password Reset URL'),
				]);
				break;
			case static::EMAIL_VARIANT_WELCOME:
				$shortCodeItems = array_merge($shortCodeItems, [
					'{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
					'{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
					'{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
					'{{EMAIL}}' => Yii::t('label', 'Email'),
					'{{PHONE}}' => Yii::t('label', 'Phone'),
                    '{{ACCOUNT_PAGE_URL}}' => Yii::t('label', 'Account Page URL'),
				]);
				break;
            case static::EMAIL_VARIANT_SUBSCRIPTION_CONFIRMATION:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{EMAIL}}' => Yii::t('label', 'Email'),
                    '{{PHONE}}' => Yii::t('label', 'Phone'),
                    '{{ACCOUNT_PAGE_URL}}' => Yii::t('label', 'Account Page URL'),
                ]);
                break;
				//TODO: CHECK VARIANT CANCEL SUBSCRIPTION AFTER SUBSCRIPTION WILL WORK PROPERLY
            case static::EMAIL_VARIANT_CANCEL_SUBSCRIPTION:
            case static::EMAIL_VARIANT_SUBSCRIPTION_CONTINUATION:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{EMAIL}}' => Yii::t('label', 'Email'),
                    '{{PHONE}}' => Yii::t('label', 'Phone'),
                    '{{PACKAGE_NAME}}' => Yii::t('label', 'Package Name'),
                    '{{PRICE}}' => Yii::t('label', 'Package Price'),
                    '{{BILLING_CYCLE}}' => Yii::t('label', 'Billing Cycle'),
                    '{{PAYMENT_PAGE_URL}}' => Yii::t('label','Payment Page URL'),
                    '{{SUBSCRIBER_COMPANY}}' => Yii::t('label', 'Subscriber Company'),
                    '{{CURRENCY}}' => Yii::t('label', 'Currency'),
                ]);
                break;
            case static::EMAIL_VARIANT_ANNOUNCEMENT_APPROVED:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{EMAIL}}' => Yii::t('label', 'Email'),
                    '{{PHONE}}' => Yii::t('label', 'Phone'),
                    '{{ANNOUNCEMENT}}' => Yii::t('label', 'Announcement'),
                    '{{ANNOUNCEMENT_PAGE_URL}}' => Yii::t('label', 'Announcement Page URL'),
                    '{{PROMOTE_ANNOUNCEMENT_URL}}' => Yii::t('label', 'Promote Announcement URL'),
                ]);
                break;
            case static::EMAIL_VARIANT_ANNOUNCEMENT_RENEWED:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{EMAIL}}' => Yii::t('label', 'Email'),
                    '{{PHONE}}' => Yii::t('label', 'Phone'),
                    '{{ANNOUNCEMENT}}' => Yii::t('label', 'Announcement'),
                    '{{ANNOUNCEMENT_PAGE_URL}}' => Yii::t('label', 'Announcement Page URL'),
                    '{{ACCOUNT_PAGE_URL}}' => Yii::t('label', 'Account Page URL'),
                ]);
                break;
            case static::EMAIL_VARIANT_ANNOUNCEMENT_CONTINUATION:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{EMAIL}}' => Yii::t('label', 'Email'),
                    '{{PHONE}}' => Yii::t('label', 'Phone'),
                    '{{ANNOUNCEMENT}}' => Yii::t('label', 'Announcement'),
                    '{{ANNOUNCEMENT_PAGE_URL}}' => Yii::t('label', 'Announcement Page URL'),
                    '{{RENEW_ANNOUNCEMENT_PAGE_URL}}' => Yii::t('label', 'Renew Announcement Page URL'),
                    '{{ACCOUNT_PAGE_URL}}' => Yii::t('label', 'Account Page URL'),
                ]);
                break;
            case static::EMAIL_VARIANT_ORDER_PLACEMENT:
			case static::EMAIL_VARIANT_ORDER_COMPLETION:
			case static::EMAIL_VARIANT_ORDER_CANCELLATION:
			case static::EMAIL_VARIANT_ORDER_REJECTION:
				$shortCodeItems = array_merge($shortCodeItems, [
					'{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
					'{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
					'{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
					'{{CODE}}' => Yii::t('label', 'Code'),
					'{{STATUS}}' => Yii::t('label', 'Status'),
					'{{ITEMS_LIST}}' => Yii::t('label', 'Articles'),
					'{{SHIPPING_DETAILS}}' => Yii::t('label', 'Shipping Details'),
					'{{BILLING_DETAILS}}' => Yii::t('label', 'Billing Details'),
					'{{PAYMENT_METHOD}}' => Yii::t('label', 'Payment Method'),
					'{{TOTAL_AMOUNT}}' => Yii::t('label', 'Total Amount'),
				]);
				break;
            case static::EMAIL_VARIANT_REVIEW_RECEIVED:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{CONFIRM_URL}}' => Yii::t('label', 'Confirm URL'),
                    '{{CLIENT_FIRST_NAME}}' => Yii::t('label', 'Client First Name'),
                    '{{CLIENT_MIDDLE_NAME}}' => Yii::t('label', 'Client Middle Name'),
                    '{{CLIENT_LAST_NAME}}' => Yii::t('label', 'Client Last Name'),
                    '{{REVIEW}}' => Yii::t('label', 'Review'),
                ]);
                break;
            case static::EMAIL_VARIANT_MESSAGE_RECEIVED:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{CLIENT_FIRST_NAME}}' => Yii::t('label', 'Client First Name'),
                    '{{CLIENT_MIDDLE_NAME}}' => Yii::t('label', 'Client Middle Name'),
                    '{{CLIENT_LAST_NAME}}' => Yii::t('label', 'Client Last Name'),
                    '{{ANNOUNCEMENT}}' => Yii::t('label', 'Announcement'),
                    '{{MESSAGE_PAGE_URL}}' => Yii::t('label', 'Message Page URL'),
                ]);
                break;
            case static::EMAIL_VARIANT_COMPANY_MESSAGE_RECEIVED:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{COMPANY_NAME}}' => Yii::t('label', 'Company Name'),
                    '{{SUBJECT}}' => Yii::t('label', 'Subject'),
                    '{{MESSAGE}}' => Yii::t('label', 'Message'),
                    '{{CLIENT_FIRST_NAME}}' => Yii::t('label', 'Client First Name'),
                    '{{CLIENT_MIDDLE_NAME}}' => Yii::t('label', 'Client Middle Name'),
                    '{{CLIENT_LAST_NAME}}' => Yii::t('label', 'Client Last Name'),
                    '{{CLIENT_PHONE}}' => Yii::t('label', 'Client Phone'),
                    '{{CLIENT_EMAIL}}' => Yii::t('label', 'Client Email'),
                ]);
                break;
            case static::EMAIL_VARIANT_RESERVATION_APPROVED:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{ANNOUNCEMENT}}' => Yii::t('label', 'Announcement'),
                    '{{ANNOUNCEMENT_OWNER_DETAILS}}' => Yii::t('label', 'Announcement Owner Details'),
                    '{{RESERVATION_CODE}}' => Yii::t('label', 'Reservation'),
                    '{{RESERVATION_START_DATE}}' => Yii::t('label', 'Reservation Start Date'),
                    '{{RESERVATION_END_DATE}}' => Yii::t('label', 'Reservation End Date'),
                    '{{CLIENT_FIRST_NAME}}' => Yii::t('label', 'Client First Name'),
                    '{{CLIENT_MIDDLE_NAME}}' => Yii::t('label', 'Client Middle Name'),
                    '{{CLIENT_LAST_NAME}}' => Yii::t('label', 'Client Last Name'),
                    '{{RESERVATION_PAGE_URL}}' => Yii::t('label', 'Reservation Page URL')
                ]);
                break;
			case static::EMAIL_VARIANT_INVOICE_ISSUANCE:
			case static::EMAIL_VARIANT_INVOICE_PAYMENT_CONFIRMATION:
				$shortCodeItems = array_merge($shortCodeItems, [
                    '{{FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{MIDDLE_NAME}}' => Yii::t('label', 'Middle Name'),
                    '{{LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{PACKAGE}}' => Yii::t('label', 'Package'),
                    '{{BILLING_CYCLE}}' => Yii::t('label', 'Billing Cycle'),
					'{{DOCUMENT_NUMBER}}' => Yii::t('label', 'Document Number'),
					'{{ISSUED_AT}}' => Yii::t('label', 'Issued At'),
					'{{DUE_AT}}' => Yii::t('label', 'Due At'),
					'{{PAYMENT_METHOD}}' => Yii::t('label', 'Payment Method'),
					'{{PAYMENT_PROCESSOR}}' => Yii::t('label', 'Payment Processor'),
					'{{TOTAL_AMOUNT}}' => Yii::t('label', 'Total Amount'),
					'{{CLIENT_NAME}}' => Yii::t('label', 'Client Name'),
				]);
				break;
            case static::EMAIL_VARIANT_NEWSLETTER_SUBSCRIPTION:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{RECIPIENT_NAME}}' => Yii::t('label', 'Full Name'),
                    '{{RECIPIENT_FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{RECIPIENT_LAST_NAME}}' => Yii::t('label', 'Last Name'),
                    '{{RECIPIENT_EMAIL}}' => Yii::t('label', 'Email'),
                    '{{RECIPIENT_PHONE}}' => Yii::t('label', 'Phone'),
                ]);
                break;
            case static::EMAIL_VARIANT_NEWSLETTER_CONFIRMATION:
                $shortCodeItems = array_merge($shortCodeItems, [
                    '{{RECIPIENT_NAME}}' => Yii::t('label', 'Full Name'),
                    '{{RECIPIENT_FIRST_NAME}}' => Yii::t('label', 'First Name'),
                    '{{RECIPIENT_LAST_NAME}}' => Yii::t('label', 'Last Name'),
                ]);
                break;
			default:
				break;
		}

		return $shortCodeItems;
	}
}
