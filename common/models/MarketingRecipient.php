<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Html;
use tws\helpers\Url;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%marketing_recipient}}".
 *
 * @property int $id
 * @property string $name
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property MarketingCampaignHasRecipient[] $marketingCampaignHasRecipients
 * @property MarketingCampaign[] $marketingCampaigns
 * @property MarketingGroupHasRecipient[] $marketingGroupHasRecipients
 * @property MarketingGroup[] $marketingGroups
 * @property User $user
 * @property User $creator
 * @property User $updater
 */
class MarketingRecipient extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%marketing_recipient}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['created_at', 'updated_at'], 'default'],
			[['status'], 'required'],
			[['name', 'first_name', 'last_name', 'email', 'phone'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
            'name' => Yii::t('label', 'Name'),
            'first_name' => Yii::t('label', 'First Name'),
            'last_name' => Yii::t('label', 'Last Name'),
            'email' => Yii::t('label', 'Email'),
            'phone' => Yii::t('label', 'Phone'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getMarketingCampaignHasRecipients()
	{
		return $this->hasMany(MarketingCampaignHasRecipient::class, ['marketing_recipient_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getMarketingCampaigns()
	{
		return $this->hasMany(MarketingCampaign::class, ['id' => 'marketing_campaign_id'])->viaTable('{{%marketing_campaign_has_recipient}}', ['marketing_recipient_id' => 'id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroupHasRecipients()
    {
        return $this->hasMany(MarketingGroupHasRecipient::class, ['marketing_recipient_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarketingGroups()
    {
        return $this->hasMany(MarketingGroup::class, ['id' => 'marketing_group_id'])->viaTable('{{%marketing_group_has_recipient}}', ['marketing_recipient_id' => 'id']);
    }

	/**
	 * Gets the user by email address.
	 *
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(User::class, ['email' => 'email']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * Gets the model available short codes.
	 *
	 * @return array
	 */
	public static function getShortCodeItems()
	{
		return [
			'{{APP_NAME}}' => Yii::t('label', 'Application Name'),
			'{{APP_URL}}' => Yii::t('label', 'Application URL'),
			'{{APP_LOGO_URL}}' => Yii::t('label', 'Application Logo URL'),
			'{{APP_LOGO_ALT_URL}}' => Yii::t('label', 'Application Logo Alternative URL'),
			'{{RECIPIENT_NAME}}' => Yii::t('label', 'Full Name'),
			'{{RECIPIENT_FIRST_NAME}}' => Yii::t('label', 'First Name'),
			'{{RECIPIENT_LAST_NAME}}' => Yii::t('label', 'Last Name'),
			'{{RECIPIENT_EMAIL}}' => Yii::t('label', 'Email'),
			'{{RECIPIENT_PHONE}}' => Yii::t('label', 'Phone'),
		];
	}

	/**
	 * Gets the values for the model available short codes.
	 *
	 * @return array
	 */
	public function getShortCodeValues()
	{
		$shortCodes = [
			'{{APP_NAME}}' => Yii::$app->name,
			'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
			'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
			'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
			'{{RECIPIENT_NAME}}' => $this->name ?: implode(' ', array_filter([
                $this->last_name,
                $this->first_name,
            ])),
			'{{RECIPIENT_FIRST_NAME}}' => $this->first_name,
			'{{RECIPIENT_LAST_NAME}}' => $this->last_name,
			'{{RECIPIENT_EMAIL}}' => $this->email,
			'{{RECIPIENT_PHONE}}' => $this->phone,
		];

		return $shortCodes;
	}
}
