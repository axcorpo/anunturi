<?php

/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Announcement */

use common\helpers\UrlHelper;
use common\models\Action;
use common\models\Announcement;
use common\models\Category;
use backend\widgets\ActiveForm;
use common\models\Company;
use common\models\Country;
use kartik\depdrop\DepDrop;
use kartik\file\FileInput;
use kartik\number\NumberControl;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\bootstrap\Tabs;
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
                    'data' => ArrayHelper::getColumn(Announcement::getStatusLabels(), 'label'),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'placeholder' => Yii::t('common', 'Choose'),
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'icon')->widget(Select2::class, [
                    'data' => \common\helpers\FontIcon::getDropdownIcons(),
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Choose'),
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
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
                <?= $form->field($model, 'category')->widget(Select2::class, [
                    'options' => [
                        'multiple' => false,
                    ],
                    'data' => ArrayHelper::map(Category::find()->active(true)->deleted(false)->where(['leaf' => 1])->all(), 'id', 'treeName'),
                    'maintainOrder' => false,
                    'showToggleAll' => false,
                    'pluginLoading' => false,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => Yii::t('common', 'Choose'),
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    ],
                    'pluginEvents' => [
                        'change.select2' => new JsExpression('function (e) {
							var $form = $("#announcement-form");
							 $.ajax({
								url: "'  .  Url::to(['/announcement-manager/announcement/category-fields'])  . '",
								type: "POST",
								data :  {
									category: $( "#' . Html::getInputId($model, 'category') . '").val(),
								},
								success: function(response) {
									 $.pjax.reload({url: $form.attr("action"), container: "#extra-fields", timeout: false});
								},
								error: function() {
								}
							});
						}'),
                    ],
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?= $form->field($model, 'action')->widget(DepDrop::class, [
                    'options' => [
                        'multiple' => true,
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
                        'url' => Url::to(['/announcement-manager/announcement/category-actions']),
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
                        'class' => 'form-group required',
                    ],
                    'template' => '{label}<div class="input-group">{input}<div class="input-group-btn with-control" style="min-width: 150px;">' . $currencyField . '</div></div>{hint}{error}',
                ])->widget(NumberControl::class, [
                    'maskedInputOptions' => [
                        'groupSeparator' => '.',
                        'radixPoint' => ',',
                        'allowMinus' => false,
                    ],
                    'displayOptions' => [
                        'autocomplete' => 'off',
                        'placeholder' => '0.0000',
                    ],
                ]) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'uom')->textInput() ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <?php
                    $companies = Yii::$app->user->identity->getCompanies()->active()->deleted(false)->all();
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
                    'pluginLoading' => false,
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
                        'deleteUrl' => Url::to(['delete-picture', 'id' => Yii::$app->request->get('id')]),
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

        <?= $this->render('_fields', [
            'form' => $form,
            'model' => $model,
            'category' => Yii::$app->session->get('category'),
        ]) ?>

        <div class="row">
            <div class="col-sm-6">
                <div class="text-info-icon fa-info-circle text-muted margin-bottom-10"><?= Yii::t('backend', 'Drag the map marker to the desired position.') ?></div>
                <div id="map-container" style="width: 100%; height: 400px;"></div>
            </div>
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-sm-12">
                        <?= $form->field($model, 'address')->textarea(['rows' => 1]) ?>
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
                            'data' => ArrayHelper::map(Country::findAllCountries(), 'iso_alpha2', 'full_name'),
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

<script async defer src="//maps.googleapis.com/maps/api/js?key=<?= Yii::$app->settings->get('googleMapKey') ?>&language=<?= Yii::$app->language ?>&callback=initMap"></script>
<script type="text/javascript">
    var defaultLat = "<?= $model->latitude ?: 45.7519379 ?>",
        defaultLng = "<?= $model->longitude ?: 21.2259002 ?>",
        latitudeControl = document.getElementById("<?= Html::getInputId($model, 'latitude') ?>"),
        longitudeControl = document.getElementById("<?= Html::getInputId($model, 'longitude') ?>"),
        localityControl = document.getElementById("<?= Html::getInputId($model, 'locality') ?>");
        countyControl = document.getElementById("<?= Html::getInputId($model, 'county') ?>");
        countryControl = document.getElementById("<?= Html::getInputId($model, 'country') ?>");
        zipCodeControl = document.getElementById("<?= Html::getInputId($model, 'zip_code') ?>");
        addressControl = document.getElementById("<?= Html::getInputId($model, 'address') ?>");
        updateAddressCheckbox = document.getElementById("update-address-checkbox");

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

        mapMarker.setMap(map);
        map.setCenter(mapMarker.position);
    };
</script>
