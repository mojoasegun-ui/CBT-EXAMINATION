<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Paper exams: frozen question versions (Paper A, B …) and paper sittings marked from
 * scanned shading sheets, with an optional theory score.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_exam_versions')} (
            exam_id BIGINT UNSIGNED NOT NULL,
            version CHAR(1) NOT NULL,
            items MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (exam_id, version),
            CONSTRAINT fk_{$p}sev_exam FOREIGN KEY (exam_id) REFERENCES {$schema->table('school_exams')} (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $schema->exec("ALTER TABLE {$schema->table('exam_sittings')}
            ADD COLUMN mode ENUM('cbt','paper') NOT NULL DEFAULT 'cbt' AFTER status,
            ADD COLUMN version CHAR(1) NULL AFTER mode,
            ADD COLUMN theory_score DECIMAL(5,1) NULL AFTER breakdown,
            ADD COLUMN theory_max SMALLINT UNSIGNED NULL AFTER theory_score,
            ADD COLUMN scan_path VARCHAR(255) NULL AFTER theory_max,
            ADD COLUMN scanned_by BIGINT UNSIGNED NULL AFTER scan_path");
    }

    public function down(Schema $schema): void
    {
        $schema->exec("ALTER TABLE {$schema->table('exam_sittings')} DROP COLUMN mode, DROP COLUMN version, DROP COLUMN theory_score, DROP COLUMN theory_max, DROP COLUMN scan_path, DROP COLUMN scanned_by");
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('school_exam_versions'));
    }
};
