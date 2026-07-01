<?php

/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Announcement */
/* @var $category common\models\Category */
/* @var $language common\models\Language */

use common\models\Field;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\JsExpression;
use yii\widgets\Pjax;

$category = Yii::$app->request->get('_pjax') ? $category : ($model->getCategories()->where(['leaf' => Field::YES])->one())->id;
$action = Yii::$app->request->get('_pjax') ? $action : null;

?>

<?php Pjax::begin(['id' => 'extra-fields']); ?>
	<?php if ($category): ?>
		<?php foreach(\common\models\Field::findAllByCategoryAction($category, $action) as $field): ?>
			<div class="row">
				<div class="col-sm-12">
					<?php switch($field->type): case Field::TYPE_TEXT: ?>
							<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->textInput()->label($field->translation->label) ?>
						<?php break; ?>
						<?php case Field::TYPE_NUMBER: ?>
							<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(TouchSpin::class, [
								'pluginOptions' => [
									'min' => $field->min ?: 0,
									'max' => $field->max ?: PHP_INT_MAX,
									'step' => $field->step ?: 1,
									'decimals' => $field->decimals ?: 0,
									'boostat' => 5,
									'maxboostedstep' => 10,
									'verticalbuttons' => true,
								],
							])->label($field->translation->label) ?>
						<?php break; ?>
						<?php case Field::TYPE_TEXTAREA: ?>
							<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->textarea([
								'rows' => 5,
							])->label($field->translation->label) ?>
						<?php break; ?>
						<?php case Field::TYPE_CHECKBOX: ?>
							<?php if ($field->options): ?>
								<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))
								->checkboxList(ArrayHelper::map($field->options, 'value', 'translation.label'), [
									'separator' => '<br>'
								])
								->label($field->translation->label) ?>
							<?php else: ?>
								<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->checkbox()->label($field->translation->label) ?>
							<?php endif; ?>
						<?php break; ?>
						<?php case Field::TYPE_RADIO: ?>
							<?php if ($field->options): ?>
								<?php
									$options = ArrayHelper::map($field->options, 'value', 'translation.label');
									if ($field->extra) {
										$options[Inflector::slug('extra_' . $field->name . '_' . $field->id, '_')] = Yii::t('frontend', 'Other');
									}
								?>
								<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))
								->radioList($options, [
										'separator' => '<br>',
									]
								)->label($field->translation->label) ?>
								<?php if ($field->extra): ?>
									<?= $form->field($model, Inflector::slug('extra_' . $field->name . '_' . $field->id, '_'))->textInput()->label($field->translation->label) ?>
								<?php endif; ?>
							<?php else: ?>
								<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->radio()->label($field->translation->label) ?>
							<?php endif; ?>
						<?php break; ?>
						<?php case Field::TYPE_SELECT: ?>
							<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(Select2::class, [
								'options' => [
									'multiple' => false,
								],
								'data' => ArrayHelper::map($field->options, 'value', 'translation.label'),
								'maintainOrder' => false,
								'showToggleAll' => false,
								'pluginLoading' => false,
								'pluginOptions' => [
									'allowClear' => true,
									'placeholder' => Yii::t('common', 'Choose'),
									'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
								],
							])->label($field->translation->label) ?>
						<?php break; ?>
						<?php case Field::TYPE_MULTIPLE_SELECT: ?>
						<?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(Select2::class, [
							'options' => [
								'multiple' => true,
							],
							'data' => ArrayHelper::map($field->options, 'value', 'translation.label'),
							'maintainOrder' => false,
							'showToggleAll' => true,
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => true,
								'placeholder' => Yii::t('common', 'Choose'),
								'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							],
						])->label($field->translation->label) ?>
						<?php break; ?>
                        <?php case Field::TYPE_DATE: ?>
                            <?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(DateTimePicker::class, [
                                'id' => Inflector::slug($field->name . '_' . $field->id, '_'),
                                'clientOptions' => [
                                    'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
                                    'ignoreReadonly' => true,
                                    'showTodayButton' => true,
                                    'showClear' => true,
                                    'showClose' => true,
                                    'allowInputToggle' => true,
                                    'useCurrent' => false,
                                ],
                            ])->label($field->translation->label) ?>
                        <?php break; ?>
                        <?php case Field::TYPE_DATETIME: ?>
                            <?= $form->field($model, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(DateTimePicker::class, [
                                'id' => Inflector::slug($field->name . '_' . $field->id, '_'),
                                'clientOptions' => [
                                    'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
                                    'ignoreReadonly' => true,
                                    'showTodayButton' => true,
                                    'showClear' => true,
                                    'showClose' => true,
                                    'allowInputToggle' => true,
                                    'useCurrent' => false,
                                ],
                            ])->label($field->translation->label)  ?>
                        <?php break; ?>
					<?php endswitch; ?>
				</div>
			</div>
		<?php endforeach; ?>
	<?php Yii::$app->session->remove('category'); ?>
	<?php Yii::$app->session->remove('action'); ?>
	<?php endif; ?>
<?php Pjax::end(); ?>
