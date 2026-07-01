<?php

/* @var $form common\widgets\ActiveForm */
/* @var $category common\models\Category */
/* @var $language common\models\Language */

use common\models\Action;
use common\models\Category;
use common\models\Field;
use common\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\JsExpression;

?>
<?php ActiveForm::begin([
    'options' => [
        'class' => 'form-inline'
    ],
]); ?>



<?php
$category = Category::find()
    ->alias('c')
    ->select('c.*')
    ->joinWith([
        'categoryTranslations ct' => function (ActiveQuery $query) {
            $query->andOnCondition(['ct.language_id' => Yii::$app->language]);
        },
    ])
    ->where([
        'c.status' => Category::STATUS_ACTIVE,
        'c.deleted' => Category::NO,
        'ct.slug' => Yii::$app->request->get('category'),
    ])
    ->limit(1)
    ->one();
?>

<?php if ($category): ?>
	<div class="sidebarInner sidebarCategory price-form " style="margin: 0 0 15px 0 !important;">
		<div class="panel panel-default" id="actionsAccordion">
			<div class="panel-heading"  style="background-color: #ffa019; padding: 0 0 15px 15px; color: #FFFF;" data-target="#actions" data-toggle="collapse" role="button">
				<?= Yii::t('frontend', 'Advanced filters') ?>  <span class="fa fa-filter pull-right" style="margin: 20px 15px 0 0;"></span>
			</div>
		</div>
        <div class="panel-collapse collapse" id="actions">
    <div class="form-fields">
		<div class="sidebarInner sidebarCategory price-form">
			<div class="panel panel-default">
				<div class="panel-heading">
					<?= Yii::t('label', 'Action') ?>
				</div>
			<?php
			$actions = ArrayHelper::getColumn($category->getCategoryHasActions()->select(['action_id'])->orderBy(['sort_order' => SORT_ASC])->all(), 'action_id');
			$query = Action::find()
				->where([
					'deleted' => Action::NO,
					'status' => Action::STATUS_ACTIVE
				]);
			if (!empty($actions)) {
				$query->andWhere([
					'id' => $actions,
				]);
			}
			?>
			<?= $form->field($modelFilterForm, 'action')->widget(Select2::class, [
				'options' => [
					'multiple' => true,
				],
				'data' => ArrayHelper::map($query->all(), 'id', function ($model) {
					return Action::getMyTypes()[$model->type];
				}),
				'maintainOrder' => false,
				'showToggleAll' => true,
				'pluginLoading' => false,
				'pluginOptions' => [
					'allowClear' => true,
					'placeholder' => Yii::t('common', 'Choose'),
					'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
				],
			])->label(false) ?>
			</div>
		</div>
		<div class="sidebarInner sidebarCategory price-form">
			<div class="panel panel-default">
				<div class="panel-heading">
					<?= Yii::t('label', 'Price') ?>
				</div>
				<?= $form->field($modelFilterForm,'min_price')->widget(TouchSpin::class, [
					'pluginOptions' => [
						'min' => 0,
						'max' => PHP_INT_MAX,
						'step' => 1,
						'decimals' => 0,
						'boostat' => 5,
						'maxboostedstep' => 10,
						'verticalbuttons' => true,
						'style' => 'border: 1px solid #e5e5e5;',
					],
				])->label(Yii::t('frontend', 'Min')) ?>
				<?= $form->field($modelFilterForm, 'max_price')->widget(TouchSpin::class, [
					'pluginOptions' => [
						'min' => 0,
						'max' => PHP_INT_MAX,
						'step' => 1,
						'decimals' => 0,
						'boostat' => 5,
						'maxboostedstep' => 10,
						'verticalbuttons' => true,
						'style' => 'border: 1px solid #e5e5e5;',
					],
				])->label(Yii::t('frontend', 'Max')) ?>
			</div>
		</div>
		<div class="sidebarInner sidebarCategory price-form">
			<div class="panel panel-default">
				<div class="panel-heading">
					<?= Yii::t('label', 'Currency') ?>
				</div>
				<?= $form->field($modelFilterForm, 'currency')->widget(Select2::class, [
					'options' => [
						'multiple' => true,
					],
					'data' => ArrayHelper::map(\common\models\Currency::findAllCurrencies(), 'iso_code', 'formattedName'),
					'maintainOrder' => false,
					'showToggleAll' => true,
					'pluginLoading' => false,
					'pluginOptions' => [
						'allowClear' => true,
						'placeholder' => Yii::t('common', 'Choose'),
						'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
					],
				])->label(false) ?>
			</div>
		</div>
        <?php foreach(\common\models\Field::findAllByCategoryId($category->id) as $field): ?>
			<?php if ($field->filter): ?>
				<?php switch($field->type): case Field::TYPE_TEXT: ?>
					<div class="sidebarInner sidebarCategory price-form">
						<div class="panel panel-default filter-expand-section">
								<div class="panel-heading">
									<a class="collapsed collpasible" data-toggle="collapse" href="#collapse<?= $field->id ?>" role="button" aria-expanded="false" aria-controls="collapse<?= $field->id ?>"><?= $field->translation->label ?></a>
								</div>
							<div class="collapse <?= $field->name ?>" id="collapse<?= $field->id ?>">
								<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))
									->checkboxList(ArrayHelper::map($field->fieldValues, 'value', 'value'), [
											'separator' => '<br>',
										])
									->label(false) ?>
							</div>
						</div>
					</div>
				<?php break; ?>
			<?php case Field::TYPE_NUMBER: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
							<div class="panel-heading">
								<?= $field->translation->label ?>
							</div>
						<?= $form->field($modelFilterForm, Inflector::slug('min_' . $field->name . '_' . $field->id, '_'))->widget(TouchSpin::class, [
							'pluginOptions' => [
								'min' => $field->min ?: 0,
								'max' => $field->max ?: PHP_INT_MAX,
								'step' => $field->step ?: 1,
								'decimals' => $field->decimals ?: 0,
								'boostat' => 5,
								'maxboostedstep' => 10,
								'verticalbuttons' => true,
								'style' => 'border: 1px solid #e5e5e5;',
							],
						])->label(Yii::t('frontend', 'Min')) ?>
						<?= $form->field($modelFilterForm, Inflector::slug('max_' . $field->name . '_' . $field->id, '_'))->widget(TouchSpin::class, [
							'pluginOptions' => [
								'min' => $field->min ?: 0,
								'max' => $field->max ?: PHP_INT_MAX,
								'step' => $field->step ?: 1,
								'decimals' => $field->decimals ?: 0,
								'boostat' => 5,
								'maxboostedstep' => 10,
								'verticalbuttons' => true,
								'style' => 'border: 1px solid #e5e5e5;',
							],
						])->label(Yii::t('frontend', 'Max')) ?>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_DATE: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
						<div class="panel-heading">
							<?= $field->translation->label ?>
						</div>
						<?= $form->field($modelFilterForm, Inflector::slug('from_' . $field->name . '_' . $field->id, '_'))->widget(DateTimePicker::class, [
							'id' => Inflector::slug('from_' . $field->name . '_' . $field->id, '_'),
							'clientOptions' => [
								'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
								'ignoreReadonly' => true,
								'showTodayButton' => true,
								'showClear' => true,
								'showClose' => true,
								'allowInputToggle' => true,
								'useCurrent' => false,
							],
						])->label(Yii::t('frontend', 'From')) ?>
						<?= $form->field($modelFilterForm, Inflector::slug('to_' . $field->name . '_' . $field->id, '_'))->widget(DateTimePicker::class, [
							'id' => Inflector::slug('to_' . $field->name . '_' . $field->id, '_'),
							'clientOptions' => [
								'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
								'ignoreReadonly' => true,
								'showTodayButton' => true,
								'showClear' => true,
								'showClose' => true,
								'allowInputToggle' => true,
								'useCurrent' => false,
							],
						])->label(Yii::t('frontend', 'To')) ?>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_DATETIME: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
						<div class="panel-heading">
							<?= $field->translation->label ?>
						</div>
						<?= $form->field($modelFilterForm, Inflector::slug('from_' . $field->name . '_' . $field->id, '_'))->widget(DateTimePicker::class, [
							'id' => Inflector::slug('from_' . $field->name . '_' . $field->id, '_'),
							'clientOptions' => [
								'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
								'ignoreReadonly' => true,
								'showTodayButton' => true,
								'showClear' => true,
								'showClose' => true,
								'allowInputToggle' => true,
								'useCurrent' => false,
							],
						])->label(Yii::t('frontend', 'From')) ?>
						<?= $form->field($modelFilterForm, Inflector::slug('to_' . $field->name . '_' . $field->id, '_'))->widget(DateTimePicker::class, [
							'id' => Inflector::slug('to_' . $field->name . '_' . $field->id, '_'),
							'clientOptions' => [
								'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
								'ignoreReadonly' => true,
								'showTodayButton' => true,
								'showClear' => true,
								'showClose' => true,
								'allowInputToggle' => true,
								'useCurrent' => false,
							],
						])->label(Yii::t('frontend', 'To')) ?>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_TEXTAREA: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default filter-expand-section">
						<div class="panel-heading">
							<a class="collapsed collpasible" data-toggle="collapse" href="#collapse<?= $field->id ?>" role="button" aria-expanded="false" aria-controls="collapse<?= $field->id ?>"><?= $field->translation->label ?></a>
						</div>
						<div class="collapse <?= $field->name ?>" id="collapse<?= $field->id ?>">
							<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))
								->checkboxList(ArrayHelper::map($field->options, 'id', 'translation.label'), [
										'separator' => '<br>',
									])
								->label(false) ?>
						</div>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_CHECKBOX: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
						<div class="panel-heading">
							<?= $field->translation->label ?>
						</div>
						<?php if ($field->options): ?>
							<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))
								->checkboxList(ArrayHelper::map($field->options, 'id', 'translation.label'), [
										'separator' => '<br>',
									])
								->label(false) ?>
						<?php else: ?>
							<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))->checkbox([
								'template' => function($index, $label, $name, $checked, $value) {
										return "<label class='containercb'>{$label}
										<input type='checkbox' {$checked} name='{$name}' value='{$value}'>
										<span class='checkmark'></span></label>";

									}
							])->label($field->translation->label) ?>
						<?php endif; ?>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_RADIO: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
						<div class="panel-heading">
							<?= $field->translation->label ?>
						</div>
						<?php if ($field->options): ?>
							<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))
							->radioList(ArrayHelper::map($field->options, 'id', 'translation.label'), [
									'separator' => '<br>',
								]
							)->label(false) ?>
						<?php else: ?>
							<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))->radio()->label($field->translation->label) ?>
						<?php endif; ?>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_SELECT: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
						<div class="panel-heading">
							<?= $field->translation->label ?>
						</div>
						<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(Select2::class, [
							'options' => [
								'multiple' => false,
							],
							'data' => ArrayHelper::map($field->options, 'id', 'translation.label'),
							'maintainOrder' => false,
							'showToggleAll' => false,
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => true,
								'placeholder' => Yii::t('common', 'Choose'),
								'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							],
						])->label(false) ?>
					</div>
				</div>
			<?php break; ?>
			<?php case Field::TYPE_MULTIPLE_SELECT: ?>
				<div class="sidebarInner sidebarCategory price-form">
					<div class="panel panel-default">
						<div class="panel-heading">
							<?= $field->translation->label ?>
						</div>
						<?= $form->field($modelFilterForm, Inflector::slug($field->name . '_' . $field->id, '_'))->widget(Select2::class, [
							'options' => [
								'multiple' => true,
							],
							'data' => ArrayHelper::map($field->options, 'id', 'translation.label'),
							'maintainOrder' => false,
							'showToggleAll' => true,
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => true,
								'placeholder' => Yii::t('common', 'Choose'),
								'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							],
						])->label(false) ?>
					</div>
				</div>
			<?php break; ?>
			<?php endswitch; ?>
		<?php endif; ?>
        <?php endforeach; ?>
        <?= Html::submitButton('<span class="fa fa-filter" aria-hidden="true"></span>', ['class' => 'btn btn-primary', 'title' => Yii::t('frontend', 'Filter'), 'data' => ['toggle' => 'tooltip'], 'style' => 'width: 100%;']) ?>
    </div>
        </div>
    </div>

<?php endif; ?>
<?php ActiveForm::end() ?>
