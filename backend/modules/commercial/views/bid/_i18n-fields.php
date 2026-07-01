<?php

/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Bid */
/* @var $language common\models\Language */

use yii\helpers\Html;
use tws\widgets\tinymce\TinyMCE;
use kartik\select2\Select2;
?>

<?= $form->field($model, "name[{$language->language_id}]", [
	'options' => [
		'class' => 'form-group' . ($language->language_id == Yii::$app->language ? ' required' : ''),
	],
])->textInput() ?>

<?= $form->field($model, "content[{$language->language_id}]")->textarea(['rows' => 6])->widget(TinyMCE::class) ?>
