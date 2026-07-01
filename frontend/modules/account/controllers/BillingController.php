<?php

namespace frontend\modules\account\controllers;

use common\models\Package;
use frontend\controllers\MainController;
use frontend\modules\account\models\InvoiceSearch;
use Yii;
use yii\filters\AccessControl;

class BillingController extends MainController
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
						'actions' => ['index', 'dt-invoices'],
						'roles' => ['@'],
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
	 */
	public function actionIndex()
	{
		if (Yii::$app->request->get('deleted') == Package::YES) {
			if (!Yii::$app->settings->get('enableSoftDelete') || !Yii::$app->user->can('managePackage')) {
				return $this->redirect(['index']);
			}
		}
		return $this->render('index');
	}
}
