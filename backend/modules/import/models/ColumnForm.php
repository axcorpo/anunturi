<?php

namespace backend\modules\import\models;

use common\models\ImportAlternativeSource;
use common\models\ImportColumn;
use common\models\ImportSheet;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class ColumnForm extends ImportColumn
{
	public $columns;

	public $alternative_source;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->field_type = static::FIELD_TYPE_STRING;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['columns', 'alternative_source'], 'each', 'rule' => ['trim']],
			[['target'], 'required'],
			[['sheet_id', 'sort_order', 'deleted'], 'integer'],
			[['target', 'source', 'field_type'], 'string', 'max' => 255],
			[['sheet_id'], 'exist', 'skipOnError' => true, 'targetClass' => ImportSheet::class, 'targetAttribute' => ['sheet_id' => 'id']],
		];
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
		if ($this->alternativeSources) {
			$data = [];
			foreach($this->alternativeSources as $alternativeSource) {
				$data[$alternativeSource->value][] = $alternativeSource->source_index;
			}
			$this->alternative_source = $data;
		}
	}

	/**
	 * Save Alternative Sources.
	 *
	 * @return bool
	 */
	protected function saveAlternativeSources()
	{
		try {
			$this->unlinkAll('alternativeSources', true);
			if ($this->alternative_source) {
				foreach ($this->alternative_source as $key => $value) {
					if ($key && !empty($value)) {
						foreach ($value as $index) {
							$alternativeSource = new ImportAlternativeSource();
							$alternativeSource->column_id = $this->id;
							$alternativeSource->value = (string)$key;
							$alternativeSource->source_index = $index;
							$alternativeSource->source = $this->columns[$index];
							if (!$alternativeSource->save()) {
								throw new \Exception();
							}
						}
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			echo '<pre>';
			print_r($e->getMessage());
			print_r($e->getLine());
			print_r($alternativeSource->errors);
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveAlternativeSources()) {
				throw new \Exception();
			}
			$transaction->commit();
			return $this;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
