<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use common\models\Auction;
use common\models\ScheduledTask;
use common\models\Bid;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Bid')]);
?>

<?php if (Yii::$app->request->isAjax): ?>
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<div class="modal-title"><?= $this->title ?></div>
		</div>
		<div class="modal-body">
<?php endif; ?>

			<div class="table-responsive">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => [
                        'class' => 'table table-striped table-bordered detail-view detail-view-fixed',
                    ],
                    'attributes' => [
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Auction'),
                            'value' => function (Bid $model) {
                                if ($model->auction) {
                                    return Html::a($model->auction->id . ' - ' . Auction::getPositionLabels()[$model->auction->position], ['/commercial-manager/auction/view', 'id' => $model->auction->id], ['target' => '']);
                                }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Broker'),
                            'value' => function (Bid $model) {
                                if ($model->broker) {
                                    return Html::a($model->broker->user->fullName, ['/commercial-manager/broker/view', 'id' => $model->broker->id], ['target' => '']);
                                }
                                return '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Price'),
                            'value' => function (Bid $model) {
                                return $model->price ? $model->price . ' ' . $model->currency : '&mdash;';
                            },
                        ],
                        'created_at:datetime',
                        'updated_at:datetime',
						[
							'format' => 'html',
							'label' => Yii::t('common', 'Position'),
							'value' => function (Bid $model) {
								$count = Bid::find()
									->where([
										'AND',
										['=', 'auction_id', $model->auction_id],
										['>', 'price', $model->price],
									])
									->count();
								return $count + 1;
							},
						],
                        [
                            'format' => 'html',
                            'label' => Yii::t('common', 'Status'),
                            'value' => function (Bid $model) {
                                $status = $model::getStatusLabels()[$model->status];

                                return Html::tag('span', $status['label'], ['class' => 'label label-' . $status['color']]);
                            },
                        ],
                    ],
                ]) ?>
			</div>

<?php if (Yii::$app->request->isAjax): ?>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" data-dismiss="modal" style="padding: 15.5px 20px;"><?= Yii::t('common', 'Close') ?></button>
		</div>
	</div>
</div>
<?php endif; ?>
