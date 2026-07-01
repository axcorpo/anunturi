<?php

namespace common\models;

use common\helpers\DateHelper;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Class MarketingRecipientSearchForm
 *
 * @property ActiveQuery $query
 */
class MarketingRecipientSearchForm extends Model
{
	// Marketing Recipient
	public $name;
	public $email;
	public $phone;
	public $marketing_group_id;

	/**
	 * @var ActiveQuery The search ActiveQuery.
	 */
	private $_query;


	/**
	 * @inheritdoc
	 */
	public function formName()
	{
		return 'filters';
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->_query = MarketingRecipient::find()
			->alias('mr')
			->select([
				'mr.id',
				'mr.name',
				'mr.email',
				'mr.phone',
			])
            ->joinWith([
                'marketingGroups.marketingGroupTranslations mgt' => function (ActiveQuery $query) {
                    $query->andOnCondition([
                        'mgt.language_id' => Yii::$app->language,
                        'mgt.deleted' => MarketingRecipient::NO,
                    ]);
                },
            ])
			->andWhere([
				'mr.status' => MarketingRecipient::STATUS_ACTIVE,
				'mr.deleted' => MarketingRecipient::NO,
			])
			->groupBy(['mr.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
            //Marketing Recipient
            ['name', 'string'],
            ['email', 'email'],
            ['phone', 'number'],
            [['marketing_group_id'], 'each', 'rule' => ['exist', 'targetClass' => MarketingGroup::class, 'targetAttribute' => ['marketing_group_id' => 'id'], 'skipOnError' => true]],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			// Marketing Recipient
			'name' => Yii::t('label', 'Name'),
			'email' => Yii::t('label', 'Email'),
			'phone' => Yii::t('label', 'Phone'),
            'marketing_group_id' => Yii::t('label', 'Groups'),
		];
	}

	/**
	 * Gets the search ActiveQuery.
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getQuery()
	{
		return $this->_query;
	}

	/**
	 * Applies User model filters.
	 */
	protected function applyMarketingRecipientFilters()
	{
		$this->_query->andFilterWhere([
		    'AND',
			['LIKE', 'mr.name', $this->name],
			['LIKE', 'mr.email', $this->email],
			['LIKE', 'mr.phone', $this->phone],
            ['IN', 'mgt.marketing_group_id', $this->marketing_group_id],
		]);
	}

	/**
	 * Searches the MarketingRecipient models.
	 *
	 * @return null|array|\yii\db\ActiveRecord[]|MarketingRecipient[]
	 */
	public function search()
	{
		$this->applyMarketingRecipientFilters();

		return $this->getQuery()->indexBy('id')->all();
	}
}
