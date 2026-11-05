<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Proctoring: identity check, camera snapshots, seat numbers and one-device locking. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec("ALTER TABLE {$schema->table('exam_sittings')}
            ADD COLUMN face_status ENUM('none','verified','mismatch','approved') NOT NULL DEFAULT 'none' AFTER warnings,
            ADD COLUMN face_score DECIMAL(5,3) NULL AFTER face_status,
            ADD COLUMN flags SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER face_score,
            ADD COLUMN seat_no VARCHAR(12) NULL AFTER flags,
            ADD COLUMN device_hash CHAR(64) NULL AFTER seat_no");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('exam_snapshots')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sitting_id BIGINT UNSIGNED NOT NULL,
            kind ENUM('verify','random') NOT NULL DEFAULT 'random',
            path VARCHAR(255) NOT NULL,
            faces TINYINT UNSIGNED NULL,
            match_score DECIMAL(5,3) NULL,
            flag VARCHAR(20) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}snap_sitting (sitting_id, id),
            KEY idx_{$p}snap_created (created_at),
            CONSTRAINT fk_{$p}snap_sitting FOREIGN KEY (sitting_id) REFERENCES {$schema->table('exam_sittings')} (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('exam_snapshots'));
        $schema->exec("ALTER TABLE {$schema->table('exam_sittings')} DROP COLUMN face_status, DROP COLUMN face_score, DROP COLUMN flags, DROP COLUMN seat_no, DROP COLUMN device_hash");
    }
};
