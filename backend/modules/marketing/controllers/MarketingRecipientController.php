<?php

namespace backend\modules\marketing\controllers;

use backend\controllers\MainController;
use backend\modules\marketing\models\MarketingRecipientBulkForm;
use backend\modules\marketing\models\MarketingRecipientForm;
use backend\modules\marketing\models\MarketingRecipientSearch;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use common\helpers\Inflector;
use common\helpers\UploadHelper;
use common\models\MarketingRecipient;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MarketingRecipientController extends MainController
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
                        'actions' => ['index', 'view', 'export', 'dt-marketing-recipients'],
                        'roles' => ['viewMarketingRecipient'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['createMarketingRecipient'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'bulk-update'],
                        'roles' => ['updateMarketingRecipient'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['deleteMarketingRecipient'],
                        'verbs' => ['POST'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['restore'],
                        'roles' => ['restoreMarketingRecipient'],
                        'verbs' => ['POST'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['import'],
                        'roles' => ['importMarketingRecipient'],
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
            'dt-marketing-recipients' => MarketingRecipientSearch::class,
        ];
    }

    /**
     * Lists all MarketingRecipient models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        if (Yii::$app->request->get('deleted') == MarketingRecipient::YES) {
            if (!Yii::$app->settings->get('enableSoftDelete') || !Yii::$app->user->can('restoreMarketingRecipient')) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('index');
    }

    /**
     * Displays a single Service model.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new MarketingRecipient model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new MarketingRecipientForm();
        $result = true;

        Yii::$app->eventLog->beginRecord($model);
        if ($model->load(Yii::$app->request->post()) && ($result = $model->save())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMarketingRecipients']));
            Yii::$app->eventLog->endRecord();

            $message = Yii::t('common', 'Record has been created.');
            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool)$result,
                'data' => $this->renderAjax('create', [
                    'model' => $model,
                ]),
            ]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing MarketingRecipient model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id, MarketingRecipientForm::class);
        $result = true;

        Yii::$app->eventLog->beginRecord($model);
        if ($model->load(Yii::$app->request->post()) && ($result = $model->save())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMarketingRecipients']));
            Yii::$app->eventLog->endRecord();

            $message = Yii::t('common', 'Record has been updated.');
            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool)$result,
                'data' => $this->renderAjax('update', [
                    'model' => $model,
                ]),
            ]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Bulk updates existing models.
     * If deletion is successful, JSON is returned.
     *
     * @return mixed
     * @throws \Exception
     */
    public function actionBulkUpdate()
    {
        $emptyModel = new MarketingRecipientBulkForm();
        $selection = Yii::$app->request->get('selection') ?: Yii::$app->session->get('MarketingRecipientSelection');
        $models = MarketingRecipient::find()
            ->alias('t')
            ->where([
                't.id' => $selection,
            ])
            ->all();
        $result = true;

        if (Yii::$app->request->post('dt_bulk_operation') && Yii::$app->request->post('selection')) {
            Yii::$app->session->set('MarketingRecipientSelection', Yii::$app->request->post('selection'));
        }

        $bodyParams = Yii::$app->request->post();
        if ($bodyParams['MarketingRecipientBulkForm']) {
            foreach ($models as $model) {
                $params = [];
                $post = $bodyParams['MarketingRecipientBulkForm'];
                $attributes = $model->getAttributes();
                $keys = [];
                if ($post['columns']) {
                    foreach($post['columns'] as $column) {
                        $keys[] = $column;
                    }
                }
                foreach ($post as $key => $value) {
                    if (in_array($key, $keys)) {
                        $params[$key] = $post[$key];
                    }
                }

                unset($params['columns']);

                $bodyParams['MarketingRecipientForm'] = $params;
                $currentModel = $this->findModel($model->id, MarketingRecipientForm::class);

                $eventLog = new \backend\modules\eventlog\components\EventLog([
                    'model' => Yii::$app->eventLog->model,
                ]);

                $eventLog->beginRecord($currentModel);

                if ($currentModel->load($bodyParams) && ($result &= $currentModel->save(false))) {

                    Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMarketingRecipients']));

                    $eventLog->endRecord();
                }
            }
            if ($result) {
                $message = Yii::t('common', 'Records have been updated.');

                if (Yii::$app->request->isAjax) {
                    return $this->asJson([
                        'success' => true,
                        'message' => $message,
                    ]);
                }

                Yii::$app->session->setFlash('success', $message);

                Yii::$app->session->remove('MarketingRecipientSelection');
                return $this->redirect(['index']);
            }
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool) $result,
                'data' => $this->renderAjax('_bulk-update', [
                    'model' => $emptyModel,
                ]),
            ]);
        }

        return $this->render('_bulk-update', [
            'model' => $emptyModel,
        ]);
    }

    /**
     * Deletes existing MarketingRecipient models.
     * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
     *
     * @param null|int $id
     * @return mixed
     * @throws \Throwable
     * @throws NotFoundHttpException if no model was found
     */
    public function actionDelete($id = null)
    {
        $bodyParams = Yii::$app->request->post();
        $isPermanent = !Yii::$app->settings->get('enableSoftDelete') || ($bodyParams['dt_operation'] == 'delete-permanently' || $bodyParams['dt_bulk_operation'] == 'delete-permanently');
        if ($id === null) {
            $id = Yii::$app->request->post('selection');
        }
        $models = $this->findModel($id, null, true);
        $response = [
            'success' => true,
            'message' => [
                'title' => Yii::t('common', 'The delete operation was successful.'),
                'body' => [],
            ],
        ];
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            $deletedModels = [];
            /** @var MarketingRecipient $model */
            foreach ($models->each() as $model) {
                Yii::$app->eventLog
                    ->setData([
                        'operation' => $isPermanent ? (Yii::$app->eventLog)::ACTION_DELETE : (Yii::$app->eventLog)::ACTION_SOFT_DELETE,
                    ])
                    ->beginRecord($model);
                if ($model->delete($isPermanent)) {
                    $deletedModels[] = $model->id;
                    Yii::$app->eventLog->endRecord();
                } else {
                    throw new \Exception();
                }
            }
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMarketingRecipients']));
            $dbTransaction->commit();
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            $response['success'] = false;
            $response['message']['title'] = Yii::t('common', 'The delete operation was unsuccessful.');
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson($response);
        }
        Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

        return $this->redirect(['index']);
    }

    /**
     * Restores MarketingRecipient models that are marked as deleted.
     * If restoration is successful, JSON is returned or the browser will be redirected to the 'index' page.
     *
     * @param null|int $id
     * @return mixed
     * @throws NotFoundHttpException if no model was found
     */
    public function actionRestore($id = null)
    {
        if ($id === null) {
            $id = Yii::$app->request->post('selection');
        }
        $models = $this->findModel($id, null, true);
        $response = [
            'success' => true,
            'message' => [
                'title' => Yii::t('common', 'The restore operation was successful.'),
                'body' => [],
            ],
        ];
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            /** @var MarketingRecipient $model */
            foreach ($models->each() as $model) {
                Yii::$app->eventLog->beginRecord($model);
                if ($model->restore()) {
                    Yii::$app->eventLog->endRecord();
                } else {
                    throw new \Exception();
                }
            }
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMarketingRecipientCategories']));
            $dbTransaction->commit();
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            $response['success'] = false;
            $response['message']['title'] = Yii::t('common', 'The restore operation was unsuccessful.');
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson($response);
        }
        Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

        return $this->redirect(['index']);
    }

    /**
     * Finds the MarketingRecipient model(s) based on its primary key value.
     * A 404 HTTP exception will be thrown if no record was found.
     *
     * @param int|array $id
     * @param \yii\db\ActiveRecord|null $modelName
     * @param bool $asActiveQuery
     * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|MarketingRecipient|MarketingRecipientForm
     * @throws NotFoundHttpException if no model was found
     */
    protected function findModel($id, $modelName = null, $asActiveQuery = false)
    {
        $modelName = class_exists($modelName) ? $modelName : MarketingRecipient::class;
        $query = $modelName::find()->andWhere([
            'id' => $id,
        ]);

        if ($asActiveQuery) {
            if (!$query->count()) {
                throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
            }
            return $query;
        }

        $query->andWhere(['deleted' => MarketingRecipient::NO]);
        if (($model = $query->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
    }

    /**
     * Imports new Product models.
     * If creation is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionImport()
    {
        return $this->render('import');
    }


    /**
     * Updates DataTable column.
     *
     * @return array
     * @throws NotFoundHttpException
     */
    protected function updateDtColumn()
    {
        // Request data
        $params = Yii::$app->request->post();
        // Set the response format as JSON
        Yii::$app->response->format = Response::FORMAT_JSON;
        // Find model by key
        $model = $this->findModel($params['key']);
        // Set attributes
        $model->{$params['column']} = $params['value'];
        // Save
        $result = $model->save();
        // Return the operation status
        return [
            'success' => (bool) $result,
            'message' => $result ?
                Yii::t('common', 'Record has been updated.') :
                Yii::t('common', 'Cannot update the record.'),
        ];
    }

    public function filter_me(&$array)
    {
        foreach ( $array as $key => $item ) {
            is_array ( $item ) && $array [$key] = $this->filter_me ( $item );
            if (empty ( $array [$key] ))
                unset ( $array [$key] );
        }
        return $array;
    }

    /**
     * Exports data to a specific file format.
     *
     * @return \yii\web\Response
     */
    public function actionExport()
    {
        try {
            $bodyParams = Yii::$app->request->post();
            $dataTable = $bodyParams['dataTable'];
            $result = null;

            // Create a new DataTableAction model
            $model = $this->getDataTableActionModel($bodyParams['model']);
            // Set the externalFilters
            if (is_array($dataTable['external_filters']) && !empty($dataTable['external_filters'])) {
                $model->externalFilters = ArrayHelper::map($dataTable['external_filters'], 'name', 'value');
            }

            $model->applyFilter($model->query, $dataTable['columns'], $dataTable['search']);
            $model->applyOrder($model->query, $dataTable['columns'], $dataTable['order']);

            // Get data, then filter the columns to be exported
            if (method_exists($model, 'getExportData')) {
                $records = $model->getExportData($model->query, []);
            } else {
                $records = $model->formatData($model->query, []);
            }
            $columns = array_filter($dataTable['columns'], function ($item) {
                return !empty($item['name']) && $item['visible'] == 'true' && !in_array($item['name'], ['action', 'group', 'created_at', 'status']);
            });
            $result = $this->exportAsXlsx($bodyParams, $columns, $records);
            if (empty($result)) {
                throw new \Exception(Yii::t('common', 'Cannot export the requested data.'));
            }

            return $this->asJson([
                'success' => true,
                'returnUrl' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Export as XLSX.
     *
     * @param array $bodyParams
     * @param array $columns
     * @param array $records
     * @return string|bool
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\SpoutException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterAlreadyOpenedException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    protected function exportAsXlsx($bodyParams, $columns, $records)
    {
        /** @var \Box\Spout\Writer\XLSX\Writer $writer */
        $writer = WriterFactory::create(Type::XLSX);

        if (isset($bodyParams['config']['shouldCreateNewSheetsAutomatically'])) {
            $writer->setShouldCreateNewSheetsAutomatically($bodyParams['config']['shouldCreateNewSheetsAutomatically']);
        }
        if (isset($bodyParams['config']['shouldUseInlineStrings'])) {
            $writer->setShouldUseInlineStrings($bodyParams['config']['shouldUseInlineStrings']);
        }

        // Ensure the file path
        $filePath = 'export/' . Yii::$app->user->id;
        $name = [$bodyParams['title']];
        $name[] = Yii::$app->name;
        $name[] = date('d.m.Y');
        $fileName = Inflector::slug(implode('-', $name)) . ".xlsx";
        FileHelper::removeDirectory(Yii::getAlias("@uploads/{$filePath}"));
        UploadHelper::ensureDirectoryTree($filePath);

        // Write data to the file
        $writer->openToFile(Yii::getAlias("@uploads/{$filePath}/{$fileName}"));
        $writer->addRowWithStyle(ArrayHelper::getColumn($columns, 'title'), (new StyleBuilder())->setFontBold()->build());

        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $column) {
                if (!empty($column['visible'])) {
                    $value = strip_tags(html_entity_decode($record[$column['name']]));
                    if (is_numeric($value)) {
                        if (in_array($column['name'], ['phone'])) {
                            $value = $value . ' ';
                        } else {
                            $value = (double)$value;
                        }
                    }
                    $row[] = $value;
                }
            }
            $writer->addRow($row);
        }

        $writer->close();

        return UploadHelper::getFileUrl("{$filePath}/{$fileName}");
    }

    /**
     * Creates a new instance of a DataTableAction.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param \common\widgets\datatable\DataTableAction $className
     * @return \common\widgets\datatable\DataTableAction the loaded model
     * @throws \yii\web\NotFoundHttpException if the model cannot be found
     */
    protected function getDataTableActionModel($className)
    {
        if (class_exists($className)) {
            return new $className('dt-action', $this->id);
        }

        throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
    }
}
