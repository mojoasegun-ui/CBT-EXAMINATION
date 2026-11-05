-- ExamCore CBT schema (generated from the migrations).
-- Preferred: run `php cbt migrate`. Use this file only where there is no shell
-- (e.g. phpMyAdmin on shared hosting), then import database/seed.sql if you want demo data.
-- Safe to import repeatedly.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `cbt_attempt_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attempt_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `selected_option` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbt_attempt_answers_attempt_question` (`attempt_id`,`question_id`),
  KEY `idx_cbt_attempt_answers_question` (`question_id`),
  CONSTRAINT `fk_cbt_attempt_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `cbt_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cbt_attempt_answers_question` FOREIGN KEY (`question_id`) REFERENCES `cbt_questions` (`id`),
  CONSTRAINT `chk_cbt_attempt_answers_option` CHECK (`selected_option` is null or `selected_option` in ('A','B','C','D'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cbt_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint(20) unsigned NOT NULL,
  `session_token` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `student_name` varchar(80) DEFAULT NULL,
  `student_email` varchar(180) DEFAULT NULL,
  `access_token` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `multi_exam` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`multi_exam`)),
  `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_order`)),
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `score` decimal(9,2) DEFAULT NULL,
  `total` decimal(9,2) NOT NULL DEFAULT 0.00,
  `duration_minutes` smallint(5) unsigned DEFAULT NULL,
  `status` enum('in_progress','submitted','timed_out') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbt_attempts_access_token` (`access_token`),
  KEY `idx_cbt_attempts_session_recent` (`session_token`,`started_at`),
  KEY `idx_cbt_attempts_exam_status` (`exam_id`,`status`),
  KEY `idx_cbt_attempts_student_email` (`student_email`),
  KEY `idx_cbt_attempts_status_started` (`status`,`started_at`),
  CONSTRAINT `fk_cbt_attempts_exam` FOREIGN KEY (`exam_id`) REFERENCES `cbt_exams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cbt_exam_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(40) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbt_exam_types_slug` (`slug`),
  KEY `idx_cbt_exam_types_active` (`is_active`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cbt_exams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exam_type_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `title` varchar(180) NOT NULL,
  `duration_minutes` smallint(5) unsigned NOT NULL DEFAULT 15,
  `total_questions` smallint(5) unsigned NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbt_exam_type_subject_year` (`exam_type_id`,`subject_id`,`year`),
  KEY `idx_cbt_exams_available` (`exam_type_id`,`subject_id`,`is_active`,`year`),
  KEY `fk_cbt_exams_subject` (`subject_id`),
  CONSTRAINT `fk_cbt_exams_exam_type` FOREIGN KEY (`exam_type_id`) REFERENCES `cbt_exam_types` (`id`),
  CONSTRAINT `fk_cbt_exams_subject` FOREIGN KEY (`subject_id`) REFERENCES `cbt_subjects` (`id`),
  CONSTRAINT `chk_cbt_exams_duration` CHECK (`duration_minutes` > 0),
  CONSTRAINT `chk_cbt_exams_questions` CHECK (`total_questions` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cbt_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(100) NOT NULL,
  `payload` text NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `available_at` datetime NOT NULL,
  `reserved_at` datetime DEFAULT NULL,
  `reservation` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `last_error` varchar(1000) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cbt_jobs_ready` (`failed_at`,`reserved_at`,`available_at`),
  KEY `idx_cbt_jobs_reservation` (`reservation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cbt_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint(20) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `correct_option` char(1) NOT NULL,
  `explanation` text NOT NULL,
  `marks` decimal(7,2) NOT NULL DEFAULT 1.00,
  `sort_order` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbt_questions_exam_order` (`exam_id`,`sort_order`),
  CONSTRAINT `fk_cbt_questions_exam` FOREIGN KEY (`exam_id`) REFERENCES `cbt_exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_cbt_questions_correct_option` CHECK (`correct_option` in ('A','B','C','D')),
  CONSTRAINT `chk_cbt_questions_marks` CHECK (`marks` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cbt_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(40) NOT NULL DEFAULT 'book-open',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbt_subjects_slug` (`slug`),
  KEY `idx_cbt_subjects_active` (`is_active`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
