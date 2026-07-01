<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use yii\helpers\Html;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Message')]);
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
