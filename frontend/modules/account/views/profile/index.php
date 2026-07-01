<?php
/* @var $this yii\web\View */
/* @var $model common\models\User */

use borales\extensions\phoneInput\PhoneInput;
use common\components\PhoneInputConfig;
use common\models\Auth;
use common\models\Country;
use common\models\Page;
use common\models\User;
use common\widgets\ActiveForm;
use tws\widgets\datetimepicker\DateTimePicker;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\authclient\widgets\AuthChoice;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>


<?php if (!empty($this->params['breadcrumbs'])) : ?>
	<!-- Dashboard breadcrumb section -->
	<div class="section dashboard-breadcrumb-section bg-dark">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<h2><?= Html::encode($this->title) ?></h2>
					<?= Breadcrumbs::widget([
						'options' => [
							'class' => 'breadcrumb',
						],
						'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
					]) ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ($content = $this->context->currentPage->translation->content): ?>
	<section class="clearfix bg-dark">
		<div class="container">
			<div class="row">
				<?= $content ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- DASHBOARD PROFILE SECTION -->
<section class="clearfix bg-dark profileSection">
	<div class="container">
		<?php $form = ActiveForm::begin([
			'id' => mb_strtolower($model->formName()),
			'options' => [
				'class' => 'profile-form',
				'novalidate' => true,
			],
			'validateOnType' => true,
			'validateOnBlur' => false,
		]); ?>
		<div class="row">
			<div class="col-md-4 col-sm-5 col-xs-12">
				<div class="dashboardBoxBg mb30">
					<div class="profileImage">
						<?= FileInput::widget([
							'options' => [
								'accept' => 'image/*',
								'data' => [
									'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							],
							'model' => $model,
							'attribute' => 'imageFile',
							'resizeImages' => false,
							'sortThumbs' => false,
							'purifyHtml' => false,
							'pluginOptions' => [
								'allowedFileExtensions' => Yii::$app->params['image.extensions'],
								'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
								'browseClass' => 'btn btn-file-preview',
								'browseIcon' => '<span class="fa fa-camera"></span> ',
								'browseLabel' => Yii::t('common', 'Change Image'),
								'uploadUrl' => Url::to(['/account/profile/upload-file']),
								'uploadExtraData' => [
									'attribute' => 'image',
									'fileName' => "{$model->formName()}[imageFile]",
								],
								'showUploadedThumbs' => true,
								'showAjaxErrorDetails' => false,
								'previewClass' => 'file-preview-custom',
								'frameClass' => 'file-preview-frame-custom',
								'layoutTemplates' => [
									'footer' => '',
									'progress' => '',
								],
								'dropZoneEnabled' => false,
								'showClose' => false,
								'showUpload' => false,
								'showCaption' => false,
								'showRemove' => false,
								'showCancel' => false,
								'showPreview' => true,
								'fileActionSettings' => [
									'showDownload' => false,
									'showRemove' => false,
									'showUpload' => false,
									'showZoom' => false,
									'showDrag' => false,
								],
								'initialPreview' => $model->imageUrl ?: Url::to('@web/img/img-placeholder-user.png'),
								'initialPreviewAsData' => true,
								'overwriteInitial' => true,
							],
							'pluginEvents' => [
								'filebatchselected' => new \yii\web\JsExpression('function () {
										$(this).fileinput("upload");
									}'),
							],
						]) ?>
					</div>
					<div class="profileIntro">
						<h3><?= Yii::t('common', 'Account Data') ?></h3> <small>(<?= $model->email ?>)</small>
						<div class="row">
							<div class="form-group col-sm-12">
								<?= $form->field($model, 'new_password')->passwordInput(['autocomplete' => 'new-password']) ?>
								<?= $form->field($model, 'new_password_confirm')->passwordInput(['autocomplete' => 'new-password']) ?>
								<hr>
								<?php
								$items = []; $socialAccounts = [];
								$authChoice = AuthChoice::begin(['baseAuthUrl' => ['site/auth'], 'autoRender' => false]);
								foreach ($authChoice->getClients() as $client) {
									$auth = Auth::find()->where([
										'user_id' => Yii::$app->user->id,
										'source' => $client->getId(),
									])->one();
									if (!$auth->id) {
										$socialAccounts[] = Html::a('<span style="color: #fff;">' . Yii::t('common', 'Link Account') . '</span><i class="fa fa-' . $client->name . '" style="width: 20px; line-height: 20px;background-color:#fff; color: ' . str_replace(['google', 'facebook'], ['#d93025', '#1877f2'], $client->name) . '; padding: 2px ' . str_replace(['google', 'facebook'], ['4', '6'], $client->name) . 'px; border-radius: 0.1875em; margin-left: 5px;"></i>', ['/site/auth', 'authclient' => $client->name], [
											'class' => 'btn btn-block btn-' . $client->name,
											'style' => 'background-color: ' . str_replace(['google', 'facebook'], ['#d93025', '#1877f2'], $client->name),
										]);
									}  else {
										$socialAccounts[] = Html::a('<span style="color: #fff;">' . Yii::t('common', 'Unlink Account') . '</span><i class="fa fa-' . $client->name . '" style="width: 20px; line-height: 20px;background-color:#fff; color: ' . str_replace(['google', 'facebook'], ['#ed9d97', '#77a7ff'], $client->name) . '; padding: 2px ' . str_replace(['google', 'facebook'], ['4', '6'], $client->name) . 'px; border-radius: 0.1875em; margin-left: 5px;"></i>', ['/account/profile/index', 'unlink' => $auth->source_id], [
											'class' => 'btn btn-block btn-' . $client->name,
											'style' => 'background-color: ' . str_replace(['google', 'facebook'], ['#ed9d97', '#77a7ff'], $client->name) . '; ' . 'border-color: ' . str_replace(['google', 'facebook'], ['#ed9d97', '#77a7ff'], $client->name) . ';',
										]);
									}
								}
								AuthChoice::end();
								?>
								<?php foreach ($socialAccounts as $account): ?>
									<?= $account; ?>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-8 col-sm-7 col-xs-12">
				<?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
					<div class="dashboardBoxBg mb30">
						<div class="profileIntro">
							<?= $form->errorSummary($model, [
								'header' => false,
								'class' => 'alert alert-danger alert-icon',
							]) ?>
						</div>
					</div>
				<?php endif; ?>
				<div class="dashboardBoxBg">
					<div class="profileIntro">
						<h3><?= Yii::t('common', 'Personal Data') ?></h3>
						<div class="row">
							<div class="col-md-4">
								<?= $form->field($model, 'last_name')->textInput() ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'first_name')->textInput() ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'middle_name')->textInput() ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<?= $form->field($model, 'phone')->widget(PhoneInput::class, [
									'jsOptions' => PhoneInputConfig::jsOptions(),
									'options' => PhoneInputConfig::inputClassOptions(),
								]) ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'pin')->textInput() ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'gender')->radioList(User::getGenderLabels(), [
									'class' => 'radio-inline',
								]) ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<?= $form->field($model, 'date_of_birth')->widget(DateTimePicker::class, [
									'options' => [
										'value' => $model->date_of_birth ? Yii::$app->formatter->asDate($model->date_of_birth) : null,
										'placeholder' => Yii::$app->settings->get('dateFormat'),
									],
									'clientOptions' => [
										'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
										'maxDate' => (new DateTime)->format(DATE_ATOM),
										'ignoreReadonly' => true,
										'showTodayButton' => false,
										'showClear' => true,
										'showClose' => true,
										'allowInputToggle' => true,
										'useCurrent' => false,
									],
								]) ?>
							</div>
						</div>

						<hr>
						<h3><?= Yii::t('label', 'Address') ?></h3>
						<div class="row">
							<div class="col-md-4">
								<?= $form->field($model, 'street_name')->textInput() ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'street_number')->textInput() ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'locality')->textInput() ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<?= $form->field($model, 'zip_code')->textInput() ?>
							</div>
							<div class="col-md-4">
								<?= $form->field($model, 'county')->textInput() ?>
							</div>
							<div class="col-md-4">
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
						<button type="submit" class="btn btn-primary" style="padding: 25.5px 30px;"><?= Yii::t('common', 'Save') ?></button>
					</div>
				</div>
				<div class="dashboardBoxBg">
					<div class="profileIntro">
						<h3><?= Yii::t('common', 'Bank Account') ?></h3>
						<div class="row">
							<div class="col-md-6">
								<?= $form->field($model, 'bank_account_holder')->textInput() ?>
							</div>
							<div class="col-md-6">
								<?= $form->field($model, 'bank_name')->textInput() ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<?= $form->field($model, 'bank_iban')->textInput() ?>
							</div>
							<div class="col-md-6">
								<?= $form->field($model, 'bank_bic')->textInput() ?>
							</div>
						</div>
					</div>
				</div>
				<?php if ($model->subscriber->code): ?>
					<div class="dashboardBoxBg">
					<div class="profileIntro">
						<h3><?= Yii::t('common', 'Referral URL') ?></h3>
						<div class="row">
							<div class="col-md-12">
								<div class="input-group">
									 <span id="copyButtonReferral" class="input-group-addon btn-primary" style="padding: 15px 20px;" title="<?= Yii::t('common', 'Click To Copy') ?>">
								      <i class="fa fa-clipboard" aria-hidden="true"></i>
								    </span>
									<input type="text" id="copyTargetReferral" class="form-control" value="<?= Url::to(['/site/referral', 'code' => $model->subscriber->code], true) ?>">
									<span class="copied"><?= Yii::t('common', 'Copied') ?></span>
								</div>
							</div>
						</div>
						<h3><?= Yii::t('common', 'Embed URL') ?></h3>
						<div class="row">
							<div class="col-md-12">
								<div class="input-group">
									 <span id="copyButtonEmbed" class="input-group-addon btn-primary" style="padding: 15px 20px;" title="<?= Yii::t('common', 'Click To Copy') ?>">
								      <i class="fa fa-clipboard" aria-hidden="true"></i>
								    </span>
									<input type="text" id="copyTargetEmbed" class="form-control" value='<script id="announcement-embed" src="<?= Url::to(['/announcement/default/index'], true) ?>/embed" defer="defer" data-action="create" data-code="<?= $model->subscriber->code ?>" data-language="<?= Yii::$app->language ?>" data-title="TITLE_FIELD_ID" data-content="CONTENT_FIELD_ID" data-parent="PARENT_URL"></script>'>
									<span class="copied"><?= Yii::t('common', 'Copied') ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php ActiveForm::end(); ?>
	</div>
</section>

<?php
$this->registerJs('
	(function() {
		"use strict";
		function copyToClipboard(elem) {
			var target = elem;
			// select the content
			var currentFocus = document.activeElement;
			target.focus();
			target.setSelectionRange(0, target.value.length);
			// copy the selection
			var succeed;
			try {
				succeed = document.execCommand("copy");
			} catch (e) {
				console.warn(e);
				succeed = false;
			}
			// Restore original focus
			if (currentFocus && typeof currentFocus.focus === "function") {
				currentFocus.focus();
			}
			if (succeed) {
				$(target).closest("div").find(".copied").animate({ top: -25, opacity: 0 }, 700, function() {
					$(this).css({ top: 0, opacity: 1 });
				});
			}
			return succeed;
		}
		$("#copyButtonReferral, #copyTargetReferral").on("click", function() {
			copyToClipboard(document.getElementById("copyTargetReferral"));
		});
		$("#copyButtonEmbed, #copyTargetEmbed").on("click", function() {
			copyToClipboard(document.getElementById("copyTargetEmbed"));
		});
	})();
');
?>
