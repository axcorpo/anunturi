<?php

/* @var $this yii\web\View */
/* @var $model common\models\Bid */

use yii\helpers\Html;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Bid')]);

?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
