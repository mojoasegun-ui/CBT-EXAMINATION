<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/** Remembers the last accepted TOTP time-step so a captured code cannot be replayed. */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->addColumnIfMissing('users', 'two_factor_last_step', 'INT UNSIGNED NULL AFTER `two_factor_recovery_codes`');
    }

    public function down(Schema $schema): void
    {
        if ($schema->columnExists('users', 'two_factor_last_step')) {
            $schema->exec('ALTER TABLE ' . $schema->table('users') . ' DROP COLUMN `two_factor_last_step`');
        }
    }
};
