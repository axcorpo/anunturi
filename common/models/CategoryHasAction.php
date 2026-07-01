<?php

namespace common\models;

use Yii;
use yii2tech\ar\position\PositionBehavior;

/**
 * This is the model class for table "{{%category_has_action}}".
 *
 * @property int $category_id
 * @property int $action_id
 * @property int $sort_order
 *
 * @property Action $action
 * @property Category $category
 */
class CategoryHasAction extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category_has_action}}';
    }

	/**
	 * @inheritdoc
	 * @throws \Exception
	 */
	public function behaviors()
	{
		return [
			'PositionBehavior' => [
				'class' => PositionBehavior::class,
				'positionAttribute' => 'sort_order',
				'groupAttributes' => ['category_id'],
			],
		];
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'action_id'], 'required'],
            [['category_id', 'action_id', 'sort_order'], 'integer'],
            [['category_id', 'action_id'], 'unique', 'targetAttribute' => ['category_id', 'action_id']],
            [['action_id'], 'exist', 'skipOnError' => true, 'targetClass' => Action::class, 'targetAttribute' => ['action_id' => 'id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'category_id' => Yii::t('label', 'Category ID'),
            'action_id' => Yii::t('label', 'Action ID'),
            'sort_order' => Yii::t('label', 'Sort Order'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAction()
    {
        return $this->hasOne(Action::class, ['id' => 'action_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }
}
