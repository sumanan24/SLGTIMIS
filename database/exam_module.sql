-- SLGTI MIS — Exam module (EXAM / ADM). Run once on your database.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `exams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` VARCHAR(50) NOT NULL,
  `group_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Batch (groups.id)',
  `semester` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Semester used when creating exam (module filter)',
  `exam_date` DATE NOT NULL,
  `exam_modules` TEXT NOT NULL COMMENT 'JSON: [{module_id, exam_date, exam_time, location}, ...]',
  `exam_time` VARCHAR(80) NOT NULL COMMENT 'Summary: single time or Various',
  `location` VARCHAR(255) NOT NULL COMMENT 'Summary: single venue or Various',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exams_course` (`course_id`),
  KEY `idx_exams_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` INT UNSIGNED NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_student` (`exam_id`, `student_id`),
  KEY `idx_es_student` (`student_id`),
  CONSTRAINT `fk_exam_students_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `marks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` INT UNSIGNED NOT NULL,
  `module_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Module this mark row belongs to',
  `student_id` VARCHAR(50) NOT NULL,
  `marks` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Legacy: mirror of marks_final',
  `marks_second` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Legacy: mirror of marks_second_final',
  `marks_q1` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_q2` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_q3` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_q4` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_q5` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_q6` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_q7` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_final` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'First marking final total',
  `marks_second_q1` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_q2` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_q3` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_q4` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_q5` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_q6` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_q7` DECIMAL(7,2) NULL DEFAULT NULL,
  `marks_second_final` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Second marking final total',
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_marks_exam_module_student` (`exam_id`, `module_id`, `student_id`),
  KEY `idx_marks_exam` (`exam_id`),
  CONSTRAINT `fk_marks_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
