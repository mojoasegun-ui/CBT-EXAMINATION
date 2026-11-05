<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * One sitting per student per exam. Each sitting owns its own question order and option order,
 * so random papers stay fixed when the student refreshes or resumes on another device.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('exam_sittings')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            exam_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            status ENUM('in_progress','submitted','auto_submitted') NOT NULL DEFAULT 'in_progress',
            started_at DATETIME NOT NULL,
            deadline_at DATETIME NOT NULL,
            extra_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            submitted_at DATETIME NULL,
            last_seen_at DATETIME NULL,
            current_position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            question_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            answered_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            score SMALLINT UNSIGNED NULL,
            percent DECIMAL(5,2) NULL,
            breakdown TEXT NULL,
            warnings SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}sitting_student (exam_id, student_id),
            KEY idx_{$p}sitting_status (exam_id, status),
            KEY idx_{$p}sitting_deadline (status, deadline_at),
            KEY idx_{$p}sitting_student (school_id, student_id),
            CONSTRAINT fk_{$p}sitting_exam FOREIGN KEY (exam_id) REFERENCES {$schema->table('school_exams')} (id) ON DELETE CASCADE
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('exam_sitting_items')} (
            sitting_id BIGINT UNSIGNED NOT NULL,
            position SMALLINT UNSIGNED NOT NULL,
            paper_id BIGINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            option_order VARCHAR(5) NOT NULL,
            answer CHAR(1) NULL,
            flagged TINYINT(1) NOT NULL DEFAULT 0,
            answered_at DATETIME NULL,
            PRIMARY KEY (sitting_id, position),
            CONSTRAINT fk_{$p}sitem_sitting FOREIGN KEY (sitting_id) REFERENCES {$schema->table('exam_sittings')} (id) ON DELETE CASCADE
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('exam_sitting_events')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sitting_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            detail VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}sevent_sitting (sitting_id, id),
            CONSTRAINT fk_{$p}sevent_sitting FOREIGN KEY (sitting_id) REFERENCES {$schema->table('exam_sittings')} (id) ON DELETE CASCADE
        ) {$o}");
        $schema->exec("ALTER TABLE {$schema->table('school_exams')} ADD COLUMN results_released_at DATETIME NULL AFTER published_at");
    }

    public function down(Schema $schema): void
    {
        $schema->exec("ALTER TABLE {$schema->table('school_exams')} DROP COLUMN results_released_at");
        foreach (['exam_sitting_events', 'exam_sitting_items', 'exam_sittings'] as $t) {
            $schema->exec('DROP TABLE IF EXISTS ' . $schema->table($t));
        }
    }
};
