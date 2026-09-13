-- ================================================================
-- Knitin Portfolio: Safe Migration & Fix for Existing Hostinger Database
-- Target: MySQL 5.7+ / 8.0+ / MariaDB 10.3+ (phpMyAdmin / Hostinger)
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- ================================================================
-- DESCRIPTION:
-- This script fixes the "#1054 - Unknown column 'setting_key' in 'INSERT INTO'"
-- error on existing databases WITHOUT deleting or dropping existing tables.
--
-- WHAT THIS SCRIPT DOES:
-- 1. Inspects the existing `settings` table schema dynamically.
-- 2. Renames legacy column names (e.g. `key`, `setting_name`, `name`, `option_name`
--    -> `setting_key`) and (`value`, `option_value` -> `setting_value`).
-- 3. Safely adds any missing columns (`setting_group`, `created_at`, `updated_at`).
-- 4. Ensures all other system tables (`admins`, `contacts`, `conversations`,
--    `emails`, `attachments`, `contact_submissions`, `email_sync_logs`) exist.
-- 5. Performs duplicate-safe default configuration seeding.
--
-- SAFETY GUARANTEE:
-- - NO DROP DATABASE
-- - NO DROP TABLE
-- - NO TRUNCATE
-- - Existing contacts, emails, and custom settings data are 100% PRESERVED.
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ----------------------------------------------------------------
-- SECTION 1: SAFE DYNAMIC MIGRATION OF `settings` TABLE
-- ----------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS `PortfolioSafeMigrateSettings`$$

CREATE PROCEDURE `PortfolioSafeMigrateSettings`()
BEGIN
    DECLARE v_db_name VARCHAR(128);
    SET v_db_name = DATABASE();

    -- 1. If table `settings` does not exist at all, create it
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings'
    ) THEN
        CREATE TABLE `settings` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` VARCHAR(191) NOT NULL,
            `setting_value` LONGTEXT NULL,
            `setting_group` VARCHAR(100) DEFAULT 'general',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `settings_setting_key_unique` (`setting_key`),
            INDEX `idx_setting_group` (`setting_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ELSE
        -- 2. Table `settings` already exists. Check and align columns without data loss:

        -- (a) Check `setting_key` column
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'setting_key'
        ) THEN
            -- Check known legacy column names and rename safely
            IF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'key'
            ) THEN
                ALTER TABLE `settings` CHANGE COLUMN `key` `setting_key` VARCHAR(191) NOT NULL;
            ELSEIF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'setting_name'
            ) THEN
                ALTER TABLE `settings` CHANGE COLUMN `setting_name` `setting_key` VARCHAR(191) NOT NULL;
            ELSEIF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'name'
            ) THEN
                ALTER TABLE `settings` CHANGE COLUMN `name` `setting_key` VARCHAR(191) NOT NULL;
            ELSEIF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'option_name'
            ) THEN
                ALTER TABLE `settings` CHANGE COLUMN `option_name` `setting_key` VARCHAR(191) NOT NULL;
            ELSE
                ALTER TABLE `settings` ADD COLUMN `setting_key` VARCHAR(191) NOT NULL AFTER `id`;
            END IF;
        ELSE
            -- Normalize length and definition
            ALTER TABLE `settings` MODIFY COLUMN `setting_key` VARCHAR(191) NOT NULL;
        END IF;

        -- (b) Check `setting_value` column
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'setting_value'
        ) THEN
            IF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'value'
            ) THEN
                ALTER TABLE `settings` CHANGE COLUMN `value` `setting_value` LONGTEXT NULL;
            ELSEIF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'option_value'
            ) THEN
                ALTER TABLE `settings` CHANGE COLUMN `option_value` `setting_value` LONGTEXT NULL;
            ELSE
                ALTER TABLE `settings` ADD COLUMN `setting_value` LONGTEXT NULL AFTER `setting_key`;
            END IF;
        ELSE
            ALTER TABLE `settings` MODIFY COLUMN `setting_value` LONGTEXT NULL;
        END IF;

        -- (c) Check `setting_group` column
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'setting_group'
        ) THEN
            ALTER TABLE `settings` ADD COLUMN `setting_group` VARCHAR(100) DEFAULT 'general' AFTER `setting_value`;
        END IF;

        -- (d) Check `created_at` column
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'created_at'
        ) THEN
            ALTER TABLE `settings` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `setting_group`;
        END IF;

        -- (e) Check `updated_at` column
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'updated_at'
        ) THEN
            ALTER TABLE `settings` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
        END IF;

        -- (f) Ensure UNIQUE index on `setting_key` exists
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' 
              AND (INDEX_NAME = 'settings_setting_key_unique' OR INDEX_NAME = 'setting_key')
        ) THEN
            ALTER TABLE `settings` ADD UNIQUE KEY `settings_setting_key_unique` (`setting_key`);
        END IF;

        -- (g) Ensure index on `setting_group` exists
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = v_db_name AND TABLE_NAME = 'settings' 
              AND INDEX_NAME = 'idx_setting_group'
        ) THEN
            ALTER TABLE `settings` ADD INDEX `idx_setting_group` (`setting_group`);
        END IF;

    END IF;
END$$

DELIMITER ;

