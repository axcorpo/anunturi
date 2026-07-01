<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Renewal */

use common\helpers\UrlHelper;
use common\models\Announcement;
use common\models\Currency;
use common\models\Renewal;
use backend\widgets\ActiveForm;
use common\models\Country;
use kartik\file\FileInput;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\bootstrap\Tabs;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

?>

<?php $form = ActiveForm::begin([
    'id' => 'category-form',
    'options' => [
        'novalidate' => true,
        'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
    ],
    'validateOnType' => true,
]); ?>
<div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
    <?php if (Yii::$app->request->isAjax) : ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <div class="modal-title"><?= $this->title ?></div>
        </div>
    <?php endif; ?>

    <div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'status')->widget(Select2::class, [
                    'data' => ArrayHelper::getColumn(Renewal::getStatusLabels(), 'label'),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-6">
                <? $announcements = \common\models\Promotional::getAnnouncements() ?>
                <?= $form->field($model, 'announcement_id')->widget(Select2::class, [
                    'options' => [
                        'multiple' => false,
                    ],
                    'data' =>ArrayHelper::map($announcements, 'id', 'translation.title'),
                    'maintainOrder' => false,
                    'showToggleAll' => false,
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'price')->widget(TouchSpin::class, [
                    'pluginOptions' => [
                        'min' => 0,
                        'max' => PHP_INT_MAX,
                        'step' => 0.01,
                        'decimals' => 2,
                        'boostat' => 5,
                        'maxboostedstep' => 10,
                        'verticalbuttons' => true,
                    ],
                ]) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'currency')->widget(Select2::class, [
                    'options' => [
                        'multiple' => false,
                    ],
                    'data' => ArrayHelper::map(Currency::find()->active(true)->deleted(false)->all(), 'iso_code', 'formattedName'),
                    'maintainOrder' => false,
                    'showToggleAll' => false,
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
        </div>
    </div>
    <?php if (Yii::$app->request->isAjax) : ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
            <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success']) ?>
        </div>
    <?php else : ?>
        <div class="form-actions floating">
            <?= Html::submitButton('<span class="fa fa-check"></span>', [
                'class' => 'btn btn-xlg btn-fab btn-success',
                'title' => Yii::t('common', 'Save'),
                'data' => [
                    'toggle' => 'tooltip',
                ],
            ]) ?>
        </div>
    <?php endif; ?>
</div>
<?php ActiveForm::end(); ?>
