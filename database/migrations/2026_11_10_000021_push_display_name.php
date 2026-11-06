<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** First name of a visitor (typed on the home page), so reminders can greet them. Accounts use their own name. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->exec('ALTER TABLE ' . $schema->table('push_subscriptions') . ' ADD COLUMN display_name VARCHAR(60) NULL');
    }

    public function down(Schema $schema): void
    {
        $schema->exec('ALTER TABLE ' . $schema->table('push_subscriptions') . ' DROP COLUMN display_name');
    }
};
