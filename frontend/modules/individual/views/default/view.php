<?php
/* @var $this \yii\web\View */
/* @var $model common\models\Subscriber */
/* @var $tags array */

use common\helpers\FontIcon;
use common\models\Announcement;
use common\models\Company;
use common\models\Review;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\rating\StarRating;
use kartik\touchspin\TouchSpin;
use tws\widgets\carousel\Carousel;
use tws\widgets\socialshare\SocialShare;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use yii\widgets\LinkPager;

$this->params['breadcrumbs'][] = [
	'label' => \common\models\Page::findPageByRoute(['/individual/default/index'])->translation->title,
	'url' => ['index'],
];
$this->params['breadcrumbs'][] = $model->user->shortName;
$reviews = Review::findSubscriberReviews($model->id, true);
$reviewsCount = count($reviews);
?>
<?php if ($title = $this->context->currentPage->translation->title): ?>
    <section class="clearfix pageTitleSection" style="background-image: url();">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <div class="pageTitle">
                        <h2><?= $title ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($breadcrumbs = $this->params['breadcrumbs']): ?>
    <section class="clearfix">
        <div class="container">
            <div class="section dashboard-breadcrumb-section">
                <div class="row">
                    <div class="col-xs-12">
                        <?php
                        if (!empty($breadcrumbs)) {
                            $lastBreadcrumb = array_pop($breadcrumbs);
                            $lastBreadcrumb = Html::tag('h1', $lastBreadcrumb, ['class' => 'page-title']);
                            $breadcrumbs[] = $lastBreadcrumb;
                        }
                        ?>
                        <?= Breadcrumbs::widget([
                            'options' => [
                                'class' => 'breadcrumb breadcrumb-list',
                            ],
                            'encodeLabels' => false,
                            'links' => !empty($breadcrumbs) ? $breadcrumbs : [],
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'class' => 'company-form',
		'novalidate' => true,
	],
	'validateOnType' => true,
	'validateOnBlur' => false,
]); ?>
	<section class="clearfix paddingAdjustTop company-profile">
		<div class="container">
			<div class="row">
				<div class="col-lg-3 col-md-5 col-sm-12 col-xs-12">
					<div class="listSidebar" style="margin-top: 0;">
						<?php if ($model->user->imageUrl && is_file(Yii::getAlias("@uploads/user/{$model->user->id}/{$model->user->image}"))): ?>
							<img src="<?= $model->user->imageUrl; ?>" alt="<?= $model->user->shortName ?>" class="img-ratio-object img-responsive img-rounded">
						<? else: ?>
							<img src="<?= Yii::getAlias('@web/img/img-placeholder-user.png') ?>" alt="<?= $model->user->shortName ?>" class="img-ratio-object img-responsive img-rounded">
						<?php endif; ?>
						<div class="side-title-info">
							<h2><?= $model->user->shortName ?></h2>
							<?php if ($model->fullAddress): ?>
								<p><i class="fa fa-map-marker icon-dash" aria-hidden="true"></i> <?= $model->fullAddress ?></p>
							<?php endif; ?>
							<div class="listingReview">
                                <?= StarRating::widget([
                                    'id' => 'rating-' . rand(),
                                    'name' => '',
                                    'value' => \common\models\Subscriber::getSubscriberRating($model->id) ?: 0,
                                    'pluginOptions' => [
                                        'displayOnly' => true,
                                        'showCaption' => false,
                                        'showClear' => false,
                                        'size' => 'xxs',
                                        'min' => 0,
                                        'max' => 5,
                                        'stars' => 5,
                                        'step' => 0.1,
                                        'theme' => 'krajee-fa',
                                        'filledStar' => '<i class="fa fa-star" aria-hidden="true"></i>',
                                        'emptyStar' => '<i class="fa fa-star-o" aria-hidden="true"></i>'
                                    ],
                                ]) ?>
								<?php if ($reviewsCount == 1): ?>
									<span><?= '(' . $reviewsCount . ' ' . Yii::t('label', 'Review') . ')'?></span>
								<?php else: ?>
									<span><?= '(' . $reviewsCount . ' ' . Yii::t('label', 'Reviews') . ')'?></span>
								<?php endif; ?>
							</div>
						</div>
						<?php if (Yii::$app->user->id != $model->user->id): ?>
							<div class="contact-buttons hidden-sm hidden-xs">
								<div class="phone-button">
									<a href="#" class="btn btn-primary phone-link" data-prevent-page-overlay="true">
										<i class="fa fa-phone" aria-hidden="true"></i> <span class="phone-anchor"><?= Yii::t('frontend', 'Show Phone') ?></span>
									</a>
								</div>
								<div class="send-message">
									<?= Html::a('<i class="fa fa-envelope"></i>' . ' ' . Yii::t('frontend', 'Send Message'), ['/account/message/create', 'recipient_id' => $model->user->id], [
										'class' => 'btn btn-primary',
										'data' => [
											'popup-action' => '',
										]
									]) ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="col-lg-9 col-md-7 col-sm-12 col-xs-12">
					<div class="listDetailsInfo">
						<?php $announcementCount = Announcement::find()
							->alias('a')
							->where([
								'a.created_by' => $model->user->id,
								'a.status' => Announcement::STATUS_ACTIVE,
								'a.deleted' => Announcement::NO,
							])
							->all();
						?>
						<?php if (count($announcementCount) > 0): ?>
							<div class="individual-product company-published" id="published_announcements">
								<h3><?= Yii::t('label','Announcements') ?></h3>
								<div class="listContent">
									<div class="resultBar barSpaceAdjust">
                                        <?php if (count($announcementCount) != 1): ?>
                                            <h2><?= $model->user->shortName . ' ' . Yii::t('frontend','has published <span>{number}</span> announcements', ['number' => count($announcementCount)]) ?></h2>
                                        <?php else: ?>
                                            <h2><?= $model->user->shortName . ' ' . Yii::t('frontend','has published <span>{number}</span> announcement', ['number' => count($announcementCount)]) ?></h2>
                                        <?php endif; ?>
									</div>
									<div class="row">
										<?php foreach ($dataProvider->getModels() as $announcement): ?>
											<?php $announcementTranslation = $announcement->translation;
											$slug = $announcementTranslation->slug;
											$actions = [];
											foreach ($announcement->actions as $action) {
												$actions[] = \common\models\Action::getMyTypes()[$action->type];
											}
											?>
											<div class="col-md-4 col-sm-6 col-xs-12 isotopeSelector">
												<article class="">
													<figure>
														<div class="advert-img img-ratio">
															<?php if ($announcement->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$announcement->id}/{$announcement->image}"))): ?>
																<img src="<?= $announcement->imageUrl; ?>" alt="<?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive">
															<?php else: ?>
																<img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive">
															<?php endif; ?>
														</div>
														<div class="overlay-background">
															<div class="inner"></div>
														</div>
														<a href="<?= Url::to(['/announcement/default/view', 'slug' => $announcementTranslation->slug]) ?>">
															<div class="overlay">
																<div class="overlayInfo">
																	<span class="label label-primary"><i class="fa fa-eye" aria-hidden="true"></i> <?= $announcement->views; ?></span>
																	<span class="label label-share"><i class="fa fa-hand-pointer-o" aria-hidden="true"></i> <?= $announcement->visits; ?></span>
																</div>
															</div>
														</a>
													</figure>
													<div class="figureBody">
														<h2><a href="<?= Url::to(['/announcement/default/view', 'slug' => $announcementTranslation->slug]) ?>"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?></a></h2>
														<?php if ($announcement->company->name): ?>
															<p class="company"><a href="<?= Url::to(['/firm/default/view', 'slug' => $announcement->company->translation->slug]) ?>"><span class="fa fa-building-o"></span> <?= $announcement->company->name ?></a></p>
														<?php else: ?>
															<p class="company"><a href="<?= Url::to(['/individual/default/view', 'slug' => $announcement->creator->slug]) ?>"><span class="fa fa-user"></span> <?= $announcement->creator->shortName ?></a></p>
														<?php endif; ?>
														<?= StarRating::widget([
															'id' => 'rating-' . rand(),
															'name' => '',
															'value' => Announcement::getAnnouncementRating($announcement->id) ?: 0,
															'pluginOptions' => [
																'displayOnly' => true,
																'showCaption' => false,
																'showClear' => false,
																'size' => 'xxs',
																'min' => 0,
																'max' => 5,
																'stars' => 5,
																'step' => 1,
																'theme' => 'krajee-fa',
																'filledStar' => '<i class="fa fa-star" aria-hidden="true"></i>',
																'emptyStar' => '<i class="fa fa-star-o" aria-hidden="true"></i>'
															],
														]) ?>
														<?php if ($announcement->announcementHasActions[0]->price && $announcement->announcementHasActions[0]->currency): ?>
															<?php
															$content = [];
															if ($announcement->announcementHasActions[0]->price) {
																$content[] = $announcement->announcementHasActions[0]->price;
															}
															if ($announcement->announcementHasActions[0]->currency) {
																$content[0] = $content[0]. ' ' . $announcement->announcementHasActions[0]->currency;
															}
															if ($announcement->announcementHasActions[0]->uom) {
																$content[] = $announcement->announcementHasActions[0]->uom;
															}
															?>
															<p class="company"><?= implode(' / ', $content); ?></p>
														<?php else: ?>
															<p class="company">&nbsp;</p>
														<?php endif; ?>
													</div>
													<div class="figureFooter">
														<?php if ($announcement->county || $announcement->locality): ?>
															<?php $url = ['/announcement/default/index'];
															$content = [];
															if ($announcement->locality) {
																$content[] = $announcement->locality;
																$url['locality'] = $announcement->locality;
															}
															if ($announcement->county) {
																$content[] = $announcement->county;
																$url['county'] = $announcement->county;
															}
															?>

															<p class="advert-location">
																<a href="<?= Url::to($url) ?>">
																	<?= implode(', ', $content); ?>
																</a>
															</p>
														<?php else: ?>
															<?php if ($announcement->renewed_at || $announcement->created_at): ?>
																<p class="advert-location">
																	<?= Yii::$app->formatter->asDate($announcement->renewed_at ?: $announcement->created_at, 'dd, MMMM yyyy') ?>
																</p>
															<?php endif; ?>
														<?php endif; ?>
														<?php if ($announcement->renewed_at || $announcement->created_at): ?>
															<p class="advert-date">
																<?= Yii::$app->formatter->asDate($announcement->renewed_at ?: $announcement->created_at, 'dd, MMMM yyyy') ?>
															</p>
														<?php endif; ?>
													</div>
												</article>
											</div>
										<?php endforeach; ?>
									</div>
									<div class="paginationCommon blogPagination categoryPagination">
										<nav aria-label="Page navigation">
											<?= LinkPager::widget([
												'pagination' => $dataProvider->pagination,
												'maxButtonCount' => 5,
												'registerLinkTags' => true,
												'prevPageLabel' => '&lsaquo;',
												'nextPageLabel' => '&rsaquo;',
												'firstPageLabel' => '&laquo;',
												'lastPageLabel' => '&raquo;',
											]) ?>
										</nav>
									</div>
								</div>
							</div>
						<?php endif; ?>
						<?php if (($reviewsCount) > 0): ?>
							<div class="detailsInfoBox" id="reviews">
								<h3><?= Yii::t('label', 'Reviews') . ' (' . $reviewsCount .  ')' ?></h3>
								<?php foreach ($reviews as $review): ?>
									<div class="media media-comment">
										<div class="media-left">
											<img src="<?= $review->creator->imageUrl ?: Yii::getAlias('@web/img/img-placeholder-user.png') ?>" alt="<?= $review->creator->shortName ?>" class="media-object img-circle">
										</div>
										<div class="media-body">
											<h4 class="media-heading"><?= $review->creator->shortName ?></h4>
											<?= StarRating::widget([
												'id' => 'rating-' . rand(),
												'name' => '',
												'value' => $review->score,
												'pluginOptions' => [
													'displayOnly' => true,
													'showCaption' => false,
													'showClear' => false,
													'size' => 'xxs',
													'min' => 0,
													'max' => 5,
													'stars' => 5,
													'step' => 0.1,
													'theme' => 'krajee-fa',
													'filledStar' => '<span class="fa fa-star" aria-hidden="true"></span>',
													'emptyStar' => '<span class="fa fa-star-o" aria-hidden="true"></span>'
												],
											]) ?>
                                            <p><strong><?= $review->translation->title ?></strong></p>
                                            <p><?= $review->translation->content ?></p>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if (Yii::$app->user->id != $model->user->id): ?>
							<div class="detailsInfoBox" id="write-review" >
								<h3><?= Yii::t('common', 'Write a review') ?></h3>
								<?= $this->render('_review-form', [
									'reviewModel' => $reviewModel,
								]) ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

			</div>
		</div>
	</section>
<?php ActiveForm::end(); ?>

<?php if (!empty($model->user->phone)): ?>
<?php
	$phoneNumber = $model->user->phone;
	$js = <<<JS
		$(document).ready(function() {
		    $(".phone-button").one("click", function() {
				event.preventDefault();
				var phoneLink = $(".phone-link");
				var phoneAnchor = $(".phone-anchor");
				if (!phoneLink.attr("href").startsWith("tel:")) {
				    phoneLink.attr("href", "tel:$phoneNumber");
				    phoneAnchor.text("$phoneNumber");
				}
		    });
		});
	JS;
	$this->registerJs($js);
?>
<?php endif; ?>
