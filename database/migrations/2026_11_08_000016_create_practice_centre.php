<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Practice Centre: sessions (topic drills, quick practice, mistakes quizzes, mock exams), their answers,
 * running per-topic statistics and daily activity. The owner is either a signed-in student ("s:ID") or an
 * anonymous visitor identified by a random cookie ("g:TOKEN"), so progress works for both.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('practice_sessions')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token CHAR(32) NOT NULL,
            owner_key VARCHAR(40) NOT NULL,
            school_id BIGINT UNSIGNED NULL,
            student_id BIGINT UNSIGNED NULL,
            mode ENUM('topic','quick','mistakes','mock') NOT NULL,
            title VARCHAR(160) NOT NULL,
            config TEXT NULL,
            status ENUM('in_progress','submitted') NOT NULL DEFAULT 'in_progress',
            started_at DATETIME NOT NULL,
            deadline_at DATETIME NULL,
            submitted_at DATETIME NULL,
            question_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            correct_count SMALLINT UNSIGNED NULL,
            percent DECIMAL(5,2) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}psess_token (token),
            KEY idx_{$p}psess_owner (owner_key, status, id),
            KEY idx_{$p}psess_school (school_id, submitted_at),
            KEY idx_{$p}psess_status (status, deadline_at)
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('practice_items')} (
            session_id BIGINT UNSIGNED NOT NULL,
            position SMALLINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            subject VARCHAR(80) NOT NULL,
            topic VARCHAR(120) NOT NULL,
            option_order VARCHAR(5) NOT NULL,
            answer CHAR(1) NULL,
            is_correct TINYINT(1) NULL,
            answered_at DATETIME NULL,
            PRIMARY KEY (session_id, position),
            KEY idx_{$p}pitem_question (question_id),
            CONSTRAINT fk_{$p}pitem_session FOREIGN KEY (session_id) REFERENCES {$schema->table('practice_sessions')} (id) ON DELETE CASCADE
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('practice_topic_stats')} (
            owner_key VARCHAR(40) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            topic VARCHAR(120) NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            correct INT UNSIGNED NOT NULL DEFAULT 0,
            last_at DATETIME NOT NULL,
            PRIMARY KEY (owner_key, subject, topic)
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('practice_days')} (
            owner_key VARCHAR(40) NOT NULL,
            day DATE NOT NULL,
            answered SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            correct SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (owner_key, day)
        ) {$o}");
    }

    public function down(Schema $schema): void
    {
        foreach (['practice_days', 'practice_topic_stats', 'practice_items', 'practice_sessions'] as $t) {
            $schema->exec('DROP TABLE IF EXISTS ' . $schema->table($t));
        }
    }
};
