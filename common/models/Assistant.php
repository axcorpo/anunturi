<?php

namespace common\models;

use tws\behaviors\DefaultBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%assistant}}".
 *
 * @property int $id
 * @property string $name
 * @property string $model
 * @property string $instructions
 * @property string $temperature
 * @property string $top_p
 * @property int $max_tokens
 * @property int $provider
 * @property int $type
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property KnowledgeBase[] $knowledgeBases
 * @property AiMessage[] $messages
 * @property User $creator
 * @property User $updater
 */
class Assistant extends CommonActiveRecord
{
	const PROVIDER_OPENAI = 1;
	const PROVIDER_CLAUDE = 2;

	const TYPE_CHAT = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%assistant}}';
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
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
				'ensureDefaultValue' => true,
				'groupAttributes' => ['type'],
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
			[['default', 'created_by', 'updated_by', 'status', 'deleted', 'max_tokens', 'provider', 'type'], 'integer'],
			[['max_tokens'], 'integer', 'min' => 1, 'max' => 8192],
			[['name', 'status', 'provider', 'type'], 'required'],
			[['instructions'], 'string'],
			[['temperature', 'top_p'], 'number'],
			[['created_at', 'updated_at'], 'safe'],
			[['name', 'model'], 'string', 'max' => 255],
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
			'model' => Yii::t('label', 'Model'),
			'instructions' => Yii::t('label', 'Instructions'),
			'temperature' => Yii::t('label', 'Temperature'),
			'top_p' => Yii::t('label', 'Top P'),
			'max_tokens' => Yii::t('label', 'Max Tokens'),
			'provider' => Yii::t('label', 'Provider'),
			'type' => Yii::t('label', 'Type'),
			'default' => Yii::t('label', 'Default'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAssistantKnowledgeBases()
	{
		return $this->hasMany(AssistantKnowledgeBase::class, ['assistant_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getKnowledgeBases()
	{
		return $this->hasMany(KnowledgeBase::class, ['id' => 'knowledge_base_id'])
			->via('assistantKnowledgeBases');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMessages()
	{
		return $this->hasMany(AiMessage::class, ['assistant_id' => 'id']);
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
	 * Whether this assistant should use the Responses API with file_search
	 * instead of legacy Chat Completions + manual RAG.
	 * Enabled when provider is OpenAI and at least one KB has a vector_store_id.
	 */
	public function useResponsesApi(): bool
	{
		if ((int)$this->provider !== self::PROVIDER_OPENAI) {
			return false;
		}
		return $this->collectVectorStoreIds() !== [];
	}

	/**
	 * Vector store IDs of all knowledge bases linked to this assistant (deduplicated, in link order).
	 *
	 * @return string[]
	 */
	public function collectVectorStoreIds(): array
	{
		$ids = [];
		foreach ($this->knowledgeBases as $kb) {
			if (!empty($kb->vector_store_id)) {
				$ids[] = (string) $kb->vector_store_id;
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Resolves the Assistant that drives the announcement listing chat.
	 *
	 * Order: the `announcementChatAssistantId` param (optional PK override) when it points to an
	 * active assistant, otherwise the default active chat assistant from DB. Returns null when
	 * neither exists — callers fall back to params-based config.
	 */
	public static function findChatAssistant(): ?self
	{
		$id = (int) (Yii::$app->params['announcementChatAssistantId'] ?? 0);
		if ($id > 0) {
			$assistant = static::find()
				->where(['id' => $id, 'status' => static::STATUS_ACTIVE, 'deleted' => static::NO])
				->with('knowledgeBases')
				->one();
			if ($assistant !== null) {
				return $assistant;
			}
		}
		return static::find()
			->where([
				'type' => static::TYPE_CHAT,
				'default' => static::YES,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->with('knowledgeBases')
			->one();
	}

	/**
	 * Provider type labels (OpenAI, Claude, etc.)
	 *
	 * @return array
	 */
	public static function getProviderTypeLabels()
	{
		return [
			static::PROVIDER_OPENAI => Yii::t('label', 'OpenAI'),
			static::PROVIDER_CLAUDE => Yii::t('label', 'Claude (Anthropic)'),
		];
	}

	/**
	 * Assistant type labels.
	 *
	 * @return array
	 */
	public static function getAssistantTypeLabels()
	{
		return [
			static::TYPE_CHAT => Yii::t('label', 'Chat'),
		];
	}
}
