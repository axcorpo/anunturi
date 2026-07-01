<?php

/* @var $this \yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $tags array */
/* @var $modelFilterForm \frontend\modules\announcement\models\FilterForm */



use common\helpers\DateHelper;
use common\models\Announcement;
use common\models\Auction;
use common\models\Category;
use common\models\Commercial;
use common\models\Field;
use kartik\rating\StarRating;
use tws\widgets\typeahead\Typeahead;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use common\helpers\Inflector;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;
use yii\widgets\Breadcrumbs;
use yii\widgets\LinkPager;

if (Yii::$app->request->get('category') || Yii::$app->request->get('county')) {
	$this->params['breadcrumbs'][] = [
		'label' => Yii::t('frontend', 'Announcements'),
		'url' => Url::to(['/announcement/default/index']),
	];
	if (Yii::$app->request->get('category')) {
		$category = Category::findCategoryBySlug(Yii::$app->request->get('category'));
		if (!empty($category)) {
			$current = $category->id;
		}
	}
}
$this->params['breadcrumbs'][] = Html::encode($this->title);
$tags = [];
?>
<!-- CATEGORY SEARCH SECTION -->
<section class="clearfix searchArea banerInfo searchAreaGray">
    <form>
        <div class="container">
            <div class="row search-row">
                <?php $form = ActiveForm::begin([
                    'method' => 'GET',
                    'action' => ['/announcement/default/index'],
                    'options' => [
                        'class' => 'form-inline'
                    ],
                ]); ?>
                <div class="col-md-5 col-sm-12 col-xs-12 form-col">
                    <div class="form-group">
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
                                'value' => Yii::$app->request->get()[Inflector::slug(Yii::t('label', 'Search'))],
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
                </div>
                <div class="col-md-5 col-sm-12 col-xs-12 form-col">
                    <div class="form-group">
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
                                'value' => Yii::$app->request->get()[Inflector::slug(Yii::t('label', 'Location'))],
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
                </div>
                <div class="col-md-2 col-sm-12 col-xs-12 form-col">
                    <?= Html::submitButton('<i class="fa fa-search" aria-hidden="true"></i>', ['class' => 'btn btn-primary', 'title' => Yii::t('frontend', 'Search'), 'data' => ['toggle' => 'tooltip']]) ?>
                </div>
                <?php ActiveForm::end() ?>
            </div>
        </div>
    </form>
</section>


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

