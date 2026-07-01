<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Company */

use common\models\Country;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use tws\helpers\Url;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;

$shouldRenderModal = Yii::$app->request->isAjax;
?>

<?php $form = ActiveForm::begin([
    'id' => mb_strtolower($model->formName()),
    'options' => [
        'novalidate' => true,
        'class' => $shouldRenderModal ? 'modal-dialog modal-lg' : '',
    ],
    'validateOnType' => true,
]); ?>
<div class="form-body <?= $shouldRenderModal ? 'modal-content' : '' ?>">
    <?php if ($shouldRenderModal): ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <div class="modal-title"><?= $this->title ?></div>
        </div>
    <?php endif; ?>

    <div class="form-fields <?= $shouldRenderModal ? 'modal-body' : '' ?>">
        <?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
            <?= $form->errorSummary($model, [
                'header' => false,
                'class' => 'alert alert-danger alert-icon',
            ]) ?>
        <?php endif; ?>
        <div class="row">
            <div class="col-sm-12">
                <?= $form->field($model, 'imageFile')->widget(FileInput::class, [
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
                        'allowedFileExtensions' => Yii::$app->params['image.extensions'],
                        'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
                        'dropZoneEnabled' => false,
                        'showClose' => false,
                        'showUpload' => false,
                        'showCaption' => true,
                        'showRemove' => false,
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
            <div class="col-sm-4">
                <?= $form->field($model, 'tin')->textInput([
                    'data' => [
                        'autofill-target' => '#' . mb_strtolower($model->formName()),
                        'autofill-url' => Url::to(['/commercial/company/find']),
                        'autofill-param' => 'tin',
                        'autofill-data' => Json::encode([
                            'data.registrationNumber' => '#' . Html::getInputId($model, 'registration_number'),
                            'data.name' => '#' . Html::getInputId($model, 'name'),
                            'data.email' => '#' . Html::getInputId($model, 'email'),
                            'data.phone' => '#' . Html::getInputId($model, 'phone'),
                            'data.address.streetName' => '#' . Html::getInputId($model, 'street_name'),
                            'data.address.streetNumber' => '#' . Html::getInputId($model, 'street_number'),
                            'data.address.locality' => '#' . Html::getInputId($model, 'locality'),
                            'data.address.county' => '#' . Html::getInputId($model, 'county'),
                            'data.address.country' => '#' . Html::getInputId($model, 'country'),
                            'data.address.zipCode' => '#' . Html::getInputId($model, 'zip_code'),
                            'data.fullAddress' => '#' . Html::getInputId($model, 'address'),
                        ]),
                    ],
                ]) ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'registration_number')->textInput() ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'name')->textInput() ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'email')->input('email') ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'phone')->input('tel') ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'fax')->input('tel') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'street_name')->textInput() ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'street_number')->textInput() ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'locality')->textInput() ?>
            </div>
        </div>
		<div class="row">
			<div class="col-sm-12">
				<?= $form->field($model, 'address')->textInput() ?>
			</div>
			<?= $form->field($model, 'latitude')->hiddenInput()->label(false) ?>
			<?= $form->field($model, 'longitude')->hiddenInput()->label(false) ?>
		</div>
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'zip_code')->textInput() ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'county')->textInput() ?>
            </div>
            <div class="col-sm-4">
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
        <div class="row">
            <div class="col-sm-12">
                <div id="company-map" style="width:100%; height:400px;"></div>
            </div>
        </div>


    </div>

    <?php if ($shouldRenderModal): ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-light btn-slide-right btn-primary" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
            <button type="submit" class="btn btn-default btn-slide-right btn-primary"><?= Yii::t('common', 'Save') ?></button>
        </div>
    <?php else: ?>
        <div class="form-actions floating">
            <?= Html::submitButton('<span class="fa fa-check"></span>', [
                'class' => 'btn btn-xlg btn-fab btn-default',
                'title' => Yii::t('common', 'Save'),
                'data' => [
                    'toggle' => 'tooltip',
                ],
            ]) ?>
        </div>
    <?php endif; ?>
