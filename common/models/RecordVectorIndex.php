<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * OpenAI vector store file mapping for announcements (semantic index).
 *
 * @property int $id
 * @property int $record_id
 * @property string $openai_file_id
 * @property string|null $vector_store_file_id
 * @property string $vector_store_id
 * @property string|null $indexed_at
 * @property string|null $error_message
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 */
class RecordVectorIndex extends ActiveRecord
{
	public const STATUS_INACTIVE = 0;
	public const STATUS_ACTIVE = 1;
	public const STATUS_ERROR = 2;

	public const NO = 0;
	public const YES = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%record_vector_index}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime())->format('Y-m-d H:i:s'),
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['record_id', 'openai_file_id', 'vector_store_id'], 'required'],
			[['record_id', 'status', 'deleted'], 'integer'],
			[['status'], 'default', 'value' => static::STATUS_ACTIVE],
			[['status'], 'in', 'range' => [static::STATUS_INACTIVE, static::STATUS_ACTIVE, static::STATUS_ERROR]],
			[['deleted'], 'default', 'value' => static::NO],
			[['openai_file_id', 'vector_store_file_id', 'vector_store_id'], 'string', 'max' => 255],
			[['indexed_at', 'created_at', 'updated_at'], 'safe'],
			[['error_message'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'record_id' => Yii::t('label', 'Record ID'),
			'openai_file_id' => Yii::t('label', 'OpenAI File ID'),
			'vector_store_file_id' => Yii::t('label', 'Vector Store File ID'),
			'vector_store_id' => Yii::t('label', 'Vector Store ID'),
			'indexed_at' => Yii::t('label', 'Indexed At'),
			'error_message' => Yii::t('label', 'Error'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}
}
