<?php

/* @var $this yii\web\View */
/* @var $model common\models\Commercial */

use yii\helpers\Html;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Commercial')]);
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
