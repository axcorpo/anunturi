<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Action;
use common\models\Announcement;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\rating\StarRating;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use yii\widgets\LinkPager;

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

<!-- DASHBOARD PROFILE SECTION -->
<section class="clearfix bg-dark profileSection">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-sm-5 col-xs-12 mb30">
                <div class="dashboardBoxBg">
					<?= Html::a('+ <span>' . Yii::t('frontend', 'Add Announcement') . '</span>', ['/account/announcement/create'], [
						'class' => 'btn btn-primary',
					]) ?>
					<ul class="dashboard-vertical-menu">
						<li class="<?= !Yii::$app->request->get('status') ? 'active' : '' ?>"><a class="" href="<?= Url::to(['/account/announcement/index']) ?>"><i class="fa fa-briefcase icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'All Announcements') . '(' . $totalCount . ')' ?></a></li>
						<li class="<?= Yii::$app->request->get('status') == Announcement::STATUS_ACTIVE ? 'active' : '' ?>"><a class="" href="<?= Url::to(['/account/announcement/index', 'status' => Announcement::STATUS_ACTIVE]) ?>"><i class="fa fa-check-square-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Active Announcements') . '(' . $activeCount . ')' ?></a></li>
						<li><a class="<?= Yii::$app->request->get('status') == Announcement::STATUS_PENDING ? 'active' : '' ?>" href="<?= Url::to(['/account/announcement/index', 'status' => Announcement::STATUS_PENDING]) ?>"><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Pending Announcements') . '(' . $pendingCount . ')' ?></a></li>
						<li><a class="<?= Yii::$app->request->get('status') == Announcement::STATUS_INACTIVE ? 'active' : '' ?>" href="<?= Url::to(['/account/announcement/index', 'status' => Announcement::STATUS_INACTIVE]) ?>"><i class="fa fa-minus-square-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Inactive Announcements') . '(' . $inactiveCount . ')' ?></a></li>
						<li><a class="<?= Yii::$app->request->get('status') == Announcement::STATUS_REJECTED ? 'active' : '' ?>" href="<?= Url::to(['/account/announcement/index', 'status' => Announcement::STATUS_REJECTED]) ?>"><i class="fa fa-ban icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Rejected Announcements') . '(' . $rejectedCount . ')' ?></a></li>
					</ul>
                </div>
            </div>
            <?php if ($announcements = $dataProvider->getModels()): ?>
                <div class="col-md-9 col-sm-7 col-xs-12 dashboardBoxBg">
                    <?php foreach ($announcements as $announcement) : ?>
						<?php
							$actions = [];
							foreach ($announcement->actions as $action) {
								$actions[] = \common\models\Action::getMyTypes()[$action->type];
							}
						?>
                        <?php $announcementTranslation = $announcement->translation; ?>
                        <div class="listContent dashboard-ad-list">
                            <div class="row">
                                <div class="col-sm-4 col-xs-12">
                                    <div class="categoryImage">
										<div class="img-ratio">
											<?php if ($announcement->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$announcement->id}/{$announcement->image}"))): ?>
												<img src="<?= $announcement->imageUrl; ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive img-rounded">
											<?php else: ?>
												<img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive img-rounded">
											<?php endif; ?>
										</div>
                                        <span class="label label-<?= str_replace([Announcement::STATUS_INACTIVE, Announcement::STATUS_ACTIVE, Announcement::STATUS_PENDING, Announcement::STATUS_REJECTED], ['inactive', 'success', 'warning', 'danger'], $announcement->status) ?>"><?= Announcement::getStatusLabels()[$announcement->status]['label'] ?></span>
                                        <?php $isPromoted = \common\models\Promotional::isPromoted($announcement->id);  ?>
                                        <?php if ($announcement->status == Announcement::STATUS_ACTIVE && !$isPromoted): ?>
                                            <?= Html::a('<i class="fa fa-bullhorn icon-dash" aria-hidden="true"></i> ' . Yii::t('frontend', 'Promote'), ['/account/premium/create', 'announcement' => Yii::$app->security->maskToken((string)$announcement->id)], [
                                                'class' => 'btn btn-primary btn-promote',
                                                'title' => Yii::t('common', 'Promote'),
                                                'data' => [
                                                    'toggle' => 'tooltip',
                                                    'popup-action' => '',
                                                ],
                                            ]) ?>
                                        <?php else: ?>
                                            <?php if ($isPromoted): ?>
                                                <div class="btn btn-success btn-promote" style="background-color: #28a745; margin-top: 10px;"><?= Yii::t('frontend', 'Promoted') ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-sm-8 col-xs-12">
                                    <div class="categoryDetails">
                                        <h3><a href="<?= Url::to(['/announcement/default/view', 'slug' => $announcementTranslation->slug]) ?>" target="_blank" style="color: #222222"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?></a></h3>
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
                                        <p><span><i class="fa fa-hand-pointer-o" aria-hidden="true"></i> <?= $announcement->visits; ?></span>&nbsp;&nbsp; <span><i class="fa fa-eye" aria-hidden="true"></i> <?= $announcement->views; ?></span></p>
                                        <?php if ($announcement->renewed_at || $announcement->created_at): ?>
                                            <p>
                                                <i class="fa fa-calendar" aria-hidden="true"></i> <?= Yii::$app->formatter->asDate($announcement->renewed_at ?: $announcement->created_at, 'dd, MMMM yyyy') ?>
                                            </p>
                                        <?php endif; ?>
                                        <ul class="list-inline list-tag">
                                            <li>
                                                <?= Html::a('<i class="fa fa-eye" aria-hidden="true"></i>', ['/announcement/default/view', 'slug' => $announcementTranslation->slug], [
                                                    'class' => 'btn btn-primary',
                                                    'title' => Yii::t('common', 'Preview'),
                                                    'data' => [
                                                        'toggle' => 'tooltip'
                                                    ],
                                                    'target' => '_blank',
                                                ]) ?>
                                            </li>
											<?php if (in_array($announcement->status,  [Announcement::STATUS_ACTIVE])): ?>
                                            <li>
												<?= Html::a('<i class="fa fa-refresh icon-dash" aria-hidden="true"></i>', ['/account/renewal/create'], [
                                                    'class' => 'btn btn-primary',
                                                    'title' => Yii::t('common', 'Renew'),
													'data' => [
														'toggle' => 'tooltip',
														'method' => 'POST',
                                                        'params' => ['id' => Yii::$app->security->maskToken((string)$announcement->id)],
													],
                                                ]) ?>
											</li>
											<li>
												<?= Html::a('<i class="fa fa-minus-circle icon-dash" aria-hidden="true"></i>', ['/account/announcement/cancel', 'id' => Yii::$app->security->maskToken((string)$announcement->id)], [
													'class' => 'btn btn-primary btn-deactivate',
													'title' => Yii::t('common', 'Deactivate'),
													'data' => [
														'toggle' => 'tooltip',
														'method' => 'POST',
														'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
													],
												]) ?>
											</li>
											<?php endif; ?>
                                            <?php if (in_array($announcement->status, [Announcement::STATUS_PENDING, Announcement::STATUS_INACTIVE])): ?>
												<li>
													<?= Html::a('<i class="fa fa-check" aria-hidden="true"></i> ' . Yii::t('common', 'Activate'), ['/account/announcement/activate', 'id' => Yii::$app->security->maskToken((string)$announcement->id)], [
														'class' => 'btn btn-primary',
														'title' => Yii::t('common', 'Activate'),
                                                        'data' => [
                                                            'toggle' => 'tooltip',
                                                            'popup-action' => '',
                                                        ],
													]) ?>
												</li>
											<?php endif; ?>
                                            <li>
                                                <?= Html::a('<i class="fa fa-pencil icon-dash" aria-hidden="true"></i>', ['/account/announcement/update', 'id' => Yii::$app->security->maskToken((string)$announcement->id)], [
                                                    'class' => 'btn btn-primary',
                                                    'title' => Yii::t('common', 'Update'),
                                                    'data' => [
                                                        'toggle' => 'tooltip'
                                                    ],
                                                ]) ?>
                                            </li>
											<?php if (in_array($announcement->status,  [Announcement::STATUS_PENDING, Announcement::STATUS_REJECTED, Announcement::STATUS_INACTIVE])): ?>
											<li>
												<?= Html::a('<i class="fa fa-trash-o icon-dash" aria-hidden="true"></i>', ['/account/announcement/delete', 'id' => Yii::$app->security->maskToken((string)$announcement->id)], [
													'class' => 'btn btn-primary btn-deactivate',
													'title' => Yii::t('common', 'Delete'),
													'data' => [
														'toggle' => 'tooltip',
														'method' => 'POST',
														'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
													],
												]) ?>
											</li>
											<?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="paginationCommon blogPagination text-center dashboard-pagination-list">
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
            <?php else: ?>
                <div class="col-md-9 col-sm-7 col-xs-12 dashboardBoxBg">
                    <div class="alert alert-info mt-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>