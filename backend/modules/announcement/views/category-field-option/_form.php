<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Option */

use common\models\Category;
use common\models\Field;
use common\models\Option;
use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use kartik\typeahead\Typeahead;
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
					<?= $form->field($model, 'field_id')->widget(Select2::class, [
						'disabled' => !$model->isNewRecord,
						'data' => ArrayHelper::map(Field::findAllByCategoryId($model->category_id), 'id', 'translation.label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Choose'),
						],
						'pluginEvents' => [
							'change' => 'function(e) {
    						$("#' . Html::getInputId($model, 'value') . '").val("").prop("disabled", false).trigger("typeahead:autocomplete");
    						$("[name*=\"label\"]").val("").prop("disabled", false).trigger("change");
    					}',
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?php
					$suggestionTemplate = '<div class="suggestion-item">' .
						'<div class="item">{{value}} ({{label}})</div>' .
					'</div>';
					?>
					<?= $form->field($model, 'value')->widget(Typeahead::class, [
						'disabled' => !$model->isNewRecord,
						'options' => [
							'autocomplete' => 'off',
						],
						'pluginOptions' => [
							'minLength' => 2,
							'highlight' => true,
						],
						'pluginEvents' => [
    					'typeahead:selected' => 'function(e, suggestion) {
    						var $target = $(e.currentTarget),
									targetData = $target.data(),
    							languages = ' . json_encode(ArrayHelper::getColumn(\common\models\Language::findAllLanguages(), 'language_id')) . ';

    						targetData.selectedValue = suggestion.value;
    						$.each(languages, function (i, languageID) {
    							if (suggestion.translations[languageID]) {
										$("[name*=\"[label][" + languageID + "]\"]").val(suggestion.translations[languageID].label).prop("disabled", true);
    							}
    						});
    					}',
    					'typeahead:change' => 'function(e) {
    						var $target = $(e.currentTarget),
									targetData = $target.data();

								if (typeof targetData.selectedValue === "undefined") {
									targetData.selectedValue = false;
								}
    						if (targetData.selectedValue !== $target.val() && targetData.selectedValue !== false) {
    							$("[name*=\"label\"]").val("").prop("disabled", false);
    						}
    					}',
						],
						'dataset' => [
							[
								'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
								'display' => 'value',
								'remote' => [
									'url' => Url::to(['category-field-option/search']),
									'prepare' => new JsExpression('function (query, settings) {
										settings.type = "POST";
										settings.data = {
											query: query,
											attribute: "value",
											field_id: $("#' . Html::getInputId($model, 'field_id') . '").val(),
										};
										return settings;
									}'),
								],
								'templates' => [
									'suggestion' => new JsExpression("Handlebars.compile('{$suggestionTemplate}')"),
								],
							],
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'sort_order')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 1,
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
				'id' => 'tab-field',
				'items' => $i18nFields,
			]) ?>

			<?= $form->field($model, 'category')->widget(Select2::class, [
				'options' => [
					'multiple' => true,
				],
				'data' => ArrayHelper::map(Category::getChildren($model->category_id), 'id', 'treeName'),
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
				],
			])->hint(Yii::t('common', 'Choose all categories and subcategories for which this field option applies.')) ?>
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
