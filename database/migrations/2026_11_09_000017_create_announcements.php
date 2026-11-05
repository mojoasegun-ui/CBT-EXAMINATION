<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Announcements. A school admin or teacher posts to the students (optionally one class) or to the staff of
 * their own school; the platform owner can post to everyone. school_id NULL means "from the platform".
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('announcements')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            author_name VARCHAR(120) NOT NULL,
            author_role VARCHAR(20) NOT NULL,
            audience ENUM('students','teachers','everyone') NOT NULL,
            class_id BIGINT UNSIGNED NULL,
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            pinned TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}ann_school (school_id, audience, id),
            KEY idx_{$p}ann_author (author_id, id)
        ) {$o}");
        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('announcement_reads')} (
            announcement_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL,
            PRIMARY KEY (announcement_id, user_id),
            KEY idx_{$p}annr_user (user_id)
        ) {$o}");
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('announcement_reads'));
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('announcements'));
    }
};
