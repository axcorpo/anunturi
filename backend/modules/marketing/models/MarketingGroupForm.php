<?php

namespace backend\modules\marketing\models;

use common\models\Language;
use common\models\MarketingGroupTranslation;
use common\models\MarketingGroup;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class MarketingGroupForm extends MarketingGroup
{
    /**
     * @var array The multilingual name of the MarketingGroup.
     */
    public $name = [];

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
            [['name'], 'required', 'when' => function ($model) {
                return empty($model->name[Yii::$app->language]);
            }, 'whenClient' => 'function (attribute, value) {
            return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
            }'],
            [['name'], 'each', 'rule' => ['trim']],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => Yii::t('label', 'Name'),
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
        $this->name = ArrayHelper::map($this->marketingGroupTranslations, 'language_id', 'name');
	}

    /**
     * Saves the Marketing Group translations.
     *
     * @return bool
     */
    protected function saveMarketingGroupTranslations()
    {
        try {
            $languages = ArrayHelper::getColumn(Yii::$app->translate->discover()['data']['languages'], 'language');
            $defaultLanguage = Language::findOne(['language_id' => Yii::$app->language]);

            foreach (\common\models\Language::findAllLanguages() as $language) {
                $marketingGroupTranslation = MarketingGroupTranslation::findOne([
                    'marketing_group_id' => $this->id,
                    'language_id' => $language->language_id,
                ]);
                if (!$marketingGroupTranslation) {
                    $marketingGroupTranslation = new MarketingGroupTranslation();
                    $marketingGroupTranslation->marketing_group_id = $this->id;
                    $marketingGroupTranslation->language_id = $language->language_id;
                }

                if ($language->language_id != Yii::$app->language && in_array($defaultLanguage->language, $languages) & in_array($language->language, $languages)) {
                    $source = $defaultLanguage->language;
                    $target = $language->language;
                }

                $marketingGroupTranslation->name = $this->name[$language->language_id] ?: ($source && $target ? Yii::$app->translate->translate($source, $target, $this->name[Yii::$app->language])['data']['translations'][0]['translatedText'] : null);

                $this->link('marketingGroupTranslations', $marketingGroupTranslation);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
            if (!$this->saveMarketingGroupTranslations()) {
                throw new \Exception();
            }
			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
