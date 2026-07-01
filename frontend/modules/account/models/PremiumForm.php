<?php

namespace frontend\modules\account\models;

use common\models\Feature;
use common\models\Package;
use common\models\Promotional;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use common\models\SubscriptionHasPromotional;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class PremiumForm extends Promotional
{

    public $subscription_id;
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->status = static::STATUS_ACTIVE;
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
     * Links the Hunting Area model.
     *
     * @return bool
     * @throws \Exception
     */
    protected function saveSubscriptionHasPromotional()
    {
        try {
            $subscriptionHasPromotionals = SubscriptionHasPromotional::findAll([
                'subscription_id' => $this->subscription_id,
                'promotional_id' => $this->id,
            ]);

            if (!$subscriptionHasPromotionals) {
                $subscriptionHasPromotionals = new SubscriptionHasPromotional();
                $subscriptionHasPromotionals->promotional_id = $this->id;
                $subscriptionHasPromotionals->subscription_id = $this->subscription_id;
            }

            if (!$subscriptionHasPromotionals->save()) {
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
                    'sf.name' => [Feature::PROMOTIONS, Feature::PROMOTION_DAYS],
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

            $subscriptionPromotionals = SubscriptionHasPromotional::find()
                ->where([
                    'subscription_id' => $this->subscription_id,
                ])
                ->all();

            if (count($subscriptionPromotionals) < $data['promotions']) {
                $this->announcement_id = $this->announcement_id ?: Yii::$app->security->unmaskToken((string) Yii::$app->request->get('announcement'));
                if (!$this->code) {
                    $this->code = static::generateUniqueCode();
                }
                $currentDate = date('Y-m-d H:i:s');
                $this->start_at = $currentDate;
                $this->end_at = date('Y-m-d H:i:s', strtotime($currentDate . ($data['promotion_days'] ? ' +' . $data['promotion_days'] . ' days' : '')));
                if (!$this->save()) {
                    throw new \Exception();
                }
                if (!$this->saveSubscriptionHasPromotional()) {
                    throw new \Exception();
                }

                $dbTransaction->commit();
                return true;
            }
            else {
                $subscription = Subscription::findOne(['id' => $this->subscription_id]);
                $package = Package::findOne(['id' => $subscription['package_id']]);
                $this->addError('subscription_id', Yii::t('frontend', 'The {subscription} subscription has reached the limit of announcements that can be promoted.', [
                    'subscription' => $package->translation->name,
                ]));
            }
        } catch(\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }
}
