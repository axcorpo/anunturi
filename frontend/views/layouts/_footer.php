<?php
/* @var $this \yii\web\View */

use yii\helpers\Html;
use tws\helpers\Url;
use yii\widgets\ActiveForm;

?>

<!-- FOOTER -->
<footer>
	<!-- FOOTER INFO -->
	<div class="clearfix footerInfo">
		<div class="container">
			<div class="row">
				<div class="col-sm-7 col-xs-12">
					<div class="footerText">
						<a href="<?= Url::to(['/site/index']) ?>">
							<img class="img-responsive" src="<?= Url::to(Yii::$app->settings->get('appLogoAlt')) ?: Url::to('@web/img/logo-alt.png') ?>" alt="<?= Yii::$app->name ?>" style="max-width: 200px;">
						</a>
						<?php if ($appDescription = Yii::$app->settings->get('appDescription', 'general')): ?>
							<p class="app-description"><?= $appDescription[Yii::$app->language] ?></p>
						<?php endif; ?>

						<?php $appLanguage = mb_strtolower(substr(Yii::$app->language, 0, 2)); ?>
						<?php
						$contact = Yii::$app->settings->getCategory('contact');
						$address = implode(', ', array_filter([
							$contact['streetName'],
							$contact['streetNumber'],
							$contact['locality'],
							$contact['zipCode'],
							$contact['county'],
							$contact['country'] ? \common\models\Country::findAllCountries()[$contact['country']]->translation->name : null,
						]));
						?>
						<ul class="list-icon list-contact">
							<?php if ($contact['company']): ?>
								<li class="fa-briefcase"><?= $contact['company'] ?></li>
							<?php endif; ?>
							<?php if ($address): ?>
								<li class="fa-map-marker">
									<?php if ($contact['latitude'] && $contact['longitude']): ?>
										<a class="link-underline" href="//www.google.com/maps/place/<?= $contact['latitude'] ?>,<?= $contact['longitude'] ?>" target="_blank"><?= $address ?></a>
									<?php else: ?>
										<?= $address ?>
									<?php endif; ?>
								</li>
							<?php endif; ?>
							<?php if ($contact['schedule'][Yii::$app->language]): ?>
								<li class="fa-clock-o"><?= nl2br($contact['schedule'][Yii::$app->language]) ?></li>
							<?php endif; ?>
							<?php if ($contact['mobilePhone'] || $contact['fixedPhone']): ?>
								<li class="fa-phone">
									<?= implode(', ', array_filter([
										$contact['mobilePhone'] ? Html::a($contact['mobilePhone'], "tel:{$contact['mobilePhone']}", ['class' => 'link-underline', 'data-prevent-page-overlay' => 'true']) : null,
										$contact['fixedPhone'] ? Html::a($contact['fixedPhone'], "tel:{$contact['fixedPhone']}", ['class' => 'link-underline', 'data-prevent-page-overlay' => 'true']) : null,
									])) ?>
								</li>
							<?php endif; ?>
							<?php if ($contact['email']): ?>
								<li class="fa-envelope-o">
									<?= Html::a($contact['email'], "mailto:{$contact['email']}", ['class' => 'link-underline', 'data-prevent-page-overlay' => 'true']) ?>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				</div>
				<?php if ($navMenu = \common\models\Menu::findDefaultFooterMenu()): ?>
					<div class="col-sm-3 col-xs-12">
						<div class="footerInfoTitle">
							<h4><?= Yii::t('frontend', 'Explore') ?></h4>
						</div>
						<div class="useLink">
							<ul class="list-unstyled" style="margin-top: 1em;">
								<li>
									<a href="<?= Url::to(['/announcement/default/index']) ?>"><?= Yii::t('frontend', 'All Announcements') ?></a>
								</li>
                                <li>
                                    <a href="<?= Url::to(['/firm/default/index']) ?>"><?= Yii::t('frontend', 'All Companies') ?></a>
                                </li>
                                <li>
                                    <a href="<?= Url::to(['/individual/default/index']) ?>"><?= Yii::t('frontend', 'All Individuals') ?></a>
                                </li>
								<?php $categories = \common\models\Category::findMainCategories(); ?>
								<?php foreach ($categories as $category): ?>
									<li>
										<?= Html::a($category->translation->name, ['/announcement/default/index', 'category' => $category->translation->slug]) ?>
									</li>
								<?php endforeach; ?>
								<li>
									<a href="<?= Url::to(['/account/announcement/create']) ?>"><?= Yii::t('frontend', 'Add Announcement') ?></a>
								</li>
							</ul>
						</div>
					</div>
				<?php endif; ?>
				<?php if ($navMenu = \common\models\Menu::findDefaultFooterMenu()): ?>
					<div class="col-sm-2 col-xs-12">
						<div class="footerInfoTitle">
							<h4><?= Yii::t('frontend', 'Quick Links') ?></h4>
						</div>
						<div class="useLink">
							<?= \common\widgets\sitemenu\SiteNav::widget([
								'options' => [
									'class' => 'list-unstyled',
									'style' => 'margin-top: 1em;',
								],
								'activateParents' => true,
								'encodeLabels' => false,
								'items' => $navMenu->getNestedItems(),
							]) ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- COPY RIGHT -->
	<div class="clearfix copyRight">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<div class="copyRightWrapper">
						<div class="row">
							<div class="col-sm-4 col-sm-push-8 col-xs-12">
								<?php $socialNetwork = Yii::$app->settings->getCategory('socialNetwork'); ?>
								<ul class="list-inline socialLink">
									<?php if ($socialNetwork['facebookPage']): ?>
										<li>
											<a class="link-social-facebook" href="<?= $socialNetwork['facebookPage'] ?>" title="Facebook" target="_blank">
												<span class="fa fa-facebook"></span>
											</a>
										</li>
									<?php endif; ?>
									<?php if ($socialNetwork['instagramPage']): ?>
										<li>
											<a class="link-social-instagram" href="<?= $socialNetwork['instagramPage'] ?>" title="Instagram" target="_blank">
												<span class="fa fa-instagram"></span>
											</a>
										</li>
									<?php endif; ?>
									<?php if ($socialNetwork['twitterPage']): ?>
										<li>
											<a class="link-social-twitter" href="<?= $socialNetwork['twitterPage'] ?>" title="Twitter" target="_blank">
												<span class="fa fa-twitter"></span>
											</a>
										</li>
									<?php endif; ?>
									<?php if ($socialNetwork['linkedinPage']): ?>
										<li>
											<a class="link-social-linkedin" href="<?= $socialNetwork['linkedinPage'] ?>" title="Linked In" target="_blank">
												<span class="fa fa-linkedin"></span>
											</a>
										</li>
									<?php endif; ?>
									<?php if ($socialNetwork['pinterestPage']): ?>
										<li>
											<a class="link-social-pinterest" href="<?= $socialNetwork['pinterestPage'] ?>" title="Pinterest" target="_blank">
												<span class="fa fa-pinterest"></span>
											</a>
										</li>
									<?php endif; ?>
								</ul>
							</div>
							<div class="col-sm-8 col-sm-pull-4 col-xs-12">
								<div class="copyRightText">
									<p>
										<?= Yii::t('common', '&copy; {0} {1}. All rights reserved.', [Yii::$app->name, date('Y')]) ?>
										<?= Yii::t('common', 'Developed by {0} & {1}', ['<a href="http://themewolves.com/" target="_blank">Themewolves</a>', '<a href="http://treewebsolutions.com/" target="_blank">Tree Web Solutions</a>']) ?>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</footer>

