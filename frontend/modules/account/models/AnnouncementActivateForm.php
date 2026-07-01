<?php

namespace frontend\modules\account\models;

use common\models\Announcement;
use common\models\Feature;
use common\models\Package;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use common\models\SubscriptionHasAnnouncement;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class AnnouncementActivateForm extends Announcement
{

    public $subscription_id;
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->status = static::STATUS_ACTIVE;
        $package = Package::findFreePackage();
        if (empty(Subscription::find()
                ->where([
                    'subscriber_id' => Yii::$app->user->identity->subscriber->id,
                    'package_id' => $package->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'deleted' => Subscription::NO,
                ])
                ->andWhere([
                    'IS NOT', 'package_id', null
                ])
                ->one())) {
            $this->saveSubscription($package);
        }
		$this->subscription_id = $this->subscriptions[0]->id ?: (Subscription::findAvailableAnnouncementSubscriptions(Yii::$app->user->identity->subscriber->id))[0]->id;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['subscription_id', 'integer']
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'subscription_id' => Yii::t('common', 'Subscription'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Saves the Subscription model.
     *
     * @return bool
     */
    protected function saveSubscription($package)
    {
        try {
            $subscription = new Subscription();
            $subscription->code = Subscription::generateUniqueCode();
            $subscription->subscriber_id = Yii::$app->user->identity->subscriber->id;
            $subscription->package_id = $package->id;
            $subscription->price = 0;
            $subscription->type = Subscription::TYPE_FREE;
            $subscription->status = Subscription::STATUS_ACTIVE;
            if (!$subscription->save()) {
                throw new \Exception();
            }
            $this->saveSubscriptionFeatures($package, $subscription);
            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
            return false;
        }
    }


    /**
     * Saves the SubscriptionFeature model(s).
     *
     * @return bool
     */
    protected function saveSubscriptionFeatures($package, $subscription)
    {
        try {
            SubscriptionFeature::deleteAll([
                'subscription_id' => $subscription->id,
            ]);

            $columns = ['subscription_id', 'feature_id', 'name', 'value', 'price', 'renewable', 'type'];
            $rows = [];
            foreach ($package->packageFeatures as $packageFeature) {
                $rows[] = [
                    'subscription_id' => $subscription->id,
                    'feature_id' => $packageFeature->feature_id,
                    'name' => $packageFeature->name,
                    'value' => $packageFeature->value,
                    'price' => $packageFeature->price,
                    'renewable' => $packageFeature->renewable,
                    'type' => SubscriptionFeature::TYPE_PACKAGE_FEATURE,
                ];
            }
            if (!Yii::$app->db->createCommand()->batchInsert(SubscriptionFeature::tableName(), $columns, $rows)->execute()) {
                throw new \Exception();
            }

            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
            return false;
        }
    }

    /**
     * Links the Hunting Area model.
     *
     * @return bool
     * @throws \Exception
     */
    protected function saveSubscriptionHasAnnouncement()
    {
        try {
            $subscriptionHasAnnouncements = SubscriptionHasAnnouncement::findAll([
                'subscription_id' => $this->subscription_id,
                'announcement_id' => $this->id,
            ]);

            if (!$subscriptionHasAnnouncements) {
                $subscriptionHasAnnouncements = new SubscriptionHasAnnouncement();
                $subscriptionHasAnnouncements->announcement_id = $this->id;
                $subscriptionHasAnnouncements->subscription_id = $this->subscription_id;
            }

            if (!$subscriptionHasAnnouncements->save()) {
                throw new \Exception();
            }

            return true;
        } catch (InvalidCallException $e) {
            $this->addError('', $e->getMessage());

            return false;
        }
    }


    /**
     * Saves the model.
     *
     * @return bool|\yii\db\ActiveRecord|self
     */
    public function saveModel()
    {
        $dbTransaction = static::getDb()->beginTransaction();
        try {
            $subscriptionFeatures = SubscriptionFeature::find()
                ->alias('sf')
                ->select([
                    'sf.*',
                ])
                ->where([
                    'sf.subscription_id' => $this->subscription_id,
                    'sf.name' => [Feature::ANNOUNCEMENTS],
                ])
                ->andWhere([
                    'AND',
                    ['IS NOT', 'sf.value', null],
                    ['!=', 'sf.value', ''],
                    ['>', 'sf.value', 0],
                ])
                ->all();

            foreach ($subscriptionFeatures as $subscriptionFeature) {
                $data[$subscriptionFeature->name] = $subscriptionFeature->value;
            }

            $subscriptionAnnouncements = SubscriptionHasAnnouncement::find()
                ->where([
                    'subscription_id' => $this->subscription_id,
                ])
                ->all();

            if (count($subscriptionAnnouncements) < $data['announcements']) {
                $this->status = self::STATUS_ACTIVE;
                if (!$this->save()) {
                    throw new \Exception();
                }
                if (!$this->saveSubscriptionHasAnnouncement()) {
                    throw new \Exception();
                }

                $dbTransaction->commit();
                return true;
            } else {
                $subscription = Subscription::findOne(['id' => $this->subscription_id]);
                $package = Package::findOne(['id' => $subscription['package_id']]);
                $this->addError('subscription_id', Yii::t('frontend', 'The {subscription} subscription has reached the limit of announcements that can be concomitant active.', [
                    'subscription' => $package->translation->name,
                ]));
            }
        } catch(\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }
}