</div>
<?php ActiveForm::end(); ?>

<br>

<script async defer src="//maps.googleapis.com/maps/api/js?key=<?= Yii::$app->settings->get('googleMapKey') ?>&language=<?= Yii::$app->language ?>&callback=initMap"></script>
<script type="text/javascript">

    var myZoom = 12;
    var myMarkerIsDraggable = true;
    var myCoordsLenght = 8;
    var defaultLat = '<?= $model->latitude ?: 45.7519379 ?>';
    var defaultLng = '<?= $model->longitude ?: 21.2259002 ?>';
    var map;

	var geocode = function (address) {
		var geocoder = new google.maps.Geocoder();

		geocoder.geocode({address: address}, function (results, status) {
			if (status === google.maps.GeocoderStatus.OK) {
				var geometry = [],
					latitude = '',
					longitude = '';

				for (var i = 0, len = results.length; i < len; i++) {
					if (results[i].geometry) {
						geometry = results[i].geometry;
						break;
					}
				}

				latitude = geometry.location.lat();
				longitude = geometry.location.lng();

				document.getElementById('<?= Html::getInputId($model, 'latitude') ?>').value =  latitude;
				document.getElementById('<?= Html::getInputId($model, 'longitude') ?>').value =  longitude;

				var map = new google.maps.Map(document.getElementById('company-map'), {
						zoom: 14,
						center: new google.maps.LatLng(latitude, longitude),
						mapTypeId: google.maps.MapTypeId.ROADMAP
					}),
					mapMarker = new google.maps.Marker({
						position: new google.maps.LatLng(latitude, longitude),
						draggable: true
					});

				google.maps.event.addListener(mapMarker, 'dragend', function (e) {
					document.getElementById('<?= Html::getInputId($model, 'latitude') ?>').value = e.latLng.lat().toFixed(8);
					document.getElementById('<?= Html::getInputId($model, 'longitude') ?>').value = e.latLng.lng().toFixed(8);
				});

				mapMarker.setMap(map);
				map.setCenter(mapMarker.position);
			}
		});
	};

    function initMap() {

        // creates the map
        // zooms
        // centers the map
        // sets the map's type
        map = new google.maps.Map(document.getElementById('company-map'), {
            zoom: myZoom,
            center: new google.maps.LatLng(defaultLat, defaultLng),
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        // creates a draggable marker to the given coords
        myMarker = new google.maps.Marker({
            position: new google.maps.LatLng(defaultLat, defaultLng),
            draggable: myMarkerIsDraggable
        });

        // adds a listener to the marker
        // gets the coords when drag event ends
        // then updates the input with the new coords
        google.maps.event.addListener(myMarker, 'dragend', function(evt){
            document.getElementById('<?= Html::getInputId($model, 'latitude') ?>').value = evt.latLng.lat().toFixed(myCoordsLenght);
            document.getElementById('<?= Html::getInputId($model, 'longitude') ?>').value = evt.latLng.lng().toFixed(myCoordsLenght);
        });

		document.getElementById('<?= Html::getInputId($model, 'tin') ?>').addEventListener('change', function (e) {
			event = document.createEvent("HTMLEvents");
			event.initEvent("change", true, true);
			event.eventName = "change";
			document.getElementById('<?= Html::getInputId($model, 'address') ?>').dispatchEvent(event);
		});

		document.getElementById('<?= Html::getInputId($model, 'address') ?>').addEventListener('change', function (e) {
			var timeout = 0;
			clearTimeout(timeout);
			timeout = setTimeout(function() {
				geocode(e.target.valueOf().value);
			}, 100);
		});

		document.getElementById('<?= Html::getInputId($model, 'address') ?>').addEventListener('keyup', function (e) {
			var timeout = 0;
			clearTimeout(timeout);
			timeout = setTimeout(function() {
				geocode(e.target.valueOf().value);
			}, 500);
		});

        // centers the map on markers coords
        map.setCenter(myMarker.position);

        // adds the marker on the map
        myMarker.setMap(map);

    }
	if (typeof window.google !== 'undefined') {
		initMap();
	}
</script>

