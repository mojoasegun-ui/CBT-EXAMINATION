<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Password recovery links. Only a SHA-256 hash of each token is stored, so a copy of the database cannot be used to
 * take over accounts. Links expire after an hour and work once.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('password_resets')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}pwreset_token (token_hash),
            KEY idx_{$p}pwreset_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('password_resets'));
    }
};
