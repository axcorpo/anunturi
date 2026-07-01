<?php

namespace common\models;

use common\behaviors\DocumentSeriesAndNumberBehavior;
use common\helpers\NumberHelper;
use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use tws\helpers\Url;
use yii\db\ActiveQuery;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%invoice}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $broker_id
 * @property int $subscriber_id
 * @property int $company_id
 * @property string $document_series
 * @property int $document_number
 * @property string $issued_at
 * @property string $due_at
 * @property string $paid_at
 * @property int $payment_method
 * @property int $payment_processor
 * @property string $amount
 * @property string $currency
 * @property string $vat
 * @property string $exchange_rate
 * @property int $multiplier
 * @property string $details
 * @property string $e_invoice_upload_id
 * @property string $e_invoice_download_id
 * @property int $e_invoice_status
 * @property string $e_invoice_error
 * @property string $e_invoice_sent_at
 * @property string $sent_at
 * @property int $type
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 *
 * @property Invoice $parent
 * @property Invoice[] $invoices
 * @property Broker $broker
 * @property Company $company
 * @property Subscriber $subscriber
 * @property InvoiceHasTemplate[] $invoiceHasTemplates
 * @property Template[] $templates
 * @property Item[] $items
 * @property User $creator
 * @property User $updater
 *
 * @method string getDefaultDocumentSeries()
 * @method int getNextDocumentNumber($documentSeries = null)
 */
class Invoice extends CommonActiveRecord
{
	const STATUS_UNPAID = 1;
	const STATUS_PAID = 2;

    const TYPE_INVOICE = 1;
    const TYPE_PROFORM = 2;
	const TYPE_RECTIFY = 3;