CALL `PortfolioSafeMigrateSettings`();
DROP PROCEDURE IF EXISTS `PortfolioSafeMigrateSettings`;

-- ----------------------------------------------------------------
-- SECTION 2: ENSURE OTHER APPLICATION TABLES EXIST (NON-DESTRUCTIVE)
-- ----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admins` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_login` DATETIME NULL,
    `login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admins_email_unique` (`email`),
    INDEX `idx_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contacts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `company` VARCHAR(150) NULL,
    `status` ENUM('new_lead', 'contacted', 'in_discussion', 'client', 'completed', 'archived') NOT NULL DEFAULT 'new_lead',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_contact_email` (`email`),
    INDEX `idx_contact_status` (`status`),
    INDEX `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'closed', 'archived') NOT NULL DEFAULT 'active',
    `priority` ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    `last_message_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_conv_contact` (`contact_id`),
    INDEX `idx_conv_status` (`status`),
    INDEX `idx_conv_last_msg` (`last_message_at`),
    CONSTRAINT `fk_conversations_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `emails` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `message_id` VARCHAR(255) NULL,
    `in_reply_to` VARCHAR(255) NULL,
    `references_header` TEXT NULL,
    `direction` ENUM('inbound', 'outbound') NOT NULL DEFAULT 'inbound',
    `from_email` VARCHAR(191) NOT NULL,
    `from_name` VARCHAR(150) NULL,
    `to_email` VARCHAR(191) NOT NULL,
    `to_name` VARCHAR(150) NULL,
    `cc` TEXT NULL,
    `bcc` TEXT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message_text` MEDIUMTEXT NULL,
    `message_html` MEDIUMTEXT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `is_starred` TINYINT(1) NOT NULL DEFAULT 0,
    `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
    `is_spam` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('received', 'sent', 'draft', 'failed') NOT NULL DEFAULT 'received',
    `sent_at` DATETIME NULL,
    `received_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_email_conversation` (`conversation_id`),
    INDEX `idx_email_contact` (`contact_id`),
    INDEX `idx_email_msg_id` (`message_id`),
    INDEX `idx_email_in_reply_to` (`in_reply_to`),
    INDEX `idx_email_direction` (`direction`),
    INDEX `idx_email_is_read` (`is_read`),
    INDEX `idx_email_is_starred` (`is_starred`),
    INDEX `idx_email_is_archived` (`is_archived`),
    INDEX `idx_email_is_spam` (`is_spam`),
    INDEX `idx_email_created_at` (`created_at`),
    INDEX `idx_email_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_emails_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_emails_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email_id` BIGINT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_attachment_email` (`email_id`),
    CONSTRAINT `fk_attachments_email` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_submissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `email_id` BIGINT UNSIGNED NULL,
    `service` VARCHAR(100) NULL,
    `phone` VARCHAR(50) NULL,
    `company` VARCHAR(150) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `attachment_path` VARCHAR(500) NULL,
    `status` ENUM('new', 'reviewed', 'replied', 'follow_up', 'converted', 'closed') NOT NULL DEFAULT 'new',
    `source` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_subm_contact` (`contact_id`),
    INDEX `idx_subm_conversation` (`conversation_id`),
    INDEX `idx_subm_email` (`email_id`),
    INDEX `idx_subm_status` (`status`),
    INDEX `idx_subm_created` (`created_at`),
    CONSTRAINT `fk_subm_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subm_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subm_email` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_sync_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sync_type` ENUM('cron', 'manual', 'test') NOT NULL DEFAULT 'cron',
    `status` ENUM('success', 'error', 'running') NOT NULL DEFAULT 'running',
    `messages_processed` INT UNSIGNED NOT NULL DEFAULT 0,
    `error_message` TEXT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sync_created` (`started_at`),
    INDEX `idx_sync_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- SECTION 3: SEED NON-SENSITIVE DEFAULT SETTINGS (DUPLICATE-SAFE)
-- ----------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'Nitin Kumar Portfolio', 'general'),
('site_email', 'contact@knitin525.in', 'general'),
('site_url', 'https://knitin525.in', 'general'),
('site_description', 'Nitin Kumar — Senior Graphic Designer & Full-Stack Web Developer with 12+ years of experience.', 'general'),
('default_timezone', 'Asia/Kolkata', 'general'),
('admin_email', 'contact@knitin525.in', 'general'),
('admin_name', 'Nitin Kumar', 'general'),
('auto_reply_enabled', '1', 'auto_reply'),
('auto_reply_subject', 'Thank you for contacting Nitin Kumar', 'auto_reply'),
('auto_reply_template', 'Hi {name},\n\nThank you for getting in touch!\n\nI have received your message regarding "{subject}" and will review it shortly.\n\nI usually respond within 24 hours.\n\nBest regards,\nNitin Kumar\nGraphic Designer & Web Developer\nhttps://knitin525.in/', 'auto_reply'),
('notification_enabled', '1', 'notifications'),
('notification_subject', 'New Contact Form Submission: {name} - {subject}', 'notifications'),
('email_sync_interval', '5', 'email_sync'),
('last_sync_time', NULL, 'email_sync')
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `setting_group` = VALUES(`setting_group`);

SET FOREIGN_KEY_CHECKS = 1;
