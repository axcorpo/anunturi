<?php

namespace frontend\modules\announcement\models;

use common\helpers\Inflector;
use common\models\Field;
use Yii;
use yii\base\Model;

class FilterForm extends Field
{

	public $action = [];

	/**
	 * @inheritdoc
	 */
	public function init()
    {
        parent::init();
    }

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
		];
	}

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        foreach (Field::findAllFields() as $field) {
            $attributes[] = Inflector::slug($field->name . '_' . $field->id, '_');
            if ($field->type == Field::TYPE_NUMBER) {
				$attributes[] = Inflector::slug('min_' .$field->name . '_' . $field->id, '_');
				$attributes[] = Inflector::slug('max_' .$field->name . '_' . $field->id, '_');
			}
			if ($field->type == Field::TYPE_DATE || $field->type == Field::TYPE_DATETIME) {
				$attributes[] = Inflector::slug('from_' .$field->name . '_' . $field->id, '_');
				$attributes[] = Inflector::slug('to_' .$field->name . '_' . $field->id, '_');
			}
        }
		$attributes[] = 'min_price';
		$attributes[] = 'max_price';
		$attributes[] = 'currency';
        return $attributes;
    }
}
