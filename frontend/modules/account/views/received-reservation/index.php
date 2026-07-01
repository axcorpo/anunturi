<?php

/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\Reservation;
use common\widgets\ActiveForm;
use common\widgets\fullcalendar\FullCalendar;
use kartik\file\FileInput;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
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
                    <?php if ($reservations = $dataProvider->getModels()): ?>
                        <ul class="dashboard-vertical-menu">
                            <li class="<?= !Yii::$app->request->get('status') ? 'active' : '' ?>"><a class="" href="<?= Url::to(['/account/received-placed-reservation/index']) ?>"><i class="fa fa-briefcase icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'All') . ' ('  . $totalCount . ')' ?></a></li>
                            <li class="<?= Yii::$app->request->get('status') == Reservation::STATUS_ACTIVE ? 'active' : '' ?>"><a class="" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_ACTIVE]) ?>"><i class="fa fa-check-square-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Active') . ' (' . $activeCount . ')' ?></a></li>
                            <li><a class="<?= Yii::$app->request->get('status') == Reservation::STATUS_PENDING ? 'active' : '' ?>" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_PENDING]) ?>"><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Pending') . ' ('  . $pendingCount . ')' ?></a></li>
                            <li><a class="<?= Yii::$app->request->get('status') == Reservation::STATUS_FINISHED ? 'active' : '' ?>" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_FINISHED]) ?>"><i class="fa fa-hourglass-end icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Finished') . ' ('  . $finishedCount . ')' ?></a></li>
                            <li><a class="<?= Yii::$app->request->get('status') == Reservation::STATUS_CANCELED ? 'active' : '' ?>" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_CANCELED]) ?>"><i class="fa fa-ban icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Canceled') . ' ('  . $canceledCount . ')' ?></a></li>
                        </ul>
                    <?php else: ?>
                        <ul class="dashboard-vertical-menu">
                            <li class="<?= !Yii::$app->request->get('status') ? 'active' : '' ?>"><a class="" href="<?= Url::to(['/account/received-placed-reservation/index']) ?>"><i class="fa fa-briefcase icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'All') . ' ('  . $totalCount . ')' ?></a></li>
                            <li class="<?= Yii::$app->request->get('status') == Reservation::STATUS_ACTIVE ? 'active' : '' ?>"><a class="" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_ACTIVE]) ?>"><i class="fa fa-check-square-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Active') . ' (' . $activeCount . ')' ?></a></li>
                            <li><a class="<?= Yii::$app->request->get('status') == Reservation::STATUS_PENDING ? 'active' : '' ?>" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_PENDING]) ?>"><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Pending')  . ' ('  . $pendingCount . ')' ?></a></li>
                            <li><a class="<?= Yii::$app->request->get('status') == Reservation::STATUS_FINISHED ? 'active' : '' ?>" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_FINISHED]) ?>"><i class="fa fa-hourglass-end icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Finished')  . '('  . $finishedCount . ')' ?></a></li>
                            <li><a class="<?= Yii::$app->request->get('status') == Reservation::STATUS_CANCELED ? 'active' : '' ?>" href="<?= Url::to(['/account/received-placed-reservation/index', 'status' => Reservation::STATUS_CANCELED]) ?>"><i class="fa fa-ban icon-dash" aria-hidden="true"></i><?= Yii::t('frontend' ,'Canceled')  . ' ('  . $canceledCount . ')' ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($reservations): ?>
                <div class="col-md-9 col-sm-7 col-xs-12 dashboardBoxBg">
                    <?= FullCalendar::widget([
                        'id' => 'placed-reservation-calendar',
                        'clientOptions' => [
                            'header' => [
                                'left' => 'prev,next today',
                                'center' => 'title',
                                'right' => 'month,agendaWeek,agendaDay',
                            ],
                            'navLinks' => true,
                            'weekNumbers' => true,
                            'editable' => false,
                            'fixedWeekCount' => false,
                            'showNonCurrentDates' => false,
                            'eventLimit' => true,
                            'events' => [
                                'method' => 'GET',
                                'url' => Url::to(['/account/unavailability/list']),
                                'startParam' => 'start_date',
                                'endParam' => 'end_date',
                            ],
                            'lazyFetching' => true,
                            'loading' => new JsExpression('function (isLoading) {
			                            $(this).overlay(isLoading ? "" : "remove");
		                            }'),
                            'eventRender' => new JsExpression('function (event, $element, e) {
			                            $element.find(".fc-title").html(event.title);
		                            }'),
                        ],
                    ]) ?>
                    <?php foreach ($reservations as $reservation) : ?>
                        <?php
                        $actions = [];
                        foreach ($reservation->announcement->actions as $action) {
                            $actions[] = \common\models\Action::getMyTypes()[$action->type];
                        }
                        ?>
                        <?php $reservationAnnouncementTranslation = $reservation->announcement->translation; ?>
                        <div class="listContent dashboard-ad-list">
                            <div class="row">
                                <div class="col-sm-3 col-xs-12">
                                    <div class="categoryImage">
                                        <?php if ($reservation->announcement->imageUrl && is_file(Yii::getAlias("@uploads/announcement/{$reservation->announcement->id}/{$reservation->announcement->image}"))): ?>
                                            <img src="<?= $reservation->announcement->imageUrl; ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $reservationAnnouncementTranslation->title ?>" class="img-ratio-object img-responsive img-rounded">
                                        <?php else: ?>
                                            <img src="<?= Yii::getAlias('@web/img/img-placeholder-blank.png') ?>" alt="<?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $reservationAnnouncementTranslation->title ?>" class="img-ratio-object img-responsive img-rounded">
                                        <?php endif; ?>
                                        <span class="label label-<?= str_replace([0,1,2,3,4], ['inactive', 'success', 'warning', 'info', 'danger'], $reservation->status) ?>"><i class="fa fa-check-square-o icon-dash" aria-hidden="true"></i> <?= Reservation::getStatusLabels()[$reservation->status]['label'] ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-9 col-xs-12">
                                    <div class="col-sm-6 col-xs-12">
                                        <div class="categoryDetails">
                                            <h3><a href="<?= Url::to(['/announcement/default/view', 'slug' => $reservationAnnouncementTranslation->slug]) ?>" style="color: #222222"><?= $actions ? implode(', ', $actions) . ' - ' : '' ?><?= $reservationAnnouncementTranslation->title ?></a></h3>
                                            <?php if ($reservation->code): ?>
                                                <p><?= Yii::t('frontend', 'Reservation Code') . ': '  . $reservation->code ?></p>
                                            <?php endif; ?>
                                            <?php if ($reservation->created_at): ?>
                                                <p><i class="fa fa-clock-o icon-dash" aria-hidden="true"></i> <?= Yii::t('frontend', 'Reserved at') . ' ' . Yii::$app->formatter->asDate($reservation->created_at, 'dd, MMMM yyyy') ?> </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xs-12">
                                        <div class="categoryDetails">
                                            <div class="row reservation-dates">
                                                <div class="col-sm-6 col-xs-6 checkin-border">
                                                    <p><?= Yii::t('frontend', 'From') ?><br><i class="fa fa-calendar-check-o icon-dash" aria-hidden="true"></i> <?= Yii::$app->formatter->asDate($reservation->start_at, 'dd, MMMM yyyy') ?></p>
                                                </div>
                                                <div class="col-sm-6 col-xs-6">
                                                    <p><?= Yii::t('frontend', 'To') ?><br><i class="fa fa-calendar-check-o icon-dash" aria-hidden="true"></i> <?= Yii::$app->formatter->asDate($reservation->end_at, 'dd, MMMM yyyy') ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <ul class="list-inline list-tag">
                                            <?php if ($reservation->status != Reservation::STATUS_ACTIVE): ?>
                                            <li>
                                                <?= Html::a(Yii::t('common', 'Activate'),['/account/received-placed-reservation/activate', 'id' => $reservation->id], [
                                                    'class' => 'btn btn-primary',
                                                    'title' => Yii::t('common', 'Activate'),
                                                    'data' => [
                                                        'toggle' => 'tooltip',
                                                        'method' => 'POST',
                                                        'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                                                    ],
                                                ]) ?>
                                            </li>
                                            <?php endif; ?>
                                            <li><?= Html::a(Yii::t('common', 'Update'),['/account/placed-reservation/update', 'id' => $reservation->id], [
                                                    'class' => 'btn btn-primary',
                                                    'data' => [
                                                        'popup-action' => '',
                                                        'popup-done' => ['redrawDataTable' => '#dt-reservations'],
                                                    ],
                                                ]) ?>
                                            </li>
                                            <li><?= Html::a(Yii::t('common', 'Cancel'), ['/account/received-placed-reservation/cancel', 'id' => $reservation->id], [
                                                    'class' => 'btn btn-primary btn-deactivate',
                                                    'title' => Yii::t('common', 'Cancel'),
                                                    'data' => [
                                                        'method' => 'POST',
                                                        'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
                                                    ],
                                                ]) ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
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
                </div>
            <?php else: ?>
                <div class="col-md-9 col-sm-7 col-xs-12 dashboardBoxBg">
                    <div class="alert alert-info mt-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $this->registerJs('
	$(document).on("done.popupAction", function (e, $element, $modal, response) {
		if ($modal.hasClass("modal-placed-reservation")) {
			$("#placed-reservation-calendar").fullCalendar("refetchEvents");
		}
	});
'); ?>
