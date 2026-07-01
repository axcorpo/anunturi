<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use yii\helpers\Html;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Reservation')]);
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
