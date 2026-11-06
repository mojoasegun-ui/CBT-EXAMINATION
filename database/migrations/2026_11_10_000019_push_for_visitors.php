<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Phone notifications for visitors without an account: they are identified by the same anonymous cookie the
 * Practice Centre uses. Also tracks when a device last opened the app, so "come back" reminders stop on their own.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $s = $schema->table('push_subscriptions');
        $n = $schema->table('notifications');
        $schema->exec("ALTER TABLE {$s} MODIFY user_id BIGINT UNSIGNED NULL,
            ADD COLUMN guest_key CHAR(24) NULL AFTER user_id,
            ADD COLUMN last_seen_at DATETIME NULL,
            ADD COLUMN last_engage_at DATETIME NULL,
            ADD COLUMN engage_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            ADD KEY idx_{$p}push_guest (guest_key)");
        $schema->exec("ALTER TABLE {$n} MODIFY user_id BIGINT UNSIGNED NULL,
            ADD COLUMN guest_key CHAR(24) NULL AFTER user_id,
            ADD KEY idx_{$p}notif_guest (guest_key, delivered_at, id)");
    }

    public function down(Schema $schema): void
    {
        $p = $schema->prefix();
        $schema->exec('ALTER TABLE ' . $schema->table('notifications') . " DROP KEY idx_{$p}notif_guest, DROP COLUMN guest_key");
        $schema->exec('ALTER TABLE ' . $schema->table('push_subscriptions') . " DROP KEY idx_{$p}push_guest, DROP COLUMN guest_key, DROP COLUMN last_seen_at, DROP COLUMN last_engage_at, DROP COLUMN engage_count");
    }
};