<?php if (!Yii::$app->getRequest()->getCookies()->has('acceptCookies')): ?>
	<div id="cookiebar">
		<div class="row">
			<div class="col-md-1"></div>
			<div class="col-md-8" style="padding: 0 15px;">
				<h3 style="margin: 0 15px;"><?= Yii::t('frontend', '{0} uses COOKIES', [Yii::$app->name]) ?></h3>
				<p style="margin: 0 15px;"><?= Yii::t('frontend', 'By clicking on "Yes, I accept" you agree to the use of cookies installed on the site. These are used to give you the best browsing experience possible.') ?></p>
			</div>
			<div class="col-md-3">
				<?php $form = ActiveForm::begin([
					'action' =>['/accept-cookies'],
					'id' => 'accept-cookies-form',
				]); ?>
				<?= Html::hiddenInput('acceptCookies', 1); ?>
				<?= Html::hiddenInput('backUrl', Url::canonical()); ?>
				<?= Html::submitButton('&nbsp;&nbsp;&nbsp;' . Yii::t('frontend', 'Yes, I Accept'), ['id' => 'okcookies']) ?>
				<?= Html::a(Yii::t('common', 'Details'), ['/site/cookie-policy']); ?>
				<?php ActiveForm::end() ?>
			</div>
		</div>
	</div>
<?php endif; ?>
