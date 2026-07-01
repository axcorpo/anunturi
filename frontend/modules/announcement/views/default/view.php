<?php

/* @var $this \yii\web\View */
/* @var $model common\models\Announcement */
/* @var $tags array */

use common\helpers\Inflector;
use common\helpers\UploadHelper;
use common\models\Field;
use common\models\FieldTranslation;
use common\models\FieldValue;
use common\models\IgnoredUser;
use common\models\Option;
use common\models\Review;
use common\models\Subscriber;
use common\widgets\fullcalendar\FullCalendar;
use kartik\rating\StarRating;
use tws\helpers\StringHelper;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = [
    'label' => \common\models\Page::findPageByRoute(['/announcement/default/index'])->translation->title,
    'url' => ['index'],
];
$this->params['breadcrumbs'][] = Html::encode($this->title);
$reviews = Review::findAllReviews($model->id, true);
?>

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

<!-- LISTINGS DETAILS TITLE SECTION -->
<section class="clearfix" style="padding: 0 0 20px 0;">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <div class="listingTitleArea">
                    <?php
	                    $actions = [];
	                    foreach ($model->actions as $action) {
	                        $actions[] = \common\models\Action::getMyTypes()[$action->type];
	                    }
                    ?>
                    <h1><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $model->translation->title ?></h1>
                    <?php if ($model->county || $model->locality): ?>
                        <?php $url = ['/model/default/index'];
                        $content = [];
                        if ($model->locality) {
                            $content[] = '<span class="fa fa-map-marker"></span>' . ' ' .$model->locality;
                            $url['locality'] = $model->locality;
                        }
                        if ($model->county) {
                            $content[] = $model->county;
                            $url['county'] = $model->county;
                        }
                        ?>
                        <?php if ($model->created_at): ?>
                            <p>
                                <?= implode(', ', $content) . ' '?>  <i class="fa fa-calendar icon-dash" aria-hidden="true"></i> <?= Yii::$app->formatter->asDate($model->created_at, 'dd, MMMM yyyy') ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="social-widget">
                        <a class="social-share" target="_blank" href="https://pinterest.com/pin/create/bookmarklet/?media={img}&url={url}&is_video={is_video}&description={title}"><div class="socialbox"><i class="fa fa-pinterest"></i></div></a>
                        <a class="social-share" target="_blank" href="http://www.linkedin.com/shareArticle?url={url}&title={title}"><div class="socialbox"><i class="fa fa-linkedin"></i></div></a>
                        <a class="social-share" target="_blank" href="https://twitter.com/share?url={url}&text={title}&via={via}&hashtags={hashtags}"><div class="socialbox"><i class="fa fa-twitter"></i></div></a>
                        <a class="social-share" target="_blank" href="http://www.facebook.com/sharer.php?u={url}&picture={img}&title={title}&quote={desc}"><div class="socialbox"><i class="fa fa-facebook"></i></div></a>
                        <input type="hidden" id="input_url" value="<?= Yii::$app->request->absoluteUrl ?>" />
                        <input type="hidden" id="input_img" value="<?= UploadHelper::getFileUrl("announcement/{$model->id}/{$model->image}") ?>" />
                        <input type="hidden" id="input_title" value="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $model->translation->title ?>" />
                        <input type="hidden" id="input_desc" value="<?= strip_tags(StringHelper::truncate($model->translation->content, 120)) ?>" />
                        <input type="hidden" id="input_app_id" value="" />
                        <input type="hidden" id="input_redirect_url" value="<?= Yii::$app->urlManager->createAbsoluteUrl(['/']) ?>" />
                        <input type="hidden" id="input_via" value="" />
                        <input type="hidden" id="input_hashtags" value="" />
                    </div>
                    <div class="listingReview">
                        <?= StarRating::widget([
                            'id' => 'rating-' . rand(),
                            'name' => '',
                            'value' => \common\models\Announcement::getAnnouncementRating($model->id) ?: 0,
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
                        <span>( <?= count($reviews) ?> <?= count($reviews) == 1 ? Yii::t('common', 'Review') : Yii::t('common', 'Reviews') ?> )</span>
                        <?php if ($model->currency && $model->price > 0): ?>
                        <h2 class="price-heading"><?= Yii::$app->formatter->asCurrency($model->price, $model->currency) ?></h2>
                        <?php else: ?>
                        <h2 class="price-heading">&nbsp;</h2>
                        <?php endif; ?>
                        <?php if ($model->announcementHasActions[0]->price): ?>
                            <?php
                            $content = [];
                            if ($model->announcementHasActions[0]->price) {
                                $content[] = $model->announcementHasActions[0]->price;
                            }
                            if ($model->announcementHasActions[0]->currency) {
                                $content[0] = $content[0]. ' ' . $model->announcementHasActions[0]->currency;
                            }
                            if ($model->announcementHasActions[0]->uom) {
                                $content[] = $model->announcementHasActions[0]->uom;
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- LISTINGS DETAILS INFO SECTION -->
<section class="clearfix paddingAdjustTop">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-md-7 col-sm-12 col-xs-12">
                <?php if (($model->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$model->id}/{$model->image}"))) || count($model->pictures) > 0): ?>
                    <div class="owl-carousel productgallery">
                        <?php if ($model->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$model->id}/{$model->image}"))): ?>
                            <div class="slide">
                                <div class="partnersLogo clearfix">
                                    <a href="<?= $model->imageUrl ?>" data-fancybox="gallery" data-caption="Caption #1">
                                        <img src="<?= $model->imageUrl ?>" alt="<?= $model->translation->title ?>" class="img-responsive">
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($model->pictures): ?>
                            <?php foreach ($model->pictures as $picture): ?>
                                    <div class="slide">
                                        <div class="partnersLogo clearfix">
                                                <?php if (is_file(Yii::getAlias("@uploads/announcement/{$model->id}/{$picture->image}"))): ?>
                                                <a href="<?= UploadHelper::getFileUrl("announcement/{$model->id}/{$picture->image}") ?>" data-fancybox="gallery" data-caption="Caption #2">
                                                    <img src="<?= UploadHelper::getFileUrl("announcement/{$model->id}/{$picture->image}") ?>" alt="<?= $model->translation->title ?>" class="img-responsive">
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="listDetailsInfo">
                    <div class="detailsInfoBox mt30">
                        <?= nl2br($model->translation->content) ?>
                    </div>
                    <?php if ($model->extraFeatures): ?>
                        <div class="detailsInfoBox">
                            <h3><?= Yii::t('frontend', 'Extra Features') ?></h3>
                            <ul class="list-inline featuresItems">
                                <?php foreach ($model->extraFeatures as $extraFeature): ?>
                                    <li><i class="fa fa-check-circle-o" aria-hidden="true"></i> <?= $extraFeature->translation->name ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php $fieldValues = FieldValue::find()
                        ->alias('fv')
                        ->select('fv.*')
                        ->joinWith([
                            'announcement a',
                            'field f',
                            'field.fieldTranslations ft' => function (ActiveQuery $query) {
                                $query->andOnCondition([
                                    'ft.language_id' => Yii::$app->language,
                                    'ft.deleted' => FieldTranslation::NO,
                                ]);
                            },
                        ])
                        ->where([
                            'fv.status' => FieldValue::STATUS_ACTIVE,
                            'fv.deleted' => FieldValue::NO,
                        ])
                        ->andWhere([
                            'fv.announcement_id' => $model->id,
                        ])
                        ->all();
                    $values = [];
                    foreach ($fieldValues as $fieldValue) {
                        if (in_array($fieldValue->field->type, [Field::TYPE_CHECKBOX, Field::TYPE_RADIO, Field::TYPE_SELECT, Field::TYPE_MULTIPLE_SELECT])) {
                            $option = Option::findOne([
                                'field_id' => $fieldValue->field_id,
                                'value' => $fieldValue->value,
                                'status' => Option::STATUS_ACTIVE,
                                'deleted' => Option::NO,
                            ]);
                            $values[$fieldValue->field->translation->label][] = $option->translation->label;
                        } elseif ($fieldValue->field->type == Field::TYPE_DATE) {
                            $values[$fieldValue->field->translation->label][] = Yii::$app->formatter->asDate($fieldValue->value);
                        } elseif ($fieldValue->field->type == Field::TYPE_DATETIME) {
                            $values[$fieldValue->field->translation->label][] = Yii::$app->formatter->asDatetime($fieldValue->value);
                        } else {
                            $values[$fieldValue->field->translation->label][] = $fieldValue->value;
                        }
                    }
                    ?>
                    <?php if ($values): ?>
                        <div class="detailsInfoBox">
                            <div class="row mt30">
                                <div class="col-sm-12">
                                    <table class="table table-dotari">
                                        <tbody>
                                        <?php foreach($values as $key => $value): ?>
                                            <tr>
                                                <th><?= $key ?></th>
                                                <td>
                                                    <?= implode(', ', $values[$key]) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($model->latitude && $model->longitude): ?>
                        <div class="detailsInfoBox individual-product">
                            <h3><?= Yii::t('frontend', 'Location') ?></h3>
                            <p><?= $location ?></p>
                            <div class="clearfix map-sidebar map-right mb30 mt30">
                                <div id="map" style="position:relative; margin: 0;padding: 0;height: 400px; max-width: 100%;"><?= $map->display() ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($reviews): ?>
                        <div class="detailsInfoBox" id="scrie-recenzie">
                            <h3><?= Yii::t('common', 'Reviews') ?> (<?= count($reviews) ?>)</h3>
                            <?php foreach ($reviews as $review): ?>
                                <div class="media media-comment">
                                    <div class="media-left">
                                        <img src="<?= $review->creator->imageUrl ?: Yii::getAlias('@web/img/img-placeholder-user.png') ?>" alt="<?= $review->creator->fullName ?>" class="media-object img-circle">
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
                    <?php if (!Yii::$app->user->isGuest && $model->created_by != Yii::$app->user->id): ?>
                        <div class="detailsInfoBox" >
                            <h3><?= Yii::t('common', 'Write a review') ?></h3>
                            <?= $this->render('_review-form', [
                                'reviewModel' => $reviewModel,
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4 col-md-5 col-sm-12 col-xs-12 hidden-sm">
                <?php if ($model->company): ?>
                    <div class="listSidebar">
                        <h3><a href="<?= Url::to(['/firm/default/view', 'slug' => $model->company->translation->slug]) ?>"><?= $model->company->name ?></a></h3>
                        <div class="media media-company">
                            <?php if ($model->company->imageUrl && is_file(Yii::getAlias("@uploads/company/{$model->company->id}/{$model->company->image}"))): ?>
                                <div class="media-left">
                                    <a href="<?= Url::to(['/firm/default/view', 'slug' => $model->company->translation->slug]) ?>"><img src="<?= $model->company->imageUrl; ?>" alt="<?= $model->company->name ?>"></a>
                                </div>
                            <?php else: ?>
                                <div class="media-left">
                                    <a href="#"><img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $model->company->name ?>"></a>
                                </div>
                            <?php endif; ?>
                            <div class="media-body">
                                <h4 class="media-heading"><?= Yii::t('label', 'Company') ?></h4>
                                <?= StarRating::widget([
                                    'id' => 'rating-' . rand(),
                                    'name' => '',
                                    'value' => \common\models\Company::getCompanyRating($model->company->id),
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
                                <p><a href="<?= Url::to(['/firm/default/view', 'slug' => $model->company->translation->slug]) ?>"><?= Yii::t('frontend', 'See company announcements') ?></a></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="listSidebar">
                        <h3><a href="<?= Url::to(['/individual/default/view', 'slug' => $model->creator->slug]) ?>"><?= $model->creator->shortName ?></a></h3>
                        <div class="media media-company">
                            <?php if ($model->creator->imageUrl && is_file(Yii::getAlias("@uploads/user/{$model->created_by}/{$model->creator->image}"))): ?>
                                <div class="media-left">
                                    <a href="<?= Url::to(['/individual/default/view', 'slug' => $model->creator->slug]) ?>"><img src="<?= $model->creator->imageUrl; ?>" alt="<?= $model->creator->fullName ?>"></a>
                                </div>
                            <?php else: ?>
                                <div class="media-left">
                                    <a href="#"><img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $model->creator->fullName ?>"></a>
                                </div>
                            <?php endif; ?>
                            <?php $subscriber = Subscriber::findOne(['user_id' => $model->created_by]); ?>
                            <div class="media-body">
                                <h4 class="media-heading"><?= Yii::t('label', 'Individual') ?></h4>
                                <?= StarRating::widget([
                                    'id' => 'rating-' . rand(),
                                    'name' => '',
                                    'value' => Subscriber::getSubscriberRating($subscriber->id),
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
                                <p><a href="<?= Url::to(['/individual/default/view', 'slug' => $model->creator->slug]) ?>"><?= Yii::t('frontend', 'See announcements') ?></a></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($model->created_by != Yii::$app->user->id): ?>
                    <?php if ($actions = $model->actions): ?>
                        <?php foreach ($actions as $action): ?>
                            <?php if ($action->type == \common\models\Action::TYPE_RENT): ?>
                                <div class="listSidebar hidden-xs">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <form action="" method="POST" role="form">
                                                <div class="send-reservation">
                                                    <?= Html::a(Yii::t('common', 'Reserve'),['/account/placed-reservation/create', 'announcement_id' => $model->id], [
                                                        'class' => 'action-create btn btn-primary',
                                                        'data' => [
                                                            'popup-action' => '',
                                                            'popup-css-class' => 'modal-placed-reservation',
                                                        ],
                                                    ]) ?>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!Yii::$app->user->isGuest): ?>
                    <?php if ($model->created_by != Yii::$app->user->id): ?>
	                    <div class="listSidebar hidden-xs">
		                    <div class="phone-button">
			                    <a href="#" class="btn btn-primary phone-link" data-prevent-page-overlay="true">
				                    <i class="fa fa-phone" aria-hidden="true"></i> <span class="phone-anchor"><?= Yii::t('frontend', 'Show Phone') ?></span>
			                    </a>
		                    </div>
	                        <?php
	                        $isBanned = IgnoredUser::find()
	                            ->alias('iu')
	                            ->where([
	                                'user_id' => Yii::$app->user->id,
	                                'created_by' => $model->created_by,
	                            ])
	                            ->andWhere([
	                                'iu.status' => IgnoredUser::STATUS_ACTIVE,
	                                'iu.deleted' => IgnoredUser::NO,
	                            ])
	                            ->one();
	                        ?>
	                        <?php if (!$isBanned): ?>
	                                <div class="send-message">
	                                    <?= Html::a('<i class="fa fa-envelope"></i>' . ' ' . Yii::t('frontend', 'Send Message'), ['/account/message/create', 'announcement_id' => $model->id], [
	                                        'class' => 'btn btn-primary',
	                                        'data' => [
	                                            'popup-action' => '',
	                                        ]
	                                    ]) ?>
	                                </div>
	                        <?php endif; ?>
	                        <?php if (!in_array(Yii::$app->user->id, \yii\helpers\ArrayHelper::getColumn($model->userHasAnnouncements, 'user_id'))): ?>
	                            <div class="save-favorite">
	                                <?= Html::a('<i class="fa fa-heart" aria-hidden="true"></i>' . ' ' . Yii::t('frontend','Add to favourite'), ['/account/favourite/create'], [
	                                    'class' => 'btn btn-primary btn-deactivate',
	                                    'data' => [
	                                        'method' => 'POST',
	                                        'params' => ['id' => $model->id],
	                                    ],
	                                ]) ?>
	                            </div>
	                        <?php endif; ?>
	                    </div>
	                <?php endif; ?>
                <?php else: ?>
		            <div class="listSidebar hidden-xs">
			            <div class="phone-button">
				            <a href="#" class="btn btn-primary phone-link" data-prevent-page-overlay="true">
					            <i class="fa fa-phone" aria-hidden="true"></i> <span class="phone-anchor"><?= Yii::t('frontend', 'Show Phone') ?></span>
				            </a>
			            </div>
		            </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($model->company->phone) || !empty($model->creator->phone)): ?>
<?php
	$phoneNumber = $model->company->phone ?: $model->creator->phone;
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

<?php $this->registerJsFile('https://maps.googleapis.com/maps/api/js?key=' . Yii::$app->settings->getCategory('general')['googleMapKey'] . '&language=' . Yii::$app->language, ['depends' => 'frontend\assets\AppAsset', 'async' => 'async', 'defer' => 'defer']) ?>
