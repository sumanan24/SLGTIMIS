-- Course enrollment visibility: only `active` courses are shown to students.
-- Existing rows default to active when this column is added.
ALTER TABLE `course`
    ADD COLUMN `course_status` ENUM('active','draft','deactivated') NOT NULL DEFAULT 'active'
    COMMENT 'Only active courses are visible for student enrollment'
    AFTER `course_institute_training`;
