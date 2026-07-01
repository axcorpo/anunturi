<?php
/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\MarketingRecipientSearchForm */

use common\models\MarketingGroup;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

?>
<div class="row">
    <div class="col-sm-4">
        <?= $form->field($model, 'name')->textInput() ?>
    </div>
    <div class="col-sm-4">
        <?= $form->field($model, 'email')->textInput() ?>
    </div>
    <div class="col-sm-4">
        <?= $form->field($model, 'phone')->textInput() ?>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <?= $form->field($model, 'marketing_group_id')->widget(Select2::class, [
            'options' => [
                'multiple' => true,
            ],
            'data' => ArrayHelper::map(MarketingGroup::findAllMarketingGroups(), 'id', 'translation.name'),
            'maintainOrder' => false,
            'showToggleAll' => true,
            'pluginLoading' => false,
            'pluginOptions' => [
                'allowClear' => true,
                'placeholder' => Yii::t('common', 'Choose'),
            ],
        ]) ?>
    </div>
</div>
