<?php

/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Company */
/* @var $language common\models\Language */

use yii\helpers\Html;
use tws\widgets\tinymce\TinyMCE;
use kartik\select2\Select2;
?>

<?//= $form->field($model, "keywords[{$language->language_id}]")->widget(Select2::class, [
//	'options' => [
//		'multiple' => true,
//		'data' => [
//			'seo-meta' => 'keywords',
//			'seo-minlength' => 5,
//			'seo-maxlength' => 10,
//			'seo-minlength-hint' => Yii::t('backend', '%s keywords were introduced. It is recommended to have at least %s keywords.'),
//			'seo-maxlength-hint' => Yii::t('backend', '%s keywords were introduced. Maximum number of keywords should not exceed %s.'),
//		],
//	],
//	'pluginLoading' => true,
//	'showToggleAll' => false,
//	'pluginOptions' => [
//		'allowClear' => false,
//		'tags' => true,
//		'tokenSeparators' => [',', ';'],
//		'maximumSelectionLength' => 10,
//	],
//])->hint(Yii::t('common', '{min} to {max} words that best describe the page.', ['min' => 5, 'max' => 10])) ?>
<!---->
<?//= $form->field($model, "description[{$language->language_id}]")
//->textarea([
//	'rows' => 3,
//	'data' => [
//		'seo-meta' => 'description',
//		'seo-minlength' => 120,
//		'seo-maxlength' => 150,
//		'seo-minlength-hint' => Yii::t('backend', 'The entered text has %s characters. It is recommended to have at least %s characters.'),
//		'seo-maxlength-hint' => Yii::t('backend', 'The entered text has %s characters. Maximum number of characters should not exceed %s.'),
//		'seo-keyscount-hint' => Yii::t('backend', '%s keywords were used %s. It is recommended to use at least 2 keywords in description.'),
//	],
//])
//->hint(Yii::t('common', 'Recommended length is between {min}-{max} characters.', ['min' => 120, 'max' => 150])) ?>

<?= $form->field($model, "content[{$language->language_id}]")->textarea(['rows' => '3']) ?>
<?= $form->field($model, "schedule[{$language->language_id}]")->textarea(['rows' => '3']) ?>
