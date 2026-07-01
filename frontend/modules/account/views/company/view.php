<?php
/* @var $this yii\web\View */
/* @var $model common\models\Company */

use common\models\Company;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Company')]);
?>

<?php if (Yii::$app->request->isAjax): ?>
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<div class="modal-title"><?= $this->title ?></div>
		</div>
		<div class="modal-body p0">
<?php endif; ?>

			<div class="table-responsive">
				<?= DetailView::widget([
					'model' => $model,
					'options' => [
						'class' => 'table table-striped table-bordered detail-view detail-view-fixed',
					],
					'attributes' => [
                        [
                            'format' => 'raw',
                            'label' => Yii::t('label', 'Image'),
                            'value' => function (Company $model) {
                                if ($model->image && is_file(Yii::getAlias("@uploads/company/{$model->id}/{$model->image}"))) {
                                    $imgTag = Html::img($model->getImageUrl(), [
                                        'alt' => $model->name,
                                    ]);
                                    return Html::a($imgTag, $model->getImageUrl(), [
                                        'class' => 'gallery-thumbnail',
                                        'title' => Yii::t('common', 'Open Gallery'),
                                        'data' => [
                                            'toggle' => 'tooltip',
                                            'fancybox' => 'companies',
                                            'caption' => $model->name,
                                        ],
                                    ]);
                                }
                                return '&mdash;';
                            },
                        ],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Name'),
							'value' => function (Company $model) {
								return $model->name ?: '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Registration Number'),
							'value' => function (Company $model) {
								return $model->registration_number ?: '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Tin'),
							'value' => function (Company $model) {
								return $model->tin ?: '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Email'),
							'value' => function (Company $model) {
								return $model->email ? Html::a($model->email, "mailto:{$model->email}", ['data-prevent-page-overlay' => 'true']) : '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Phone'),
							'value' => function (Company $model) {
								return $model->phone ? Html::a($model->phone, "tel:{$model->phone}", ['data-prevent-page-overlay' => 'true']) : '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Fax'),
							'value' => function (Company $model) {
								return $model->fax ? Html::a($model->fax, "tel:{$model->fax}", ['data-prevent-page-overlay' => 'true']) : '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Address'),
							'value' => function (Company $model) {
								return $model->getFullAddress() ?: '&mdash;';
							},
						],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Latitude'),
                            'value' => function (Company $model) {
                                return $model->latitude;
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Longitude'),
                            'value' => function (Company $model) {
                                return $model->longitude;
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Description'),
                            'value' => function (Company $model) {
                                return $model->translation->description ?: '&mdash;';
                            },
                        ],
                        [
                            'format' => 'html',
                            'label' => Yii::t('label', 'Content'),
                            'value' => function (Company $model) {
                                return $model->translation->content ? Html::tag('div', $model->translation->content, ['class' => 'small-preview-container']) : '&mdash;';
                            },
                        ],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Created At'),
							'value' => function (Company $model) {
								return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
							},
						],
						[
							'format' => 'html',
							'label' => Yii::t('label', 'Updated At'),
							'value' => function (Company $model) {
								return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
							},
						],
					],
				]) ?>
			</div>

<?php if (Yii::$app->request->isAjax): ?>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" data-dismiss="modal"><?= Yii::t('common', 'Close') ?></button>
		</div>
	</div>
</div>
<?php endif; ?>
