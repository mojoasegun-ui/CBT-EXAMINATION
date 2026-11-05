<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Baseline: the original practice-simulator tables.
 * Uses IF NOT EXISTS so it is a no-op on databases created from the old schema.sql.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $options = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('exam_types')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(40) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}exam_types_slug (slug),
            KEY idx_{$p}exam_types_active (is_active, name)
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('subjects')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(60) NOT NULL,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(40) NOT NULL DEFAULT 'book-open',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}subjects_slug (slug),
            KEY idx_{$p}subjects_active (is_active, name)
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('exams')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_type_id BIGINT UNSIGNED NOT NULL,
            subject_id BIGINT UNSIGNED NOT NULL,
            year SMALLINT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
            total_questions SMALLINT UNSIGNED NOT NULL DEFAULT 10,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}exam_type_subject_year (exam_type_id, subject_id, year),
            KEY idx_{$p}exams_available (exam_type_id, subject_id, is_active, year),
            CONSTRAINT fk_{$p}exams_exam_type FOREIGN KEY (exam_type_id) REFERENCES {$schema->table('exam_types')} (id),
            CONSTRAINT fk_{$p}exams_subject FOREIGN KEY (subject_id) REFERENCES {$schema->table('subjects')} (id),
            CONSTRAINT chk_{$p}exams_duration CHECK (duration_minutes > 0),
            CONSTRAINT chk_{$p}exams_questions CHECK (total_questions > 0)
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('questions')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id BIGINT UNSIGNED NOT NULL,
            question_text TEXT NOT NULL,
            option_a TEXT NOT NULL,
            option_b TEXT NOT NULL,
            option_c TEXT NOT NULL,
            option_d TEXT NOT NULL,
            correct_option CHAR(1) NOT NULL,
            explanation TEXT NOT NULL,
            marks DECIMAL(7,2) NOT NULL DEFAULT 1.00,
            sort_order SMALLINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}questions_exam_order (exam_id, sort_order),
            CONSTRAINT fk_{$p}questions_exam FOREIGN KEY (exam_id) REFERENCES {$schema->table('exams')} (id) ON DELETE CASCADE,
            CONSTRAINT chk_{$p}questions_correct_option CHECK (correct_option IN ('A', 'B', 'C', 'D')),
            CONSTRAINT chk_{$p}questions_marks CHECK (marks > 0)
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('attempts')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id BIGINT UNSIGNED NOT NULL,
            session_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            student_name VARCHAR(80) NULL,
            student_email VARCHAR(180) NULL,
            access_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
            multi_exam JSON NULL,
            question_order JSON NULL,
            started_at DATETIME NOT NULL,
            submitted_at DATETIME NULL,
            score DECIMAL(9,2) NULL,
            total DECIMAL(9,2) NOT NULL DEFAULT 0.00,
            status ENUM('in_progress', 'submitted', 'timed_out') NOT NULL DEFAULT 'in_progress',
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}attempts_access_token (access_token),
            KEY idx_{$p}attempts_session_recent (session_token, started_at),
            KEY idx_{$p}attempts_exam_status (exam_id, status),
            KEY idx_{$p}attempts_student_email (student_email),
            CONSTRAINT fk_{$p}attempts_exam FOREIGN KEY (exam_id) REFERENCES {$schema->table('exams')} (id)
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('attempt_answers')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            attempt_id BIGINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            selected_option CHAR(1) NULL,
            is_correct TINYINT(1) NULL,
            is_flagged TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}attempt_answers_attempt_question (attempt_id, question_id),
            KEY idx_{$p}attempt_answers_question (question_id),
            CONSTRAINT fk_{$p}attempt_answers_attempt FOREIGN KEY (attempt_id) REFERENCES {$schema->table('attempts')} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}attempt_answers_question FOREIGN KEY (question_id) REFERENCES {$schema->table('questions')} (id),
            CONSTRAINT chk_{$p}attempt_answers_option CHECK (selected_option IS NULL OR selected_option IN ('A', 'B', 'C', 'D'))
        ) {$options}");
    }
};
