<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Message */

use backend\widgets\ActiveForm;
use kartik\rating\StarRating;
use yii\helpers\Html;
use yii\helpers\Url;

?>

<?php $form = ActiveForm::begin([
    'id' => mb_strtolower($model->formName()),
    'options' => [
        'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
        'novalidate' => true,
    ],
    'validateOnType' => true,
    'validateOnBlur' => false,
]); ?>
<div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
    <?php if (Yii::$app->request->isAjax) : ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <div class="modal-title"><?= $this->title ?></div>
        </div>
    <?php endif; ?>

    <div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
        <?= $form->field($model, 'conversation_id')->hiddenInput([
                'value' => $model->conversation_id,
        ])->label(false) ?>
        <?= $form->field($model, 'recipient_id')->hiddenInput([
            'value' => $model->created_by,
        ])->label(false) ?>
        <div class="row">
			<div class="col-sm-12">
				<?= $form->field($model, 'content')->textarea(['rows' => 5]) ?>
			</div>
        </div>
    </div>
    <?php if (Yii::$app->request->isAjax) : ?>
        <div class="modal-footer">
			<button type="button" class="btn btn-primary btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
			<?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-primary']) ?>
        </div>
    <?php else : ?>
        <div class="form-actions floating">
            <?= Html::submitButton(Yii::t('label', 'Send'), [
                'class' => 'btn btn-primary',
                'title' => Yii::t('common', 'Send'),
                'data' => [
                    'toggle' => 'tooltip',
                ],
            ]) ?>
        </div>
    <?php endif; ?>
</div>
<?php ActiveForm::end(); ?>


