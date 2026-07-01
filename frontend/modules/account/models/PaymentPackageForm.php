<?php

namespace frontend\modules\account\models;

use common\models\Company;
use common\models\Package;
use common\models\Payment;
use common\models\PaymentMetadata;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use common\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class PaymentPackageForm extends Model
{
	/**
	 * @var string|int The Package model ID.
	 */
	public $package_id;

    /**
     * @var string|int The Announcement model ID.
     */
    public $announcement;

	/**
	 * @var string|int The Company model ID.
	 */
	public $company_id;

	/**
	 * @var int The payment method.
	 */
	public $payment_method;

	/**
	 * @var string The payment method.
	 */
	public $payment_processor;

	/**
	 * @var bool Flag that indicates if the payer agrees with the automatic payment.
	 */
	public $enable_recurring_payment;

	/**
	 * @var User The User model.
	 */
	private $_user;

	/**
	 * @var Package The Package model.
	 */
	private $_package;

	/**
	 * @var Subscription The Subscription model.
	 */
	private $_subscription;

    /**
     * @var bool Flag that indicates if the payer agrees with the automatic payment.
     */
    public $acceptTerms;

	/**
	 * @var string The email address to be used for payment.
	 */
	public $email;

	/**
	 * @var string The payment processor authorization token.
	 */
	public $token;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if (array_key_exists(PaymentMetadata::PAYMENT_METHOD_CARD, Yii::$app->settings->getCategory('payment')['paymentMethods'])) {
			$this->payment_method = PaymentMetadata::PAYMENT_METHOD_CARD;
			$this->payment_processor = PaymentMetadata::PAYMENT_PROCESSOR_STRIPE;
			$this->enable_recurring_payment = Yii::$app->settings->get('enableRecurringPayment', 'payment');
		} else {
			$this->payment_method = PaymentMetadata::PAYMENT_METHOD_BANK;
		}

		$this->email = $this->getUser()->email;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['package_id', 'payment_method', 'payment_processor', 'acceptTerms'], 'required'],
			[['payment_method', 'payment_processor'], 'integer'],
			[['token'], 'string'],
			[['enable_recurring_payment'], 'boolean'],
			[['package_id'], 'exist', 'skipOnError' => true, 'targetClass' => Package::class, 'targetAttribute' => ['package_id' => 'id']],
			[['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
			[['announcement'], 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'announcement' => Yii::t('label', 'Announcement'),
			'package_id' => Yii::t('label', 'Package'),
			'company_id' => Yii::t('label', 'Company'),
			'payment_method' => Yii::t('label', 'Payment Method'),
			'payment_processor' => Yii::t('label', 'Payment Processor'),
			'enable_recurring_payment' => Yii::t('label', 'Enable Recurring Payment'),
            'acceptTerms' => Yii::t('common', 'Terms and Conditions'),
			'email' => Yii::t('label', 'Email'),
			'token' => Yii::t('label', 'Token'),
		]);
	}

	/**
	 * Gets the User model.
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$this->_user = Yii::$app->user->identity;
		}
		return $this->_user;
	}

	/**
	 * Gets the Package model.
	 *
	 * @return Package|null
	 */
	public function getPackage()
	{
		if (!$this->_package) {
			$this->_package = Package::findOne([
				'id' => $this->package_id,
				'status' => Package::STATUS_ACTIVE,
				'deleted' => Package::NO,
			]);
		}
		return $this->_package;
	}

	/**
	 * Gets the Subscription model.
	 *
	 * @return Subscription|null
	 */
	public function getSubscription()
	{
		return $this->_subscription;
	}

	/**
	 * Saves the Subscription model.
	 *
	 * @return bool
	 */
	protected function saveSubscription()
	{
		try {
			$user = $this->getUser();
			$subscriber = $user->subscriber;
			$package = $this->getPackage();

			Subscription::deleteAll([
				'subscriber_id' => $subscriber->id,
				'status' => Subscription::STATUS_INCOMPLETE,
			]);

			$subscription = new Subscription();
			$subscription->code = Subscription::generateUniqueCode();
			$subscription->subscriber_id = $subscriber->id;
			$subscription->package_id = $package->id;
			$subscription->company_id = $this->company_id;
			$subscription->trial_period = null;
			$subscription->trial_cycle = null;
			$subscription->billing_period = $package->billing_period;
			$subscription->billing_cycle = $package->billing_cycle;
			$subscription->price = $package->price;
			$subscription->currency = $package->currency;
			$subscription->type = $package->type;
			$subscription->status = Subscription::STATUS_INCOMPLETE;
			if (!$subscription->save()) {
				throw new \Exception('Subscription cannot be saved.');
			}
			$this->_subscription = $subscription;

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the SubscriptionFeature model(s).
	 *
	 * @return bool
	 */
	protected function saveSubscriptionFeatures()
	{
		try {
			$package = $this->getPackage();
			$subscription = $this->getSubscription();

			SubscriptionFeature::deleteAll([
				'subscription_id' => $subscription->id,
			]);

			$columns = ['subscription_id', 'feature_id', 'name', 'value', 'price', 'renewable', 'type'];
			$rows = [];
			foreach ($package->packageFeatures as $packageFeature) {
				$rows[] = [
					'subscription_id' => $subscription->id,
					'feature_id' => $packageFeature->feature_id,
					'name' => $packageFeature->name,
					'value' => $packageFeature->value,
					'price' => $packageFeature->price,
					'renewable' => $packageFeature->renewable,
					'type' => SubscriptionFeature::TYPE_PACKAGE_FEATURE,
				];
			}
			if (!Yii::$app->db->createCommand()->batchInsert(SubscriptionFeature::tableName(), $columns, $rows)->execute()) {
				throw new \Exception('SubscriptionFeature cannot be saved.');
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the PaymentMetadata model.
	 *
	 * @return bool
	 */
	protected function savePaymentMetadata()
	{
		try {
			$subscription = $this->getSubscription();

			if (!($paymentMetadata = $subscription->paymentMetadata)) {
				$paymentMetadata = new PaymentMetadata();
				$paymentMetadata->subscription_id = $subscription->id;
			}
			$paymentMetadata->payment_method = $this->payment_method;
			$paymentMetadata->payment_processor = $this->payment_processor;
			$paymentMetadata->recurring_payment = $this->enable_recurring_payment;
			$paymentMetadata->ip_address = Yii::$app->request->userIP;
			$paymentMetadata->status = PaymentMetadata::STATUS_ACTIVE;
			if (!$paymentMetadata->save()) {
				throw new \Exception($paymentMetadata->getErrorSummary(false)[0]);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the Payment model.
	 *
	 * @return bool
	 */
	protected function savePayment()
	{
		try {
			$subscription = $this->getSubscription();
			$subscriber = $subscription->subscriber;

			if (!empty($subscription->subscriber->parent_id) && !empty(Yii::$app->settings->get('splitIncomePercentage'))) {
				$payment = new Payment();
				$payment->subscription_id = $subscription->id;
				$payment->payment_id = null;
				$payment->amount = $subscription->price;
				$payment->currency = $subscription->currency;
				$payment->split_percentage = Yii::$app->settings->get('splitIncomePercentage') ?: 0;
				$payment->bank_account_holder = $subscriber->bank_account_holder;
				$payment->bank_name = $subscriber->bank_name;
				$payment->bank_iban = $subscriber->bank_iban;
				$payment->bank_bic = $subscriber->bank_bic;
				$payment->status = Payment::STATUS_UNPAID;
				$payment->deleted = Payment::NO;
				if (!$payment->save()) {
					throw new \Exception($payment->getErrorSummary(false)[0]);
				}
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the models.
	 *
	 * @return bool
	 */
	public function save()
	{
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$this->validate()) {
				throw new \Exception();
			}
			if (!$this->getPackage()) {
				throw new \Exception();
			}
			if (!$this->saveSubscription()) {
				throw new \Exception();
			}
			if (!$this->saveSubscriptionFeatures()) {
				throw new \Exception();
			}
			if (!$this->savePaymentMetadata()) {
				throw new \Exception();
			}
			if (!$this->savePayment()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
