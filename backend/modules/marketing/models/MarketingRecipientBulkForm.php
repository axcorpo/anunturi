<?php

namespace backend\modules\marketing\models;

use common\models\MarketingGroup;
use common\models\MarketingRecipient;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class MarketingRecipientBulkForm extends MarketingRecipient
{
    /**
     * @var int|array The marketing group model ID.
     */
    public $marketing_group_id;

    public $status;

    public $columns = [];

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
            [['status'], 'required'],
            [['marketing_group_id'], 'each', 'rule' => ['exist', 'targetClass' => MarketingGroup::class, 'targetAttribute' => ['marketing_group_id' => 'id'], 'skipOnError' => true]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'columns' => Yii::t('common', 'Columns'),
            'marketing_group_id' => Yii::t('label', 'Marketing Groups'),
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
        $this->marketing_group_id = ArrayHelper::getColumn($this->marketingGroups, 'id');
	}

    /**
     * Links the Category models.
     *
     * @return bool
     * @throws \Throwable
     */
    protected function linkMarketingGroups()
    {
        try {
            $this->unlinkAll('marketingGroups', true);
            if (empty($this->marketing_group_id)) {
                return true;
            }
            $groups = MarketingGroup::findAll([
                'id' => $this->marketing_group_id,
                'status' => MarketingGroup::STATUS_ACTIVE,
                'deleted' => MarketingGroup::NO,
            ]);
            foreach ($groups as $group) {
                $this->link('marketingGroups', $group);
            }
            return true;
        } catch (InvalidCallException $e) {
            $this->addError('', $e->getMessage());
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
            if (!$this->linkMarketingGroups()) {
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
