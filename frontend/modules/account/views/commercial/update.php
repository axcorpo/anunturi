<?php

/* @var $this yii\web\View */
/* @var $model common\models\Bid */

use common\models\Bid;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Update {item}', ['item' => Yii::t('common', 'Bid')]);
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
