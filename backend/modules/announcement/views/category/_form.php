<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Category */

use common\models\Action;
use common\models\Category;
use backend\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
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
						'data' => ArrayHelper::getColumn(Category::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'icon')->widget(Select2::class, [
						'data' => \common\helpers\FontIcon::getDropdownIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'sort_order')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 0,
							'max' => PHP_INT_MAX,
							'step' => 1,
							'decimals' => 0,
							'boostat' => 5,
							'maxboostedstep' => 10,
							'verticalbuttons' => true,
						],
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'parent_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(Category::findAllAvailableCategories($model->id), 'id', 'treeName'),
						'maintainOrder' => true,
						'showToggleAll' => false,
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
						],
					]) ?>
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
					//'required' => $model->isNewRecord,
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
					'initialPreview' => $model->imageUrl ?: false,
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

		<div class="row">
			<div class="col-sm-6">
				<?= $form->field($model, 'action')->widget(Select2::class, [
					'options' => [
						'multiple' => true,
					],
					'data' => ArrayHelper::map(Action::find()->where(['deleted' => Action::NO, 'status' => Action::STATUS_ACTIVE])->all(), 'id', function ($model) {
						return Action::getTypes()[$model->type];
					}),
					'maintainOrder' => true,
					'showToggleAll' => true,
					'toggleAllSettings' => [
						'selectLabel' => '<span class="glyphicon glyphicon-unchecked"></span> ' . Yii::t('common', 'Select All'),
						'unselectLabel' => '<span class="glyphicon glyphicon-check"></span> ' . Yii::t('common', 'Unselect All'),
					],
					'pluginLoading' => false,
					'pluginOptions' => [
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Choose'),
					],
				]) ?>
			</div>
			<div class="col-sm-6">
				<?= $form->field($model, 'subcategory')->widget(Select2::class, [
					'options' => [
						'multiple' => true,
					],
					'data' => ArrayHelper::map(Category::getChildren($model->id), 'id', 'treeName'),
					'maintainOrder' => false,
					'showToggleAll' => true,
					'toggleAllSettings' => [
						'selectLabel' => '<span class="glyphicon glyphicon-unchecked"></span> ' . Yii::t('common', 'Select All'),
						'unselectLabel' => '<span class="glyphicon glyphicon-check"></span> ' . Yii::t('common', 'Unselect All'),
					],
					'pluginLoading' => false,
					'pluginOptions' => [
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Choose'),
						'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
					],
				])->hint(Yii::t('common', 'Choose all categories and subcategories for which actions applies.')) ?>
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
