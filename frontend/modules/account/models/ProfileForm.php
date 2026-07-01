<?php

namespace frontend\modules\account\models;

use borales\extensions\phoneInput\PhoneInputValidator;
use common\models\Country;
use common\models\Subscriber;
use common\models\User;
use Yii;
use yii\base\Model;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class ProfileForm extends User
{
	/**
	 * @var UploadedFile The imageFile.
	 */
	public $imageFile;

	/**
	 * @var string The personal identification number.
	 */
	public $pin;

	/**
	 * @var string The street name.
	 */
	public $street_name;

	/**
	 * @var string The street number.
	 */
	public $street_number;

	/**
	 * @var string The locality.
	 */
	public $locality;

	/**
	 * @var string The zip code.
	 */
	public $zip_code;

	/**
	 * @var string The county.
	 */
	public $county;

	/**
	 * @var string The country.
	 */
	public $country;

	/**
	 * @var string The date of birth.
	 */
	public $date_of_birth;

	/**
	 * @var string The new password.
	 */
	public $new_password;

	/**
	 * @var string The new password confirm.
	 */
	public $new_password_confirm;

	public $_subscriber;


	public $bank_account_holder;

	public $bank_name;

	public $bank_iban;

	public $bank_bic;




	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'phone', 'gender'], 'required'],
			['phone', PhoneInputValidator::class, 'skipOnEmpty' => true],
			[['phone'], 'unique'],
			[['gender'], 'integer'],
			['gender', 'in', 'range' => [1, 2]],
			[['date_of_birth'], 'safe'],
			[['new_password', 'new_password_confirm', 'first_name', 'middle_name', 'last_name', 'phone', 'pin', 'street_name', 'street_number', 'locality', 'zip_code', 'county', 'bank_account_holder', 'bank_name', 'bank_iban', 'bank_bic'], 'string', 'max' => 255],
			[['new_password', 'new_password_confirm', 'first_name', 'middle_name', 'last_name', 'phone', 'pin', 'street_name', 'street_number', 'locality', 'zip_code', 'county', 'bank_account_holder', 'bank_name', 'bank_iban', 'bank_bic'], 'trim'],
			['new_password', 'string', 'min' => 6],
			['new_password_confirm', 'required', 'when' => function ($model) {
				return !empty($model->new_password);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[new_password]\"]").val() != "";
			}'],
			['new_password_confirm', 'compare', 'compareAttribute' => 'new_password', 'message' => Yii::t('common', 'Passwords don\'t match.')],
			[['imageFile'], 'file', 'extensions' => Yii::$app->params['image.extensions'], 'mimeTypes' => Yii::$app->params['image.mimeTypes'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'skipOnEmpty' => true],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['country'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country' => 'iso_alpha2']],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'imageFile' => Yii::t('label', 'Image'),
			'pin' => Yii::t('label', 'Personal Identification Number'),
			'street_name' => Yii::t('label', 'Street Name'),
			'street_number' => Yii::t('label', 'Street Number'),
			'locality' => Yii::t('label', 'Locality'),
			'zip_code' => Yii::t('label', 'Zip Code'),
			'county' => Yii::t('label', 'County'),
			'country' => Yii::t('label', 'Country'),
			'date_of_birth' => Yii::t('label', 'Date Of Birth'),
			'new_password' => Yii::t('label', 'New Password'),
			'new_password_confirm' => Yii::t('label', 'Confirm New Password'),
			'marketing_recipient' => Yii::t('label', 'Receive newsletter emails'),
			'bank_account_holder' => Yii::t('label', 'Account Holder'),
			'bank_name' => Yii::t('label', 'Bank'),
			'bank_iban' => Yii::t('label', 'IBAN'),
			'bank_bic' => Yii::t('label', 'SWIFT / BIC'),
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
        $subscriber = Subscriber::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->andWhere([
                'deleted' => Subscriber::NO,
                'status' => Subscriber::STATUS_ACTIVE,
            ])->one();
		$this->pin = $subscriber->pin;
		$this->street_name = $subscriber->street_name;
		$this->street_number = $subscriber->street_number;
		$this->locality = $subscriber->locality;
		$this->zip_code = $subscriber->zip_code;
		$this->county = $subscriber->county;
		$this->country = $subscriber->country;
		$this->bank_account_holder = $subscriber->bank_account_holder;
		$this->bank_name = $subscriber->bank_name;
		$this->bank_iban = $subscriber->bank_iban;
		$this->bank_bic = $subscriber->bank_bic;
		$this->date_of_birth = $subscriber->date_of_birth;
	}

    /**
     * {@inheritdoc}
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);
        if (!empty($data)) {
            $this->imageFile = UploadedFile::getInstance($this, 'imageFile');
        }
        return $result;
    }

	/**
	 * Saves the user files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
		try {
            if (!($file = $this->imageFile)) {
				return true;
			}

			$dirPath = Yii::getAlias("@uploads/user/{$this->id}");
			$oldFilePath = "{$dirPath}/{$this->oldAttributes['image']}";
			$fileName = StringHelper::truncate(implode('_', array_filter([
				Inflector::slug($this->fullName),
				Yii::$app->security->generateRandomString(8),
			])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);
			if (!$file->saveAs($filePath)) {
				throw new \Exception();
			}
			if (!$this->updateAttributes(['image' => $fileName])) {
				throw new \Exception();
			}
			if (is_file($oldFilePath) && $oldFilePath != $filePath) {
				FileHelper::unlink($oldFilePath);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!$this->validate()) {
				throw new \Exception();
			}
			if (!empty($this->new_password)) {
				$this->setPassword($this->new_password);
				$this->generateAuthKey();
			}
			$subscriber = Subscriber::find()
                ->where(['user_id' => Yii::$app->user->id])
                ->andWhere([
                    'deleted' => Subscriber::NO,
                    'status' => Subscriber::STATUS_ACTIVE,
                ])->one();

			if ($subscriber) {
                $subscriber->pin = $this->pin;
                $subscriber->date_of_birth = $this->date_of_birth;
                $subscriber->street_name = $this->street_name;
                $subscriber->street_number = $this->street_number;
                $subscriber->locality = $this->locality;
                $subscriber->zip_code = $this->zip_code;
                $subscriber->county = $this->county;
                $subscriber->country = $this->country;
                $subscriber->bank_account_holder = $this->bank_account_holder;
                $subscriber->bank_name = $this->bank_name;
                $subscriber->bank_iban = $this->bank_iban;
                $subscriber->bank_bic = $this->bank_bic;
            } else {
                $subscriber = new Subscriber();
                $subscriber->user_id = Yii::$app->user->id;
                $subscriber->code = Subscriber::generateUniqueCode();
                $subscriber->pin = $this->pin;
                $subscriber->date_of_birth = $this->date_of_birth;
                $subscriber->street_name = $this->street_name;
                $subscriber->street_number = $this->street_number;
                $subscriber->locality = $this->locality;
                $subscriber->zip_code = $this->zip_code;
                $subscriber->county = $this->county;
                $subscriber->country = $this->country;
				$subscriber->bank_account_holder = $this->bank_account_holder;
				$subscriber->bank_name = $this->bank_name;
				$subscriber->bank_iban = $this->bank_iban;
				$subscriber->bank_bic = $this->bank_bic;
                $subscriber->status = Yii::$app->user->identity->status;
            }
            if (!$this->save()) {
                throw new \Exception();
            }
            if (!$this->saveFiles()) {
                throw new \Exception();
            }
            if (!$subscriber->save()) {
                throw new \Exception();
            }
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
