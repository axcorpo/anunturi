<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Announcement */
/* @var $language common\models\Language */

use backend\widgets\ActiveForm;
use common\models\Action;
use common\models\Category;
use common\models\Country;
use kartik\depdrop\DepDrop;
use kartik\select2\Select2;
use tws\widgets\typeahead\Typeahead;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

?>

<?php $form = ActiveForm::begin([
	'id' => 'announcement-form',
	'method'=>'post',
	'action' => implode(['', Url::to(['/announcement/default/index'], true),
		'/create/',
		Yii::$app->request->get('code'),
		'?language=',
		Yii::$app->request->get('language'),
	]),
	'options' => [
		'novalidate' => true,
	],
	'validateOnType' => true,
]); ?>
<div class="form-body">
	<fieldset>
		<h3>
			<?= Yii::t('common', 'Create {0} in {1}', ['0' => mb_strtolower(Yii::t('common', 'Announcement'), 'UTF-8'), '1' => Yii::$app->name]); ?>
		</h3>
	</fieldset>
	<?= $form->field($model, "title[" . Yii::$app->request->get('language') . "]", ['enableClientValidation' => false])->textInput() ?>
	<?= $form->field($model, "content[" . Yii::$app->request->get('language') . "]", ['enableClientValidation' => false])->textarea() ?>
    <div class="form-fields">
	    <div class="row">
		    <div class="col-sm-12">
			    <?= $form->field($model, 'category')->widget(Select2::class, [
				    'options' => [
					    'multiple' => false,
				    ],
				    'data' => ArrayHelper::map(Category::find()->active(true)->deleted(false)->where(['leaf' => 1])->all(), 'id', 'treeName'),
				    'pluginOptions' => [
					    'allowClear' => true,
					    'placeholder' => Yii::t('common', 'Choose'),
					    'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
				    ],
			    ]) ?>
		    </div>
	    </div>
	    <div class="row">
		    <div class="col-sm-12">
			    <?= $form->field($model, 'action')->widget(DepDrop::class, [
				    'options' => [
					    'multiple' => false,
				    ],
				    'data' => ArrayHelper::map(Action::find()->where(['deleted' => Action::NO, 'status' => Action::STATUS_ACTIVE])->all(), 'id', function ($model) {
					    return Action::getMyTypes()[$model->type];
				    }),
				    'type' => DepDrop::TYPE_SELECT2,
				    'select2Options' => [
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
				    ],
				    'pluginOptions' => [
					    'url' => Url::to(['category-actions']),
					    'depends' => [Html::getInputId($model, 'category')],
					    'initialize' => true,
					    'loading' => true,
					    'loadingText' => Yii::t('common', 'Loading...'),
					    'placeholder' => Yii::t('common', 'Choose'),
				    ],
			    ]) ?>
		    </div>
	    </div>
	    <div class="row">
		    <div class="col-sm-4">
			    <?= $form->field($model, 'country')->widget(Select2::class, [
				    'options' => [
					    'multiple' => false,
				    ],
				    'data' => ArrayHelper::map(Country::find()
					    ->alias('c')
					    ->joinWith(['countryTranslations ct'])
					    ->andWhere([
						    'c.status' => Country::STATUS_ACTIVE,
						    'c.deleted' => Country::NO,
					    ])
					    ->orderBy(['c.name' => SORT_ASC])
					    ->indexBy('iso_alpha2')
					    ->all(), 'iso_alpha2', 'translation.name'),
				    'pluginLoading' => false,
				    'pluginOptions' => [
					    'allowClear' => true,
					    'placeholder' => Yii::t('common', 'Choose'),
				    ],
			    ]) ?>
		    </div>
		    <div class="col-sm-4">
			    <?= $form->field($model, 'county')->widget(Typeahead::class, [
				    'options' => [
					    'class' => 'form-control',
					    'type' => 'text',
					    'placeholder' => '',
					    'autocomplete' => 'off-county',
				    ],
				    'name' => 'county',
				    'clientOptions' => [
					    'minLength' => 2,
					    'maxItem' => 10,
					    'hint' => true,
					    'accent' => [
						    'from' => 'âăîşţșț',
						    'to' => 'aaistst',
					    ],
					    'cancelButton' => false,
					    'dynamic' => true,
					    'searchOnFocus' => true,
					    'backdrop' => [
						    'background-color' => '#ffffff',
						    'opacity' => '0.4',
					    ],
					    'source' => [
						    'results' => [
							    'display' => 'label',
							    'ajax' => new JsExpression('function (query) {
										return {
											"method": "POST",
											"url": "' . Url::to(['/site/search']) . '",
											"path": "results",
											"data": {
												"county": query,
												"country_code": $("#' . Html::getInputId($model, 'country') . '").val() 
											}
										};
									}'),
						    ],
					    ],
					    'callback' => [
						    'onClick' => new JsExpression('function (node, a, item, event) {
								if (item.url) {
									window.location.href = item.url;
								}
							}'),
					    ],
				    ],
			    ]) ?>
		    </div>
		    <div class="col-sm-4">
			    <?= $form->field($model, 'locality')->widget(Typeahead::class, [
				    'options' => [
					    'class' => 'form-control',
					    'type' => 'text',
					    'placeholder' => '',
					    'autocomplete' => 'off-locality',
				    ],
				    'name' => 'locality',
				    'clientOptions' => [
					    'minLength' => 2,
					    'maxItem' => 10,
					    'hint' => true,
					    'accent' => [
						    'from' => 'âăîşţșț',
						    'to' => 'aaistst',
					    ],
					    'cancelButton' => false,
					    'dynamic' => true,
					    'searchOnFocus' => true,
					    'backdrop' => [
						    'background-color' => '#ffffff',
						    'opacity' => '0.4',
					    ],
					    'source' => [
						    'results' => [
							    'display' => 'label',
							    'ajax' => new JsExpression('function (query) {
									return {
										"method": "POST",
										"url": "' . Url::to(['/site/search']) . '",
										"path": "results",
										"data": {
											"locality": query,
											"county": $("#' . Html::getInputId($model, 'county') . '").val()
										}
									};
								}'),
						    ],
					    ],
					    'callback' => [
						    'onClick' => new JsExpression('function (node, a, item, event) {
								if (item.url) {
									window.location.href = item.url;
								}
							}'),
					    ],
				    ],
			    ])->hint(Yii::t('backend', 'Locality shall take one of the values “Sector 1”, “Sector2”,  “Sector  3”,  “Sector 4”,  “Sector  5”  or “Sector 6” if county is “Bucharest”.')) ?>
		    </div>
	    </div>
    </div>
	<div class="form-actions floating">
		<?= Html::submitButton(Yii::t('common', 'Submit'), [
			'class' => 'btn btn-xlg btn-fab btn-success btn-primary',
		]) ?>
	</div>
</div>
<?php ActiveForm::end(); ?>

<?php
$parentDomain = Yii::$app->request->get("parent"); // URL of the parent
$iframeDomain = 'https://www.econstructii.ro';
$this->registerJs('
    window.addEventListener("message", function(event) {
        let currentDateTime = new Date();
		let formattedDateTime = new Date().getTime();
        if (event.origin === "' . $parentDomain . '") {
            var message = event.data;
            var title = message.title;
            var content = message.content;
            if (title && content) {
                document.getElementById("' . Html::getInputId($model, "title[" . Yii::$app->request->get('language') . "]") . '").value = title;
                document.getElementById("' . Html::getInputId($model, "content[" . Yii::$app->request->get('language') . "]") . '").value = content;
            }
        } else {
            return;
        }
    });
', \yii\web\View::POS_END);
?>

