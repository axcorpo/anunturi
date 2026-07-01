<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Field */

use common\models\Category;
use common\models\Field;
use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
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
						'data' => ArrayHelper::getColumn(Field::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'name')->textInput() ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'type')->widget(Select2::class, [
                        'options' => [
                            'data' => [
                                'toggle-visibility' => "#{$form->id}",
                                'toggle-visibility-val' => [
                                    Field::TYPE_NUMBER => '.tv-number',
                                ],
                            ],
                        ],
                        'data' => Field::getTypeLabels(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
			</div>
            <div class="row">
                <div class="col-sm-4">
                    <label></label>
                    <?= $form->field($model, 'extra')->checkbox() ?>
                </div>
                <div class="col-sm-4">
                    <label></label>
                    <?= $form->field($model, 'filter')->checkbox() ?>
                </div>
                <div class="col-sm-4">
                    <?= $form->field($model, 'filter_type')->widget(Select2::class, [
                        'data' => Field::getFilterTypeLabels(),
                        'pluginLoading' => false,
                        'pluginOptions' => [
                            'allowClear' => false,
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ]) ?>
                </div>
            </div>
                <div class="row">
                <div class="col-sm-3 tv-number <?= in_array($model->type, [Field::TYPE_NUMBER]) ? '' : 'hidden' ?>">
                    <?= $form->field($model, 'min')->widget(TouchSpin::class, [
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
                <div class="col-sm-3 tv-number <?= in_array($model->type, [Field::TYPE_NUMBER]) ? '' : 'hidden' ?>">
                    <?= $form->field($model, 'max')->widget(TouchSpin::class, [
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
                <div class="col-sm-3 tv-number <?= in_array($model->type, [Field::TYPE_NUMBER]) ? '' : 'hidden' ?>">
                    <?= $form->field($model, 'step')->widget(TouchSpin::class, [
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
				<div class="col-sm-3 tv-number <?= in_array($model->type, [Field::TYPE_NUMBER]) ? '' : 'hidden' ?>">
					<?= $form->field($model, 'decimals')->widget(TouchSpin::class, [
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
				'data' => ArrayHelper::map(Category::getChildren(null), 'id', 'treeName'),
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
			])->hint(Yii::t('common', 'Choose all categories and subcategories for which this field applies.')) ?>
		</div>

		<?php if (Yii::$app->request->isAjax): ?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
				<?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success']) ?>
			</div>
		<?php else: ?>
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
