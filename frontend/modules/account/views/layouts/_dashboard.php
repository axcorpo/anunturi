<?php

/* @var $this \yii\web\View */
/* @var $navWrapperClass string */
/* @var $navClass string */
/* @var $showWhiteLogo bool */

use yii\helpers\Html;
use yii\helpers\Url;

?>

<?php if ($navMenu = \common\models\Menu::findDefaultTopBarMenu()): ?>
<!-- Dashboard header -->
<section class="navbar-dashboard-area">
	<nav class="navbar navbar-default lightHeader navbar-dashboard" role="navigation">
		<div class="container">

			<!-- Brand and toggle get grouped for better mobile display -->
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-dash">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
			</div>

			<!-- Collect the nav links, forms, and other content for toggling -->
			<div class="collapse navbar-collapse navbar-dash">
				<?= \common\widgets\sitemenu\SiteNav::widget([
					'options' => [
						'class' => 'nav navbar-nav mr0',
					],
					'activateParents' => true,
					'encodeLabels' => false,
					'items' => $navMenu->getNestedItems(null, common\models\MenuItem::STATUS_ACTIVE),
				]) ?>
			</div>
		</div>
	</nav>
</section>
<?php endif; ?>