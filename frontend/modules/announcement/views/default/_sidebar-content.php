<?php

/* @var $this yii\web\View */
/* @var $tags array */

use common\helpers\FontIcon;
use yii\helpers\Html;
use common\helpers\Inflector;
use yii\helpers\Url;

?>

<div class="row">
	<?php if ($categories = \common\models\Category::findAllCategories()): ?>
		<div class="col-md-12 widget-category widget">
			<div class="well-bg">
				<div class="well-inner">
					<h2 class="widget-title"><?= Yii::t('frontend', 'Categories') ?></h2>
					<ul class="listnone long-arrow-right sidenav">
						<?php foreach ($categories as $category) : ?>
							<?php $categoryTranslation = $category->getTranslation(); ?>
							<li>
								<?= Html::a($categoryTranslation->name, ['index', 'category' => $categoryTranslation->slug]) ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?php if ($latestAnnouncements = \common\models\Announcement::findLatestAnnouncements(5)): ?>
		<div class="col-md-12 widget-recent-post widget">
			<div class="well-bg">
				<div class="well-inner">
					<h2 class="widget-title"><?= Yii::t('frontend', 'Latest News') ?></h2>
					<?php foreach ($latestAnnouncements as $announcement): ?>
						<?php $announcementTranslation = $announcement->getTranslation(); ?>
						<div class="row recent-post-block">
							<div class="col-md-5 col-sm-4">
								<?php if ($announcement->video): ?>
									<div class="recent-img">
										<div class="embed-responsive embed-responsive-4by3">
											<iframe class="embed-responsive-item" src="<?= $announcement->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
										</div>
									</div>
								<?php elseif ($announcement->image && is_file(Yii::getAlias("@uploads/announcement/{$announcement->id}/{$announcement->image}"))): ?>
									<a class="recent-img" href="<?= Url::to(['view', 'slug' => $announcementTranslation->slug]) ?>" style="background-image: url('<?= $announcement->getImageUrl() ?>')">
										<img class="img-responsive" src="<?= $announcement->getImageUrl() ?>" alt="<?= $announcementTranslation->title ?>">
									</a>
								<?php else: ?>
									<a class="recent-img recent-img-icon" href="<?= Url::to(['view', 'slug' => $announcementTranslation->slug]) ?>">
										<?php if ($announcement->icon): ?>
											<?= FontIcon::render($announcement->icon, ['class' => 'post-icon']) ?>
										<?php endif; ?>
									</a>
								<?php endif; ?>
							</div>
							<div class="col-md-7 recent-desc col-sm-8">
								<h3>
									<a href="<?= Url::to(['view', 'slug' => $announcementTranslation->slug]) ?>" class="recent-title"><?= $announcementTranslation->title ?></a>
								</h3>
								<div class="meta">
									<span class="meta-date">
										<i class="fa fa-calendar"></i>
										<?= Yii::$app->formatter->asDate($announcement->created_at, 'dd MMM yyyy') ?>
									</span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?php if ($yearAnnouncements = \common\models\Announcement::countAnnouncementsByYear()): ?>
		<div class="col-md-12 widget-archives widget">
			<div class="well-bg">
				<div class="well-inner">
					<h2 class="widget-title"><?= Yii::t('frontend', 'Archives') ?></h2>
					<ul class="listnone">
						<?php foreach ($yearAnnouncements as $yearAnnouncement): ?>
							<li>
								<a href="<?= Url::to(['index', 'year' => $yearAnnouncement['year']]) ?>">
									<?= $yearAnnouncement['year'] ?> <strong>(<?= $yearAnnouncement['total'] ?>)</strong>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?php if (!empty($tags)): ?>
		<div class="col-md-12 widget-tags">
			<div class="well-bg">
				<div class="well-inner">
					<h2 class="widget-title"><?= Yii::t('frontend', 'Tags') ?></h2>
					<?php foreach ($tags as $tag): ?>
						<?= Html::a($tag, ['index', 'tag' => $tag], [
							'class' => 'tags',
						]) ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>