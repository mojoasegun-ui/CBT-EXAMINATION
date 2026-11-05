<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Scheduled school exams: papers (one per subject), fixed or random question selection, target classes. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_exams')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            term_id BIGINT UNSIGNED NULL,
            title VARCHAR(160) NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'test',
            status ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            duration_minutes SMALLINT UNSIGNED NOT NULL,
            instructions TEXT NULL,
            settings TEXT NOT NULL,
            created_by BIGINT UNSIGNED NULL,
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}sexams_window (school_id, status, starts_at),
            KEY idx_{$p}sexams_author (school_id, created_by),
            CONSTRAINT fk_{$p}sexams_school FOREIGN KEY (school_id) REFERENCES {$schema->table('schools')} (id) ON DELETE CASCADE
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_exam_papers')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id BIGINT UNSIGNED NOT NULL,
            subject VARCHAR(80) NOT NULL,
            mode ENUM('random','fixed') NOT NULL DEFAULT 'random',
            question_count SMALLINT UNSIGNED NOT NULL DEFAULT 20,
            topics TEXT NULL,
            difficulty VARCHAR(10) NULL,
            position TINYINT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}paper_subject (exam_id, subject),
            CONSTRAINT fk_{$p}papers_exam FOREIGN KEY (exam_id) REFERENCES {$schema->table('school_exams')} (id) ON DELETE CASCADE
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_exam_questions')} (
            paper_id BIGINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            position SMALLINT UNSIGNED NOT NULL,
            PRIMARY KEY (paper_id, question_id),
            CONSTRAINT fk_{$p}seq_paper FOREIGN KEY (paper_id) REFERENCES {$schema->table('school_exam_papers')} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}seq_question FOREIGN KEY (question_id) REFERENCES {$schema->table('bank_questions')} (id)
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_exam_classes')} (
            exam_id BIGINT UNSIGNED NOT NULL,
            class_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (exam_id, class_id),
            KEY idx_{$p}sec_class (class_id),
            CONSTRAINT fk_{$p}sec_exam FOREIGN KEY (exam_id) REFERENCES {$schema->table('school_exams')} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}sec_class FOREIGN KEY (class_id) REFERENCES {$schema->table('school_classes')} (id) ON DELETE CASCADE
        ) {$o}");
    }

    public function down(Schema $schema): void
    {
        foreach (['school_exam_classes', 'school_exam_questions', 'school_exam_papers', 'school_exams'] as $t) {
            $schema->exec('DROP TABLE IF EXISTS ' . $schema->table($t));
        }
    }
};
