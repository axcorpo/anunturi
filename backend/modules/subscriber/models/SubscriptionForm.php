<?php

namespace backend\modules\subscriber\models;

use common\models\Feature;
use common\models\PackageFeature;
use common\models\ScheduledTask;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class SubscriptionForm extends Subscription
{
    public $announcements;

    public $promotions;

    public $promotion_days;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->price = 0;
		$this->currency = Yii::$app->settings->get('currencyCode');
		$this->billing_period = 1;
		$this->billing_cycle = ScheduledTask::CYCLE_MONTH;
		$this->type = static::TYPE_FREE;
		$this->status = static::STATUS_INACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function attributes()
	{
		$attributes = parent::attributes();

		foreach (array_keys(Feature::getFeatureLabels()) as $packageFeatureName) {
			$attributes[] = $packageFeatureName;
		}

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['package_id', 'type', 'billing_period', 'billing_cycle'], 'required'],
			[['billing_period'], 'integer', 'min' => 1],

			// Package features
			[[
				Feature::ANNOUNCEMENTS,
			], 'required'],
			[[
				Feature::ANNOUNCEMENTS,
			], 'integer', 'min' => 1],
			[array_keys(Feature::getFeatureLabels()), 'trim'],
			[array_keys(Feature::getFeatureLabels()), 'default', 'value' => null],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), Feature::getFeatureLabels(), [
			'package_id' => Yii::t('label', 'Package'),
        ]);
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		foreach ($this->subscriptionFeatures as $subscriptionFeature) {
			if ($this->hasAttribute($subscriptionFeature->name)) {
				$this->{$subscriptionFeature->name} = $subscriptionFeature->value;
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * Saves the SubscriptionFeature models.
	 *
	 * @return bool
	 */
	protected function saveSubscriptionFeatures()
	{
		try {
			$availableSubscriptionFeatures = Feature::getFeatureLabels();
			$existingSubscriptionFeatures = $this->getSubscriptionFeatures()->indexBy('name')->all();
			$newSubscriptionFeatures = array_diff(array_keys($availableSubscriptionFeatures), array_keys($existingSubscriptionFeatures));
			$subscriptionFeatures = [];

			/** @var SubscriptionFeature[] $existingSubscriptionFeatures */
			foreach ($existingSubscriptionFeatures as $existingSubscriptionFeature) {
				if ($this->hasAttribute($existingSubscriptionFeature->name)) {
					$existingSubscriptionFeature->value = $this->{$existingSubscriptionFeature->name};
					$subscriptionFeatures[] = $existingSubscriptionFeature;
				}
			}

			if (!empty($newSubscriptionFeatures)) {
				$features = Feature::findAllFeatures();
				foreach ($newSubscriptionFeatures as $newSubscriptionFeature) {
				    $package = $this->package->id;
				    $packageFeature = PackageFeature::findOne(['package_id' => $package, 'name' => $newSubscriptionFeature]);
					$subscriptionFeature = new SubscriptionFeature();
					$subscriptionFeature->subscription_id = $this->id;
					$subscriptionFeature->feature_id = $features[$newSubscriptionFeature]->id;
					$subscriptionFeature->name = $features[$newSubscriptionFeature]->name;
					if ($packageFeature['value'] != 0) {
                        $subscriptionFeature->value = $packageFeature['value'];
                    }
					else {
                        $subscriptionFeature->value = (string) 0;
                    }
					$subscriptionFeature->renewable = $features[$newSubscriptionFeature]->renewable;
					$subscriptionFeatures[] = $subscriptionFeature;
				}
			}

			foreach ($subscriptionFeatures as $subscriptionFeature) {
				if (!$subscriptionFeature->save()) {
					$this->addErrors($subscriptionFeature->getErrors());
					throw new \Exception('Cannot save the SubscriptionFeature models.');
				}
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!empty($this->start_at)) {
				$this->end_at = $this->getEndAtDate()->format('Y-m-d H:i:s');
			}
			$this->type = $this->package->type;
			// Update standard package price based on the billing period and cycle
			if ($this->type == static::TYPE_STANDARD) {
				$this->trial_period = $this->package->trial_period;
				$this->trial_cycle = $this->package->trial_cycle;
				$this->currency = $this->package->currency;
				$this->price = $this->package->price * $this->billing_period;
				if ($this->billing_cycle == ScheduledTask::CYCLE_YEAR) {
					$this->price *= 12;
				}
			}
            if (!$this->code) {
                $this->code = static::generateUniqueCode();
            }
			if (!$this->save(true, parent::attributes())) {
				throw new \Exception();
			}
			if (!$this->saveSubscriptionFeatures()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
