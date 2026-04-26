-- Run if you already have `exams` from an older install.
-- Adds semester column and allows richer JSON in `exam_modules` (per-module date/time/location).

ALTER TABLE `exams`
  ADD COLUMN IF NOT EXISTS `semester` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Semester (module filter)' AFTER `group_id`;

-- MySQL 8.0.12+ supports IF NOT EXISTS for ADD COLUMN; if your server errors, run:
-- SHOW COLUMNS FROM `exams` LIKE 'semester';
-- then add the column manually only when missing.
