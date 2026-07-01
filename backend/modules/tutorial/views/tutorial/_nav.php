<?php

/* @var $this yii\web\View */
/* @var $order_id int */

use yii\helpers\Html;
?>

<ul class="nav nav-tabs">
	<li class="<?= ($this->context->id == 'tutorial') ? 'active' : '' ?>">
		<?= Html::a(Yii::t('common', 'Tutorial'), ['tutorial/update', 'id' => $tutorial_id]) ?>
	</li>
	<li class="<?= ($this->context->id == 'tutorial-member') ? 'active' : '' ?>">
		<?= Html::a(Yii::t('common', 'Tutorial Members'), ['tutorial-member/index', 'tutorial_id' => $tutorial_id]) ?>
	</li>
</ul>
