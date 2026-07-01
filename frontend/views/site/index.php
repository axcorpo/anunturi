<?php

/* @var $this \yii\web\View */
/* @var $romaniaMap array */

use common\helpers\DateHelper;
use common\helpers\FontIcon;
use common\models\Commercial;
use common\models\Auction;
use common\models\CarouselItem;
use kartik\rating\StarRating;
use tws\widgets\typeahead\Typeahead;
use yii\helpers\Html;
use common\helpers\Inflector;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

// Theme is now globally available via $this->params
$theme = $this->params['theme'];

?>

<h1 class="hidden"><?= $this->context->currentPage->translation->title ?></h1>
<?php if ($homepageCarousel = \common\models\Carousel::findDefaultCarousel(\common\models\Carousel::TYPE_MAIN)): ?>
    <!-- SLIDER SECTION -->
    <section class="main-slider homeBanner" data-loop="false" data-autoplay="true" data-interval="7000">
        <div class="inner owl-carousel">
            <?php foreach ($homepageCarousel->getCarouselItems()->where(['status' => CarouselItem::STATUS_ACTIVE])->all() as $carouselItem): ?>
                <?php /** @var $carouselItem CarouselItem */ ?>
                <?php $carouselItemTranslation = $carouselItem->getTranslation(); ?>
                <!-- Slide One -->
                <div class="slide slide1 item" style="background-image: url('<?= $carouselItem->getImageUrl() ?>');">
                    <div class="container">
                        <div class="slide-inner1 common-inner">
                            <?php if ($carouselItemTranslation->title): ?>
                                <span class="h1 from-bottom"><?= $carouselItemTranslation->title ?></span>
                            <?php endif; ?>
                            <?php if ($carouselItemTranslation->content): ?>
                                <span class="h2 from-bottom"><?= $carouselItemTranslation->content ?></span><br>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- SEARCH BROWSE SECTION -->

