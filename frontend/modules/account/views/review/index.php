<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Announcement;
use common\models\Review;
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


<!-- DASHBOARD REVIEWS SECTION -->
<section class="clearfix bg-dark dashboard-review-section">
	<div class="container">
		<?php if ($reviews = $dataProvider->getModels()): ?>
			<div class="row">
				<div class="col-lg-12 col-xs-12 dashboardBoxBg">
					<div class="dashboard-list-box">
						<?php foreach ($reviews as $review): ?>
							<?php
							$actions = [];
							if ($review->announcement->actions) {
								foreach ($review->announcement->actions as $action) {
									$actions[] = \common\models\Action::getMyTypes()[$action->type];
								}
							}
							?>
							<div class="single-list">
								<div class="media comments-media">
									<div class="media-left">
										<a href="#">
											<?= Html::img($review->creator->imageUrl ?: Url::to('@web/img/img-placeholder-user.png'), [
												'class' => 'img-circle',
												'alt' => $review->creator->fullName,
											]) ?>
										</a>
									</div>
									<div class="media-body">
										<?php if ($review->announcement || $review->company): ?>
											<h4 class="media-heading"><?= $review->creator->fullName ?> - <a href=""><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $review->announcement->translation->title ?: $review->company->name ?></a></h4>
											<div>
												<?= StarRating::widget([
													'id' => 'rating-' . rand(),
													'name' => '',
													'value' => $review->score ?: 0,
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
											</div>
										<?php endif; ?>
										<div class="date"><?= Yii::$app->formatter->asDate($review->created_at, 'dd, MMMM yyyy') ?></div>
										<?php if ($review->translation->content): ?>
											<p><?= $review->translation->content ?></p>
										<?php endif; ?>
										<?= Html::a(Yii::t('common', 'Update'),['/account/review/update', 'id' => $review->id], [
											'class' => 'action-update btn btn-primary',
											'data' => [
												'popup-action' => '',
												'popup-done' => ['redrawDataTable' => '#dt-reviews'],
											],
										]) ?>
										<?= Html::a(Yii::t('common', 'Delete'), ['/account/review/delete', 'id' => $review->id], [
											'class' => 'btn btn-primary',
											'title' => Yii::t('common', 'Delete'),
											'data' => [
												'toggle' => 'tooltip',
												'method' => 'POST',
												'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
											],
										]) ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
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