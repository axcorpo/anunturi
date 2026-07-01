<?php

namespace backend\modules\subscriber\controllers;

use backend\modules\subscriber\models\EInvoiceForm;
use common\models\Integration;
use common\models\Invoice;
use common\models\Template;
use backend\controllers\MainController;
use backend\modules\subscriber\models\InvoiceSearch;
use DateTime;
use kartik\mpdf\Pdf;
use Yii;
use yii\filters\AccessControl;
use tws\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class InvoiceController extends MainController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'actions' => ['index', 'view', 'dt-invoices', 'e-invoice'],
						'roles' => ['viewInvoice'],
					],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'dt-invoices' => InvoiceSearch::class,
		];
	}

    /**
     * Displays index view.
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
	public function actionIndex()
	{
        // Get the DataTable request parameters from the current session
        $dtInvoice = Yii::$app->session->get('InvoiceSearch', []);

        if (!Yii::$app->session->has('InvoiceSearch')) {
            if (!isset($dtInvoice['external_filters']['date_start'])) {
                $dtInvoice['external_filters'][] = [
                    'name' => 'date_start',
                    'value' => Yii::$app->formatter->asDate(new DateTime('monday this week')),
                ];
            }
            if (!isset($dtInvoice['external_filters']['date_end'])) {
                $dtInvoice['external_filters'][] = [
                    'name' => 'date_end',
                    'value' => Yii::$app->formatter->asDate(new DateTime('sunday this week')),
                ];
            }
        }

        // Get the DataTable external filters from the current session
        $dtExternalFilters = isset($dtInvoice['external_filters']) ?
            ArrayHelper::map($dtInvoice['external_filters'], 'name', 'value') : null;

		$query = "
		    SELECT document_series, document_number, type, MIN(id) AS min_id
		    FROM invoice
		    GROUP BY document_series, document_number, type
		    HAVING COUNT(*) > 1
		    LIMIT 1
		";
		$result = Yii::$app->db->createCommand($query)->queryOne();
		if (!empty($result)) {
			$query = "
			    DELETE i
				FROM invoice i
				JOIN (
				    SELECT document_series, document_number, type, MIN(id) AS min_id
				    FROM invoice
				    GROUP BY document_series, document_number, type
				    HAVING COUNT(*) > 1
				) dup ON i.document_series = dup.document_series AND i.document_number = dup.document_number AND i.type = dup.type
				WHERE i.id > dup.min_id";
			Yii::$app->db->createCommand($query)->execute();
		}

		$integration = Integration::find()
			->where([
				'type' => Integration::TYPE_SPV,
				'status' => Integration::STATUS_ACTIVE,
				'deleted' => Integration::NO,
			])
			->one();

        return $this->render('index', [
            'dtInvoice' => $dtInvoice,
            'dtExternalFilters' => $dtExternalFilters,
	        'integration' => $integration,
        ]);
	}

	/**
	 * Views the invoice of an existing model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 * @throws \yii\base\InvalidConfigException
	 */
	public function actionView($id)
	{
		$model = $this->findModel($id);
		/** @var Template $template */
		$template = $model->getTemplates()->andWhere([
			'type' => Template::TYPE_INVOICE,
			'status' => Template::STATUS_ACTIVE,
			'deleted' => Template::NO,
		])->limit(1)->one();

		if (!$template || !($templateTranslation = $template->getTranslation())) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		$fileName = "{$model->getDocumentSeriesNumber()}.pdf";
		$filePath = Yii::getAlias("@runtime/" . Yii::$app->user->id . "-{$fileName}");
		$pdf = new Pdf([
			'mode' => Pdf::MODE_UTF8,
			'format' => Pdf::FORMAT_A4,
			'orientation' => Pdf::ORIENT_PORTRAIT,
			'destination' => Pdf::DEST_BROWSER,
			'content' => strtr($templateTranslation->content, $model->getShortCodeValues(true)),
			'filename' => $filePath,
			'marginTop' => 5,
			'marginBottom' => 5,
		]);
		$pdfApi = $pdf->getApi();

		$pdfApi->setAutoTopMargin = true;
		$pdfApi->setAutoBottomMargin = true;
		$pdfApi->defaultheaderline = 0;
		$pdfApi->defaultfooterline = 0;

		if ($model->status == Invoice::STATUS_UNPAID) {
			$pdfApi->SetWatermarkText(mb_strtoupper(Invoice::getStatusLabels()[$model->status]['label']), 0.1);
			$pdfApi->showWatermarkText = true;
		}

		return $pdf->render();
	}

	/**
	 * Generates an XML document as export for e-Invoice.
	 *
	 * @return false|string
	 */
	public function actionEInvoice($id)
	{
		try {
			$model = $this->findModel($id, EInvoiceForm::class);
			$action = Yii::$app->request->get('action');
			if (!$model->saveModel($action)) {
				throw new \Exception();
			}
			if (!in_array($action, ['generate', 'download'])) {
				return $this->redirect(['index']);
			}

		} catch (\Exception $e) {
			if (!empty($model->errors)) {
				$errors = array_values($model->errors)[0];
				$message = implode('<br>', $errors);
				Yii::$app->session->setFlash('error', $message);
				switch ($action) {
					default:
						$this->redirect(['index']);
						break;
				}
			}
			return null;
		}
	}

	/**
	 * Finds the Invoice model(s) based on its primary key value.
	 * A 404 HTTP exception will be thrown if no record was found.
	 *
	 * @param int|array $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Invoice
	 * @throws NotFoundHttpException if no model was found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Invoice::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
		]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['deleted' => Invoice::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