<div class="container">
    <div class="row">
        <div class="col-xs-12 ">
            <div class="banerInfo slider-search1">
                <?php $form = ActiveForm::begin([
                    'method' => 'GET',
                    'action' => ['/announcement/default/index'],
                    'options' => [
                        'class' => 'form-inline'
                    ],
                ]); ?>
                <div class="form-group home-search-input">
                    <div class="input-group">
                        <div class="input-group-addon"><?= Yii::t('frontend', 'I need') ?>?</div>
                        <?= Typeahead::widget([
                            'options' => [
                                'class' => 'form-control',
                                'type' => 'text',
                                'placeholder' => Yii::t('frontend', 'Products, materials, services'),
                                'autocomplete' => 'off',
                            ],
                            'name' => Inflector::slug(Yii::t('label', 'Search')),
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
												"q": query,
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
                        <div class="input-group-addon addon-right"></div>
                    </div>
                </div>
                <div class="form-group home-search-input">
                    <div class="input-group">
                        <div class="input-group-addon"><?= Yii::t('frontend', 'Where') ?>?</div>
                        <?= Typeahead::widget([
                            'options' => [
                                'class' => 'form-control',
                                'type' => 'text',
                                'placeholder' => Yii::t('frontend', 'Locality or county'),
                                'autocomplete' => 'off',
                            ],
                            'name' => Inflector::slug(Yii::t('label', 'Location')),
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
												"l": query,
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
                        <div class="input-group-addon addon-right"><i class="icon-listy icon-target" aria-hidden="true"></i></div>
                    </div>
                </div>
                <div class="form-group home-search-btn">
                    <?= Html::submitButton('<i class="fa fa-search" aria-hidden="true"></i>', ['class' => 'btn btn-primary', 'title' => Yii::t('frontend', 'Search')]) ?>
                </div>
                <div class="text-center">
                    <a class="btn btn-highlight" href="<?= Url::to(['/announcement/default/index']) ?>"><i class="fa fa-eye"></i> <?= Yii::t('frontend', 'All Announcements') ?></a>
                </div>
                <?php ActiveForm::end() ?>
            </div>
        </div>
    </div>
</div>

<?php if ($latestAnnouncements = \common\models\Announcement::findLatestAnnouncements(12)): ?>
	<!-- THINGS SECTION -->
	<section class="clearfix thingsArea">
		<div class="container">
			<div class="page-header text-center">
				<h2><?= Yii::t('frontend', 'The latest ads')?><small><?= Yii::t('frontend','On e-Construct you can find everything you need')?></small></h2>
			</div>
			<div class="row equal">
				<div class="col-xs-12">
					<div id="thubmnailSlider" class="carousel slide thumbnailCarousel">
						<ol class="carousel-indicators">
							<?php $i = 0; foreach ($latestAnnouncements as $announcement): ?>
								<li data-target="#thubmnailSlider" data-slide-to="<?= $i ?>" class="<?= $i == 0 ? 'active': '' ?>"></li>
								<?php $i++; endforeach; ?>
						</ol>
						<!-- Carousel items -->
						<div class="carousel-inner">
							<?php $index = rand(1, count($latestAnnouncements)); $i = 0; foreach ($latestAnnouncements as $announcement): ?>
								<?php $announcementTranslation = $announcement->translation; ?>
								<?php if ($index != $i || $i != 99): ?>
									<?php
									$actions = [];
									foreach ($announcement->actions as $action) {
										$actions[] = \common\models\Action::getMyTypes()[$action->type];
									}
									?>
									<div class="item row <?= $i == 0 ? 'active': '' ?>">
										<div class="col-md-4 col-sm-6 col-xs-12">
											<div class="thingsBox">
												<div class="thingsImage img-ratio">
													<?php if ($announcement->imageUrl): ?>
														<img class="img-ratio-object img-responsive" src="<?= $announcement->imageUrl; ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>">
													<?php else: ?>
														<img class="img-ratio-object img-responsive" src="<?= Yii::getAlias("@web/tpl/{$theme}/img/img-placeholder-blank.png") ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>">
													<?php endif; ?>
													<div class="thingsMask">
														<h2>
															<a href="<?= Url::to(['/announcement/default/view', 'slug' => $announcementTranslation->slug]) ?>" title="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>">
																<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>
															</a>
														</h2>
														<?php if ($announcement->company->name): ?>
															<p class="company">
																<a href="<?= Url::to(['/firm/default/view', 'slug' => $announcement->company->translation->slug]) ?>" title="<?= $announcement->company->name ?>"><span class="fa fa-building-o"></span> <?= $announcement->company->name ?></a>
															</p>
														<?php else: ?>
															<p class="company">
																<a href="<?= Url::to(['/individual/default/view', 'slug' => $announcement->creator->slug]) ?>" title="<?= $announcement->creator->shortName ?>"><span class="fa fa-user"></span> <?= $announcement->creator->shortName ?></a>
															</p>
														<?php endif; ?>
														<?php if ($announcement->county || $announcement->locality): ?>
															<?php $url = ['/announcement/default/index'];
															$content = [];
															if ($announcement->locality) {
																$content[] = $announcement->locality;
																$url['locality'] = Inflector::slug($announcement->locality);
															}
															if ($announcement->county) {
																$content[] = $announcement->county;
																$url['county'] = Inflector::slug($announcement->county);
															}
															?>
															<p>
																<a href="<?= Url::to($url) ?>" title="<?= !empty($content) ? Yii::t('frontend', 'Announcements') . ' ' . implode(', ', $content) : Yii::t('frontend', 'Announcements'); ?>">
																	<?= implode(', ', $content); ?>
																</a>
															</p>
														<?php endif; ?>
													</div>
												</div>
												<div class="thingsCaption">
													<ul class="list-inline captionItem">
														<li><i class="fa fa-eye" aria-hidden="true"></i> <?= $announcement->views; ?></li>
														<li><a href="<?= Url::to(['/announcement/default/index', 'category' => $announcement->categories[0]->translation->slug]) ?>"><?= $announcement->categories[0]->translation->name; ?></a></li>
													</ul>
												</div>
											</div>
										</div>
									</div>
								<?php else: ?>
									<?php
									$auction = Auction::findAuctionByPosition(Auction::POSITION_HOME_PAGE_300X300_CAROUSEL);
									?>
									<?php if ($auction->id): ?>
										<?php
										$commercial = Commercial::findCommercialByPosition(Auction::POSITION_HOME_PAGE_300X300_CAROUSEL);
										$auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
										$remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
										$progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
										?>
										<?php if ($commercial->image): ?>
											<div class="commercial-wrapper">
												<?php $banner = Html::img($commercial->imageUrl, [
													'class' => 'img-responsive center-block promo-harta',
													'alt' => '',
													'title' => '',
												]); ?>
												<?= Html::a($banner, $commercial->url, [
													'target' => '_blank',
												]) ?>
												<?php if ($progress < 100): ?>
													<div class="commercial-progress">
														<div class="progress-bar progress-bar-striped active" role="progressbar"
														     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
															<?= $progress ?>%
														</div>
													</div>
													<div class="badge-bottom-right">
														<?php $icon = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/bid.png"), [
															'alt' => Yii::t('frontend', 'Bid'),
															'title' => Yii::t('frontend', 'Bid'),
														]); ?>
														<?= Html::a($icon, ['/account/bid/create', 'auction_id' => $auction->id], [
															'data' => [
																'toggle' => 'tooltip',
																'popup-action' => '',
															]
														]) ?>
													</div>
												<?php endif; ?>
											</div>
										<?php else: ?>
											<div class="commercial-wrapper">
												<?php $commercial = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/commercials/300x300.jpg"), [
													'class' => 'img-responsive center-block promo-harta',
													'alt' => '',
													'title' => '',
												]); ?>
												<?php if ($progress < 100): ?>
													<?= Html::a($commercial, ['/account/bid/create', 'auction_id' => $auction->id], [
														'data' => [
															'toggle' => 'tooltip',
															'popup-action' => '',
														]
													]) ?>
													<div class="commercial-progress">
														<div class="progress-bar progress-bar-striped active" role="progressbar"
														     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
															<?= $progress ?>%
														</div>
													</div>
												<?php else: ?>
													<?= $commercial; ?>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									<?php endif; ?>
								<?php endif; ?>
								<?php $i++; endforeach; ?>
						</div>
						<a class="left carousel-control" href="#thubmnailSlider" data-slide="prev"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
						<a class="right carousel-control" href="#thubmnailSlider" data-slide="next"><i class="fa fa-angle-right" aria-hidden="true"></i></a>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="clearfix thingsArea">
    <div class="container">
        <div class="row">
            <div class="col-sm-3 col-xs-12 text-center">
                <?php
                $auction = Auction::findAuctionByPosition(Auction::POSITION_HOME_PAGE_300X440_LEFT);
                ?>
                <?php if ($auction->id): ?>
                    <?php
	                    $commercial = Commercial::findCommercialByPosition(Auction::POSITION_HOME_PAGE_300X440_LEFT);
	                    $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
	                    $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
	                    $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                    ?>
                    <?php if ($commercial->image): ?>
                        <div class="commercial-wrapper">
                            <?php $banner = Html::img($commercial->imageUrl, [
                                'class' => 'img-responsive center-block promo-harta',
                                'alt' => '',
                                'title' => '',
                            ]); ?>
                            <?= Html::a($banner, $commercial->url, [
                                'target' => '_blank',
                            ]) ?>
                            <?php if ($progress < 100): ?>
                                <div class="commercial-progress">
                                    <div class="progress-bar progress-bar-striped active" role="progressbar"
                                         aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
                                        <?= $progress ?>%
                                    </div>
                                </div>
                                <div class="badge-bottom-right">
                                    <?php $icon = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/bid.png"), [
                                        'alt' => Yii::t('frontend', 'Bid'),
                                        'title' => Yii::t('frontend', 'Bid'),
                                    ]); ?>
                                    <?= Html::a($icon, ['account/bid/create', 'auction_id' => $auction->id], [
                                        'data' => [
                                            'toggle' => 'tooltip',
                                            'popup-action' => '',
                                            'popup-done' => '',
                                        ]
                                    ]) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="commercial-wrapper">
                            <?php $commercial = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/commercials/300x440.jpg"), [
                                'class' => 'img-responsive center-block promo-harta',
                                'alt' => '',
                                'title' => '',
                            ]); ?>
                            <?php if ($progress < 100): ?>
                                <?= Html::a($commercial, ['/account/bid/create', 'auction_id' => $auction->id], [
                                    'data' => [
                                        'toggle' => 'tooltip',
                                        'popup-action' => '',
                                    ]
                                ]) ?>
                                <div class="commercial-progress">
                                    <div class="progress-bar progress-bar-striped active" role="progressbar"
                                         aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
                                        <?= $progress ?>%
                                    </div>
                                </div>
                            <?php else: ?>
                                <?= $commercial; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="col-sm-6 col-xs-12 harta-judete">
                <div class="page-header text-center">
                    <h2><?= Yii::t('frontend','Choose your county') ?><small><?= Yii::t('frontend','See all ads in the area')?></small></h2>
                    <?= \common\widgets\jsmaps\JsMaps::widget([
                        'id' => 'jsmaps-romania',
                        'maps' => [
                            'romania' => $romaniaMap,
                        ],
                        'clientOptions' => [
                            'map' => 'romania',
                            'displayAbbreviations' => true,
                            'displayAbbreviationOnDisabledStates' => false,
                            'autoPositionAbbreviations' => false,
                            'stateClickAction' => 'none',
                            'preloaderText' => Yii::t('common', 'Loading map...'),
                            'disableTooltip' => false,
                            'selectElement' => false,
                            'onStateClick' => new \yii\web\JsExpression('function (data) {
								window.location.href = "' . Url::to(['/announcement/default/index', 'county' => '/']) . '" + data.slug;
							}'),
                        ],
                    ]) ?>
                </div>
            </div>
	        <div class="col-sm-3 col-xs-12 text-center">
		        <?php
		        $auction = Auction::findAuctionByPosition(Auction::POSITION_HOME_PAGE_300X440_RIGHT);
		        ?>
		        <?php if ($auction->id): ?>
			        <?php
			        $commercial = Commercial::findCommercialByPosition(Auction::POSITION_HOME_PAGE_300X440_RIGHT);
			        $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
			        $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
			        $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
			        ?>
			        <?php if ($commercial->image): ?>
				        <div class="commercial-wrapper">
					        <?php $banner = Html::img($commercial->imageUrl, [
						        'class' => 'img-responsive center-block promo-harta',
						        'alt' => '',
						        'title' => '',
					        ]); ?>
					        <?= Html::a($banner, $commercial->url, [
						        'target' => '_blank',
					        ]) ?>
					        <?php if ($progress < 100): ?>
						        <div class="commercial-progress">
							        <div class="progress-bar progress-bar-striped active" role="progressbar"
							             aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
								        <?= $progress ?>%
							        </div>
						        </div>
						        <div class="badge-bottom-right">
							        <?php $icon = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/bid.png"), [
								        'alt' => Yii::t('frontend', 'Bid'),
								        'title' => Yii::t('frontend', 'Bid'),
							        ]); ?>
							        <?= Html::a($icon, ['account/bid/create', 'auction_id' => $auction->id], [
								        'data' => [
									        'toggle' => 'tooltip',
									        'popup-action' => '',
									        'popup-done' => '',
								        ]
							        ]) ?>
						        </div>
					        <?php endif; ?>
				        </div>
			        <?php else: ?>
				        <div class="commercial-wrapper">
					        <?php $commercial = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/commercials/300x440.jpg"), [
						        'class' => 'img-responsive center-block promo-harta',
						        'alt' => '',
						        'title' => '',
					        ]); ?>
					        <?php if ($progress < 100): ?>
						        <?= Html::a($commercial, ['/account/bid/create', 'auction_id' => $auction->id], [
							        'data' => [
								        'toggle' => 'tooltip',
								        'popup-action' => '',
							        ]
						        ]) ?>
						        <div class="commercial-progress">
							        <div class="progress-bar progress-bar-striped active" role="progressbar"
							             aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
								        <?= $progress ?>%
							        </div>
						        </div>
					        <?php else: ?>
						        <?= $commercial; ?>
					        <?php endif; ?>
				        </div>
			        <?php endif; ?>
		        <?php endif; ?>
	        </div>
        </div>
    </div>
