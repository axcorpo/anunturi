<?php

namespace backend\modules\marketing\models;

use common\components\ApplicationBootstrap;
use common\models\Language;
use common\models\MarketingCampaignHasRecipient;
use common\models\MarketingCampaignTranslation;
use common\models\MarketingRecipient;
use common\models\ScheduledTask;
use common\models\TemplateTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class DirectEmailCampaignForm extends DirectMarketingCampaign
{
	/**
	 * @var array The multilingual name of the MarketingCampaign.
	 */
	public $name = [];

	/**
	 * @var array The multilingual subject of the MarketingCampaign.
	 */
	public $subject = [];

	/**
	 * @var array The multilingual content of the MarketingCampaign.
	 */
	public $content = [];

	/**
	 * @var array The filters to be added as serialized data.
	 */
	public $filters = [];

	/**
	 * @var array The targeted MarketingRecipient models IDs.
	 */
	public $targetedRecipients = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->type = static::TYPE_DIRECT;
		$this->variant = static::VARIANT_EMAIL;
		$this->frequency = 30;
		$this->cycle = ScheduledTask::CYCLE_MINUTE;
		$this->status = static::STATUS_INACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['frequency'], 'required'],
			['frequency', 'integer', 'min' => 1, 'max' => 30],
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['subject'], 'required', 'when' => function ($model) {
				return empty($model->subject[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[subject][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['content'], 'required', 'when' => function ($model) {
				return empty($model->content[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[content][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['name', 'subject', 'content'], 'each', 'rule' => ['trim']],
			[['targetedRecipients'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => MarketingRecipient::class, 'targetAttribute' => ['targetedRecipients' => 'id']]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'name' => Yii::t('label', 'Name'),
			'subject' => Yii::t('label', 'Subject'),
			'content' => Yii::t('label', 'Content'),
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
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->name = ArrayHelper::map($this->marketingCampaignTranslations, 'language_id', 'name');
		$this->subject = ArrayHelper::map($this->marketingCampaignTranslations, 'language_id', 'subject');
		$this->content = ArrayHelper::map($this->marketingCampaignTranslations, 'language_id', 'content');
		$this->filters = (array) $this->getUnserializedValue('data')['filters'];
		$this->targetedRecipients = ArrayHelper::getColumn($this->marketingCampaignHasRecipients, 'marketing_recipient_id');
	}

	/**
	 * Saves the Marketing Campaign translations.
	 *
	 * @return bool
	 */
	protected function saveMarketingCampaignTranslations()
	{
		try {
			$languages = ArrayHelper::getColumn(Yii::$app->translate->discover()['data']['languages'], 'language');
			$defaultLanguage = Language::findOne(['language_id' => Yii::$app->language]);

			foreach (\common\models\Language::findAllLanguages() as $language) {
				$marketingCampaignTranslation = MarketingCampaignTranslation::findOne([
					'marketing_campaign_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$marketingCampaignTranslation) {
					$marketingCampaignTranslation = new MarketingCampaignTranslation();
					$marketingCampaignTranslation->marketing_campaign_id = $this->id;
					$marketingCampaignTranslation->language_id = $language->language_id;
				}

				if ($language->language_id != Yii::$app->language && in_array($defaultLanguage->language, $languages) & in_array($language->language, $languages)) {
					$source = $defaultLanguage->language;
					$target = $language->language;
				}

				$marketingCampaignTranslation->name = $this->name[$language->language_id] ?: ($source && $target ? Yii::$app->translate->translate($source, $target, $this->name[Yii::$app->language]) : null);
				$marketingCampaignTranslation->subject = $this->subject[$language->language_id] ?: ($source && $target ? Yii::$app->translate->translate($source, $target, $this->subject[Yii::$app->language]) : null);
				if ($marketingCampaignTranslation->content = $this->content[$language->language_id]) {
					$marketingCampaignTranslation->content = $this->content[$language->language_id];
				} else {
					$translation = html_entity_decode($this->content[Yii::$app->language]);
					$translation = $source && $target ? Yii::$app->translate->translate($source, $target, $translation) : null;
					$translation = html_entity_decode($translation);
					$translation = ApplicationBootstrap::recursiveStripTags($translation);
					$translation = str_replace(['& nbsp;', '< / p>'], [' ', ''], $translation);
					$marketingCampaignTranslation->content = $translation ?: null;
				}

				$this->link('marketingCampaignTranslations', $marketingCampaignTranslation);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the MarketingRecipient models.
	 *
	 * @return bool
	 */
	protected function saveMarketingRecipients()
	{
		try {
			// Target only the selected MarketingRecipient models
			$existingMarketingRecipients = ArrayHelper::getColumn($this->marketingCampaignHasRecipients, 'marketing_recipient_id');

			// Delete all MarketingRecipient models that are not selected
			MarketingCampaignHasRecipient::deleteAll([
				'AND',
				['=', 'marketing_campaign_id', $this->id],
				['NOT IN', 'marketing_recipient_id', $this->targetedRecipients],
			]);

			// Get only the selected and not already existing MarketingRecipient models
			/** @var MarketingRecipient[] $marketingRecipients */
			$marketingRecipients = MarketingRecipient::find()
				->andWhere([
					'id' => $this->targetedRecipients,
					'status' => MarketingRecipient::STATUS_ACTIVE,
					'deleted' => MarketingRecipient::NO,
				])
				->andFilterWhere(['NOT IN', 'id', $existingMarketingRecipients])
				->all();

			foreach ($marketingRecipients as $marketingRecipient) {
				$marketingCampaignHasRecipient = new MarketingCampaignHasRecipient();
				$marketingCampaignHasRecipient->marketing_campaign_id = $this->id;
				$marketingCampaignHasRecipient->marketing_recipient_id = $marketingRecipient->id;
				$marketingCampaignHasRecipient->save();
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$oldAttributes = $this->getOldAttributes();

			$this->setSerializedValue('data', [
				'filters' => $this->filters,
			]);
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveMarketingCampaignTranslations()) {
				throw new \Exception();
			}
			if (!$this->saveMarketingRecipients()) {
				throw new \Exception();
			}
			if (!$this->saveScheduledTask()) {
				throw new \Exception();
			}

			switch ($this->status) {
				case static::STATUS_INACTIVE:
					$this->stopCampaign();
					break;
				case static::STATUS_ACTIVE:
					if ($oldAttributes['status'] == static::STATUS_COMPLETE) {
						$this->restartCampaign();
					} else {
						$this->startCampaign();
					}
					break;
				case static::STATUS_PAUSED:
					$this->pauseCampaign();
					break;
				case static::STATUS_COMPLETE:
					$this->completeCampaign();
					break;
				default: break;
			}

			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
