<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\models\Action;
use common\models\Category;
use common\models\CategoryField;
use common\models\Field;
use common\models\FieldTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use common\helpers\Inflector;

class CategoryFieldForm extends CategoryField
{
	/**
	 * @var array A list of (sub)Category models.
	 */
	public $category = [];


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['action_id'], 'required'],
            [['category'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category' => 'id']]],
        ]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'action_id' => Yii::t('label', 'Action'),
			'field_id' => Yii::t('label', 'Field'),
			'category' => Yii::t('label', 'Subcategories'),
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
	}

	/**
	 * Links the Category models.
	 *
	 * @return bool
	 * @throws \Throwable
	 */
	protected function saveCategories()
	{
		try {
			if (empty($this->category)) {
				return true;
			}
			$categories = Category::findAll([
				'id' => $this->category,
				'status' => Category::STATUS_ACTIVE,
				'deleted' => Category::NO,
			]);
			foreach ($categories as $category) {
				$model = CategoryField::findOne([
					'field_id' => $this->field_id,
					'action_id' => $this->action_id,
					'category_id' => $category->id,
				]);
				if (!$model) {
					$model = new CategoryField();
				}
				$model->field_id = $this->field_id;
				$model->action_id = $this->action_id;
				$model->category_id = $category->id;
				$model->sort_order =  $this->sort_order;
				$model->status =  $this->status;
				if (!$model->save()) {
					throw new \Exception();
				}
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|static
	 */
	public function saveModel()
	{

		$transaction = static::getDb()->beginTransaction();
		try {
            if (!$this->save()) {
                throw new \Exception();
            }
			if (!$this->saveCategories()) {
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
