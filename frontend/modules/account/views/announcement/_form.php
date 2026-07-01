<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Announcement */

use common\helpers\Inflector;
use common\helpers\UrlHelper;
use common\models\Action;
use common\models\Announcement;
use common\models\Category;
use backend\widgets\ActiveForm;
use common\models\CategoryTranslation;
use common\models\Company;
use common\models\Country;
use common\models\Field;
use common\models\Subscription;
use common\widgets\fullcalendar\FullCalendar;
use common\widgets\fullcalendar\FullCalendarAsset;
use common\widgets\fullcalendar\MomentAsset;
use kartik\depdrop\DepDrop;
use kartik\file\FileInput;
use kartik\number\NumberControl;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\bootstrap\Tabs;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Pjax;

$category = Yii::$app->session->get('category') ? Category::findOne(['id' => Yii::$app->session->get('category')]) : ($model->getCategories()->where(['leaf' => Category::YES])->one());
$action = Yii::$app->session->get('action') ? Yii::$app->session->get('action') : $model->actions[0]->id;
$model->category = $category->id ?: null;
$model->action = $action ?: null;
?>
<?php
$this->registerJs('
		$(document).on("change", "#' . Html::getInputId($model, 'category') . '", function (e) {
			var $form = $("#announcement-form");
			$.ajax({
				url: $form.attr("action"),
				type: "POST",
				data :  {
					category: $(\'input[name="' . Html::getInputName($model, 'category') . '"]:checked\').val(),
				},
				success: function(response) {
					 $.pjax.reload({url: $form.attr("action"), container: "#announcement-container", timeout: false});
				},
				error: function() {
				}
			});
		});
		$(document).on("change", "#' . Html::getInputId($model, 'action') . '", function (e) {
			var $form = $("#announcement-form");
			$.ajax({
				url: $form.attr("action"),
				type: "POST",
				data :  {
					category: $(\'input[name="' . Html::getInputName($model, 'category') . '"]:checked\').val(),
					action: $( "#' . Html::getInputId($model, 'action') . '").val(),
				},
				success: function(response) {
					 $.pjax.reload({url: $form.attr("action"), container: "#announcement-container", timeout: false});
				},
				error: function() {
				}
			});
		});
		$(document).on("click", ".change-category", function (e) {
			var $form = $("#announcement-form");
			$.ajax({
				url: $form.attr("action"),
				type: "POST",
				data :  {
					category: 999,
				},
				success: function(response) {
					 $.pjax.reload({url: $form.attr("action"), container: "#announcement-container", timeout: false});
				},
				error: function() {
				}
			});
		});
		$("#announcement-container").on("pjax:start", function() {
		 	$(body).overlay();
	 	});
	 	$("#announcement-container").on("pjax:end", function() {
		 	$(body).overlay("remove");
	 	});
	');
?>

<?php Pjax::begin(['id' => 'announcement-container', 'timeout' => false]); ?>
<?php $form = ActiveForm::begin([
    'id' => 'announcement-form',
    'options' => [
        'novalidate' => true,
        'data-pjax'=> true,
    ],
    'validateOnType' => true,
]); ?>
<div class="form-body">
    <div class="form-fields">
        <div class="row">
            <div class="col-sm-12">
                <?php $categories = Category::find()
                    ->alias('c')
                    ->joinWith([
                        'parent p' => function (ActiveQuery $query) {
                            $query->andOnCondition([
                                'pt.language_id' => Yii::$app->language,
                                'pt.deleted' => Category::NO,
                            ]);
                        },
                        'parent p',
                        'categoryTranslations ct' => function (ActiveQuery $query) {
                            $query->andOnCondition([
                                'ct.language_id' => Yii::$app->language,
                                'ct.deleted' => Category::NO,
                            ]);
                        },
                    ])
                    ->where([
                        'c.status' => Category::STATUS_ACTIVE,
                        'c.deleted' => Category::NO,
                    ])
                    ->orderBy(new Expression('[[p.sort_order]] IS NULL'))
                    ->orderBy(new Expression('[[c.sort_order]] IS NULL'))
                    ->addOrderBy(['p.sort_order' => SORT_ASC])
                    ->addOrderBy(['c.sort_order' => SORT_ASC])
                    ->addOrderBy(['ct.name' => SORT_ASC]);
                if (empty($category)) {
                    $categories->andWhere([
                        'c.parent_id' => null,
                    ]);
                } else {
                    if ($category->leaf) {
                        $categories->andWhere([
                            'c.id' => $category->id,
                        ]);
                    } else {
                        $categories->andWhere([
                            'c.parent_id' => $category->id,
                        ]);
                    }

                }
                $data = ArrayHelper::map($categories->all(), 'id', function ($model) {
                    return $model->translation->name;
                });
                ?>
                <?= $form->field($model, 'category')->radioList($data,
                    [
                        'class' => 'radiobtn',
                        'disabled' => $category->leaf ? 'disabled' : '',
                    ]
                )->label(Yii::t('label', 'Category') . ($category ? ' (' . $category->treename . ')' : '') . ($category->id ? ' ' . Html::button('<span class="fa fa-pencil"></span> ' . Yii::t('common', 'Update'), [
                            'class' => 'btn btn-primary change-category',
                            'style' => 'padding: 5px 5px 5px 5px;',
                            'title' => Yii::t('common', 'Update'),
                            'data' => [
                                'toggle' => 'tooltip',
                            ],
                        ]) : '')) ?>
            </div>
        </div>
        <?php if ($category->leaf): ?>
            <div class="row">
                <div class="col-sm-12">
                    <?php
                    $actions = Action::find()
                        ->alias('a')
                        ->select([
                            'a.*',
                        ])
                        ->joinWith([
                            'categories c',
                        ], false)
                        ->where([
                            'a.status' => Action::STATUS_ACTIVE,
                            'a.deleted' => Action::NO,
                            'c.id' => $category->id,
                        ])
                    ?>
                    <?= $form->field($model, 'action')->widget(Select2::class, [
                        'options' => [
                            'multiple' => false,
                        ],
                        'data' => ArrayHelper::map($actions->all(), 'id', function ($model) {
                            return Action::getMyTypes()[$model->type];
                        }),
                        'pluginOptions' => [
                            'allowClear' => true,
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ]) ?>
                </div>
            </div>
            <?php if ($action): ?>
                <?php if ($action == Action::TYPE_RENT): ?>
				<div class="row">
					<div class="col-sm-2">
					</div>
					<div class="col-sm-1 text-right">
						<?= Html::a('<span class="fa fa-calendar-times-o fa-lg"></span>', ['/account/unavailability/create', 'announcement_id' => $model->id], [
							'class' => 'btn btn-xs btn-primary',
							'style' => 'padding: 15px; margin: 0 0 15px 0;',
							'title' => Yii::t('common', 'Add unavailability period'),
							'data' => [
								'toggle' => 'tooltip',
								'popup-action' => '',
								'popup-css-class' => 'modal-unavailability',
							],
						]); ?>
					</div>
					<div class="col-sm-7">
						<?= FullCalendar::widget([
							'id' => 'announcement-calendar',
							'clientOptions' => [
								'header' => [
									'left' => 'prev,next today',
									'center' => 'title',
									'right' => 'month,agendaWeek,agendaDay',
								],
								'navLinks' => true,
								'weekNumbers' => true,
								'editable' => false,
								'fixedWeekCount' => false,
								'showNonCurrentDates' => false,
								'eventLimit' => true,
								'events' => [
									'method' => 'GET',
									'url' => Url::to(['/account/unavailability/index', 'announcement_id' => $model->id]),
									'startParam' => 'start_date',
									'endParam' => 'end_date',
								],
								'lazyFetching' => true,
								'loading' => new JsExpression('function (isLoading) {
									$(this).overlay(isLoading ? "" : "remove");
								}'),
								'eventRender' => new JsExpression('function (event, $element, e) {
									$element.find(".fc-title").html(event.title);
								}'),
								'dayClick' => new JsExpression('function (date, jsEvent, view) {
									alert("Here");
								}'),
							],
						]) ?>
					</div>
					<div class="col-sm-2">
					</div>
				</div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-sm-6">
						<?php $currencyField = $form->field($model, 'currency', [
							'template' => '{input}',
						])->widget(Select2::class, [
							'data' => ArrayHelper::map(\common\models\Currency::findAllCurrencies(), 'iso_code', 'formattedName'),
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => false,
								'placeholder' => Yii::t('common', 'Currency'),
							],
						]) ?>
						<?= $form->field($model, 'price', [
							'options' => [
								'class' => 'form-group',
							],
							'template' => '{label}<div class="row row-no-gutters row-custom"><div class="col-xs-6">{input}</div><div class="col-xs-6">' . $currencyField . '</div></div>{error}{hint}',
						])->widget(TouchSpin::class, [
								'pluginOptions' => [
									'min' => 0,
									'max' => PHP_INT_MAX,
									'step' => 0.1,
									'decimals' => 2,
									'boostat' => 5,
									'maxboostedstep' => 10,
									'verticalbuttons' => true,
							],
						]) ?>
                    </div>
                    <div class="col-sm-6">
						<?php $uomField = $form->field($model, 'uom', [
							'template' => '{input}',
						])->textInput([
							'placeholder' => mb_strtolower(Yii::t('common', 'Unit Of Measure'), 'UTF-8')
						])
						?>
						<?= $form->field($model, 'quantity', [
							'options' => [
								'class' => 'form-group',
							],
							'template' => '{label}<div class="row row-no-gutters row-custom"><div class="col-xs-6">{input}</div><div class="col-xs-6">' . $uomField . '</div></div>{error}{hint}',
						])->widget(TouchSpin::class, [
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
                'items' => $i18nFields,
            ]) ?>

                <?php foreach(\common\models\Field::findAllByCategoryAction($category->id, $action) as $field): ?>
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
        <div class="row">
            <div class="col-sm-12">
            <?= $form->field($model, 'imageFile', [
                'options' => [
                    'class' => 'form-group',
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
                    'required' => false,
                    'msgFileRequired' => Yii::t('yii', 'Please upload a file.'),
                    'allowedFileExtensions' => Yii::$app->params['image.extensions'],
                    'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
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
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?= $form->field($model, 'picture[]')->widget(FileInput::class, [
                'options' => [
                    'accept' => 'image/*',
                    'multiple' => true,
                    'data' => [
                        'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                    ],
                ],
                'resizeImages' => false,
                'sortThumbs' => false,
                'purifyHtml' => false,
                'pluginOptions' => [
                    //'theme' => 'fa',
                    'allowedFileExtensions' => Yii::$app->params['image.extensions'],
                    'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
                    'dropZoneEnabled' => true,
                    'showClose' => false,
                    'showUpload' => false,
                    'showCaption' => true,
                    'showRemove' => true,
                    'showPreview' => true,
                    'hideThumbnailContent' => false,
                    'fileActionSettings' => [
                        'showDownload' => true,
                        'showRemove' => true,
                        'showUpload' => false,
                        'showZoom' => true,
                        'showDrag' => false,
                    ],
                    'initialPreview' => ArrayHelper::getColumn($model->pictureFiles, 'downloadUrl'),
                    'initialPreviewConfig' => $model->pictureFiles,
                    'initialPreviewAsData' => true,
                    'initialPreviewShowDelete' => true,
                    'overwriteInitial' => false,
                    'deleteUrl' => Url::to(['delete-picture', 'id' => $model->id]),
                ],
            ]) ?>
            </div>
        </div>
                <div class="row">
                    <div class="col-sm-12">
                        <?php
                        $companies = Yii::$app->user->identity->getCompanies()->active()->deleted(false)->all();
                        $createBtn = Html::a('<span class="fa fa-plus" style="position:absolute; margin-top: -5px; margin-left: -5px;"></span>', ['/account/company/create'], [
                            'class' => 'btn btn-xs btn-primary',
                            'style' => 'max-height: 25px;',
                            'title' => Yii::t('common', 'Create'),
                            'data' => [
                                'toggle' => 'tooltip',
                                'popup-action' => '',
                                'popup-done' => ['reload' => '#' . Html::getInputId($model, 'company_id')],
                            ],
                        ]);
                        ?>
                        <?= $form->field($model, 'company_id')->widget(Select2::class, [
                            'options' => [
                                'options' => ArrayHelper::map($companies, 'id', function (Company $company) {
                                    return [
                                        'data' => [
                                            'email' => $company->email,
                                        ],
                                    ];
                                }),
                            ],
                            'data' => ArrayHelper::map($companies, 'id', 'name'),
                            'addon' => [
                                'append' => [
                                    'content' => $createBtn,
                                    'asButton' => true,
                                ],
                            ],
                            'pluginLoading' => false,
                            'pluginOptions' => [
                                'allowClear' => true,
                                'placeholder' => Yii::t('common', 'Choose'),
                            ],
                        ]) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-sm-12">
                                <?= $form->field($model, 'address')->textInput() ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'latitude')->textInput() ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'longitude')->textInput() ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'locality')->textInput() ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'zip_code')->textInput() ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'county')->textInput() ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'country')->widget(Select2::class, [
                                    'data' => ArrayHelper::map(Country::findAllCountries(), 'iso_alpha2', function ($model) {
                                        return $model->translation->name ?: $model->name;
                                    }),
                                    'pluginLoading' => false,
                                    'pluginOptions' => [
                                        'allowClear' => true,
                                        'placeholder' => Yii::t('common', 'Choose'),
                                    ],
                                ]) ?>
                            </div>
                        </div>
                        <?= Html::checkbox('', true, [
                            'id' => 'update-address-checkbox',
                            'value' => 1,
                            'label' => Yii::t('backend', 'Update address while dragging the map marker'),
                            'labelOptions' => [
                                'class' => 'mt-checkbox mt-checkbox-outline',
                            ],
                        ]) ?>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-info-icon fa-info-circle text-muted margin-bottom-10"><?= Yii::t('backend', 'Drag the map marker to the desired position.') ?></div>
                        <div id="map-container" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <?= $form->field($model, 'imageSourceFile', [
                            'options' => [
                                'class' => 'form-group',
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
                                'required' => false,
                                'msgFileRequired' => Yii::t('yii', 'Please upload a file.'),
                                'allowedFileExtensions' => Yii::$app->params['image.extensions'],
                                'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
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
                                'initialPreview' => $model->source_image ? $model->sourceImageUrl : false,
                                'initialPreviewConfig' => [
                                    [
                                        'caption' => $model->source_image,
                                        'downloadUrl' => $model->sourceImageUrl,
                                    ],
                                ],
                                'initialPreviewAsData' => true,
                                'initialPreviewShowDelete' => true,
                                'overwriteInitial' => true,
                                'deleteUrl' => Url::to(['delete-source-file', 'id' => $model->id]),
                                'deleteExtraData' => [
                                    'attribute' => 'source_image',
                                ],
                            ],
                        ]) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'source')->textInput() ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'source_url')->textInput() ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div style="margin-top: 20px;">
        <button type="submit" class="hidden"></button>
        <input class="btn btn-primary" type="submit" name="submit" value="<?= Yii::t('common', 'Save') ?>"/>
    </div>
