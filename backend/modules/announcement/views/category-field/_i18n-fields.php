<?php
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Field */
/* @var $language common\models\Language */

use yii\helpers\Html;
?>

<?= $form->field($model, "label[{$language->language_id}]", [
	'options' => [
		'class' => 'form-group' . ($language->language_id == Yii::$app->language ? ' required' : ''),
	],
])->textInput(['disabled' => !$model->isNewRecord]) ?>
<?= $form->field($model, "placeholder[{$language->language_id}]")->textInput(['disabled' => !$model->isNewRecord]) ?>
<?= $form->field($model, "help_text[{$language->language_id}]")->textInput(['disabled' => !$model->isNewRecord]) ?>

