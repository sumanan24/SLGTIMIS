-- Align groups tables with utf8mb4_unicode_ci (same as course/student after
-- 2026_11_15_utf8mb4_student_core.sql) to avoid illegal mix on JOIN/=.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `group_students` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
