<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Html::encode($message);
?>


<section class="page-error-section bg-dark">
	<div class="container">
		<div class="row">
			<div class="col-xs-12 col-sm-8 col-sm-offset-2">
				<h1><?= Yii::t('frontend', '404 Error') ?></h1>
				<p><?= Html::encode($exception->getMessage()) ?></p>
				<div class="text-center">
					<div class="col-sm-12">
						<a href="<?= Url::to(['/site/index']) ?>" class="btn btn-default"><?= Yii::t('frontend', 'Go to Home Page') ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