</section>

<!-- INTEREST SECTION -->
<section class="clearfix interestArea">
    <div class="container">
        <div class="row">
            <?php $categories = \common\models\Category::findMainCategories(); ?>
            <?php $i = 0; foreach ($categories as $category): ?>
            <?php if ($i && $i % 3 == 0): ?>
        </div>
        <div class="row">
            <?php endif; ?>
            <div class="col-sm-4 col-xs-12" style="margin-top:15px;">
                <a href="<?= URL::to(['/announcement/default/index', 'category' => $category->translation->slug]) ?>" class="interestContent" style="background-image: url('<?= $category->imageUrl ?: Yii::getAlias("@web/tpl/{$theme}/img/img-placeholder-blank.png") ?>'); background-size: cover; color: #fff;">
                    <div class="services-overlay"></div>
                    <?php if ($category->icon): ?>
                        <?= FontIcon::render($category->icon, ['style' => 'z-index: 11; font-size: 2em;' . ($category->imageUrl ? '' : 'color: #000;')]) ?>
                    <?php endif; ?>
                    <span <?= $category->icon ? 'style="z-index: 11; margin: 30px 0 0 0; ' . ($category->imageUrl ? '' : 'color: #000;') . '"' : '"' ?>>
						<?= $category->translation->name ?>
					</span>
                </a>
            </div>
            <?php $i++; endforeach; ?>
        </div>
    </div>
