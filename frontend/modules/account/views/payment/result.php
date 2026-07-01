<?php
/* @var $this yii\web\View */
/* @var $model \frontend\modules\account\models\PaymentResult */

use yii\helpers\Html;
use tws\helpers\Url;
use yii\widgets\Breadcrumbs;

$this->title = Yii::t('frontend', 'Payment Result');
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

<?php if ($content = $this->context->currentPage->translation->content): ?>
    <section class="clearfix bg-dark">
        <div class="container">
            <div class="row">
                <?= $content ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="clearfix bg-dark dashboard-review-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-xs-12 dashboardBoxBg">
                <?php if ($model->hasErrors()): ?>
                    <header class="section-header text-center">
                        <h1 class="section-heading color-danger font-md"><?= Yii::t('frontend', 'Payment was unsuccessful.') ?></h1>
                    </header>
                    <div class="gap-b-md">
                        <p class="color-grey text-center"><?= Yii::t('frontend', 'What happened?') ?></p>
                        <div class="row">
                            <div class="col-md-offset-1 col-md-10 col-lg-offset-2 col-lg-8">
                                <?= Html::errorSummary($model, [
                                    'header' => false,
                                    'class' => 'error-summary alert alert-danger alert-icon',
                                ]) ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="color-grey gap-b-md"><?= Yii::t('frontend', 'What can you do now?') ?></p>
                        <ul class="list-inline">
                            <li class="inline-block-sm">
                                <a class="btn btn-primary btn-outline btn-slide-right" href="<?= Url::to(['/account/payment/package', 'id' => $model->getSubscription() ? Yii::$app->security->maskToken((string) $model->getSubscription()->package_id) : null]) ?>"><?= Yii::t('frontend', 'Try Again') ?></a>
                            </li>
                            <li><?= mb_strtolower(Yii::t('common', 'Or')) ?></li>
                            <li class="inline-block-sm">
                                <a class="btn btn-primary btn-outline btn-slide-right" href="<?= Url::to(['/site/contact']) ?>"><?= Yii::t('frontend', 'Contact Us') ?></a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <header class="section-header text-center">
                        <h1 class="section-heading color-success font-md"><?= Yii::t('frontend', 'Payment was successful.') ?></h1>
                        <span class="loader"><span class="loader-inner"></span></span>
                        <?php $this->registerJs('window.setTimeout(function() {window.location.href = "' . Url::to(['/account/announcement/index']) . '";}, 3000);'); ?>
                    </header>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