</div>
<?php
Yii::$app->session->remove('category');
Yii::$app->session->remove('action');
?>
<?php ActiveForm::end(); ?>
<script async defer src="//maps.googleapis.com/maps/api/js?key=<?= Yii::$app->settings->get('googleMapKey') ?>&language=<?= Yii::$app->language ?>&callback=initMap"></script>
<script type="text/javascript">
    var defaultLat = "<?= $model->latitude ?: 45.7519379 ?>",
        defaultLng = "<?= $model->longitude ?: 21.2259002 ?>",
        latitudeControl = document.getElementById("<?= Html::getInputId($model, 'latitude') ?>"),
        longitudeControl = document.getElementById("<?= Html::getInputId($model, 'longitude') ?>"),
        localityControl = document.getElementById("<?= Html::getInputId($model, 'locality') ?>"),
        countyControl = document.getElementById("<?= Html::getInputId($model, 'county') ?>"),
        countryControl = document.getElementById("<?= Html::getInputId($model, 'country') ?>"),
        zipCodeControl = document.getElementById("<?= Html::getInputId($model, 'zip_code') ?>"),
        addressControl = document.getElementById("<?= Html::getInputId($model, 'address') ?>");
    updateAddressCheckbox = document.getElementById("update-address-checkbox");

    var geocode = function (address) {
        var geocoder = new google.maps.Geocoder();

        geocoder.geocode({address: address}, function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                var addressComponents = [],
                    geometry = [],
                    latitude = '',
                    longitude = '',
                    locality = '',
                    county = '',
                    country = '',
                    zipCode = '',
                    streetName = '',
                    streetNumber = '';

                for (var i = 0, len = results.length; i < len; i++) {
                    if (results[i].address_components) {
                        addressComponents = results[i].address_components;
                        break;
                    }
                }
                for (var i = 0, len = results.length; i < len; i++) {
                    if (results[i].geometry) {
                        geometry = results[i].geometry;
                        break;
                    }
                }
                for (i = 0, len = addressComponents.length; i < len; i++) {
                    if (addressComponents[i].types[0] === 'locality') {
                        locality = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'administrative_area_level_1') {
                        county = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'country') {
                        country = addressComponents[i].short_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'postal_code') {
                        zipCode = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'route') {
                        streetName = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'street_number') {
                        streetNumber = addressComponents[i].long_name.trim();
                    }
                }

                latitude = geometry.location.lat();
                longitude = geometry.location.lng();

                localityControl.value = locality;
                countyControl.value = county;
                countryControl.value = country;
                zipCodeControl.value = zipCode;
                // addressControl.value = streetName;
                // if (streetName && streetNumber) {
                // 	addressControl.value += (', ' + streetNumber);
                // }
                if (countryControl.className.indexOf('select2-hidden-accessible') !== -1) {
                    countryControl.dispatchEvent(new Event('change'));
                }
                latitudeControl.value =  latitude;
                longitudeControl.value =  longitude;

                var map = new google.maps.Map(document.getElementById('map-container'), {
                        zoom: 14,
                        center: new google.maps.LatLng(latitude, longitude),
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    }),
                    mapMarker = new google.maps.Marker({
                        position: new google.maps.LatLng(latitude, longitude),
                        draggable: true
                    });

                google.maps.event.addListener(mapMarker, 'dragend', function (e) {
                    if (updateAddressCheckbox.checked) {
                        reverseGeocode(e.latLng);
                    }
                    latitudeControl.value = e.latLng.lat().toFixed(8);
                    longitudeControl.value = e.latLng.lng().toFixed(8);
                });

                mapMarker.setMap(map);
                map.setCenter(mapMarker.position);
            }
        });
    };

    var reverseGeocode = function (latLng) {
        var geocoder = new google.maps.Geocoder();

        geocoder.geocode({location: latLng}, function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                var addressComponents = [],
                    locality = '',
                    county = '',
                    country = '',
                    zipCode = '',
                    streetName = '',
                    streetNumber = '';

                for (var i = 0, len = results.length; i < len; i++) {
                    if (results[i].address_components) {
                        addressComponents = results[i].address_components;
                        break;
                    }
                }
                for (i = 0, len = addressComponents.length; i < len; i++) {
                    if (addressComponents[i].types[0] === 'locality') {
                        locality = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'administrative_area_level_1') {
                        county = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'country') {
                        country = addressComponents[i].short_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'postal_code') {
                        zipCode = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'route') {
                        streetName = addressComponents[i].long_name.trim();
                    }
                    if (addressComponents[i].types[0] === 'street_number') {
                        streetNumber = addressComponents[i].long_name.trim();
                    }
                }

                localityControl.value = locality;
                countyControl.value = county;
                countryControl.value = country;
                zipCodeControl.value = zipCode;
                addressControl.value = streetName;
                if (streetName && streetNumber) {
                    addressControl.value += (', ' + streetNumber);
                }
                if (countryControl.className.indexOf('select2-hidden-accessible') !== -1) {
                    countryControl.dispatchEvent(new Event('change'));
                }
            }
        });
    };

    var initMap = function () {
        if (document.getElementById('map-container')) {
            var map = new google.maps.Map(document.getElementById('map-container'), {
                    zoom: 14,
                    center: new google.maps.LatLng(defaultLat, defaultLng),
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                }),
                mapMarker = new google.maps.Marker({
                    position: new google.maps.LatLng(defaultLat, defaultLng),
                    draggable: true
                });

            google.maps.event.addListener(mapMarker, 'dragend', function (e) {
                if (updateAddressCheckbox.checked) {
                    reverseGeocode(e.latLng);
                }
                latitudeControl.value = e.latLng.lat().toFixed(8);
                longitudeControl.value = e.latLng.lng().toFixed(8);
            });

            addressControl.addEventListener('change', function (e) {
                geocode(e.target.valueOf().value);
            });

            addressControl.addEventListener('keyup', function (e) {
                var timeout = 0;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    geocode(e.target.valueOf().value);
                }, 500);
            });

            mapMarker.setMap(map);
            map.setCenter(mapMarker.position);
        }
    };
    if (typeof window.google !== 'undefined') {
        initMap();
    }
</script>
<?php Pjax::end(); ?>
<?php $this->registerJs('
	$(document).on("done.popupAction", function (e, $element, $modal, response) {
		if ($modal.hasClass("modal-unavailability") && $("#announcement-calendar").length) {
			$("#announcement-calendar").fullCalendar("refetchEvents");
		}
	});
'); ?>
