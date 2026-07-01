<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model frontend\modules\account\models\EmailCompanyForm */

use common\models\Country;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use tws\helpers\Url;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

$shouldRenderModal = Yii::$app->request->isAjax;
?>

<?php $form = ActiveForm::begin([
    'id' => mb_strtolower($model->formName()),
    'options' => [
        'novalidate' => true,
        'class' => $shouldRenderModal ? 'modal-dialog modal-lg' : '',
    ],
    'validateOnType' => true,
]); ?>
<div class="form-body <?= $shouldRenderModal ? 'modal-content' : '' ?>">
    <?php if ($shouldRenderModal): ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <div class="modal-title"><?= Yii::t('common', 'Message') ?></div>
        </div>
    <?php endif; ?>

    <div class="form-fields <?= $shouldRenderModal ? 'modal-body' : '' ?>">
        <?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
            <?= $form->errorSummary($model, [
                'header' => false,
                'class' => 'alert alert-danger alert-icon',
            ]) ?>
        <?php endif; ?>
        <div class="row">
            <div class="col-sm-12">
                <?= $form->field($model, 'subject')->textInput() ?>
            </div>
            <div class="col-sm-12">
                <?= $form->field($model, 'message')->textarea(['rows' => 7]) ?>
            </div>
        </div>
    </div>

    <?php if ($shouldRenderModal): ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-light btn-slide-right btn-primary" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
            <button type="submit" class="btn btn-default btn-slide-right btn-primary"><?= Yii::t('common', 'Save') ?></button>
        </div>
    <?php else: ?>
        <div class="form-actions floating">
            <?= Html::submitButton('<span class="fa fa-check"></span>', [
                'class' => 'btn btn-xlg btn-fab btn-default',
                'title' => Yii::t('common', 'Save'),
                'data' => [
                    'toggle' => 'tooltip',
                ],
            ]) ?>
        </div>
    <?php endif; ?>
</div>
<?php ActiveForm::end(); ?>