<?php

use common\db\Migration;
use common\helpers\AnnouncementListSearch;

/**
 * AI search infrastructure ported from the licitatii project:
 *  - `assistant` + `knowledge_base` + `assistant_knowledge_base` — backend-configurable chat assistant and its vector stores
 *  - `ai_conversation` + `ai_message` — listing chat persistence
 *  - `record_vector_index` — announcement ↔ OpenAI vector store file mapping
 *  - `announcement_translation.search_text` — denormalized, diacritic-folded haystack for the listing free-text search
 */
class m260702_100000_create_ai_search_tables extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$tableOptions = null;
		if ($this->db->driverName === 'mysql') {
			$tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
		}

		$this->createTable('{{%assistant}}', [
			'id' => $this->binaryUuidPrimaryKey(),
			'name' => $this->string(255)->notNull(),
			'model' => $this->string(255)->null(),
			'provider' => $this->tinyInteger()->null(),
			'instructions' => $this->text()->null(),
			'temperature' => $this->decimal(15, 2)->notNull()->defaultValue(1.00),
			'top_p' => $this->decimal(15, 2)->notNull()->defaultValue(1.00),
			'max_tokens' => $this->integer()->null(),
			'type' => $this->tinyInteger()->null(),
			'default' => $this->tinyInteger(1)->notNull()->defaultValue(0),
			'created_by' => $this->binaryUuid(false),
			'updated_by' => $this->binaryUuid(false),
			'created_at' => $this->dateTime()->null(),
			'updated_at' => $this->dateTime()->null(),
			'status' => $this->tinyInteger()->notNull(),
			'deleted' => $this->tinyInteger(1)->defaultValue(0),
		], $tableOptions);
		$this->createIndex('idx_assistant_deleted', '{{%assistant}}', 'deleted');

		$this->createTable('{{%knowledge_base}}', [
			'id' => $this->binaryUuidPrimaryKey(),
			'name' => $this->string(255)->notNull(),
			'description' => $this->text()->null(),
			'provider' => $this->tinyInteger()->null(),
			'embedding_model' => $this->string(255)->null(),
			'vector_store_id' => $this->string(255)->null(),
			'chunk_size' => $this->integer()->defaultValue(1000),
			'chunk_overlap' => $this->integer()->defaultValue(200),
			'tokens_per_file' => $this->integer()->defaultValue(0),
			'expire_at' => $this->dateTime()->null(),
			'created_by' => $this->binaryUuid(false),
			'updated_by' => $this->binaryUuid(false),
			'created_at' => $this->dateTime()->null(),
			'updated_at' => $this->dateTime()->null(),
			'status' => $this->tinyInteger()->notNull(),
			'deleted' => $this->tinyInteger(1)->defaultValue(0),
		], $tableOptions);
		$this->createIndex('idx_knowledge_base_deleted', '{{%knowledge_base}}', 'deleted');
		$this->createIndex('idx_knowledge_base_status', '{{%knowledge_base}}', 'status');
		$this->createIndex('idx_knowledge_base_provider', '{{%knowledge_base}}', 'provider');

		$this->createTable('{{%assistant_knowledge_base}}', [
			'assistant_id' => $this->binaryUuid(),
			'knowledge_base_id' => $this->binaryUuid(),
			'sort_order' => $this->integer()->defaultValue(0),
			'created_at' => $this->dateTime()->null(),
			'PRIMARY KEY ([[assistant_id]], [[knowledge_base_id]])',
		], $tableOptions);
		$this->createIndex('idx_assistant_kb_sort_order', '{{%assistant_knowledge_base}}', 'sort_order');
		$this->addForeignKey(
			'fk_assistant_knowledge_base_assistant',
			'{{%assistant_knowledge_base}}', 'assistant_id',
			'{{%assistant}}', 'id',
			'CASCADE', 'CASCADE'
		);
		$this->addForeignKey(
			'fk_assistant_knowledge_base_knowledge_base',
			'{{%assistant_knowledge_base}}', 'knowledge_base_id',
			'{{%knowledge_base}}', 'id',
			'CASCADE', 'CASCADE'
		);

		$this->createTable('{{%ai_conversation}}', [
			'id' => $this->binaryUuidPrimaryKey(),
			'summary' => $this->string(255)->null(),
			'openai_conversation_id' => $this->string(255)->null(),
			'created_by' => $this->binaryUuid(false),
			'created_at' => $this->dateTime()->null(),
			'status' => $this->tinyInteger()->notNull()->defaultValue(0),
			'deleted' => $this->tinyInteger(1)->defaultValue(0),
		], $tableOptions);
		$this->createIndex('idx_ai_conversation_deleted', '{{%ai_conversation}}', 'deleted');

		$this->createTable('{{%ai_message}}', [
			'id' => $this->binaryUuidPrimaryKey(),
			'conversation_id' => $this->binaryUuid(),
			'assistant_id' => $this->binaryUuid(false),
			'role' => $this->string(255)->null(),
			'content' => 'MEDIUMTEXT NULL DEFAULT NULL',
			'completed_at' => $this->dateTime()->null(),
			'incomplete_at' => $this->dateTime()->null(),
			'incomplete_reason' => $this->string(255)->null(),
			'created_by' => $this->binaryUuid(false),
			'created_at' => $this->dateTime()->null(),
			'status' => $this->string(255)->null(),
			'deleted' => $this->tinyInteger(1)->defaultValue(0),
		], $tableOptions);
		$this->createIndex('idx_ai_message_deleted', '{{%ai_message}}', 'deleted');
		$this->addForeignKey(
			'fk_ai_message_assistant',
			'{{%ai_message}}', 'assistant_id',
			'{{%assistant}}', 'id',
			'SET NULL', 'CASCADE'
		);
		$this->addForeignKey(
			'fk_ai_message_ai_conversation',
			'{{%ai_message}}', 'conversation_id',
			'{{%ai_conversation}}', 'id',
			'CASCADE', 'CASCADE'
		);

		$this->createTable('{{%record_vector_index}}', [
			'id' => $this->binaryUuidPrimaryKey(),
			'record_id' => $this->binaryUuid() . " COMMENT 'Announcement PK'",
			'openai_file_id' => $this->string(128)->notNull(),
			'vector_store_file_id' => $this->string(128)->null(),
			'vector_store_id' => $this->string(128)->notNull(),
			'status' => $this->tinyInteger()->notNull()->defaultValue(1)->comment('0=inactive,1=active,2=error'),
			'deleted' => $this->tinyInteger()->notNull()->defaultValue(0)->comment('0=no,1=yes (soft)'),
			'indexed_at' => $this->dateTime()->null(),
			'error_message' => $this->text()->null(),
			'created_at' => $this->dateTime()->notNull(),
			'updated_at' => $this->dateTime()->notNull(),
		], $tableOptions);
		$this->createIndex('ux_record_vector_index_record_id', '{{%record_vector_index}}', 'record_id', true);
		$this->createIndex('idx_record_vector_index_openai_file', '{{%record_vector_index}}', 'openai_file_id');
		$this->createIndex('idx_record_vector_index_status', '{{%record_vector_index}}', 'status');
		$this->createIndex('idx_record_vector_index_deleted', '{{%record_vector_index}}', 'deleted');

		// Denormalized haystack for the listing free-text search (title + description + keywords + content,
		// HTML-stripped + diacritic-folded). Kept in sync by AnnouncementTranslation::beforeSave().
		$this->addColumn('{{%announcement_translation}}', 'search_text', $this->text()->null()->after('content'));
		$this->backfillSearchText();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumn('{{%announcement_translation}}', 'search_text');
		$this->dropTable('{{%record_vector_index}}');
		$this->dropTable('{{%ai_message}}');
		$this->dropTable('{{%ai_conversation}}');
		$this->dropTable('{{%assistant_knowledge_base}}');
		$this->dropTable('{{%knowledge_base}}');
		$this->dropTable('{{%assistant}}');
	}

	/**
	 * Populates `search_text` for existing translations in batches, using the same
	 * normalization pipeline as AnnouncementTranslation::beforeSave().
	 */
	protected function backfillSearchText()
	{
		$query = (new \yii\db\Query())
			->select(['announcement_id', 'language_id', 'title', 'description', 'keywords', 'content'])
			->from('{{%announcement_translation}}');
		foreach ($query->batch(200, $this->db) as $rows) {
			foreach ($rows as $row) {
				$searchText = AnnouncementListSearch::normalize(implode(' ', [
					(string) $row['title'],
					(string) $row['description'],
					(string) $row['keywords'],
					(string) $row['content'],
				]));
				$this->db->createCommand()->update(
					'{{%announcement_translation}}',
					['search_text' => $searchText],
					['announcement_id' => $row['announcement_id'], 'language_id' => $row['language_id']]
				)->execute();
			}
		}
	}
}
