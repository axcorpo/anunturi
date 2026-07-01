<?php

namespace backend\modules\subscriber\models;

use common\models\DocumentSeries;
use common\models\Invoice;
use common\models\InvoiceHasTemplate;
use common\models\Item;
use common\models\PaymentMetadata;
use common\models\Subscriber;
use common\models\Subscription;
use common\models\Template;
use common\models\User;
use kartik\mpdf\Pdf;
use Mpdf\Tag\Sub;
use tws\helpers\Url;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class SubscriptionActivateForm extends Subscription
{
	/**
	 * @var Invoice The Invoice model.
	 */
	private $_invoice;

    public $issue_invoice;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
            ['issue_invoice', 'integer']
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'issue_invoice' => Yii::t('label', 'Issue Invoice'),
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
	 * Gets the Invoice model.
	 *
	 * @return Invoice|null
	 */
	public function getInvoice()
	{
		return $this->_invoice;
	}

	/**
	 * Saves the Invoice model.
	 *
	 * @return bool
	 */
	protected function saveInvoice()
	{
		try {
			$user = $this->subscriber->user;

			$invoice = new Invoice();
			$invoice->subscriber_id = $user->subscriber->id;
			$invoice->company_id = $this->company_id;
			$invoice->issued_at = (new \DateTime)->format('Y-m-d H:i:s');
			$invoice->payment_method = $this->paymentMetadata->payment_method ?: PaymentMetadata::PAYMENT_METHOD_BANK;
			$invoice->payment_processor = $this->paymentMetadata->payment_processor;
			$invoice->amount = $this->price;
			$invoice->currency = $this->currency;
			$invoice->paid_at = (new \DateTime)->format('Y-m-d H:i:s');
			$invoice->vat = Yii::$app->settings->get('vatRate', 'general');
			$invoice->setSerializedValue('details', [
				'merchant' => $invoice->getMerchantDetails(),
				'client' => $invoice->getClientDetails(),
                'vatRate' => Yii::$app->settings->get('vatRate', 'general'),
			]);
			$invoice->type = Invoice::TYPE_INVOICE;
			$invoice->status = Invoice::STATUS_PAID;
			$documentSeries = DocumentSeries::findDefaultDocumentSeries(true, DocumentSeries::TYPE_INVOICE);
			$documentNumber = DocumentSeries::getNextDocumentNumber($documentSeries->name, DocumentSeries::TYPE_INVOICE);
			$invoice->document_series = $documentSeries->name;
			$invoice->document_number = $documentNumber;
			if (!$invoice->save()) {
				throw new \Exception();
			}
			$this->_invoice = $invoice;

			$invoiceHasTemplate = new InvoiceHasTemplate();
			$invoiceHasTemplate->invoice_id = $invoice->id;
			$invoiceHasTemplate->template_id = Template::findDefaultByTypeAndVariant(Template::TYPE_INVOICE, TEMPLATE::INVOICE_VARIANT_FISCAL)->id;
			if (!$invoiceHasTemplate->save()) {
				throw new \Exception();
			}

			$item = new Item();
			$item->invoice_id = $invoice->id;
			$item->item_id = $this->id;
			$item->type = Item::TYPE_SUBSCRIPTION;
			$item->quantity = 1;
			$item->price = $this->price;
			$item->currency = $this->currency;
			$item->setSerializedValue('details', [
				'start_at' => $this->start_at,
				'end_at' => $this->end_at,
			]);
			$item->status = Item::STATUS_ACTIVE;
			if (!$item->save()) {
				throw new \Exception();
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Creates a PDF file for the Invoice model and sends it to email.
	 *
	 * @return bool
	 */
	protected function sendInvoiceToEmail()
	{
		try {
			$invoice = $this->getInvoice();
			$invoiceEmailTemplateVariant = $invoice->status == Invoice::STATUS_PAID ? Template::EMAIL_VARIANT_INVOICE_PAYMENT_CONFIRMATION : Template::EMAIL_VARIANT_INVOICE_ISSUANCE;
			$invoiceEmailTemplate = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, $invoiceEmailTemplateVariant);
			if (!$invoiceEmailTemplate || !($invoiceEmailTemplateTranslation = $invoiceEmailTemplate->getTranslation())) {
				throw new \Exception('Missing invoice email template.');
			}

            $subscription = Subscription::find()
                ->alias('s')
                ->select('s.*')
                ->where([
                    's.id' => $this->id,
                ])
                ->andWhere([
                    's.deleted' => Subscription::NO,
                ])
                ->one();

            $subscriber = Subscriber::find()
                ->alias('sub')
                ->select('sub.*')
                ->where([
                    'sub.id' => $subscription->subscriber_id,
                ])
                ->andWhere([
                    'sub.deleted' => Subscriber::NO,
                ])
                ->one();

            $user = User::findOne(['id' => $subscriber->user_id]);

            $shortCodeValues = [
                '{{APP_NAME}}' => Yii::$app->name,
                '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
                '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
                '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
                '{{FIRST_NAME}}' => $user->first_name,
                '{{MIDDLE_NAME}}' => $user->middle_name,
                '{{LAST_NAME}}' => $user->last_name,
                '{{PACKAGE}}' => $this->package->translation->name,
                '{{BILLING_CYCLE}}' => Yii::$app->formatter->asCurrency($this->package->price, $this->package->currency) . '/' . $this->package->getFormattedBillingCycle(),
            ];

			$email = Yii::$app->mailer->compose()
				->setTo($this->subscriber->user->email)
				->setSubject(strtr($invoiceEmailTemplateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($invoiceEmailTemplateTranslation->content, $shortCodeValues));

			if (Yii::$app->settings->get('attachInvoiceToEmail', 'payment')) {
				$invoiceTemplate = Template::findDefaultByTypeAndVariant(Template::TYPE_INVOICE, Template::INVOICE_VARIANT_FISCAL);
				if (!$invoiceTemplate || !($invoiceTemplateTranslation = $invoiceTemplate->getTranslation())) {
					throw new \Exception('Missing invoice template.');
				}
				$fileName = Yii::$app->name . " #{$invoice->getDocumentSeriesNumber()}.pdf";
				$filePath = Yii::getAlias("@runtime/" . Yii::$app->security->generateRandomString(8) . "-{$fileName}");
				$pdf = new Pdf([
					'mode' => Pdf::MODE_UTF8,
					'format' => Pdf::FORMAT_A4,
					'orientation' => Pdf::ORIENT_PORTRAIT,
					'destination' => Pdf::DEST_FILE,
					'content' => strtr($invoiceTemplateTranslation->content, $invoice->getShortCodeValues(true)),
					'filename' => $filePath,
					'marginTop' => 5,
					'marginBottom' => 5,
				]);
				$pdfApi = $pdf->getApi();
				$pdfApi->setAutoTopMargin = true;
				$pdfApi->setAutoBottomMargin = true;
				$pdfApi->defaultheaderline = 0;
				$pdfApi->defaultfooterline = 0;
				if ($invoice->status == Invoice::STATUS_UNPAID) {
					$pdfApi->SetWatermarkText(mb_strtoupper(Invoice::getStatusLabels()[$invoice->status]['label']), 0.1);
					$pdfApi->showWatermarkText = true;
				}
				$pdf->render();
				$email->attach($pdf->filename, ['fileName' => $fileName]);
			}

			$email->send();

			if (Yii::$app->settings->get('attachInvoiceToEmail', 'payment') && isset($pdf)) {
				@unlink($pdf->filename);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

    /**
     * Sends Subscription Confirmation email.
     *
     * @param $announcement
     * @param $client
     * @return bool whether the email was send.
     */
    protected function sendEmail()
    {
        try {
            $template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_SUBSCRIPTION_CONFIRMATION);
            if (!$template || !($templateTranslation = $template->getTranslation())) {
                return false;
            }

            $subscription = Subscription::find()
                ->alias('s')
                ->select('s.*')
                ->where([
                    's.id' => $this->id,
                ])
                ->andWhere([
                    's.deleted' => Subscription::NO,
                ])
                ->one();

            $subscriber = Subscriber::find()
                ->alias('sub')
                ->select('sub.*')
                ->where([
                    'sub.id' => $subscription->subscriber_id,
                ])
                ->andWhere([
                    'sub.deleted' => Subscriber::NO,
                ])
                ->one();

            $user = User::findOne(['id' => $subscriber->user_id]);

            $shortCodeValues = [
                '{{APP_NAME}}' => Yii::$app->name,
                '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
                '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
                '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
                '{{FIRST_NAME}}' => $user->first_name,
                '{{MIDDLE_NAME}}' => $user->middle_name,
                '{{LAST_NAME}}' => $user->last_name,
                '{{PACKAGE}}' => $this->package->translation->name,
                '{{BILLING_CYCLE}}' => Yii::$app->formatter->asCurrency($this->package->price, $this->package->currency) . '/' . $this->package->getFormattedBillingCycle(),
                '{{ACCOUNT_PAGE_URL}}' => Url::to(['/account/profile/index'], true, '@frontend'),
            ];

            return Yii::$app->mailer->compose()
                ->setTo([$user->email => $user->fullName])
                ->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
                ->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
                ->send();

        } catch (\Exception $e) {
            return false;
        }
    }

	/**
	 * @inheritdoc
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function activate($start_at = 'now', $trial = false)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::activate($start_at, $trial)) {
				throw new \Exception();
			}
            if ($this->issue_invoice) {
                if ($this->type != static::TYPE_FREE) {
                    if (!$this->saveInvoice()) {
                        throw new \Exception();
                    }
                    $this->sendInvoiceToEmail();
                }
            }
            $this->sendEmail();
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
