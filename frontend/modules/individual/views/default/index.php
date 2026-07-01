<?php
/* @var $this \yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use common\helpers\FontIcon;
use common\models\Announcement;
use common\models\Subscriber;
use kartik\rating\StarRating;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use yii\widgets\LinkPager;
$this->params['breadcrumbs'][] = Html::encode($this->title);
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
<section class="clearfix">
    <div class="container">
        <div class="row">
            <?php if ($individuals = $dataProvider->getModels()): ?>
            <?php $individualsCount = $dataProvider->getCount(); ?>
            <div class="col-sm-12 col-xs-12">
                <div class="listContent">
                    <div class="row">
                        <?php foreach ($individuals as $individual): ?>
                            <?php
                            /** @var common\models\Subscriber $individual */
                            $slug = $individual->user->slug;
                            ?>
                            <div class="col-md-3 col-sm-6 col-xs-12 isotopeSelector">
                                <article>
                                    <figure>
                                        <div class="advert-img img-ratio">
                                            <?php if ($individual->user->imageUrl && is_file(Yii::getAlias("@uploads/user/{$individual->user->id}/{$individual->user->image}"))): ?>
                                                <img src="<?= $individual->user->imageUrl; ?>" alt="<?= $slug ?>" class="img-ratio-object img-responsive">
                                            <?php else: ?>
                                                <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $slug ?>" class="img-responsive">
                                            <?php endif; ?>
                                        </div>
                                        <div class="overlay-background">
                                            <div class="inner"></div>
                                        </div>
                                        <a href="<?= Url::to(['/individual/default/view', 'slug' => $slug]) ?>">
                                            <div class="overlay">
                                                <div class="overlayInfo">
													<span class="label label-primary"><i class="fa fa-eye" aria-hidden="true"></i> <?= $individual->views; ?></span>
													<span class="label label-share"><i class="fa fa-hand-pointer-o" aria-hidden="true"></i> <?= $individual->visits; ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </figure>
                                    <div class="figureBody">
                                        <h2><a href="<?= Url::to(['/individual/default/view', 'slug' => $slug]) ?>"><?= $individual->user->shortName ?></a></h2>
                                        <?= StarRating::widget([
                                            'id' => 'rating-' . rand(),
                                            'name' => '',
                                            'value' => Subscriber::getSubscriberRating($individual->id) ?: 0,
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
                                    </div>
                                    <div class="figureFooter">
                                        <?php if ($individual->county || $individual->locality): ?>
                                            <?php $url = ['/individual/default/index'];
                                            $content = [];
                                            if ($individual->locality) {
                                                $content[] = $individual->locality;
                                                $url['locality'] = $individual->locality;
                                            }
                                            if ($individual->county) {
                                                $content[] = $individual->county;
                                                $url['county'] = $individual->county;
                                            }
                                            ?>

                                            <p class="advert-location">
                                                <a href="<?= Url::to($url) ?>">
                                                    <?= implode(', ', $content); ?>
                                                </a>
                                            </p>
                                        <?php else: ?>
                                            <?php if ($individual->created_at): ?>
                                                <p class="advert-location">
                                                    <?= Yii::$app->formatter->asDate($individual->created_at, 'dd, MMMM yyyy') ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($individual->created_at): ?>
                                            <p class="advert-date">
                                                <?= Yii::$app->formatter->asDate($individual->created_at, 'dd, MMMM yyyy') ?>
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
                    <?php else: ?>
                        <br>
                        <div class="alert alert-info mt-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
