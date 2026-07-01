<?php

/* @var $this yii\web\View */
/* @var $model common\models\Product */

use backend\modules\import\widgets\spreadsheetimport\SpreadsheetImport;
use backend\modules\marketing\models\MarketingRecipientForm;
use backend\modules\nomenclature\models\ProductForm;
use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Yii::t('common', 'Import {item}', ['item' => Yii::t('common', 'Marketing Recipient')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Marketing Recipients'),
		'url' => ['index'],
	],
	Yii::t('common', 'Import'),
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewMarketingRecipient'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'Marketing Recipients'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
];
?>

<?= SpreadsheetImport::widget([
	'model' => MarketingRecipientForm::class,
	'excludedColumns' => ['id', 'marketing_group_id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'status', 'deleted'],
	'returnUrl' => Url::to(['index']),
]) ?>
