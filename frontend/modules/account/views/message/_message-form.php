<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $messageModel common\models\Message */

use backend\widgets\ActiveForm;
use kartik\rating\StarRating;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($messageModel->formName()),
	'method'=>'post',
	'options' => [
		'novalidate' => true,
	],
	'validateOnType' => true,
]); ?>
<div class="form-body ">
    <div class="form-fields">
        <div class="row">
<!--            <div class="col-sm-12">-->
<!--				--><?//= $form->field($messageModel, 'subject')->textInput() ?>
<!--			</div>-->
			<div class="col-sm-12">
				<?= $form->field($messageModel, 'content')->textarea(['rows' => 5]) ?>
			</div>
        </div>
    </div>
	<div class="form-actions floating">
		<?= Html::submitButton(Yii::t('common', 'Submit'), [
			'class' => 'btn btn-xlg btn-fab btn-success btn-primary',
		]) ?>
	</div>
</div>
<?php ActiveForm::end(); ?>
