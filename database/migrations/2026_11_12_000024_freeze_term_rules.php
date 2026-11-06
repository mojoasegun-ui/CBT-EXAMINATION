<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * A term keeps its own copy of the grading bands and report settings it was issued with ("frozen"), so changing the
 * grading scale later never rewrites old report cards. Empty means "use the school's live rules".
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->exec('ALTER TABLE ' . $schema->table('terms') . ' ADD COLUMN snapshot MEDIUMTEXT NULL, ADD COLUMN frozen_at DATETIME NULL');
    }

    public function down(Schema $schema): void
    {
        $schema->exec('ALTER TABLE ' . $schema->table('terms') . ' DROP COLUMN snapshot, DROP COLUMN frozen_at');
    }
};
