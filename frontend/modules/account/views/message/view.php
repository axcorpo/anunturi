<?php

/* @var $this yii\web\View */
/* @var $model common\models\Message */

use common\models\IgnoredUser;
use common\models\Message;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\rating\StarRating;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use yii\widgets\LinkPager;
$this->params['breadcrumbs'][] = [
    'label' => \common\models\Page::findPageByRoute(['/account/message/index'])->translation->title,
    'url' => ['index'],
];
$this->params['breadcrumbs'][] = Html::encode($model->announcement->translation->title);
?>

<?php if (!empty($this->params['breadcrumbs'])) : ?>
    <!-- Dashboard breadcrumb section -->
    <div class="section dashboard-breadcrumb-section bg-dark">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <h2><?= Html::encode($model->announcement->translation->title) ?></h2>
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
<section class="clearfix bg-dark my-bookings edit-booking">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-sm-5 col-xs-12 mb30 hidden-sm hidden-xs">
                <div class="dashboardBoxBg">
                    <ul class="dashboard-vertical-menu">
                        <li class=""><a class="" href="<?= Url::to(['/account/message/index']) ?>"><i class="fa fa-envelope-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'All') ?></a></li>
                        <li class=""><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'received']) ?>"><i class="fa fa-inbox icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Received') ?></a></li>
                        <li class=""><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'sent']) ?>"><i class="fa fa-paper-plane-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Sent') ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9 col-sm-7 col-xs-12 dashboardBoxBg">
                <div class="dashboard-list-box">
                    <div class="list-sort">
                        <div class="sort-left"><a href="<?= Url::to(['/account/message/index']) ?>"><i class="fa fa-caret-left icon-dash" aria-hidden="true"></i> <?= Yii::t('frontend', 'Back to messages') ?></a></div>
                        <div class="sort-right sort-select">
                            <?php $isIgnored = IgnoredUser::find()
                                ->alias('iu')
                                ->where([
                                    'iu.user_id' => $model->conversation->created_by,
                                    'iu.created_by' => Yii::$app->user->id,
                                ])
                                ->andWhere([
                                    'iu.status' => IgnoredUser::STATUS_ACTIVE,
                                    'iu.deleted' => IgnoredUser::NO,
                                ])
                                ->one();
                            ?>
                            <?php if ($model->conversation->created_by != Yii::$app->user->id): ?>
                            <?php if ($isIgnored): ?>
                            <?= Html::a('<i class="fa fa-ban icon-dash" aria-hidden="true"></i> ' . Yii::t('frontend', 'Unblock user'),['/account/message/unblock-user', 'user_id' => $model->conversation->created_by], [
                                'class' => 'btn btn-primary btn-confirm',
                                'title' => Yii::t('frontend', 'Unblock user'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                                ],
                            ]) ?>
                            <?php else: ?>
                            <?= Html::a('<i class="fa fa-ban icon-dash" aria-hidden="true"></i> ' . Yii::t('frontend', 'Block user'),['/account/message/block-user', 'user_id' => $model->conversation->created_by], [
                                'class' => 'btn btn-primary btn-confirm',
                                'title' => Yii::t('frontend', 'Block user'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                                ],
                            ]) ?>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?= Html::a('<i class="fa fa-trash-o icon-dash" aria-hidden="true"></i> ' . Yii::t('frontend', 'Delete conversation'),['/account/message/delete-conversation', 'id' => $model->conversation_id], [
                                'class' => 'btn btn-primary btn-confirm',
                                'title' => Yii::t('frontend', 'Delete conversation'),
                                'data' => [
                                    'toggle' => 'tooltip',
                                    'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="listContent dashboard-ad-list">
                    <div class="row">
                        <?php if ($model->announcement_id != null): ?>
                        <?php
                        $actions = [];
                        foreach ($model->announcement->actions as $action) {
                            $actions[] = \common\models\Action::getMyTypes()[$action->type];
                        }
                        ?>
                        <div class="col-sm-3 col-xs-12">
                            <div class="categoryImage">
                                <?php if ($model->announcement->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$model->announcement->id}/{$model->announcement->image}"))): ?>
                                    <img src="<?= $model->announcement->imageUrl; ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $model->announcement->translation->title ?>" class="img-responsive img-rounded">
                                <?php else: ?>
                                    <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $model->announcement->translation->title ?>" class="img-responsive img-rounded">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-9 col-xs-12">
                            <h3><a href="<?= Url::to(['/announcement/default/view', 'slug' => $model->announcement->translation->slug]) ?>" style="color: #222222"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $model->announcement->translation->title ?></a></h3>
                            <?= StarRating::widget([
                                'id' => 'rating-' . rand(),
                                'name' => '',
                                'value' => \common\models\Announcement::getAnnouncementRating($model->announcement->id) ?: 0,
                                'pluginOptions' => [
                                    'displayOnly' => true,
                                    'showCaption' => false,
                                    'showClear' => false,
                                    'size' => 'xxs',
                                    'min' => 0,
                                    'max' => 5,
                                    'stars' => 5,
                                    'step' => 0.1,
                                    'theme' => 'krajee-fa',
                                    'filledStar' => '<i class="fa fa-star" aria-hidden="true"></i>',
                                    'emptyStar' => '<i class="fa fa-star-o" aria-hidden="true"></i>'
                                ],
                            ]) ?>
                            <p class="company"><a href="<?= Url::to(['/firm/default/view', 'slug' => $model->announcement->company->translation->slug]) ?>"> <?= $model->announcement->company->name ?></a></p>
                            <?php if ($model->announcement->company->fullAddress): ?>
                                <p><?= $model->announcement->company->fullAddress ?></p>
                            <?php endif; ?>
                            <!--                            PRICE-->
                            <!--                            <h3>20,000 EUR</h3>-->
                        </div>
                        <?php endif; ?>
                        <div class="col-sm-12 col-xs-12">
                            <div class="detailsInfoBox">
                                <?php $messages = Message::find()->where(['conversation_id' => $model->conversation_id])->andWhere(['status' => Message::STATUS_ACTIVE, 'deleted' => Message::NO])->orderBy('created_at')->all();?>
                                <h3><?= Yii::t('frontend', 'Conversations') . ' (' . count($messages) . ')'?></h3>
                                <?php foreach ($messages as $message): ?>
                                    <?php if ($message->created_by != Yii::$app->user->identity->id): ?>
                                        <div class="media media-comment">
                                            <?php if ($message->creator->imageUrl && is_file(Yii::getAlias("@uploads/user/{$message->creator->id}/{$message->creator->image}"))): ?>
                                                <div class="media-left">
                                                    <img src="<?= $message->creator->imageUrl ?>" alt="<?= $message->creator->fullName ?>" class="media-object img-circle" style="width: 60px; height: 60px;">
                                                </div>
                                            <?php else: ?>
                                                <div class="media-left">
                                                    <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $message->creator->fullName ?>" class="media-object img-circle" style="width: 60px; height: 60px;">
                                                </div>
                                            <?php endif; ?>
                                            <div class="media-body">
                                                <h4 class="media-heading"><?= $message->creator->fullName ?></h4>
                                                <?php if ($message->content): ?>
                                                    <p><?= $message->content ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($message->created_by == Yii::$app->user->identity->id): ?>
                                        <div class="media media-comment my-message">
                                            <div class="media-body">
                                                <h4 class="media-heading"><?= $message->creator->fullName ?></h4>
                                                <?php if ($message->content): ?>
                                                    <p><?= $message->content ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($message->creator->imageUrl && is_file(Yii::getAlias("@uploads/user/{$message->creator->id}/{$message->creator->image}"))): ?>
                                                <div class="media-right">
                                                    <img src="<?= $message->creator->imageUrl ?>" alt="<?= $message->creator->fullName ?>" class="media-object img-circle" style="width: 60px; height: 60px;">
                                                </div>
                                            <?php else: ?>
                                                <div class="media-right">
                                                    <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $message->creator->fullName ?>" class="media-object img-circle" style="width: 60px; height: 60px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="detailsInfoBox">
                                <h3><?= Yii::t('frontend', 'Type your message')?></h3>
                                <div class="listingReview">
                                </div>
                                <?php $form = ActiveForm::begin([
                                    'id' => mb_strtolower($formModel->formName()),
                                    'options' => [
                                        'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
                                        'novalidate' => true,
                                    ],
                                    'validateOnType' => true,
                                    'validateOnBlur' => false,
                                ]); ?>
                                <div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
                                    <?php if (Yii::$app->request->isAjax) : ?>
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                            <div class="modal-title"><?= $this->title ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
                                        <?= $form->field($formModel, 'conversation_id')->hiddenInput([
                                            'value' => $model->conversation_id,
                                        ])->label(false) ?>
                                        <?= $form->field($formModel, 'recipient_id')->hiddenInput([
                                            'value' => $model->recipient_id == Yii::$app->user->id ? $model->created_by : $model->recipient_id,
                                        ])->label(false) ?>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <?= $form->field($formModel, 'content')->textarea(['rows' => 5]) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (Yii::$app->request->isAjax) : ?>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default btn-primary" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
                                            <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success btn-primary']) ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="form-actions floating">
                                            <?= Html::submitButton(Yii::t('label', 'Send'), [
                                                'class' => 'btn btn-primary',
                                                'title' => Yii::t('common', 'Send'),
                                                'data' => [
                                                    'toggle' => 'tooltip',
                                                ],
                                            ]) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>