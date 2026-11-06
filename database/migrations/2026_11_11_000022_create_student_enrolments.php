<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Student enrolment history: which class each student was in, in each academic session, and how that session ended
 * (promoted, repeated, graduated...). The class name is copied in, so history still reads correctly if a class is
 * later renamed or deleted. One row per student per session.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $e = $schema->table('student_enrolments');
        $schema->exec("CREATE TABLE IF NOT EXISTS {$e} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            session_id BIGINT UNSIGNED NOT NULL,
            class_id BIGINT UNSIGNED NULL,
            class_name VARCHAR(80) NOT NULL DEFAULT '',
            level_order SMALLINT UNSIGNED NULL,
            outcome ENUM('in_progress','promoted','repeated','graduated','withdrawn','suspended') NOT NULL DEFAULT 'in_progress',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}enrol_student_session (student_id, session_id),
            KEY idx_{$p}enrol_session_class (school_id, session_id, class_id),
            KEY idx_{$p}enrol_student (school_id, student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Back-fill: every existing student gets one row in their school's current session (or its newest session).
        $sp = $schema->table('student_profiles');
        $ses = $schema->table('academic_sessions');
        $cl = $schema->table('school_classes');
        $now = gmdate('Y-m-d H:i:s');
        $schema->exec("INSERT IGNORE INTO {$e} (school_id, student_id, session_id, class_id, class_name, level_order, outcome, created_at, updated_at)
            SELECT sp.school_id, sp.user_id, s.id, sp.class_id, COALESCE(c.name, ''), c.level_order,
                   CASE sp.status WHEN 'active' THEN 'in_progress' WHEN 'graduated' THEN 'graduated' WHEN 'withdrawn' THEN 'withdrawn' ELSE 'suspended' END, '{$now}', '{$now}'
            FROM {$sp} sp
            JOIN {$ses} s ON s.id = (SELECT x.id FROM {$ses} x WHERE x.school_id = sp.school_id ORDER BY x.is_current DESC, x.starts_on DESC LIMIT 1)
            LEFT JOIN {$cl} c ON c.id = sp.class_id AND c.school_id = sp.school_id");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('student_enrolments'));
    }
};