<!-- CATEGORY LIST SECTION -->
<section class="clearfix" style="padding: 0 0 20px 0;">
    <div class="container">
        <div class="row">
            <div class="col-sm-3 col-xs-12">
	            <?php if ($categories = \common\models\Category::findAllCategories()): ?>
		            <div class="sidebarInner sidebarCategory">
			            <div class="panel panel-default" id="categoriesAccordion">
				            <div class="panel-heading categories-panel-heading" data-target="#categories" data-toggle="collapse" role="button">
					            <?= Yii::t('frontend', 'Categories'); ?> <span class="fa fa-list pull-right" style="margin: 20px 15px 0 0;"></span>
				            </div>
				            <div class="panel-collapse" id="categories">
					            <div class="panel-body">
						            <nav class="treeview">
							            <?php
							            $params = [];
							            if (Yii::$app->request->get('page')) {
								            $params = array_merge($params, ['page' => Yii::$app->request->get('page')]);
							            }
							            if (Yii::$app->request->get('search')) {
								            $params = array_merge($params, ['search' => Yii::$app->request->get('search')]);
							            }
							            if (Yii::$app->request->get('location')) {
								            $params = array_merge($params, ['location' => Yii::$app->request->get('location')]);
							            }
							            if (Yii::$app->request->get('county')) {
								            $params = array_merge($params, ['county' => Yii::$app->request->get('county')]);
							            }
							            ?>
							            <ul class="list-unstyle categoryList">
								            <?= Category::treeview(null, $current, Category::STATUS_ACTIVE, $params) ?>
							            </ul>
						            </nav>
					            </div>
				            </div>
			            </div>
		            </div>
	            <?php endif; ?>
                <?php
                $auction = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_SIDEBAR_TOP);
                ?>
                <?php if ($auction->id): ?>
                    <div class="sidebarInner sidebarCategory">
                        <div class="panel panel-default">
                            <?php
                            $commercial = Commercial::findCommercialByPosition(Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_SIDEBAR_TOP);
                            $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
                            $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
                            $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                            ?>
                            <?php if ($commercial->image && is_file(Yii::getAlias("@uploads/commercial/{$commercial->id}/{$commercial->image}"))): ?>
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
                                            <?php $icon = Html::img(Yii::getAlias('@web/img/bid.png'), [
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
                                    <?php $commercial = Html::img(Yii::getAlias('@web/img/commercials/300x440.jpg'), [
                                        'class' => 'img-responsive center-block',
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
                        </div>
                    </div>
                <?php endif; ?>
                <div class="sidebarInner sidebarCategory" style="margin: 10px 0 15px 0 !important;">
                    <div class="panel panel-default" id="countiesAccordion">
                        <div class="panel-heading" style="background-color: #ffa019; padding: 0 0 15px 15px; color: #FFFF;" data-target="#counties" data-toggle="collapse" role="button">
                            <?= Yii::t('frontend', 'Counties'); ?> <span class="fa fa-map-signs pull-right" style="margin: 20px 15px 0 0;"></span>
                        </div>
                        <div class="panel-collapse collapse" id="counties">
                        <div class="panel-body">
                            <ul class="list-unstyle categoryList">
                                <?php foreach (\common\models\County::findAllCounties() as $county): ?>
                                    <?php
                                    $url = ['/announcement/default/index', 'county' => Inflector::slug($county->name)];
                                    if (Yii::$app->request->get('category')) {
                                        $url = array_merge($url, ['category' => Yii::$app->request->get('category')]);
                                    }
                                    ?>
                                    <li><a href="<?= Url::to($url) ?>"><?= $county->name ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        </div>
                    </div>
                </div>
				<?= $this->render('_fields', [
					'form' => $form,
					'modelFilterForm' => $modelFilterForm,
				]) ?>
                <?php
                $auction = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_300X300_SIDEBAR_BOTTOM);
                ?>
                <?php if ($auction->id): ?>
                    <div class="sidebarInner sidebarCategory price-form">
                        <div class="panel panel-default">
                            <?php
                            $commercial = Commercial::findCommercialByPosition(Auction::POSITION_ANNOUNCEMENTS_PAGE_300X300_SIDEBAR_BOTTOM);
                            $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
                            $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
                            $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                            ?>
                            <?php if ($commercial->image && is_file(Yii::getAlias("@uploads/commercial/{$commercial->id}/{$commercial->image}"))): ?>
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
                                            <?php $icon = Html::img(Yii::getAlias('@web/img/bid.png'), [
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
                                    <?php $commercial = Html::img(Yii::getAlias('@web/img/commercials/300x440.jpg'), [
                                        'class' => 'img-responsive center-block',
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-sm-9 col-xs-12">
                <div class="listContent" style="padding: 10px 0 0 0;">
                    <?php if ($promotionalData || $dataProvider->getModels()): ?>
                        <div class="row">
                        <?php $i = 1; foreach ($promotionalData as $announcement) : ?>
                            <?php
                            $actions = [];
                            foreach ($announcement->actions as $action) {
                                $actions[] = \common\models\Action::getMyTypes()[$action->type];
                            }
                            ?>
                            <?php /** @var common\models\Announcement $announcement */ ?>
                            <?php $announcementTranslation = $announcement->translation; ?>
                            <?php $tags = array_merge($tags, explode(',', $announcementTranslation->keywords)); ?>
                            <div class="col-md-4 col-sm-6 col-xs-12 isotopeSelector">
                                <article class="promovat">
                                    <figure>
                                        <div class="advert-img img-ratio">
                                            <?php if ($announcement->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$announcement->id}/{$announcement->image}"))): ?>
                                                <img src="<?= $announcement->imageUrl; ?>" alt="<?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive">
                                            <?php else: ?>
                                                <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $announcementTranslation->title ?>" class="img-responsive">
                                            <?php endif; ?>
                                        </div>
                                        <div class="overlay-background">
                                            <div class="inner"></div>
                                        </div>
                                        <a href="<?= Url::to(['view', 'slug' => $announcementTranslation->slug]) ?>">
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
                                            <p class="company"><a href="<?= Url::to(['/individual/default/view', 'slug' => $announcement->creator->slug]) ?>"><span class="fa fa-user"> </span> <?= $announcement->creator->shortName ?></a></p>
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
													$url['locality'] = Inflector::slug($announcement->locality);
												}
												if ($announcement->county) {
													$content[] = $announcement->county;
													$url['county'] = Inflector::slug($announcement->county);
												}
                                            ?>

                                            <p class="advert-location">
                                                <a href="<?= Url::to($url) ?>" title="<?= !empty($content) ? Yii::t('frontend', 'Announcements') . ' ' . implode(', ', $content) : Yii::t('frontend', 'Announcements'); ?>">
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
                            <?php $i++; endforeach; ?>

                        <?php $j = $i; foreach ($dataProvider->getModels() as $announcement) : ?>
                            <?php
                            $actions = [];
                            foreach ($announcement->actions as $action) {
                                $actions[] = \common\models\Action::getMyTypes()[$action->type];
                            }
                            ?>
                            <?php /** @var common\models\Announcement $announcement */ ?>
                            <?php $announcementTranslation = $announcement->translation; ?>
                            <?php $tags = array_merge($tags, explode(',', $announcementTranslation->keywords)); ?>
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
                                        <a href="<?= Url::to(['view', 'slug' => $announcementTranslation->slug]) ?>">
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
                                            <p class="company">
												<a href="<?= Url::to(['/firm/default/view', 'slug' => $announcement->company->translation->slug]) ?>" title="<?= $announcement->company->name ?>"><span class="fa fa-building-o"></span> <?= $announcement->company->name ?></a>
											</p>
                                        <?php else: ?>
                                            <p class="company">
												<a href="<?= Url::to(['/individual/default/view', 'slug' => $announcement->creator->slug]) ?>" title="<?= $announcement->creator->shortName ?>"><span class="fa fa-user"></span> <?= $announcement->creator->shortName ?></a>
											</p>
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
                                        <?php if ($announcement->currency && $announcement->price > 0): ?>
                                            <h3><?= Yii::$app->formatter->asCurrency($announcement->price, $announcement->currency) ?></h3>
                                        <?php else: ?>
                                            <h3>&nbsp;</h3>
                                        <?php endif; ?>
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
                            <?php if ($j == 5): ?>
                                <?php
                                $auction = $auctionListTop;
                                ?>
                                <?php if ($auction->id): ?>
                                    <div class="col-md-4 col-sm-6 col-xs-12">
                                        <article class="promo-article announcement-promo">
                                            <?php
                                            $commercial = Commercial::findCommercialByPosition(Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_TOP);
                                            $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
                                            $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
                                            $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                                            ?>
                                            <?php if ($commercial->image && is_file(Yii::getAlias("@uploads/commercial/{$commercial->id}/{$commercial->image}"))): ?>
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
                                                            <?php $icon = Html::img(Yii::getAlias('@web/img/bid.png'), [
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
                                                    <?php $commercial = Html::img(Yii::getAlias('@web/img/commercials/300x440.jpg'), [
                                                        'class' => 'img-responsive center-block',
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
                                        </article>
                                    </div>
                                    </div>
                                    <div class="row">
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($j == 8): ?>
                                <?php
                                $auction = $auctionListMiddle;
                                ?>
                                <?php if ($auction->id): ?>
                                    <div class="col-sm-12 col-xs-12">
                                        <?php
                                        $commercial = Commercial::findCommercialByPosition(Auction::POSITION_ANNOUNCEMENTS_PAGE_1030X120_LIST_MIDDLE);
                                        $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
                                        $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
                                        $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                                        ?>
                                        <?php if ($commercial->image && is_file(Yii::getAlias("@uploads/commercial/{$commercial->id}/{$commercial->image}"))): ?>
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
                                                        <?php $icon = Html::img(Yii::getAlias('@web/img/bid.png'), [
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
                                                <?php $commercial = Html::img(Yii::getAlias('@web/img/commercials/1030x120.jpg'), [
                                                    'class' => 'img-responsive center-block',
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
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($j == 8 && count($promotionalData) + count($dataProvider->getModels()) >= 9): ?>
                                <?php
                                $auction = $auctionListBottom;
                                ?>
                                <?php if ($auction->id): ?>
                                    <div class="col-md-4 col-sm-6 col-xs-12">
                                        <article class="promo-article announcement-promo">
                                            <?php
                                            $commercial = Commercial::findCommercialByPosition(Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_BOTTOM);
                                            $auctionDays = (new DateTime(DateHelper::formatAsDateTime($auction->end_at, '+1 day')))->diff(new DateTime($auction->start_at))->d;
                                            $remainingDays = (new DateTime(date('Y-m-d H:i:s')))->diff(new DateTime($auction->start_at))->d;
                                            $progress = $auctionDays ? number_format($remainingDays / $auctionDays * 100, 2, '.', '') : 0;
                                            ?>
                                            <?php if ($commercial->image && is_file(Yii::getAlias("@uploads/commercial/{$commercial->id}/{$commercial->image}"))): ?>
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
                                                            <?php $icon = Html::img(Yii::getAlias('@web/img/bid.png'), [
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
                                                    <?php $commercial = Html::img(Yii::getAlias('@web/img/commercials/300x440.jpg'), [
                                                        'class' => 'img-responsive center-block',
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
                                        </article>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php $j++; endforeach; ?>
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
                    <?php else: ?>
                        <br>
                        <div class="alert alert-info mt-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
