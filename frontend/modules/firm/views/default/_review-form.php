<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $reviewModel common\models\Review */

use backend\widgets\ActiveForm;
use kartik\rating\StarRating;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($reviewModel->formName()),
	'method'=>'post',
	'options' => [
		'novalidate' => true,
	],
	'validateOnType' => true,
]); ?>
<div class="form-body ">
    <div class="form-fields">
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($reviewModel, 'score')->widget(StarRating::class, [
                    'pluginOptions' => [
                        'displayOnly' => false,
                        'showCaption' => false,
                        'showClear' => false,
                        'size' => 'sm',
                        'min' => 0,
                        'max' => 5,
                        'stars' => 5,
                        'step' => 1,
						'theme' => 'krajee-fa',
						'filledStar' => '<i class="fa fa-star" aria-hidden="true"></i>',
						'emptyStar' => '<i class="fa fa-star-o" aria-hidden="true"></i>'
                    ],
                ])->label(false) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
				<?= $form->field($reviewModel, 'title')->textInput() ?>
			</div>
			<div class="col-sm-12">
				<?= $form->field($reviewModel, 'content')->textarea(['rows' => 5]) ?>
			</div>
			<div class="col-sm-12">
				<?= $form->field($reviewModel, 'verifyCode', [
					'options' => [
						'class' => 'form-verify-field',
					],
					'template' => '{input}',
				])->textInput()->label(false) ?>
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
