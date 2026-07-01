<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Review */

use common\helpers\UrlHelper;
use common\models\Announcement;
use common\models\Company;
use common\models\Currency;
use common\models\Review;
use backend\widgets\ActiveForm;
use common\models\Country;
use common\models\Subscriber;
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
            <div class="col-sm-4">
                <?= $form->field($model, 'status')->widget(Select2::class, [
                    'data' => ArrayHelper::getColumn(Review::getStatusLabels(), 'label'),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'score')->widget(TouchSpin::class, [
                    'pluginOptions' => [
                        'min' => 0,
                        'max' => 5,
                        'step' => 1,
                        'boostat' => 5,
                        'maxboostedstep' => 10,
                        'verticalbuttons' => true,
                    ],
                ]) ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'type')->widget(Select2::class, [
                    'disabled' => !$model->isNewRecord,
                    'options' => [
                        'data' => [
                            'toggle-visibility' => "#{$form->id}",
                            'toggle-visibility-val' => [
                                Review::REVIEW_TYPE_ANNOUNCEMENT => '.tv-announcement',
                                Review::REVIEW_TYPE_SUBSCRIBER => '.tv-subscriber',
                                Review::REVIEW_TYPE_COMPANY => '.tv-company',
                            ],
                        ],
                    ],
                    'data' => Review::getReviewTypes(),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 tv-announcement <?= in_array($model->type, [Review::REVIEW_TYPE_ANNOUNCEMENT]) ? '' : 'hidden' ?>">
                <?= $form->field($model, 'announcement_id')->widget(Select2::class, [
                    'options' => [
                        'multiple' => false,
                    ],
                    'data' => ArrayHelper::map(Announcement::findAll(['status' => Announcement::STATUS_ACTIVE, 'deleted' => Announcement::NO]), 'id', function ($model) {
                        return $model->translation->title;
                    }),
                    'maintainOrder' => false,
                    'showToggleAll' => false,
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-12 tv-subscriber <?= in_array($model->type, [Review::REVIEW_TYPE_SUBSCRIBER]) ? '' : 'hidden' ?>">
                <?= $form->field($model, 'subscriber_id')->widget(Select2::class, [
                    'options' => [
                        'multiple' => false,
                    ],
                    'data' => ArrayHelper::map(Subscriber::findAll(['status' => Subscriber::STATUS_ACTIVE, 'deleted' => Subscriber::NO]), 'id', function ($model) {
                        return $model->user->fullName;
                    }),
                    'maintainOrder' => false,
                    'showToggleAll' => false,
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Choose'),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-12 tv-company <?= in_array($model->type, [Review::REVIEW_TYPE_COMPANY]) ? '' : 'hidden' ?>">
                <?= $form->field($model, 'company_id')->widget(Select2::class, [
                    'options' => [
                        'multiple' => false,
                    ],
                    'data' => ArrayHelper::map(Company::findAll(['status' => Company::STATUS_ACTIVE, 'deleted' => Company::NO]), 'id', function ($model) {
                        return $model->name;
                    }),
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
            <div class="col-sm-12">
                <?php
                $i18nFields = [];
                foreach (\common\models\Language::findAllLanguages() as $language) {
                    $i18nFields[] = [
                        'label' => mb_strtoupper($language->language),
                        'content' => $this->render('_i18n-fields', [
                            'model' => $model,
                            'form' => $form,
                            'language' => $language,
                        ]),
                        'active' => $language->language_id === Yii::$app->language,
                    ];
                }
                ?>
                <?= Tabs::widget([
                    'items' => $i18nFields,
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
