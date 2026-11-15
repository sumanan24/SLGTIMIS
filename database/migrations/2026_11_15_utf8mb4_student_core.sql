-- Convert legacy latin1 student/course core tables to utf8mb4 so admission
-- registration can store Sinhala/Tamil text (MySQL error 3988 otherwise).
--
-- Run against the SIS database, e.g.:
--   mysql -u root -p sisslgti < database/migrations/2026_11_15_utf8mb4_student_core.sql
--
-- Related tables that still show latin1 may need FOREIGN KEY drop/recreate
-- before CONVERT (see 2026_11_15_utf8mb4_student_core_remaining.sql). Core
-- registration only requires student + student_enroll (+ course/academic FKs).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `academic` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `department` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `course` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `student` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `student_enroll` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `staff` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
