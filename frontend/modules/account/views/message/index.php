<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Message;
use common\models\User;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\StringHelper;
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
		<div class="row">
			<div class="col-md-3 col-sm-5 col-xs-12 mb30">
				<div class="dashboardBoxBg">
					<ul class="dashboard-vertical-menu">
						<?php if (Yii::$app->request->get('type') === 'received'): ?>
							<li class=""><a class="" href="<?= Url::to(['/account/message/index']) ?>"><i class="fa fa-envelope-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'All') ?></a></li>
							<li class="active"><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'received']) ?>"><i class="fa fa-inbox icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Received') ?> </a></li>
							<li><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'sent']) ?>"><i class="fa fa-paper-plane-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Sent') ?></a></li>
						<?php elseif (Yii::$app->request->get('type') === 'sent'): ?>
							<li class=""><a class="" href="<?= Url::to(['/account/message/index']) ?>"><i class="fa fa-envelope-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'All') ?></a></li>
							<li class=""><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'received']) ?>"><i class="fa fa-inbox icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Received') ?></a></li>
							<li class="active"><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'sent']) ?>"><i class="fa fa-paper-plane-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Sent') ?></a></li>
						<?php elseif (empty(Yii::$app->request->get('type'))): ?>
							<li class="active"><a class="" href="<?= Url::to(['/account/message/index']) ?>"><i class="fa fa-envelope-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'All') ?></a></li>
							<li class=""><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'received']) ?>"><i class="fa fa-inbox icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Received') ?></a></li>
							<li class=""><a class="" href="<?= Url::to(['/account/message/index', 'type' => 'sent']) ?>"><i class="fa fa-paper-plane-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend', 'Sent') ?></a></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
			<div class="col-md-9 col-sm-7 col-xs-12 dashboardBoxBg">
				<?php $messages = $dataProvider->getModels() ?>
				<?php if ($messages): ?>
					<?php foreach ($messages as $message): ?>
						<?php
						$actions = [];
						if ($message->announcement->actions) {
							foreach ($message->announcement->actions as $action) {
								$actions[] = \common\models\Action::getMyTypes()[$action->type];
							}
						}
                        $counter = Message::find()
                            ->alias('m')
                            ->select([
                                'COUNT([[m.id]]) AS counter',
                            ])
                            ->where([
                                'm.status' => Message::STATUS_ACTIVE,
                                'm.deleted' => Message::NO,
                                'm.conversation_id' => $message->conversation_id
                            ])
                            ->asArray()
                            ->one();
						?>
						<div class="listContent dashboard-ad-list">
                            <?php if(empty(Yii::$app->request->get('type'))): ?>
							<div class="row">
								<div class="col-sm-9 col-xs-12">
									<div class="categoryDetails">
                                        <?php if ($message->announcement_id): ?>
										<h4 class="media-heading"><?= $message->created_by == Yii::$app->user->id ? User::findOne(['id' => $message->recipient_id])->shortName . ' - ' : $message->creator->shortName . ' - ' ?> <a href="<?= Url::to(['/account/message/view', 'id' => $message->id]) ?>"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $message->announcement->translation->title ?></a></h4>
                                        <?php else: ?>
                                            <h4 class="media-heading"><?= $message->created_by == Yii::$app->user->id ? User::findOne(['id' => $message->recipient_id])->shortName . ' - ' : $message->creator->shortName . ' - ' ?> <a href="<?= Url::to(['/account/message/view', 'id' => $message->id]) ?>"><?= Yii::t('common', 'Private Message') ?></a></h4>
                                        <?php endif; ?>
                                        <p><i class="fa fa-commenting-o icon-dash" aria-hidden="true"></i> <?= $counter['counter'] ?></p>
                                        <?php $lastMessage = Message::find()
                                            ->alias('m')
                                            ->select('m.*')
                                            ->where([
                                                'recipient_id' => Yii::$app->user->id,
                                            ])
                                            ->andWhere([
                                                'm.status' => Message::STATUS_ACTIVE,
                                                'm.deleted' => Message::NO,
                                                'm.conversation_id' => $message->conversation_id,
                                            ])
                                            ->orderBy([
                                                    'id' => SORT_DESC,
                                            ])
                                            ->one();
                                        ?>
										<p> <?php if (!$lastMessage->seen_at): ?>
                                            <strong><?= StringHelper::truncate($lastMessage->content, 220) ?></strong>
                                            <?php else: ?>
                                            <?= StringHelper::truncate($lastMessage->content, 220) ?>
                                            <?php endif; ?>
                                        </p>
									</div>
								</div>
								<div class="col-sm-3 col-xs-12">
									<p><i class="fa fa-calendar-check-o icon-dash" aria-hidden="true"></i>	<?=	Yii::$app->formatter->asDateTime($message->conversation->updated_at, 'dd MMM yyyy'); ?>
										<br><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i> <?=	Yii::$app->formatter->asDateTime($message->conversation->updated_at, 'HH:i'); ?></p>
								</div>
							</div>
                            <?php elseif(Yii::$app->request->get('type') === 'received'): ?>
                                <div class="row">
                                    <div class="col-sm-9 col-xs-12">
                                        <div class="categoryDetails">
                                            <?php if ($message->announcement_id): ?>
                                                <h4 class="media-heading"><?= $message->created_by == Yii::$app->user->id ? User::findOne(['id' => $message->recipient_id])->shortName . ' - ' : $message->creator->shortName . ' - ' ?> <a href="<?= Url::to(['/account/message/view', 'id' => $message->id]) ?>"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $message->announcement->translation->title ?></a></h4>
                                            <?php else: ?>
                                                <h4 class="media-heading"><?= $message->created_by == Yii::$app->user->id ? User::findOne(['id' => $message->recipient_id])->shortName . ' - ' : $message->creator->shortName . ' - ' ?> <a href="<?= Url::to(['/account/message/view', 'id' => $message->id]) ?>"><?= Yii::t('common', 'Private Message') ?></a></h4>
                                            <?php endif; ?>
                                            <p> <?php if (!$message->seen_at): ?>
                                                    <strong><?= StringHelper::truncate($message->content, 220) ?></strong>
                                                <?php else: ?>
                                                    <?= StringHelper::truncate($message->content, 220) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-xs-12">
                                        <p><i class="fa fa-calendar-check-o icon-dash" aria-hidden="true"></i>	<?=	Yii::$app->formatter->asDateTime($message->created_at, 'dd MMM yyyy'); ?>
                                            <br><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i> <?=	Yii::$app->formatter->asDateTime($message->created_at, 'HH:i'); ?></p>
                                    </div>
                                </div>
                            <?php elseif(Yii::$app->request->get('type') === 'sent'): ?>
                                <div class="row">
                                    <div class="col-sm-9 col-xs-12">
                                        <div class="categoryDetails">
                                            <?php if ($message->announcement_id): ?>
                                                <h4 class="media-heading"><?= $message->created_by == Yii::$app->user->id ? User::findOne(['id' => $message->recipient_id])->shortName . ' - ' : $message->creator->shortName . ' - ' ?> <a href="<?= Url::to(['/account/message/view', 'id' => $message->id]) ?>"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $message->announcement->translation->title ?></a></h4>
                                            <?php else: ?>
                                                <h4 class="media-heading"><?= $message->created_by == Yii::$app->user->id ? User::findOne(['id' => $message->recipient_id])->shortName . ' - ' : $message->creator->shortName . ' - ' ?> <a href="<?= Url::to(['/account/message/view', 'id' => $message->id]) ?>"><?= Yii::t('common', 'Private Message') ?></a></h4>
                                            <?php endif; ?>
                                            <p> <?php if (!$message->seen_at): ?>
                                                    <strong><?= StringHelper::truncate($message->content, 220) ?></strong>
                                                <?php else: ?>
                                                    <?= StringHelper::truncate($message->content, 220) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-xs-12">
                                        <p><i class="fa fa-calendar-check-o icon-dash" aria-hidden="true"></i>	<?=	Yii::$app->formatter->asDateTime($message->created_at, 'dd MMM yyyy'); ?>
                                            <br><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i> <?=	Yii::$app->formatter->asDateTime($message->created_at, 'HH:i'); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
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
				<?php else: ?>
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
							<div class="alert alert-info mt-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>