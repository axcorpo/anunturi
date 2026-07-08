<?php

namespace common\models;

use Yii;
use yii2tech\ar\position\PositionBehavior;

/**
 * This is the model class for table "{{%announcement_has_action}}".
 *
 * @property int $announcement_id
 * @property int $action_id
 * @property string $price
 * @property string $uom
 * @property string $currency
 * @property int $quantity
 * @property int $package
 *
 * @property Action $action
 * @property Announcement $announcement
 */
class AnnouncementHasAction extends UuidActiveRecord
{
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%announcement_has_action}}';
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
				'groupAttributes' => ['announcement_id'],
			],
		];
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['announcement_id', 'action_id'], 'required'],
            [['announcement_id', 'action_id', 'quantity', 'package'], 'integer'],
            [['price'], 'number'],
            [['uom'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['announcement_id', 'action_id'], 'unique', 'targetAttribute' => ['announcement_id', 'action_id']],
            [['action_id'], 'exist', 'skipOnError' => true, 'targetClass' => Action::class, 'targetAttribute' => ['action_id' => 'id']],
            [['announcement_id'], 'exist', 'skipOnError' => true, 'targetClass' => Announcement::class, 'targetAttribute' => ['announcement_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'announcement_id' => Yii::t('label', 'Announcement ID'),
            'action_id' => Yii::t('label', 'Action ID'),
            'price' => Yii::t('label', 'Price'),
            'uom' => Yii::t('label', 'Uom'),
            'currency' => Yii::t('label', 'Currency'),
            'quantity' => Yii::t('label', 'Quantity'),
            'package' => Yii::t('label', 'Package'),
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
    public function getAnnouncement()
    {
        return $this->hasOne(Announcement::class, ['id' => 'announcement_id']);
    }
}
