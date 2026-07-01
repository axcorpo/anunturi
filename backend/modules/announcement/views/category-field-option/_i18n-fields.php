<?php
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Option */
/* @var $language common\models\Language */

use yii\helpers\Html;
?>

<?= $form->field($model, "label[{$language->language_id}]", [
	'options' => [
		'class' => 'form-group' . ($language->language_id == Yii::$app->language ? ' required' : ''),
	],
])->textInput(['disabled' => !$model->isNewRecord]) ?>

