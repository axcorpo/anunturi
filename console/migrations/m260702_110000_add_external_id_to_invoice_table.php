<?php

use yii\db\Migration;

/**
 * Oblio integration: `invoice.external_id` stores the Oblio document id
 * (or "<series> <number>") once the invoice has been pushed to Oblio.
 */
class m260702_110000_add_external_id_to_invoice_table extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->addColumn('{{%invoice}}', 'external_id', $this->string(255)->null()->after('document_number'));
		$this->createIndex('idx_invoice_external_id', '{{%invoice}}', 'external_id');
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropIndex('idx_invoice_external_id', '{{%invoice}}');
		$this->dropColumn('{{%invoice}}', 'external_id');
	}
}
