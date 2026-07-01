<?php

/* @var $this yii\web\View */
/* @var $model common\models\Review */

use common\models\Review;
use common\models\Field;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Update {item}', ['item' => Yii::t('common', 'Review')]);

?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
