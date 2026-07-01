<?php

/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Template */
/* @var $language common\models\Language */

use tws\widgets\tinymce\TinyMCE;
use yii\helpers\Html;
?>

<?= $form->field($model, "title[{$language->language_id}]", [
	'options' => [
		'class' => 'form-group' . ($language->language_id == Yii::$app->language ? ' required' : ''),
	],
])->textInput() ?>


<?= $form->field($model, "content[{$language->language_id}]")->widget(TinyMCE::class, [
	'clientOptions' => [
		'extended_valid_elements' => 'script[id|type|src|async|defer|data]',
		// TODO 1: insert here the bootstrap CSS for GRID, panels etc. without body and html Yii::getAlias('@bower/bootstrap/dist/bootstrap.css')
//		'content_css' => 'https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css',
		// TODO 2: create some templates with grid or move it to the widget itsef?
//		'templates' => [
//			['title' => 'Test template 1', 'content' => 'Test 1'],
//			['title' => 'Test template 2', 'content' => 'Test 2'],
//		],
	],
]) ?>



