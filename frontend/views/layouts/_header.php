<?php

/* @var $this \yii\web\View */
/* @var $navWrapperClass string */
/* @var $navClass string */
/* @var $showWhiteLogo bool */

use common\models\Auth;
use common\models\Message;
use common\models\User;
use tws\widgets\typeahead\Typeahead;
use yii\authclient\widgets\AuthChoice;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

/** @var \common\models\User $user */
$user = Yii::$app->user->identity;
$appLanguages = \common\models\Language::findAllLanguages();
$appLanguagesCount = count($appLanguages);
$currentAppLanguage = $appLanguages[Yii::$app->language];
?>

	<!-- HEADER -->
	<header id="pageTop" class="header">

		<!-- TOP INFO BAR -->

		<div class="nav-wrapper <?= $navWrapperClass ?>">
			<!-- NAVBAR -->
			<nav id="menuBar" class="<?= $navClass ?>" role="navigation">
				<div class="container">

					<!-- Brand and toggle get grouped for better mobile display -->
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-head">
							<span class="sr-only">Toggle navigation</span>
							<i class="fa fa-user"></i>
						</button>
						<?php $logo = Html::img(Yii::getAlias('@web/img/logo.png'), [
							'alt' => Yii::$app->name,
							'class' => 'logo-svg logo-black'
						]);
						if ($showWhiteLogo) {
							$logo .= Html::img(Yii::getAlias('@web/img/logo-alt.png'), [
								'alt' => Yii::$app->name,
								'class' => 'logo-svg logo-white'
							]);
						} ?>
						<?= Html::a($logo, ['/site/index'], ['class' => 'navbar-brand']) ?>
					</div>

					<!-- Collect the nav links, forms, and other content for toggling -->
					<?php if (Yii::$app->user->isGuest): ?>
						<div class="collapse navbar-collapse navbar-ex1-collapse navbar-head">
							<ul class="nav navbar-nav navbar-right">
								<li>
									<?= Html::a('<i class="fa fa-user" aria-hidden="true"></i> ' . \common\models\Page::findPageByRoute(['/site/login'])->translation->title, ['/site/login'], [
										'role' => 'button',
										'aria-haspopup' => 'true',
										'aria-expanded' => 'false',
									]) ?>
								</li>
								<li>
									<?= Html::a('<i class="fa fa-user-plus" aria-hidden="true"></i> ' . \common\models\Page::findPageByRoute(['/site/signup'])->translation->title, ['/site/signup'], [
										'role' => 'button',
										'aria-haspopup' => 'true',
										'aria-expanded' => 'false',
									]) ?>
								</li>
								<?php if (count($appLanguages = \common\models\Language::findAllLanguages()) > 1): ?>
									<li class="dropdown singleDrop">
										<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
											<i class="fa fa-language"></i> <?= substr(Yii::$app->language, 0, -3) ?>
										</a>
										<ul class="dropdown-menu dropdown-menu-left">
											<li>
												<?php
												$languageItems = [];
												if ($appLanguages = \common\models\Language::findAllLanguages()) {
													if (count($appLanguages) > 1) {
														foreach ($appLanguages as $language) {
															$languageItems[] = [
																'active' => Yii::$app->language == $language->language_id,
																'label' => mb_strtoupper($language->language),
																'url' => ['/site/index', 'language' => $language->language_id],
															];
														}
													}
												}
												?>
												<?= \common\widgets\sitemenu\SiteNav::widget([
													'activateParents' => true,
													'encodeLabels' => false,
													'items' => $languageItems,
												]) ?>
											</li>
										</ul>
									</li>
								<?php endif; ?>
							</ul>
						</div>
					<?php else: ?>
						<div class="collapse navbar-collapse navbar-ex2-collapse navbar-desktop navbar-head">
							<ul class="nav navbar-nav navbar-right">
								<li class="dropdown singleDrop">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
										<div class="userInfo">
											<?= Html::img(Yii::$app->user->identity->imageUrl ?: Url::to('@web/img/img-placeholder-user.png'), [
												'class' => 'img-circle',
												'alt' => Yii::$app->user->identity->fullName,
											]) ?>
										</div>
										<?= $user->shortName ?>
									</a>
									<?php
									$dashboard= [];
									if (Yii::$app->user->identity->getHasPermissions()) {
										$dashboard[] = [
											'label' => Yii::t('common', 'Dashboard'),
											'url' => ['/admin'],
										];
									}
									$logout[] = [
										'label' => Yii::t('common', 'Logout'),
										'url' => ['/site/logout'],
									];
									?>
									<?php $navMenu = \common\models\Menu::findDefaultHeaderMenu(); ?>
									<?= \common\widgets\sitemenu\SiteNav::widget([
										'options' => [
											'class' => 'dropdown-menu dropdown-menu-left',
										],
										'activateParents' => true,
										'encodeLabels' => false,
										'items' => array_merge($dashboard, ($navMenu ? $navMenu->getNestedItems(null, \common\models\MenuItem::STATUS_ACTIVE) : []), $logout),
									]) ?>
								</li>
                                <?php $messages = Message::findUnseenMessages(); ?>
                                <li id="messages">
                                    <a href="<?= Url::to(['/account/message/index']) ?>" id="messages-notification" role="button" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-envelope" aria-hidden="true"></i> <?= Yii::t('common', 'Messages') ?>
                                        <?php if (count($messages)): ?>
                                            <span class="mesaj-alert"><?= count($messages) ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
								<?php if (count($appLanguages = \common\models\Language::findAllLanguages()) > 1): ?>
									<li class="dropdown singleDrop">
										<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
											<i class="fa fa-language"></i> <?= substr(Yii::$app->language, 0, -3) ?>
										</a>
										<ul class="dropdown-menu dropdown-menu-left">
											<li>
												<?php
												$languageItems = [];
												if ($appLanguages = \common\models\Language::findAllLanguages()) {
													if (count($appLanguages) > 1) {
														foreach ($appLanguages as $language) {
															$languageItems[] = [
																'active' => Yii::$app->language == $language->language_id,
																'label' => mb_strtoupper($language->language),
																'url' => ['/site/index', 'language' => $language->language_id],
															];
														}
													}
												}
												?>
												<?= \common\widgets\sitemenu\SiteNav::widget([
													'activateParents' => true,
													'encodeLabels' => false,
													'items' => $languageItems,
												]) ?>
											</li>
										</ul>
									</li>
								<?php endif; ?>
						</div>
					<?php endif; ?>
					<?= Html::a('+ <span>' . Yii::t('frontend', 'Add Announcement') . '</span>', ['/account/announcement/create'], [
						'class' => 'btn btn-default navbar-btn',
					]) ?>
				</div>
			</nav>
		</div>
        <?php $this->registerJs('
//			$(document).ready(function(){
//				setInterval(function(){
//				   $("#messages").load(location.href + " #messages-notification");
//				}, 30000);
//			});
		');
        ?>
	</header>
