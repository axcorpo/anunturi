<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\widgets\ActiveForm;
use kartik\file\FileInput;
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
<section class="clearfix bg-dark my-bookings">
	<div class="container">
		<?php if ($announcements = $dataProvider->getModels()): ?>
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12 dashboardBoxBg">
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
								<div class="col-sm-3 col-xs-12">
									<div class="categoryImage">
										<div class="img-ratio">
											<?php if ($announcement->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$announcement->id}/{$announcement->image}"))): ?>
												<img src="<?= $announcement->imageUrl; ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive img-rounded">
											<?php else: ?>
												<img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?>" class="img-ratio-object img-responsive img-rounded">
											<?php endif; ?>
										</div>
									</div>
								</div>
								<div class="col-sm-9 col-xs-12">
									<div class="col-sm-7 col-xs-12">
										<div class="categoryDetails">
											<h3><a href="<?= Url::to(['/announcement/default/view', 'slug' => $announcementTranslation->slug]) ?>" style="color: #222222"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $announcementTranslation->title ?></a></h3>
											<?php if ($announcement->renewed_at || $announcement->created_at): ?>
												<p>
													<i class="fa fa-calendar" aria-hidden="true"></i> <?= Yii::$app->formatter->asDate($announcement->renewed_at ?: $announcement->created_at, 'dd, MMMM yyyy') ?>
												</p>
											<?php endif; ?>
										</div>
									</div>
									<div class="col-sm-1 col-xs-12"></div>
									<div class="col-sm-4 col-xs-12">
										<div class="categoryDetails">
											<div class="row reservation-dates">
												<div class="col-sm-6 col-xs-6 checkin-border">
                                                    <h3><?= Yii::$app->formatter->asCurrency($announcement->price, $announcement->currency) ?></h3>
												</div>
												<div class="col-sm-6 col-xs-6">
													<?php if ($announcement->company->name): ?>
														<p class="company"><a href="<?= Url::to(['/firm/default/view', 'slug' => $announcement->company->translation->slug]) ?>"><?= $announcement->company->name; ?></a></p>
													<?php endif; ?>
                                                    <?php if (!empty($announcement->company_id)): ?>
													    <?php if ($announcement->company->phone): ?>
														    <p><a href="tel:<?= $announcement->company->phone ?>" data-prevent-page-overlay="true"><span class="fa fa-phone"> <?= $announcement->company->phone ?></a></p>
													    <?php else: ?>
                                                            <?php if ($announcement->creator->phone): ?>
                                                                <p><a href="tel:<?= $announcement->creator->phone ?>" data-prevent-page-overlay="true"><span class="fa fa-phone"> <?= $announcement->creator->phone ?></a></p>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
													    <?php if ($announcement->creator->phone): ?>
													    	<p><a href="tel:<?= $announcement->creator->phone ?>" data-prevent-page-overlay="true"><span class="fa fa-phone"> <?= $announcement->creator->phone ?></a></p>
													    <?php endif; ?>
                                                    <?php endif; ?>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-12">
										<ul class="list-inline list-tag">
											<li><a href="<?= Url::to(['/announcement/default/view', 'slug' => $announcementTranslation->slug]) ?>" class="btn btn-primary"><i class="fa fa-eye icon-dash" aria-hidden="true"></i> Vizualizare</a></li>
											<li><?= Html::a('<i class="fa fa-trash-o icon-dash" aria-hidden="true"></i> ' . Yii::t('common', 'Delete'), ['/account/favourite/delete', 'id' => $announcement->id], [
													'class' => 'btn btn-primary btn-deactivate',
													'title' => Yii::t('common', 'Delete'),
													'data' => [
//                                                'toggle' => 'tooltip',
														'method' => 'POST',
														'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
													],
												]) ?>
											</li>
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
			</div>
		<?php else: ?>
			<div class="row">
				<div class="col-lg-12 col-xs-12 dashboardBoxBg">
					<div class="alert alert-info mt-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>