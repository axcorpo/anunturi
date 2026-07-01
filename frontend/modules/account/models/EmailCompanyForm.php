<?php

namespace frontend\modules\account\models;

use Yii;
use yii\base\Model;

class EmailCompanyForm extends Model
{
    /**
     * @var string The contact name.
     */
    public $name;

    /**
     * @var string The email address.
     */
    public $email;

    /**
     * @var string The phone number.
     */
    public $phone;

    /**
     * @var string The subject of the email.
     */
    public $subject;

    /**
     * @var string The message of the email.
     */
    public $message;

    /**
     * @var string The honeypot field.
     */
    public $verifyCode;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['subject', 'message'], 'required'],
			['verifyCode', 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'subject' => Yii::t('label', 'Subject'),
			'message' => Yii::t('label', 'Message'),
		];
	}
}
