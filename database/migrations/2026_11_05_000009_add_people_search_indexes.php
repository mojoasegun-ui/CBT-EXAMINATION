<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Indexes that keep student lists and search fast at tens of thousands of students
 * (measured: list 1.9 s → see docs; 25 concurrent searches no longer queue).
 *  - users(school_id, role, name): full column (a prefix index cannot serve ORDER BY name)
 *  - users(school_id, role): with the implicit primary key gives "newest first" ordering
 *  - FULLTEXT users(name): word-prefix name search instead of scanning every row
 *  - student_profiles(school_id, guardian_phone): parent phone lookups
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $users = $schema->table('users');
        if ($schema->indexExists('users', 'idx_' . $p . 'users_school_name')) {
            $schema->exec("ALTER TABLE {$users} DROP INDEX `idx_{$p}users_school_name`");
        }
        $schema->addIndexIfMissing('users', 'idx_' . $p . 'users_school_role_name', '`school_id`, `role`, `name`');
        $schema->addIndexIfMissing('users', 'idx_' . $p . 'users_school_role_id', '`school_id`, `role`');
        if (!$schema->indexExists('users', 'ft_' . $p . 'users_name')) {
            $schema->exec("ALTER TABLE {$users} ADD FULLTEXT INDEX `ft_{$p}users_name` (`name`)");
        }
        $schema->addIndexIfMissing('student_profiles', 'idx_' . $p . 'students_phone', '`school_id`, `guardian_phone`');
    }

    public function down(Schema $schema): void
    {
        $p = $schema->prefix();
        foreach ([['users', 'idx_' . $p . 'users_school_role_name'], ['users', 'idx_' . $p . 'users_school_role_id'], ['users', 'ft_' . $p . 'users_name'], ['student_profiles', 'idx_' . $p . 'students_phone']] as [$table, $index]) {
            if ($schema->indexExists($table, $index)) {
                $schema->exec('ALTER TABLE ' . $schema->table($table) . ' DROP INDEX `' . $index . '`');
            }
        }
    }
};
