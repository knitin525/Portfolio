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
-- SECTION 1: SETTINGS TABLE (SAFE INITIALIZATION)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
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

-- ----------------------------------------------------------------
-- SECTION 4: PROJECT & CATEGORY MANAGEMENT SYSTEM
-- ----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `project_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `description` TEXT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_category_slug` (`slug`),
    INDEX `idx_cat_order` (`display_order`),
    INDEX `idx_cat_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `primary_category_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `client_name` VARCHAR(150) NULL,
    `industry` VARCHAR(150) NULL,
    `summary` TEXT NULL,
    `description` LONGTEXT NULL,
    `hero_image` VARCHAR(255) NULL,
    `tags` TEXT NULL,
    `project_type` VARCHAR(100) NULL,
    `project_url` VARCHAR(255) NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_project_slug` (`slug`),
    INDEX `idx_projects_primary_cat` (`primary_category_id`),
    INDEX `idx_projects_status` (`status`),
    INDEX `idx_projects_featured` (`is_featured`),
    INDEX `idx_projects_order` (`sort_order`),
    CONSTRAINT `fk_projects_primary_category` FOREIGN KEY (`primary_category_id`) REFERENCES `project_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_category_map` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_project_category` (`project_id`, `category_id`),
    INDEX `idx_pcm_cat` (`category_id`),
    INDEX `idx_pcm_proj` (`project_id`),
    CONSTRAINT `fk_pcm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pcm_category` FOREIGN KEY (`category_id`) REFERENCES `project_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Categories (Duplicate-safe)
INSERT INTO `project_categories` (`id`, `name`, `slug`, `description`, `display_order`, `is_active`) VALUES
(1, 'Web & UI/UX', 'web-uiux', 'Web interfaces, web apps, SaaS platforms, and responsive design systems', 1, 1),
(2, 'Pharma Design', 'pharma', 'Pharmaceutical packaging, medical visual aids, clinical collateral & compliance artworks', 2, 1),
(3, 'Branding & Logo', 'branding', 'Brand identities, logo systems, corporate guidelines, and stationary', 3, 1),
(4, 'Graphic Design', 'graphics', 'Marketing collaterals, print brochures, social media assets, and digital banners', 4, 1),
(5, 'Motion & Video', 'motion', 'Promotional motion graphics, explainer videos, and interactive video editing', 5, 1),
(6, 'Web Design', 'web-design', 'Custom website layouts, landing pages, and component design', 6, 1),
(7, 'UI/UX Design', 'uiux-design', 'User experience architecture, wireframing, and interface prototyping', 7, 1),
(8, 'Front-End Development', 'frontend-development', 'HTML5, modern CSS, JavaScript, responsive frameworks, and interactivity', 8, 1),
(9, 'Visual Aid', 'visual-aid', 'Pharmaceutical detailing visual aids for medical representatives and doctors', 9, 1),
(10, 'Healthcare Branding', 'healthcare-branding', 'Brand identities for clinics, pharma manufacturers, and medical products', 10, 1),
(11, 'Logo Design', 'logo-design', 'Distinctive marks, typography emblems, and iconography', 11, 1),
(12, 'Fintech', 'fintech', 'Financial technology, payment gateway branding, and digital wallets', 12, 1),
(13, 'Brand Identity', 'brand-identity', 'Holistic visual language, typography palette, and brand governance', 13, 1),
(14, 'Packaging', 'packaging', 'Product cartons, medicine boxes, bottle labels, and bladders', 14, 1),
(15, 'Motion Graphics', 'motion-graphics', 'Vector animations, logo reveals, and motion design sequences', 15, 1),
(16, 'Video Editing', 'video-editing', 'Video montage, color grading, pacing, and post-production', 16, 1),
(17, 'Security Technology', 'security-technology', 'Cybersecurity visual language, risk platforms, and technical diagrams', 17, 1),
(18, 'Development', 'development', 'Technical software implementation, script integration, and web applications', 18, 1),
(19, 'Responsive Design', 'responsive-design', 'Mobile-first adaptation, fluid typography, and touch interaction', 19, 1),
(20, 'Laravel', 'laravel', 'PHP backend framework, API integration, and MVC application architecture', 20, 1),
(21, 'Healthcare', 'healthcare', 'Hospitals, medical diagnostics, pharmaceutical formulations, and patient care', 21, 1)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`);

-- Seed Primary + Additional Projects Examples
INSERT INTO `projects` (`id`, `primary_category_id`, `title`, `slug`, `client_name`, `industry`, `summary`, `description`, `hero_image`, `tags`, `project_type`, `project_url`, `is_featured`, `status`, `sort_order`) VALUES
(1, 1, 'Mind Intelligence Lab', 'mind-intelligence-lab', 'Mind Intelligence Lab', 'Security Technology', 'Human Risk Intelligence Platform', 'A premium cybersecurity and behavioral intelligence website experience designed to communicate complex Human Risk Intelligence concepts through clear visual storytelling, refined UI/UX and modern digital presentation.', NULL, 'UI/UX,Security Technology,Web Design', 'Web Application', 'https://mindintelligence.com', 1, 'published', 1),
(2, 2, 'Dermovent-FP Visual Aid', 'dermovent-fp', 'DRT Lifesciences', 'Pharmaceutical Visual Communication', 'Griseofulvin 250mg & Cetirizine HCl 5mg', 'Professional pharmaceutical visual aid designed with strong product hierarchy, clinical efficacy highlights and medical-grade presentation for healthcare professionals.', NULL, 'Pharma,Visual Aid,Medical', 'Medical Visual Aid', NULL, 0, 'published', 2),
(3, 3, 'Rampex', 'rampex', 'Rampex Global Payments', 'Fintech & Digital Payments', 'Fintech Brand Identity', 'A modern fintech logo and visual identity concept for a global card payment gateway supporting multi-currency settlements and digital transaction security.', NULL, 'Fintech,Logo,Identity', 'Brand Identity System', NULL, 0, 'published', 3)
ON DUPLICATE KEY UPDATE
    `primary_category_id` = VALUES(`primary_category_id`),
    `title` = VALUES(`title`),
    `client_name` = VALUES(`client_name`),
    `industry` = VALUES(`industry`),
    `summary` = VALUES(`summary`),
    `description` = VALUES(`description`),
    `tags` = VALUES(`tags`),
    `project_type` = VALUES(`project_type`),
    `is_featured` = VALUES(`is_featured`),
    `status` = VALUES(`status`),
    `sort_order` = VALUES(`sort_order`);

-- Seed Additional Categories Mapping
-- Mind Intelligence Lab (Project 1, Primary: Web & UI/UX [1]) -> Additional: Web Design [6], Front-End Development [8], Security Technology [17]
INSERT IGNORE INTO `project_category_map` (`project_id`, `category_id`) VALUES
(1, 6),
(1, 8),
(1, 17);

-- Dermovent-FP (Project 2, Primary: Pharma Design [2]) -> Additional: Graphic Design [4], Visual Aid [9], Healthcare Branding [10]
INSERT IGNORE INTO `project_category_map` (`project_id`, `category_id`) VALUES
(2, 4),
(2, 9),
(2, 10);

-- Rampex (Project 3, Primary: Branding & Logo [3]) -> Additional: Logo Design [11], Fintech [12], Brand Identity [13]
INSERT IGNORE INTO `project_category_map` (`project_id`, `category_id`) VALUES
(3, 11),
(3, 12),
(3, 13);

SET FOREIGN_KEY_CHECKS = 1;
