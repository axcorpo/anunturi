<?php

namespace backend\modules\setting\modules\integration\models;

use common\models\Integration;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class IntegrationForm extends Integration
{
	public $api_key;

	public $base_url;

	/**
	 * Data field mapping per integration type.
	 */
	public static function getDataFieldsMap(): array
	{
		return [
			static::TYPE_OPENAI => ['api_key'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->type = static::TYPE_OPENAI;
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['type'], 'required'],
			[['api_key'], 'string'],
			[['api_key'], 'required', 'when' => function ($model) {
				return in_array((int)$model->type, [static::TYPE_OPENAI]);
			}, 'whenClient' => "function (attribute, value) {
				var type = $('#integrationform-type').val();
				return type == '" . static::TYPE_OPENAI . "';
			}"],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'data' => Yii::t('label', 'Configuration'),
			'api_key' => Yii::t('label', 'API Key'),
			'base_url' => Yii::t('label', 'Base URL'),
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

		$decoded = $this->getDecodedData();
		$this->api_key = $decoded['api_key'] ?? null;
		$this->base_url = $decoded['base_url'] ?? null;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		$this->buildDataJson();
		return parent::beforeValidate();
	}

	protected function buildDataJson()
	{
		$fields = static::getDataFieldsMap()[(int)$this->type] ?? [];
		$data = [];

		foreach ($fields as $field) {
			if (!empty($this->$field)) {
				$data[$field] = $this->$field;
			}
		}

		if (!empty($data)) {
			$this->data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
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
