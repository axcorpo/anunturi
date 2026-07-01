<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Bid */

use common\helpers\UrlHelper;
use common\models\Broker;
use common\models\Auction;
use common\models\Commercial;
use common\models\Currency;
use common\models\Bid;
use backend\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\number\NumberControl;
use kartik\select2\Select2;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

?>

<?php $form = ActiveForm::begin([
    'id' => 'ad-form',
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
					'data' => ArrayHelper::getColumn(Commercial::getStatusLabels(), 'label'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'placeholder' => Yii::t('common', 'Choose'),
					],
				]) ?>
			</div>
			<div class="col-sm-4">
				<?= $form->field($model, 'bid_id')->widget(Select2::class, [
					'options' => [
						'multiple' => false,
					],
					'data' => ArrayHelper::map(Bid::find()->active()->deleted(false)->all(), 'id', function ($model) {
						return $model->bidName;
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
			<div class="col-sm-4">
				<?= $form->field($model, 'url')->textInput() ?>
			</div>
		</div>
		<?= $form->field($model, 'imageFile', [
			'options' => [
				'class' => 'form-group' . ($model->isNewRecord ? ' required' : ''),
			],
		])->widget(FileInput::class, [
			'options' => [
				'accept' => 'image/*',
				'data' => [
					'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				],
			],
			'resizeImages' => false,
			'sortThumbs' => false,
			'purifyHtml' => false,
			'pluginOptions' => [
                //'theme' => 'fa',
				'required' => $model->isNewRecord,
				'msgFileRequired' => Yii::t('yii', 'Please upload a file.'),
				'allowedFileExtensions' => Yii::$app->params['image.extensions'],
				'maxFileSize' => 20 * 1024 * 1024,
				'dropZoneEnabled' => false,
				'showClose' => false,
				'showUpload' => false,
				'showCaption' => true,
				'showRemove' => true,
				'showPreview' => true,
				'fileActionSettings' => [
					'showDownload' => true,
					'showRemove' => true,
					'showUpload' => false,
					'showZoom' => true,
					'showDrag' => false,
				],
				'initialPreview' => $model->image ? $model->imageUrl : false,
				'initialPreviewConfig' => [
					[
						'caption' => $model->image,
						'downloadUrl' => $model->imageUrl,
					],
				],
				'initialPreviewAsData' => true,
				'initialPreviewShowDelete' => true,
				'overwriteInitial' => true,
				'deleteUrl' => Url::to(['delete-file', 'id' => $model->id]),
				'deleteExtraData' => [
					'attribute' => 'image',
				],
			],
		]) ?>

	</div>

    <?php if (Yii::$app->request->isAjax) : ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
            <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-primary']) ?>
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

