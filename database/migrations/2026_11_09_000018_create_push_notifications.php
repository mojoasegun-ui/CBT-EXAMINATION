<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Phone notifications. "notifications" holds what each person should be told (the text is fetched by the
 * service worker after a wake-up ping, so nothing sensitive travels through the push provider);
 * "push_subscriptions" holds each phone/browser that agreed to receive them.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('notifications')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            school_id BIGINT UNSIGNED NULL,
            type VARCHAR(20) NOT NULL,
            title VARCHAR(120) NOT NULL,
            body VARCHAR(240) NOT NULL,
            url VARCHAR(200) NOT NULL DEFAULT '/',
            created_at DATETIME NOT NULL,
            delivered_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}notif_user (user_id, delivered_at, id)
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('push_subscriptions')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            school_id BIGINT UNSIGNED NULL,
            endpoint_hash CHAR(64) NOT NULL,
            endpoint TEXT NOT NULL,
            device VARCHAR(160) NOT NULL DEFAULT '',
            fails TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            last_ok_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}push_endpoint (endpoint_hash),
            KEY idx_{$p}push_user (user_id)
        ) {$o}");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('push_subscriptions'));
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('notifications'));
    }
};
