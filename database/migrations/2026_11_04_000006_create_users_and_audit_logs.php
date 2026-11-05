<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * One accounts table for every role. Platform owners have school_id NULL.
 *
 * Emails and usernames are unique *within* a school, so the same teacher can work at
 * two schools. The generated "scope" columns enforce that in the database, including
 * for platform accounts (a plain UNIQUE(school_id, email) would allow duplicate NULLs).
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $options = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('users')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NULL,
            role ENUM('owner', 'school_admin', 'teacher', 'reviewer', 'student') NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NULL,
            username VARCHAR(60) NULL,
            phone VARCHAR(30) NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
            must_change_password TINYINT(1) NOT NULL DEFAULT 0,
            two_factor_secret VARCHAR(255) NULL,
            two_factor_confirmed_at DATETIME NULL,
            two_factor_recovery_codes TEXT NULL,
            failed_logins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            locked_until DATETIME NULL,
            last_login_at DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            email_scope VARCHAR(200) AS (IF(email IS NULL, NULL, CONCAT(COALESCE(school_id, 0), ':', email))) STORED,
            username_scope VARCHAR(80) AS (IF(username IS NULL, NULL, CONCAT(COALESCE(school_id, 0), ':', username))) STORED,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}users_email_scope (email_scope),
            UNIQUE KEY uq_{$p}users_username_scope (username_scope),
            KEY idx_{$p}users_email (email),
            KEY idx_{$p}users_school_role (school_id, role, status),
            CONSTRAINT fk_{$p}users_school FOREIGN KEY (school_id) REFERENCES {$schema->table('schools')} (id) ON DELETE CASCADE
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('audit_logs')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NULL,
            actor_id BIGINT UNSIGNED NULL,
            actor_name VARCHAR(120) NULL,
            impersonator_id BIGINT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            target_type VARCHAR(40) NULL,
            target_id BIGINT UNSIGNED NULL,
            summary VARCHAR(255) NOT NULL,
            meta TEXT NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}audit_school_time (school_id, created_at),
            KEY idx_{$p}audit_actor (actor_id, created_at),
            KEY idx_{$p}audit_action (action, created_at)
        ) {$options}");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('audit_logs'));
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('users'));
    }
};
