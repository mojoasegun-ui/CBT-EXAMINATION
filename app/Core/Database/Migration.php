<?php
declare(strict_types=1);

namespace ExamCore\Core\Database;

use RuntimeException;

/**
 * Base class for files in database/migrations.
 *
 * Each file returns an instance: `return new class extends Migration { ... };`
 * MySQL DDL commits implicitly, so migrations must be written to be safe to
 * re-run (use the Schema existence helpers).
 */
abstract class Migration
{
    abstract public function up(Schema $schema): void;

    public function down(Schema $schema): void
    {
        throw new RuntimeException(static::class . ' cannot be rolled back. Restore from a backup instead.');
    }
}
