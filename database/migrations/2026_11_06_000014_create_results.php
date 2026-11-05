<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Results: report-card settings, parent result codes, and teacher/principal comments per term. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec("ALTER TABLE {$schema->table('schools')} ADD COLUMN report_settings TEXT NULL AFTER primary_color");
        $schema->exec("ALTER TABLE {$schema->table('student_profiles')} ADD COLUMN result_code CHAR(8) NULL AFTER photo_path");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('report_remarks')} (
            school_id BIGINT UNSIGNED NOT NULL,
            term_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            teacher_comment VARCHAR(400) NULL,
            principal_comment VARCHAR(400) NULL,
            updated_by BIGINT UNSIGNED NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (term_id, student_id),
            KEY idx_{$p}remarks_school (school_id, term_id),
            CONSTRAINT fk_{$p}remarks_school FOREIGN KEY (school_id) REFERENCES {$schema->table('schools')} (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $schema->exec("CREATE INDEX idx_{$p}sexams_term ON {$schema->table('school_exams')} (school_id, term_id, status)");
    }

    public function down(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec("DROP INDEX idx_{$p}sexams_term ON {$schema->table('school_exams')}");
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('report_remarks'));
        $schema->exec("ALTER TABLE {$schema->table('student_profiles')} DROP COLUMN result_code");
        $schema->exec("ALTER TABLE {$schema->table('schools')} DROP COLUMN report_settings");
    }
};
