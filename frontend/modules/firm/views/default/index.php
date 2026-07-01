<?php
/* @var $this \yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use common\helpers\FontIcon;
use common\models\Announcement;
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
            <?php if ($companies = $dataProvider->getModels()): ?>
            <?php $companiesCount = $dataProvider->getCount(); ?>
            <div class="col-sm-12 col-xs-12">
                <div class="listContent">
                    <div class="row">
                        <?php foreach ($companies as $company): ?>
                            <?php
                            /** @var common\models\Company $company */
                            $companyTranslation = $company->getTranslation();
                            $slug = $companyTranslation->slug;
                            ?>
                            <div class="col-md-3 col-sm-6 col-xs-12 isotopeSelector">
                                <article>
                                    <figure>
                                        <div class="advert-img img-ratio">
                                            <?php if ($company->imageUrl && is_file(Yii::getAlias("@uploads/company/{$company->id}/{$company->image}"))): ?>
                                                <img src="<?= $company->imageUrl; ?>" alt="<?= $slug ?>" class="img-ratio-object img-responsive">
                                            <?php else: ?>
                                                <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $slug ?>" class="img-responsive">
                                            <?php endif; ?>
                                        </div>
                                        <div class="overlay-background">
                                            <div class="inner"></div>
                                        </div>
                                        <a href="<?= Url::to(['/firm/default/view', 'slug' => $slug]) ?>">
                                            <div class="overlay">
                                                <div class="overlayInfo">
													<span class="label label-primary"><i class="fa fa-eye" aria-hidden="true"></i> <?= $company->views; ?></span>
													<span class="label label-share"><i class="fa fa-hand-pointer-o" aria-hidden="true"></i> <?= $company->visits; ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </figure>
                                    <div class="figureBody">
                                        <h2><a href="<?= Url::to(['/firm/default/view', 'slug' => $slug]) ?>"><?= $company->name ?></a></h2>
                                        <?= StarRating::widget([
                                            'id' => 'rating-' . rand(),
                                            'name' => '',
                                            'value' => \common\models\Company::getCompanyRating($company->id) ?: 0,
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
                                        <?php if ($company->county || $company->locality): ?>
                                            <?php $url = ['/firm/default/index'];
                                            $content = [];
                                            if ($company->locality) {
                                                $content[] = $company->locality;
                                                $url['locality'] = $company->locality;
                                            }
                                            if ($company->county) {
                                                $content[] = $company->county;
                                                $url['county'] = $company->county;
                                            }
                                            ?>

                                            <p class="advert-location">
                                                <a href="<?= Url::to($url) ?>">
                                                    <?= implode(', ', $content); ?>
                                                </a>
                                            </p>
                                        <?php else: ?>
                                            <?php if ($company->created_at): ?>
                                                <p class="advert-location">
                                                    <?= Yii::$app->formatter->asDate($company->created_at, 'dd, MMMM yyyy') ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($company->created_at): ?>
                                            <p class="advert-date">
                                                <?= Yii::$app->formatter->asDate($company->created_at, 'dd, MMMM yyyy') ?>
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
