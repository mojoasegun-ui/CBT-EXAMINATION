<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Attempts now snapshot their own time limit so that:
 *  - multi-subject sittings get the sum of the selected papers' durations, and
 *  - editing an exam's duration later never changes a running attempt.
 * The index lets the scheduler find overdue in-progress attempts cheaply.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->addColumnIfMissing('attempts', 'duration_minutes', 'SMALLINT UNSIGNED NULL AFTER `total`');
        $schema->addIndexIfMissing('attempts', 'idx_' . $schema->name('attempts') . '_status_started', '`status`, `started_at`');
    }

    public function down(Schema $schema): void
    {
        if ($schema->indexExists('attempts', 'idx_' . $schema->name('attempts') . '_status_started')) {
            $schema->exec('ALTER TABLE ' . $schema->table('attempts') . ' DROP INDEX `idx_' . $schema->name('attempts') . '_status_started`');
        }
        if ($schema->columnExists('attempts', 'duration_minutes')) {
            $schema->exec('ALTER TABLE ' . $schema->table('attempts') . ' DROP COLUMN `duration_minutes`');
        }
    }
};
