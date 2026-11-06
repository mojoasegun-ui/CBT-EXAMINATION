<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Platform-wide settings the owner can change from the Console (product name, tagline, support email). */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('platform_settings')} (
            setting_key VARCHAR(60) NOT NULL,
            setting_value TEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by BIGINT UNSIGNED NULL,
            PRIMARY KEY (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('platform_settings'));
    }
};
