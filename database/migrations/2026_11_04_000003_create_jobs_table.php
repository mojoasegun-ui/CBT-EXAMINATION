<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Database-backed job queue: works on any shared host, no Redis or daemon required. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('jobs')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(100) NOT NULL,
            payload TEXT NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
            available_at DATETIME NOT NULL,
            reserved_at DATETIME NULL,
            reservation CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
            failed_at DATETIME NULL,
            last_error VARCHAR(1000) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}jobs_ready (failed_at, reserved_at, available_at),
            KEY idx_{$p}jobs_reservation (reservation)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('jobs'));
    }
};
