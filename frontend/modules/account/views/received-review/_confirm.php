<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Review */

use common\helpers\UrlHelper;
use common\models\Announcement;
use common\models\Currency;
use common\models\Review;
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
            <div class="modal-title">
                <?= Yii::t('frontend', 'Is {item} your client ?', ['item' => $model->creator->fullName]) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
        <div class="row">
            <div class="col-sm-12">
                <?php if (!Yii::$app->request->isAjax) : ?>
                    <?= Yii::t('frontend', 'Is {item} your client ?', ['item' => $model->creator->fullName]) ?>
                <?php endif; ?>
                <?= $form->field($model, 'confirmed')->radioList(
                    [
                        1 => Review::getBooleanLabels()[Review::YES],
                        2 => Review::getBooleanLabels()[Review::NO],
                    ]
                )->label(false)
                ?>
            </div>
        </div>
    </div>
    <?php if (Yii::$app->request->isAjax) : ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-default btn-primary" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
            <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success btn-primary']) ?>
        </div>
    <?php else : ?>
        <div class="form-actions floating">
            <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success btn-primary']) ?>
        </div>
    <?php endif; ?>
</div>
<?php ActiveForm::end(); ?>
