<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Multi-school foundation. Every school (tenant) is on a plan; plans switch features on/off.
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $options = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('plans')} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(30) NOT NULL,
            name VARCHAR(80) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT '',
            price_per_student_kobo INT UNSIGNED NOT NULL DEFAULT 0,
            billing_period ENUM('term', 'month', 'year') NOT NULL DEFAULT 'term',
            max_students INT UNSIGNED NULL,
            features TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}plans_code (code)
        ) {$options}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('schools')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(60) NOT NULL,
            name VARCHAR(160) NOT NULL,
            short_name VARCHAR(40) NULL,
            motto VARCHAR(160) NULL,
            status ENUM('trial', 'active', 'suspended', 'archived') NOT NULL DEFAULT 'trial',
            plan_id INT UNSIGNED NOT NULL,
            max_students_override INT UNSIGNED NULL,
            trial_ends_at DATE NULL,
            subscription_ends_at DATE NULL,
            email VARCHAR(180) NULL,
            phone VARCHAR(30) NULL,
            address VARCHAR(255) NULL,
            state VARCHAR(60) NULL,
            country VARCHAR(60) NOT NULL DEFAULT 'Nigeria',
            logo_path VARCHAR(255) NULL,
            primary_color CHAR(7) NOT NULL DEFAULT '#1E40AF',
            custom_domain VARCHAR(190) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}schools_slug (slug),
            UNIQUE KEY uq_{$p}schools_domain (custom_domain),
            KEY idx_{$p}schools_status (status, created_at),
            CONSTRAINT fk_{$p}schools_plan FOREIGN KEY (plan_id) REFERENCES {$schema->table('plans')} (id)
        ) {$options}");

        $now = gmdate('Y-m-d H:i:s');
        $insert = $schema->pdo()->prepare('INSERT IGNORE INTO ' . $schema->table('plans') . '
            (code, name, description, price_per_student_kobo, billing_period, max_students, features, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, \'term\', ?, ?, ?, ?, ?)');
        $plans = [
            ['trial', 'Free trial', '30 days to try the platform with a small group.', 0, 50, ['school_question_bank', 'result_pdf'], 1],
            ['basic', 'Basic', 'Core CBT for a single school: exams, results and reports.', 30000, 500, ['school_question_bank', 'result_pdf', 'analytics'], 2],
            ['pro', 'Pro', 'Everything in Basic plus proctoring, the curated question bank and offline packs.', 70000, 3000,
                ['school_question_bank', 'result_pdf', 'analytics', 'global_question_bank', 'proctoring', 'face_verification', 'offline_packs'], 3],
            ['enterprise', 'Enterprise', 'School groups and large institutions: white-label, LAN mode and dedicated support.', 0, null,
                ['school_question_bank', 'result_pdf', 'analytics', 'global_question_bank', 'proctoring', 'face_verification', 'offline_packs', 'white_label', 'lan_mode', 'api_access'], 4],
        ];
        foreach ($plans as [$code, $name, $description, $price, $max, $features, $order]) {
            $insert->execute([$code, $name, $description, $price, $max, json_encode($features), $order, $now, $now]);
        }
    }

    public function down(Schema $schema): void
    {
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('schools'));
        $schema->exec('DROP TABLE IF EXISTS ' . $schema->table('plans'));
    }
};
