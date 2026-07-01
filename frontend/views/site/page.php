<?php
/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

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

<?php if ($content = $this->context->currentPage->content): ?>
	<section class="clearfix">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<?= $content ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>
