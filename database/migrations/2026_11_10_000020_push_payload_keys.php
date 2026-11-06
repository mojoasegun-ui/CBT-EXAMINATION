<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Per-device encryption keys, so the notification text can travel inside the push (required for reliable iPhone delivery). */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->exec('ALTER TABLE ' . $schema->table('push_subscriptions') . ' ADD COLUMN p256dh VARCHAR(100) NULL, ADD COLUMN auth VARCHAR(40) NULL');
    }

    public function down(Schema $schema): void
    {
        $schema->exec('ALTER TABLE ' . $schema->table('push_subscriptions') . ' DROP COLUMN p256dh, DROP COLUMN auth');
    }
};
