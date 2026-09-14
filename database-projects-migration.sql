-- ================================================================
-- Knitin Portfolio: Primary + Additional Category Migration
-- Target: MySQL 5.7+ / 8.0+ / MariaDB 10.3+ (Hostinger / phpMyAdmin)
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. Create project_categories Table
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

-- 2. Create projects Table
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

-- 3. Create project_category_map Table (Additional Categories)
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

-- 4. Seed Standard Categories
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

-- 5. Seed Demonstration Projects
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

-- 6. Seed Additional Category Maps
INSERT IGNORE INTO `project_category_map` (`project_id`, `category_id`) VALUES
(1, 6),
(1, 8),
(1, 17),
(2, 4),
(2, 9),
(2, 10),
(3, 11),
(3, 12),
(3, 13);

SET FOREIGN_KEY_CHECKS = 1;
