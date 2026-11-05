<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Question bank: per-school banks plus the platform bank (school_id NULL), passages, review workflow. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('bank_passages')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NULL,
            subject VARCHAR(80) NOT NULL,
            title VARCHAR(160) NOT NULL,
            body MEDIUMTEXT NOT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}passages_scope (school_id, subject)
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('bank_questions')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NULL,
            subject VARCHAR(80) NOT NULL,
            topic VARCHAR(120) NULL,
            level VARCHAR(10) NOT NULL DEFAULT 'any',
            difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
            exam_body VARCHAR(10) NULL,
            year SMALLINT UNSIGNED NULL,
            source ENUM('teacher','practice','past_licensed','ai_reviewed') NOT NULL DEFAULT 'teacher',
            passage_id BIGINT UNSIGNED NULL,
            body TEXT NOT NULL,
            image_path VARCHAR(255) NULL,
            option_a TEXT NOT NULL,
            option_b TEXT NOT NULL,
            option_c TEXT NULL,
            option_d TEXT NULL,
            option_e TEXT NULL,
            correct_option CHAR(1) NOT NULL,
            explanation TEXT NULL,
            marks DECIMAL(5,2) NOT NULL DEFAULT 1.00,
            status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
            review_note VARCHAR(255) NULL,
            fingerprint CHAR(40) NOT NULL,
            times_answered INT UNSIGNED NOT NULL DEFAULT 0,
            times_correct INT UNSIGNED NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            reviewed_by BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}bank_scope (school_id, subject, status),
            KEY idx_{$p}bank_status (school_id, status, updated_at),
            KEY idx_{$p}bank_author (school_id, created_by),
            KEY idx_{$p}bank_fingerprint (school_id, fingerprint),
            FULLTEXT KEY ft_{$p}bank_body (body),
            CONSTRAINT fk_{$p}bank_passage FOREIGN KEY (passage_id) REFERENCES {$schema->table('bank_passages')} (id) ON DELETE SET NULL,
            CONSTRAINT chk_{$p}bank_answer CHECK (correct_option IN ('A','B','C','D','E'))
        ) {$o}");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('bank_questions'));
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('bank_passages'));
    }
};
