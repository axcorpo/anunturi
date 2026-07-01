<?php

namespace console\controllers;

use common\models\Invoice;
use common\models\Item;
use common\models\Subscription;
use common\models\Template;
use kartik\mpdf\Pdf;
use tws\helpers\Url;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\ActiveQuery;
use yii\db\Expression;

ini_set("memory_limit","-1");

class InvoiceController extends Controller
{
	/**
	 * @var \DateTime The current date and time instance used for this process operations.
	 */
	public static $currentDate;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		self::$currentDate = new \DateTime();
	}

	/**
	 * Run the payment check.
	 *
	 * @return int
	 */
	public function actionRun()
	{
		try {
			$currentDate = self::$currentDate->format('Y-m-d H:i:s');
			$invoices = self::getUnsentPaidInvoices();
			$count = 0;

			foreach ($invoices as $invoice) {
				$subscription = Subscription::findOne(['id' => $invoice->items[0]->item_id]);
				if (empty($subscription)) {
					continue;
				}
				$invoice->sent_at = $currentDate;
				if (!$invoice->save(false)) {
					continue;
				}
				if (Yii::$app->settings->get('enableInvoiceEmailNotification', 'payment')) {
					self::sendInvoiceToEmail($subscription, $invoice);
				}
				$count++;
			}

			$this->stdout("[" . date("Y-m-d H:i:s") . "] Processed {$count} invoice(s).");
			return ExitCode::OK;
		} catch (\Exception $e) {
			$this->stderr($e->getMessage());
			return ExitCode::UNSPECIFIED_ERROR;
		} catch (\Throwable $e) {
			$this->stderr($e->getMessage());
			return ExitCode::UNSPECIFIED_ERROR;
		}
	}

	/**
	 * Gets the subscriptions that needs to be paid.
	 *
	 * @return array|\yii\db\ActiveRecord[]|Subscription[]
	 */
	protected static function getUnsentPaidInvoices()
	{
		$currentDate = self::$currentDate->format('Y-m-d H:i:s');
		$query = Invoice::find()
			->alias('i')
			->joinWith([
				'items itm' => function (ActiveQuery $query) {
					$query->andWhere(['itm.type' => Item::TYPE_SUBSCRIPTION]);
				},
			], false)
			->andWhere(new Expression("TIMESTAMPDIFF(MINUTE, [[i.issued_at]], '{$currentDate}') <= 5"))
			->andWhere([
				'IS', 'i.sent_at', null
			])
			->andWhere([
				'i.status' => Invoice::STATUS_PAID,
				'i.deleted' => Invoice::NO,
			]);

		return $query->all();
	}

	/**
	 * Creates a PDF file for an Invoice model and sends it to email.
	 *
	 * @param Subscription $subscription
	 * @param Invoice $invoice
	 * @return bool
	 */
	protected static function sendInvoiceToEmail($subscription, $invoice)
	{
		try {
			$invoiceEmailTemplateVariant = $invoice->status == Invoice::STATUS_PAID ? Template::EMAIL_VARIANT_INVOICE_PAYMENT_CONFIRMATION : Template::EMAIL_VARIANT_INVOICE_ISSUANCE;
			$invoiceEmailTemplate = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, $invoiceEmailTemplateVariant);
			if (!$invoiceEmailTemplate || !($invoiceEmailTemplateTranslation = $invoiceEmailTemplate->getTranslation())) {
				throw new \Exception('Missing invoice email template.');
			}
			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
				'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
				'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
				'{{FIRST_NAME}}' => $subscription->subscriber->user->first_name,
				'{{LAST_NAME}}' => $subscription->subscriber->user->last_name,
				'{{PACKAGE}}' => "#{$subscription->code} ({$subscription->package->translation->name})",
				'{{BILLING_CYCLE}}' => Yii::$app->formatter->asDate($subscription->start_at) . ' - ' . Yii::$app->formatter->asDate($subscription->end_at),
			];

			$email = Yii::$app->mailer->compose()
				->setTo([$subscription->subscriber->user->email => $subscription->subscriber->user->fullName])
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

			if (isset($pdf)) {
				@unlink($pdf->filename);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}
