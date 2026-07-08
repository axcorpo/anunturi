<?php

use common\db\Migration;

/**
 * FULLTEXT index for the announcement listing free-text search.
 *
 * `announcement_translation.search_text` is the denormalized, diacritic-folded haystack
 * (see AnnouncementTranslation::beforeSave()). The listing search now runs
 * `MATCH ... AGAINST (... IN BOOLEAN MODE)` against this index instead of full-scan
 * `LIKE '%term%'` conditions — see Announcement::listSearchSqlLikeOr().
 */
class m260708_130000_add_search_text_fulltext_index extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->execute('ALTER TABLE {{%announcement_translation}} ADD FULLTEXT INDEX `ftx_announcement_translation_search_text` ([[search_text]])');
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropIndex('ftx_announcement_translation_search_text', '{{%announcement_translation}}');
	}
}
