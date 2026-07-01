<?php

/* @var $this yii\web\View */
/* @var $model common\models\Unavailability */

use yii\helpers\Html;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Unavailability')]);

?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
