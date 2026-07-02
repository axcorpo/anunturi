-- -----------------------------------------------------------------------------
-- Schema changes: AI search (filters + chat) + Oblio invoicing
-- Ported from the licitatii project on 2026-07-02.
--
-- Equivalent Yii migrations (preferred way to apply — run `php yii migrate`):
--   console/migrations/m260702_100000_create_ai_search_tables.php
--   console/migrations/m260702_110000_add_external_id_to_invoice_table.php
--
-- This file is a plain-SQL mirror for environments where the schema is managed
-- from _database instead of migrations.
-- -----------------------------------------------------------------------------

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

-- -----------------------------------------------------
-- Table `assistant`
-- Backend-configurable chat assistant (model, instructions, temperature).
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assistant`;

CREATE TABLE `assistant` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `model` VARCHAR(255) NULL DEFAULT NULL,
  `provider` TINYINT NULL DEFAULT NULL,
  `instructions` TEXT NULL DEFAULT NULL,
  `temperature` DECIMAL(15,2) NOT NULL DEFAULT 1.00,
  `top_p` DECIMAL(15,2) NOT NULL DEFAULT 1.00,
  `max_tokens` INT(11) NULL DEFAULT NULL,
  `type` TINYINT NULL DEFAULT NULL,
  `default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `status` TINYINT NOT NULL,
  `deleted` TINYINT(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_assistant_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `knowledge_base`
-- OpenAI vector store host for the announcement semantic index.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `knowledge_base`;

CREATE TABLE `knowledge_base` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `provider` TINYINT NULL DEFAULT NULL,
  `embedding_model` VARCHAR(255) NULL DEFAULT NULL,
  `vector_store_id` VARCHAR(255) NULL DEFAULT NULL,
  `chunk_size` INT(11) NULL DEFAULT 1000,
  `chunk_overlap` INT(11) NULL DEFAULT 200,
  `tokens_per_file` INT(11) NULL DEFAULT 0,
  `expire_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `status` TINYINT NOT NULL,
  `deleted` TINYINT(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_knowledge_base_deleted` (`deleted`),
  KEY `idx_knowledge_base_status` (`status`),
  KEY `idx_knowledge_base_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `assistant_knowledge_base`
-- Links an assistant to the knowledge bases (vector stores) it searches.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assistant_knowledge_base`;

CREATE TABLE `assistant_knowledge_base` (
  `assistant_id` INT(11) NOT NULL,
  `knowledge_base_id` INT(11) NOT NULL,
  `sort_order` INT(11) NULL DEFAULT 0,
  `created_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`assistant_id`, `knowledge_base_id`),
  KEY `idx_assistant_kb_sort_order` (`sort_order`),
  KEY `fk_assistant_knowledge_base_knowledge_base` (`knowledge_base_id`),
  CONSTRAINT `fk_assistant_knowledge_base_assistant`
    FOREIGN KEY (`assistant_id`)
    REFERENCES `assistant` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_assistant_knowledge_base_knowledge_base`
    FOREIGN KEY (`knowledge_base_id`)
    REFERENCES `knowledge_base` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `ai_conversation`
-- Listing chat conversation persistence (best-effort, for analytics).
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ai_conversation`;

CREATE TABLE `ai_conversation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `summary` VARCHAR(255) NULL DEFAULT NULL,
  `openai_conversation_id` VARCHAR(255) NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 0,
  `deleted` TINYINT(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ai_conversation_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `ai_message`
-- Chat turns; the assistant turn stores {reply, suggested_query, relevant_ids} as JSON in `content`.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ai_message`;

CREATE TABLE `ai_message` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` INT(11) NOT NULL,
  `assistant_id` INT(11) NULL DEFAULT NULL,
  `role` VARCHAR(255) NULL DEFAULT NULL,
  `content` MEDIUMTEXT NULL DEFAULT NULL,
  `completed_at` DATETIME NULL DEFAULT NULL,
  `incomplete_at` DATETIME NULL DEFAULT NULL,
  `incomplete_reason` VARCHAR(255) NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `status` VARCHAR(255) NULL DEFAULT NULL,
  `deleted` TINYINT(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ai_message_deleted` (`deleted`),
  KEY `fk_ai_message_assistant` (`assistant_id`),
  KEY `fk_ai_message_ai_conversation` (`conversation_id`),
  CONSTRAINT `fk_ai_message_ai_conversation`
    FOREIGN KEY (`conversation_id`)
    REFERENCES `ai_conversation` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_ai_message_assistant`
    FOREIGN KEY (`assistant_id`)
    REFERENCES `assistant` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `record_vector_index`
-- Maps announcements to OpenAI vector store files for semantic search.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `record_vector_index`;

CREATE TABLE `record_vector_index` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `record_id` INT(11) NOT NULL COMMENT 'Announcement PK',
  `openai_file_id` VARCHAR(128) NOT NULL,
  `vector_store_file_id` VARCHAR(128) NULL DEFAULT NULL,
  `vector_store_id` VARCHAR(128) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '0=inactive,1=active,2=error',
  `deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '0=no,1=yes (soft)',
  `indexed_at` DATETIME NULL DEFAULT NULL,
  `error_message` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_record_vector_index_record_id` (`record_id`),
  KEY `idx_record_vector_index_openai_file` (`openai_file_id`),
  KEY `idx_record_vector_index_status` (`status`),
  KEY `idx_record_vector_index_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Alter `announcement_translation`
-- Denormalized haystack for the listing free-text search: title + description +
-- keywords + content, HTML-stripped, lowercased, Romanian diacritics folded.
-- Kept in sync by AnnouncementTranslation::beforeSave().
-- -----------------------------------------------------
ALTER TABLE `announcement_translation`
  ADD COLUMN `search_text` TEXT NULL DEFAULT NULL AFTER `content`;

-- Backfill for existing rows. This SQL approximation covers the entity-decode-free
-- case (lowercase + diacritic folding); the exact normalization (HTML strip,
-- entity decode, whitespace collapse) is applied by the migration
-- m260702_100000_create_ai_search_tables (PHP), or on the next save of each translation.
UPDATE `announcement_translation`
SET `search_text` =
  REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    LOWER(CONCAT_WS(' ', COALESCE(`title`, ''), COALESCE(`description`, ''), COALESCE(`keywords`, ''), COALESCE(`content`, ''))),
  'ă', 'a'), 'â', 'a'), 'î', 'i'), 'ș', 's'), 'ş', 's'), 'ț', 't'), 'ţ', 't')
WHERE `search_text` IS NULL OR `search_text` = '';

-- -----------------------------------------------------
-- Alter `invoice`
-- Oblio integration: external document id ("<series> <number>" or the Oblio id)
-- set once the invoice has been pushed to Oblio (idempotency guard).
-- -----------------------------------------------------
ALTER TABLE `invoice`
  ADD COLUMN `external_id` VARCHAR(255) NULL DEFAULT NULL AFTER `document_number`,
  ADD INDEX `idx_invoice_external_id` (`external_id`);

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
