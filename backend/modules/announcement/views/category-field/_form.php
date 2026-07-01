<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Field */

use common\models\Action;
use common\models\Category;
use common\models\CategoryField;
use common\models\Field;
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
                <div class="col-sm-6">
                    <?= $form->field($model, 'status')->widget(Select2::class, [
                        'data' => ArrayHelper::getColumn(CategoryField::getStatusLabels(), 'label'),
                        'pluginLoading' => false,
                        'pluginOptions' => [
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ]) ?>
                </div>
				<div class="col-sm-6">
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
            <div class="row">
                <div class="col-sm-12">
                    <?= $form->field($model, 'field_id')->widget(Select2::class, [
                        'options' => [
                            'multiple' => false,
                        ],
                        'data' => ArrayHelper::map(Field::findAll(['status' => Field::STATUS_ACTIVE, 'deleted' => Field::NO]), 'id', 'translation.label'),
                        'pluginLoading' => false,
                        'pluginOptions' => [
                            'allowClear' => false,
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <?= $form->field($model, 'action_id')->widget(Select2::class, [
                        'options' => [
                            'multiple' => false,
                        ],
                        'data' => ArrayHelper::map(Action::queryActionByCategoryId(Yii::$app->request->get('category_id')), 'id', 'name'),
                        'pluginLoading' => false,
                        'pluginOptions' => [
                            'allowClear' => false,
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ]) ?>
                </div>
            </div>
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
					'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
				],
			])->hint(Yii::t('common', 'Choose all categories and subcategories for which this field applies.')) ?>
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
