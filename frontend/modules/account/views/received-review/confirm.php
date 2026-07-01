<?php

/* @var $this yii\web\View */
/* @var $model common\models\Review */

use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

$this->params['breadcrumbs'][] = Html::encode(Yii::t('frontend', 'Confirmation'));
?>
<?php if (!Yii::$app->request->isAjax): ?>
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
<section class="clearfix bg-dark dashboard-review-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-xs-12 dashboardBoxBg">
<?= $this->render('_confirm', [
	'model' => $model,
]) ?>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<div class="container">
<div class="row">
        <?= $this->render('_confirm', [
        'model' => $model,
        ]) ?>
    </div>
</div>
<?php endif; ?>
