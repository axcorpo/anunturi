<?php

namespace common\models;

use Yii;
use yii2tech\ar\position\PositionBehavior;

/**
 * This is the model class for table "{{%category_has_option}}".
 *
 * @property int $category_id
 * @property int $option_id
 * @property int $sort_order
 *
 * @property Category $category
 * @property Option $option
 */
class CategoryHasOption extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category_has_option}}';
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
            [['category_id', 'option_id'], 'required'],
            [['category_id', 'option_id', 'sort_order'], 'integer'],
            [['category_id', 'option_id'], 'unique', 'targetAttribute' => ['category_id', 'option_id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
            [['option_id'], 'exist', 'skipOnError' => true, 'targetClass' => Option::class, 'targetAttribute' => ['option_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'category_id' => Yii::t('label', 'Category ID'),
            'option_id' => Yii::t('label', 'Option ID'),
            'sort_order' => Yii::t('label', 'Sort Order'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOption()
    {
        return $this->hasOne(Option::class, ['id' => 'option_id']);
    }
}
