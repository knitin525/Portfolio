# MySQL Database Setup & Migration Guide

This guide provides instructions for installing, repairing, and migrating the MySQL database for the **Knitin Portfolio Contact & Email Management System** on **Hostinger (hPanel / phpMyAdmin)**.

---

## Table of Contents
1. [Overview & Schema Architecture](#overview--schema-architecture)
2. [Root Cause: Error #1054 Explained](#root-cause-error-1054-explained)
3. [Scenario A: Safe Fix for Existing Hostinger Database (Recommended)](#scenario-a-safe-fix-for-existing-hostinger-database-recommended)
4. [Scenario B: Clean Fresh Installation](#scenario-b-clean-fresh-installation)
5. [phpMyAdmin Step-by-Step Instructions](#phpmyadmin-step-by-step-instructions)
6. [Verification & Sanity Checks](#verification--sanity-checks)
7. [Production Security Checklist](#production-security-checklist)

---

## Overview & Schema Architecture

The portfolio database is built for **MySQL 5.7+ / 8.0+** and **MariaDB 10.3+** using `InnoDB` engine with `utf8mb4` character set and `utf8mb4_unicode_ci` collation.

### Entity Relationships
```text
contacts (id: BIGINT UNSIGNED)
   ├── conversations (contact_id -> contacts.id)
   │      └── emails (conversation_id -> conversations.id, contact_id -> contacts.id)
   │             └── attachments (email_id -> emails.id)
   └── contact_submissions (contact_id, conversation_id, email_id)

admins (id: BIGINT UNSIGNED, password_hash: BCRYPT)
email_sync_logs (id: BIGINT UNSIGNED)
settings (id: BIGINT UNSIGNED, setting_key: VARCHAR(191) UNIQUE, setting_value: LONGTEXT)

project_categories (id: BIGINT UNSIGNED, name: VARCHAR(150), slug: VARCHAR(191) UNIQUE)
   ├── projects (id: BIGINT UNSIGNED, primary_category_id -> project_categories.id)
   └── project_category_map (project_id -> projects.id, category_id -> project_categories.id)
```

---

## Root Cause: Error #1054 Explained

### The Error
```text
#1054 - Unknown column 'setting_key' in 'INSERT INTO'
Failing query:
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('site_name', 'Nitin Kumar Portfolio');
```

### Why it happened:
1. When importing `database.sql`, the DDL statement was:
   ```sql
   CREATE TABLE IF NOT EXISTS `settings` ...
   ```
2. If your Hostinger database already contained an older or previously created `settings` table (for instance, created with legacy column names such as `key` / `value`, or `setting_name` / `setting_value`), MySQL **skipped** the `CREATE TABLE` command because the table already existed.
3. When the import script reached the `INSERT INTO settings (setting_key, setting_value)` seed statements, MySQL aborted with `#1054` because the existing table was missing the `setting_key` column.
4. Furthermore, the seed duplicate update clause in earlier revisions had a typo (`setting_key = VALUES(setting_key)` instead of `setting_value = VALUES(setting_value)`).

---

## Scenario A: Safe Fix for Existing Hostinger Database (Recommended)

> [!IMPORTANT]
> **Zero Data Loss Guarantee**: This migration **does not** drop your database, does not drop tables, and does not erase existing contacts, emails, or custom admin settings.

### Step 1: Create a Production Backup
Before applying any database changes, always create a snapshot in phpMyAdmin:
1. Log into your **Hostinger hPanel** → **Databases** → open **phpMyAdmin** for your database.
2. Select your portfolio database on the left sidebar.
3. Click the **Export** tab in the top navigation.
4. Select **Quick - display only the minimal options** and format **SQL**.
5. Click **Export** to download the `.sql` backup file to your computer.

### Step 2: Run `database-fix.sql`
1. In phpMyAdmin, ensure your database is selected.
2. Click the **Import** tab.
3. Click **Choose File** and select `database-fix.sql` from your project folder.
4. Click **Import** (or **Go** at the bottom).

### What `database-fix.sql` does automatically:
- Inspects your database schema via `information_schema`.
- If `settings` has legacy columns (`key`, `setting_name`, `name`, or `option_name`), it safely renames the column to `setting_key VARCHAR(191) NOT NULL` while preserving existing data.
- If `settings` has legacy value columns (`value`, `option_value`), it renames them to `setting_value LONGTEXT NULL`.
- Adds `setting_group`, `created_at`, `updated_at`, and the unique index if missing.
- Verifies and creates all other required system tables (`admins`, `contacts`, `conversations`, `emails`, `attachments`, `contact_submissions`, `email_sync_logs`) if they don't exist yet.
- Inserts default non-sensitive settings duplicate-safely (`ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`).

---

### Alternative: Direct Manual SQL Queries
If your phpMyAdmin user does not have permission to execute stored procedures, run this single SQL snippet in the phpMyAdmin **SQL** tab:

```sql
-- Safe manual fix for settings table
ALTER TABLE `settings`
    CHANGE COLUMN `key` `setting_key` VARCHAR(191) NOT NULL;

-- If 'value' column exists:
ALTER TABLE `settings`
    CHANGE COLUMN `value` `setting_value` LONGTEXT NULL;

-- Add group and timestamps if missing
ALTER TABLE `settings`
    ADD COLUMN IF NOT EXISTS `setting_group` VARCHAR(100) DEFAULT 'general' AFTER `setting_value`,
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `setting_group`,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Seed default settings (duplicate-safe)
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
```

---

## Scenario B: Clean Fresh Installation

For a brand-new database with no existing data:

### Method 1: Interactive Web Installer (`install.php`)
1. Upload the project files to your Hostinger `public_html` directory.
2. Navigate in your browser to: `https://knitin525.in/install.php`
3. Fill in your Hostinger MySQL database credentials:
   - **Database Host**: `localhost` (or Hostinger DB IP)
   - **Database Port**: `3306`
   - **Database Name**: Your Hostinger DB name (e.g. `u123456789_portfolio`)
   - **Database User**: Your Hostinger DB username
   - **Database Password**: Your DB password
   - **Admin Name, Email, & Password**: Create your initial admin login
4. Click **Initialize Database & Setup Admin**.
5. The installer will execute `database.sql`, write `.env`, hash your admin password with bcrypt, and create `install.lock`.

### Method 2: Manual phpMyAdmin Import of `database.sql`
1. In phpMyAdmin, select your empty database.
2. Click the **Import** tab.
3. Select `database.sql`.
4. Click **Import** (or **Go**).
5. All 8 tables and default settings are initialized with zero errors.

---

## Verification & Sanity Checks

After importing `database-fix.sql` or `database.sql`, run the following verification queries in phpMyAdmin:

### 1. Check `settings` structure:
```sql
DESCRIBE `settings`;
```
**Expected Output:**
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `id` | bigint(20) unsigned | NO | PRI | NULL | auto_increment |
| `setting_key` | varchar(191) | NO | UNI | NULL | |
| `setting_value` | longtext | YES | | NULL | |
| `setting_group` | varchar(100) | YES | MUL | general | |
| `created_at` | timestamp | YES | | CURRENT_TIMESTAMP | |
| `updated_at` | timestamp | YES | | CURRENT_TIMESTAMP | on update CURRENT_TIMESTAMP |

### 2. Verify settings rows:
```sql
SELECT `setting_key`, `setting_value`, `setting_group` FROM `settings`;
```
You should see:
- `site_name`
- `admin_email`
- `auto_reply_enabled`
- `notification_enabled`
- and all other default keys.

### 3. Verify foreign key integrity:
```sql
SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() 
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

## Production Security Checklist

- [x] **No Plaintext Passwords**: Passwords are never committed into `.sql` files. All admin accounts use bcrypt hashes generated at setup.
- [x] **No Sensitive Credentials in SQL**: SMTP and IMAP passwords, database passwords, and API keys are stored exclusively in `.env`, not in database dump files.
- [x] **Installer Lock**: Ensure `install.lock` exists in the project root on production so unauthorized users cannot rerun `install.php`.
- [x] **Prepared Statements**: All database operations in `includes/ContactService.php`, `includes/EmailSyncService.php`, and `admin/` use PDO parameterized queries to prevent SQL injection.