</section>

<?php
$auction = Auction::findAuctionByPosition(Auction::POSITION_HOME_PAGE_1030X120_BOTTOM);
?>
<?php if ($auction->id): ?>
    <!-- INTEREST SECTION -->
    <section class="clearfix interestArea home-services">
        <div class="container">
            <div class="row">
                <div class="col-sm-12 col-xs-12 text-center">
                    <?php
                    $commercial = Commercial::findCommercialByPosition(Auction::POSITION_HOME_PAGE_1030X120_BOTTOM);
                    $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
                    $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
                    $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                    ?>
                    <?php if ($commercial->image): ?>
                    <div class="commercial-wrapper">
                        <?php $banner = Html::img($commercial->imageUrl, [
                            'class' => 'img-responsive center-block',
                            'alt' => '',
                            'title' => '',
                        ]); ?>
                        <?= Html::a($banner, $commercial->url, [
                            'target' => '_blank',
                        ]) ?>
                        <?php if ($progress < 100): ?>
                            <div class="commercial-progress">
                                <div class="progress-bar progress-bar-striped active" role="progressbar"
                                     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
                                    <?= $progress ?>%
                                </div>
                            </div>
                            <div class="badge-bottom-right">
                                <?php $icon = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/bid.png"), [
                                    'alt' => Yii::t('frontend', 'Bid'),
                                    'title' => Yii::t('frontend', 'Bid'),
                                ]); ?>
                                <?= Html::a($icon, ['/account/bid/create', 'auction_id' => $auction->id], [
                                    'data' => [
                                        'toggle' => 'tooltip',
                                        'popup-action' => '',
                                    ]
                                ]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                    <div class="commercial-wrapper">
                        <?php $banner = Html::img(Yii::getAlias("@web/tpl/{$theme}/img/commercials/1030x120.jpg"), [
                            'class' => 'img-responsive center-block',
                            'alt' => '',
                            'title' => '',
                        ]); ?>
                        <?php if ($progress < 100): ?>
                            <?= Html::a($banner, ['/account/bid/create', 'auction_id' => $auction->id], [
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'popup-action' => '',
                                ]
                            ]) ?>
                            <div class="commercial-progress">
                                <div class="progress-bar progress-bar-striped active" role="progressbar"
                                     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progress ?>%">
                                    <?= $progress ?>%
                                </div>
                            </div>
                        <?php else: ?>
                            <?= $banner; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
	$countAnnouncements = \common\models\Announcement::provideAnnouncements()->orWhere(['IS NOT', 'p.id', null])->count();
	$countCompanies = \common\models\Company::find()->active(true)->deleted(false)->count();
	$countUsers = \common\models\User::find()->active(true)->deleted(false)->count();
