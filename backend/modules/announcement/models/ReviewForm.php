<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Category;
use common\models\Picture;
use common\models\Review;
use common\models\ReviewTranslation;
use common\models\User;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\web\UploadedFile;

class ReviewForm extends Review
{
    /**
     * @var array the multilingual title for review
     */
    public $title = [];

    /**
     * @var array the multilingual title for review
     */
    public $content = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->status = static::STATUS_ACTIVE;
		$this->type = static::REVIEW_TYPE_ANNOUNCEMENT;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
            [['title'], 'required', 'when' => function ($model) {
                return empty($model->title[Yii::$app->language]);
            }, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[title][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
            [['title', 'content'], 'each', 'rule' => ['trim']],
        ]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'title' => Yii::t('label', 'Title'),
            'content' => Yii::t('label', 'Content'),
            'announcement_id' => Yii::t('label', 'Announcement'),
            'subscriber_id' => Yii::t('label', 'Subscriber'),
            'company_id' => Yii::t('label', 'Company'),
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
        $this->title = ArrayHelper::map($this->reviewTranslations, 'language_id', 'title');
        $this->content = ArrayHelper::map($this->reviewTranslations, 'language_id', 'content');
	}

    /**
     * Saves the translations.
     *
     * @return bool
     */
    protected function saveReviewTranslations()
    {
        try {
            foreach (\common\models\Language::findAllLanguages() as $language) {
                $reviewTranslation = ReviewTranslation::findOne([
                    'review_id' => $this->id,
                    'language_id' => $language->language_id,
                ]);
                if (!$reviewTranslation) {
                    $reviewTranslation = new ReviewTranslation();
                    $reviewTranslation->language_id = $language->language_id;
                }
                $reviewTranslation->title = ModelHelper::getTranslation($this->title, $language->language_id);
                $reviewTranslation->content = $this->content[$language->language_id];

                $this->link('reviewTranslations', $reviewTranslation);
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
	 * @return bool
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
		    $this->confirmed = Review::YES;
			if (!$this->save()) {
				throw new \Exception();
			}
            if (!$this->saveReviewTranslations()) {
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