	const E_STATUS_NOT_SENT = 0;
	const E_STATUS_SENT = 1;
	const E_STATUS_PENDING = 2;
	const E_STATUS_PROCESSED = 3;
	const E_STATUS_UNPROCESSED = 4;
	const E_STATUS_REJECTED = 5;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%invoice}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['issued_at', 'due_at', 'paid_at', 'sent_at', 'e_invoice_sent_at'],
			],
			'DocumentSeriesAndNumberBehavior' => [
				'class' => DocumentSeriesAndNumberBehavior::class,
				'documentSeriesModel' => DocumentSeries::class,
				'documentSeriesDefaultValue' => function () {
					return DocumentSeries::findDefaultDocumentSeries()->name;
				},
				'documentSeriesAttribute' => 'document_series',
				'documentNumberAttribute' => 'document_number',
				'documentStatusAttribute' => 'status',
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['parent_id', 'broker_id', 'subscriber_id', 'company_id', 'document_number', 'payment_method', 'payment_processor', 'multiplier', 'e_invoice_status', 'type', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['issued_at', 'due_at', 'paid_at', 'sent_at', 'e_invoice_sent_at', 'created_at', 'updated_at'], 'safe'],
			[['issued_at', 'due_at', 'paid_at', 'sent_at', 'e_invoice_sent_at', 'created_at', 'updated_at'], 'default'],
			[['amount', 'exchange_rate'], 'number'],
			[['details'], 'string'],
			[['status'], 'required'],
			[['document_series', 'e_invoice_upload_id', 'e_invoice_download_id'], 'string', 'max' => 255],
			[['e_invoice_error'], 'string'],
			[['currency'], 'string', 'max' => 3],
			[['broker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Broker::class, 'targetAttribute' => ['broker_id' => 'id']],
			[['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
			[['subscriber_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscriber::class, 'targetAttribute' => ['subscriber_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
            'parent_id' => Yii::t('label', 'Parent ID'),
            'broker_id' => Yii::t('label', 'Broker ID'),
			'subscriber_id' => Yii::t('label', 'Subscriber ID'),
			'company_id' => Yii::t('label', 'Company ID'),
			'document_series' => Yii::t('label', 'Document Series'),
			'document_number' => Yii::t('label', 'Document Number'),
			'issued_at' => Yii::t('label', 'Issued At'),
			'due_at' => Yii::t('label', 'Due At'),
			'paid_at' => Yii::t('label', 'Paid At'),
			'payment_method' => Yii::t('label', 'Payment Method'),
			'payment_processor' => Yii::t('label', 'Payment Processor'),
			'amount' => Yii::t('label', 'Amount'),
			'currency' => Yii::t('label', 'Currency'),
			'exchange_rate' => Yii::t('label', 'Exchange Rate'),
			'multiplier' => Yii::t('label', 'Multiplier'),
			'details' => Yii::t('label', 'Details'),
			'e_invoice_upload_id' => Yii::t('label', 'e-Invoice Upload ID'),
			'e_invoice_download_id' => Yii::t('label', 'e-Invoice Download ID'),
			'e_invoice_status' => Yii::t('label', 'e-Invoice Status'),
			'e_invoice_error' => Yii::t('label', 'e-Invoice Error'),
			'e_invoice_sent_at' => Yii::t('label', 'e-Invoice Sent At'),
			'sent_at' => Yii::t('label', 'Sent At'),
			'type' => Yii::t('label', 'Type'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

    /**
     * @return \yii\db\ActiveQuery|CommonActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Invoice::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['parent_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getBroker()
	{
		return $this->hasOne(Broker::class, ['id' => 'broker_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCompany()
	{
		return $this->hasOne(Company::class, ['id' => 'company_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSubscriber()
	{
		return $this->hasOne(Subscriber::class, ['id' => 'subscriber_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getInvoiceHasTemplates()
	{
		return $this->hasMany(InvoiceHasTemplate::class, ['invoice_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTemplates()
	{
		return $this->hasMany(Template::class, ['id' => 'template_id'])->viaTable('{{%invoice_has_template}}', ['invoice_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getItems()
	{
		return $this->hasMany(Item::class, ['invoice_id' => 'id']);
	}

	/**
	 * Gets the zero filled documentSeriesNumber.
	 *
	 * @return string
	 */
	public function getDocumentSeriesNumber()
	{
		return trim($this->document_series . ' ' . str_pad($this->document_number, 5, 0, STR_PAD_LEFT));
	}

    /**
     * Gets the totalAmount.
     *
     * @return float|int
     */
    public function getTotalAmount()
    {
        $totalAmount = 0;
        if (!$this->items) {
            return $totalAmount;
        }
        foreach ($this->items as $item) {
            $totalAmount += (double) $item->price;
        }
        return $totalAmount;
    }

	/**
	 * Gets the sortCodeValues.
	 *
	 * @param bool $asHtml Flag that indicates if HTML code should be rendered.
	 * @return array
	 */
	public function getShortCodeValues($asHtml = false)
	{
		try {
			$details = $this->getUnserializedValue('details');
			$merchant = $details['merchant'];
			$client = $details['client'];

			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to(['/site/index'],'https', '@frontend'),
                '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png','https'),
                '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png','https'),
				'{{COMPANY_NAME}}' => $merchant['name'] ?: '&mdash;',
				'{{COMPANY_TIN}}' => $merchant['tin'] ?: '&mdash;',
				'{{COMPANY_REG_NO}}' => $merchant['registration_number'] ?: '&mdash;',
				'{{COMPANY_LEGAL_REPRESENTATIVE}}' => $merchant['legal_representative'] ?: '&mdash;',
				'{{COMPANY_EMAIL}}' => $merchant['email'] ?: '&mdash;',
				'{{COMPANY_PHONE}}' => $merchant['phone'] ?: '&mdash;',
				'{{COMPANY_FAX}}' => $merchant['fax'] ?: '&mdash;',
				'{{COMPANY_ADDRESS}}' => $merchant['address'] ?: '&mdash;',
				'{{DOCUMENT_NUMBER}}' => $this->getDocumentSeriesNumber(),
				'{{ISSUED_AT}}' => Yii::$app->formatter->asDatetime($this->issued_at),
				'{{DUE_AT}}' => Yii::$app->formatter->asDatetime($this->due_at),
				'{{PAYMENT_METHOD}}' => PaymentMetadata::getPaymentMethodLabels()[$this->payment_method] ?: '&mdash;',
				'{{PAYMENT_PROCESSOR}}' => PaymentMetadata::getPaymentProcessorLabels()[$this->payment_processor] ?: '&mdash;',
                '{{TOTAL_AMOUNT}}' => Yii::$app->formatter->asCurrency($this->getTotalAmount(), $this->currency),
				'{{CLIENT_NAME}}' => implode(' ', array_filter([
                    $client['name'],
				])) ?: '&mdash;',
				'{{CLIENT_PIN}}' => $client['pin'] ?: '&mdash;',
				'{{CLIENT_TIN}}' => $client['tin'] ?: '&mdash;',
				'{{CLIENT_REG_NO}}' => $client['registration_number'] ?: '&mdash;',
				'{{CLIENT_EMAIL}}' => $client['email'] ?: '&mdash;',
				'{{CLIENT_PHONE}}' => $client['phone'] ?: '&mdash;',
				'{{CLIENT_FAX}}' => $client['fax'] ?: '&mdash;',
				'{{CLIENT_ADDRESS}}' => $client['address'],
			];

			if ($asHtml === true) {
				$shortCodeValues['{{ITEMS_LIST}}'] = Yii::$app->controller->renderPartial('@backend/modules/subscriber/views/invoice/_items-list', ['model' => $this]);
			} else {
				$items = [];
				$shortCodeValues['{{ITEMS_LIST}}'] = !empty($items) ? implode("\n", $items) : '-';
			}

			return $shortCodeValues;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Gets the merchant details.
	 *
	 * @return array
	 */
	public function getMerchantDetails()
	{
		$contactSettings = Yii::$app->settings->getCategory('contact');

		return [
			'name' => $contactSettings['company'],
			'tin' => $contactSettings['tin'],
			'registration_number' => $contactSettings['registrationNumber'],
			'legal_representative' => implode(' ', array_filter([
				$contactSettings['firstName'],
				$contactSettings['middleName'],
				$contactSettings['lastName'],
			])),
			'email' => $contactSettings['email'],
			'phone' => implode(', ', array_filter([
				$contactSettings['mobilePhone'],
				$contactSettings['fixedPhone'],
			])),
			'fax' => $contactSettings['fax'],
			'address' => implode(', ', array_filter([
				$contactSettings['streetName'],
				$contactSettings['streetNumber'],
			])),
			'zip_code' => $contactSettings['zip_code'],
			'locality' => $contactSettings['locality'],
			'county' => $contactSettings['county'],
			'country' => $contactSettings['country'],
		];
	}

	/**
	 * Gets the client details.
	 *
	 * @return array
	 */
	public function getClientDetails()
	{
		if ($company = $this->company) {
			return [
				'name' => $company->name,
				'legal_representative' => $company->representativeFullName,
				'tin' => $company->tin,
				'registration_number' => $company->registration_number,
				'email' => $company->email,
				'phone' => $company->phone,
				'fax' => $company->fax,
				'address' => $company->fullAddress,
			];
		}

		$subscriber = $this->subscriber;
		$user = $subscriber->user;

		return [
			'name' => $user->fullName,
			'pin' => $subscriber->pin,
			'email' => $user->email,
			'phone' => $user->phone,
			'address' => $subscriber->fullAddress,
		];
	}

    public static function getProformPaidInvoices($subscription_id = null)
    {
        $invoices = Invoice::find()
            ->select(['i.*'])
            ->alias('i')
            ->joinWith([
                'items itm' => function (ActiveQuery $query) {
                    $query->andWhere([
                        'itm.deleted' => Item::NO,
                    ]);
                },
            ])
            ->andWhere([
                'i.type' => Invoice::TYPE_INVOICE,
                'i.status' => Invoice::STATUS_PAID,
            ])
            ->andWhere([
                'IS NOT', 'i.parent_id', null
            ])
            ->orderBy(['i.issued_at' => SORT_DESC]);
        if ($subscription_id) {
            $invoices->andWhere([
                'itm.item_id' => $subscription_id,
                'itm.type' => Item::TYPE_SUBSCRIPTION,
            ]);
        }
        return $invoices->all();
    }

	/**
	 * Model status labels.
	 *
	 * @return array
	 */
	public static function getStatusLabels()
	{
		return [
			self::STATUS_UNPAID => [
				'label' => Yii::t('label', 'Unpaid'),
				'color' => 'danger',
			],
			self::STATUS_PAID => [
				'label' => Yii::t('label', 'Paid'),
				'color' => 'success',
			],
		];
	}

	/**
	 * Model e-invoice status labels.
	 *
	 * @return array
	 */
	public static function getEStatusLabels()
	{
		return [
			self::E_STATUS_NOT_SENT => [
				'label' => Yii::t('label', 'Not sent'),
				'color' => 'default',
			],
			self::E_STATUS_SENT => [
				'label' => Yii::t('label', 'Sent'),
				'color' => 'warning',
			],
			self::E_STATUS_PENDING => [
				'label' => Yii::t('label', 'Pending'),
				'color' => 'info',
			],
			self::E_STATUS_PROCESSED => [
				'label' => Yii::t('label', 'Processed'),
				'color' => 'success',
			],
			self::E_STATUS_UNPROCESSED => [
				'label' => Yii::t('label', 'Unprocessed'),
				'color' => 'danger',
			],
			self::E_STATUS_REJECTED => [
				'label' => Yii::t('label', 'Rejected'),
				'color' => 'danger',
			],
		];
	}

    /**
     * Model type labels.
     *
     * @return array
     */
    public static function getTypeLabels()
    {
        return [
            self::TYPE_INVOICE => Yii::t('label', 'Invoice'),
            self::TYPE_PROFORM => Yii::t('label', 'Proform'),
            self::TYPE_RECTIFY => Yii::t('label', 'Rectify'),
        ];
    }

	public static function unitPrice($price, $quantity = 1)
	{
		$value = $price / ($quantity ?: 1);
		return round($value, 2);
	}

	public static function vatValue($price, $vat = 0)
	{
		$value = (1 - 1 / (1 + $vat / 100)) * $price;
		return NumberHelper::toNumber($value);
	}

	public function getSubtotal()
	{
		$vat = $this->vat ?: 0;
		$value = (1 - (1 - 1 / (1 + $vat / 100))) * $this->amount;
		return NumberHelper::toNumber($value);
	}

	public function getVatValue()
	{
		$value = $this->amount - $this->getSubtotal();
		return round($value, 2);
	}
}