?>
<?php if ($countAnnouncements > 30 && $countCompanies > 10 && $countUsers > 10): ?>
	<!-- COUNT UP SECTION -->
	<section class="clearfix countUpSection">
		<div class="container">
			<div class="page-header text-center">
				<h2><?= Yii::t('frontend', 'We are constantly growing')?></h2>
			</div>
			<div class="row">
				<div class="col-sm-4 col-xs-12">
					<div class="text-center countItem">
						<div class="counter"><?= $countAnnouncements ?></div>
						<div class="counterInfo bg-color-1"><?= Yii::t('frontend', 'Announcements') ?></div>
					</div>
				</div>
				<div class="col-sm-4 col-xs-12">
					<div class="text-center countItem">
						<div class="counter"><?= $countCompanies ?></div>
						<div class="counterInfo bg-color-2"><?= Yii::t('frontend', 'Companies') ?></div>
					</div>
				</div>
				<div class="col-sm-4 col-xs-12">
					<div class="text-center countItem">
						<div class="counter"><?= $countUsers ?></div>
						<div class="counterInfo bg-color-3"><?= Yii::t('frontend', 'Users') ?></div>
					</div>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- COUNT UP SECTION -->
<section class="clearfix countUpSection">
    <div class="container">
        <div class="page-header text-center">
            <h2><?= Yii::t('frontend', 'Subscribe to newsletter')?></h2>
        </div>
        <div class="row">
            <div class="col-sm-1"></div>
            <div class="col-sm-10 text-center">
                <?php $form = ActiveForm::begin([
                    'id' => mb_strtolower($newsletterModel->formName()),
                    'options' => [
                        'novalidate' => true,
                        'class' => 'form-inline margin-clear',
                        'autocomplete' => 'off'
                    ],
                    'validateOnType' => true,
                    'validateOnBlur' => false,
                ]); ?>
                <div class="form-group has-feedback">
                    <?= $form->field($newsletterModel, 'last_name')->textInput([
                        'class' => 'form-control form-control-lg',
                        'placeholder' => Yii::t('label', 'Last Name'),
                    ])->label(false) ?>
                    <?= $form->field($newsletterModel, 'first_name')->textInput([
                        'class' => 'form-control form-control-lg',
                        'placeholder' => Yii::t('label', 'First Name'),
                    ])->label(false) ?>
                    <?= $form->field($newsletterModel, 'email')->textInput([
                        'class' => 'form-control form-control-lg',
                        'placeholder' => Yii::t('label', 'Email'),
                    ])->label(false) ?>
                </div>
                <?= $form->field($newsletterModel, 'workEmail', [
                    'options' => [
                        'class' => 'work-email',
                    ],
                    'template' => '{input}',
                ])->textarea(['required' => 'required'])->label(false) ?>
                <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                    <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                    <?= $form->field($newsletterModel, 'captchaResponse', [
                        'template' => '{input}',
                    ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                <?php endif; ?>
                <?= Html::submitButton(Yii::t('common', 'Send') . ' <i class="fa fa-send"></i>', [
                    'class' => 'btn btn-primary',
                    'name' => 'newsletter-button',
                    'style' => 'border: 0;'
                ]) ?>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="col-sm-1"></div>
        </div>
    </div>
</section>

<!-- WORKS SECTION -->
<section class="clearfix worksArea">
    <div class="container">
        <div class="row equal">
            <div class="col-sm-4 col-xs-12">
                <div class="thumbnail text-center worksContent">
                    <img src="<?= Yii::getAlias("@web/tpl/{$theme}/img/works/works-1.png") ?>" alt="Image works">
                    <div class="caption">
                        <a href="<?= Url::to(['/site/signup']) ?>" title="<?= Yii::t('common','Sign Up') ?>"><h3><?= Yii::t('frontend','Create an account today') ?></h3></a>
                        <p><?= Yii::t('frontend','You will have quick access to all construction ads.')?></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="thumbnail text-center worksContent">
                    <img src="<?= Yii::getAlias("@web/tpl/{$theme}/img/works/works-2.png") ?>" alt="Image works">
                    <div class="caption">
                        <a href="<?= Url::to(['/announcement/default/index']) ?>" title="<?= Yii::t('frontend','Announcements') ?>""><h3><?= Yii::t('frontend','Find after location')?></h3></a>
                        <p><?= Yii::t('frontend','Find the products and services you need near you') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="thumbnail text-center worksContent">
                    <img src="<?= Yii::getAlias("@web/tpl/{$theme}/img/works/works-3.png") ?>" alt="Image works">
                    <div class="caption">
                        <a href="<?= Url::to(['/account/announcement/create']) ?>" title="<?= Yii::t('frontend','Add Announcement') ?>"><h3><?= Yii::t('frontend', 'Promote your business') ?></h3></a>
                        <p><?= Yii::t('frontend','Add your products, services and you will be open to customers easily and quickly') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CALL TO ACTION SECTION -->
<section class="clearfix callAction">
    <div class="container">
        <div class="row">
            <div class="col-md-9 col-sm-8 col-xs-12">
                <div class="callInfo">
                    <h4><span><?= Yii::$app->name ?></span> <?= Yii::t('frontend','is')?><span> <?= Yii::t('frontend', 'best place') ?></span> <br><?= Yii::t('frontend', 'to promote your business and services') ?></h4>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <div class="btnArea">
                    <?= Html::a('+ ' . Yii::t('common', 'Add Announcement') , ['/account/announcement/create'], [
                        'class' => 'btn btn-primary btn-block',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    $this->registerJs('
        $( "#' . Html::getInputId($newsletterModel, 'workEmail') . '").removeAttr("required");
    ');
?>
