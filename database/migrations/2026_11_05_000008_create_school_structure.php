<?php
declare(strict_types=1);

use ExamCore\Core\Database\Migration;
use ExamCore\Core\Database\Schema;

/**
 * Everything a school sets up before running exams: academic calendar, departments, classes,
 * subjects, teacher assignments, student and staff profiles, grading scale, and import batches.
 * Every row carries school_id; every query filters by it (see TenantContext).
 */
return new class extends Migration {
    public function up(Schema $schema): void
    {
        $p = $schema->prefix();
        $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $schools = $schema->table('schools');
        $users = $schema->table('users');

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('academic_sessions')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(20) NOT NULL,
            starts_on DATE NOT NULL,
            ends_on DATE NOT NULL,
            is_current TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}sessions_name (school_id, name),
            KEY idx_{$p}sessions_current (school_id, is_current),
            CONSTRAINT fk_{$p}sessions_school FOREIGN KEY (school_id) REFERENCES {$schools} (id) ON DELETE CASCADE
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('terms')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            session_id BIGINT UNSIGNED NOT NULL,
            position TINYINT UNSIGNED NOT NULL,
            name VARCHAR(30) NOT NULL,
            starts_on DATE NULL,
            ends_on DATE NULL,
            is_current TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}terms_position (session_id, position),
            KEY idx_{$p}terms_current (school_id, is_current),
            CONSTRAINT fk_{$p}terms_session FOREIGN KEY (session_id) REFERENCES {$schema->table('academic_sessions')} (id) ON DELETE CASCADE
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('departments')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(60) NOT NULL,
            code VARCHAR(12) NULL,
            description VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}departments_name (school_id, name),
            CONSTRAINT fk_{$p}departments_school FOREIGN KEY (school_id) REFERENCES {$schools} (id) ON DELETE CASCADE
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_classes')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            level VARCHAR(20) NOT NULL,
            level_order TINYINT UNSIGNED NOT NULL,
            arm VARCHAR(20) NOT NULL DEFAULT 'A',
            name VARCHAR(50) NOT NULL,
            department_id BIGINT UNSIGNED NULL,
            class_teacher_id BIGINT UNSIGNED NULL,
            capacity SMALLINT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}classes_level_arm (school_id, level, arm),
            KEY idx_{$p}classes_order (school_id, level_order, arm),
            CONSTRAINT fk_{$p}classes_school FOREIGN KEY (school_id) REFERENCES {$schools} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}classes_department FOREIGN KEY (department_id) REFERENCES {$schema->table('departments')} (id) ON DELETE SET NULL,
            CONSTRAINT fk_{$p}classes_teacher FOREIGN KEY (class_teacher_id) REFERENCES {$users} (id) ON DELETE SET NULL
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('school_subjects')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(80) NOT NULL,
            code VARCHAR(12) NULL,
            category VARCHAR(20) NOT NULL DEFAULT 'general',
            department_id BIGINT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}subjects_name (school_id, name),
            KEY idx_{$p}subjects_category (school_id, category, is_active),
            CONSTRAINT fk_{$p}school_subjects_school FOREIGN KEY (school_id) REFERENCES {$schools} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}school_subjects_department FOREIGN KEY (department_id) REFERENCES {$schema->table('departments')} (id) ON DELETE SET NULL
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('class_subjects')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            class_id BIGINT UNSIGNED NOT NULL,
            subject_id BIGINT UNSIGNED NOT NULL,
            teacher_id BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}class_subject (class_id, subject_id),
            KEY idx_{$p}class_subjects_teacher (school_id, teacher_id),
            CONSTRAINT fk_{$p}cs_class FOREIGN KEY (class_id) REFERENCES {$schema->table('school_classes')} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}cs_subject FOREIGN KEY (subject_id) REFERENCES {$schema->table('school_subjects')} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}cs_teacher FOREIGN KEY (teacher_id) REFERENCES {$users} (id) ON DELETE SET NULL
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('student_profiles')} (
            user_id BIGINT UNSIGNED NOT NULL,
            school_id BIGINT UNSIGNED NOT NULL,
            admission_no VARCHAR(40) NOT NULL,
            class_id BIGINT UNSIGNED NULL,
            department_id BIGINT UNSIGNED NULL,
            gender ENUM('female', 'male') NULL,
            date_of_birth DATE NULL,
            guardian_name VARCHAR(120) NULL,
            guardian_phone VARCHAR(30) NULL,
            guardian_email VARCHAR(180) NULL,
            address VARCHAR(255) NULL,
            photo_path VARCHAR(255) NULL,
            status ENUM('active', 'graduated', 'withdrawn', 'suspended') NOT NULL DEFAULT 'active',
            admitted_on DATE NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (user_id),
            UNIQUE KEY uq_{$p}students_admission (school_id, admission_no),
            KEY idx_{$p}students_class (school_id, class_id, status),
            KEY idx_{$p}students_status (school_id, status),
            CONSTRAINT fk_{$p}students_user FOREIGN KEY (user_id) REFERENCES {$users} (id) ON DELETE CASCADE,
            CONSTRAINT fk_{$p}students_class FOREIGN KEY (class_id) REFERENCES {$schema->table('school_classes')} (id) ON DELETE SET NULL,
            CONSTRAINT fk_{$p}students_department FOREIGN KEY (department_id) REFERENCES {$schema->table('departments')} (id) ON DELETE SET NULL
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('staff_profiles')} (
            user_id BIGINT UNSIGNED NOT NULL,
            school_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(12) NULL,
            staff_no VARCHAR(40) NULL,
            qualification VARCHAR(120) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (user_id),
            UNIQUE KEY uq_{$p}staff_no (school_id, staff_no),
            CONSTRAINT fk_{$p}staff_user FOREIGN KEY (user_id) REFERENCES {$users} (id) ON DELETE CASCADE
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('grading_scales')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            grade VARCHAR(4) NOT NULL,
            min_score DECIMAL(5,2) NOT NULL,
            max_score DECIMAL(5,2) NOT NULL,
            remark VARCHAR(40) NOT NULL,
            is_pass TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_{$p}grades (school_id, grade),
            CONSTRAINT fk_{$p}grades_school FOREIGN KEY (school_id) REFERENCES {$schools} (id) ON DELETE CASCADE
        ) {$o}");

        $schema->exec("CREATE TABLE IF NOT EXISTS {$schema->table('import_batches')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            school_id BIGINT UNSIGNED NOT NULL,
            created_by BIGINT UNSIGNED NULL,
            kind VARCHAR(20) NOT NULL,
            status ENUM('ready', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'ready',
            file_name VARCHAR(190) NULL,
            total_rows INT UNSIGNED NOT NULL DEFAULT 0,
            processed_rows INT UNSIGNED NOT NULL DEFAULT 0,
            created_rows INT UNSIGNED NOT NULL DEFAULT 0,
            skipped_rows INT UNSIGNED NOT NULL DEFAULT 0,
            payload MEDIUMTEXT NOT NULL,
            credentials MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_{$p}imports_school (school_id, created_at),
            CONSTRAINT fk_{$p}imports_school FOREIGN KEY (school_id) REFERENCES {$schools} (id) ON DELETE CASCADE
        ) {$o}");

        // Fast name search inside one school's people (students lists, quick search).
        $schema->addIndexIfMissing('users', 'idx_' . $p . 'users_school_name', '`school_id`, `role`, `name`(40)');
    }

    public function down(Schema $schema): void
    {
        foreach (['import_batches', 'grading_scales', 'staff_profiles', 'student_profiles', 'class_subjects', 'school_subjects', 'school_classes', 'departments', 'terms', 'academic_sessions'] as $table) {
            $schema->exec('DROP TABLE IF EXISTS ' . $schema->table($table));
        }
    }
};
