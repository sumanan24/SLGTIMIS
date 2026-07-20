-- Link admission schedules to a specific course (course-wise entrance exams)
ALTER TABLE `application_admission_schedule`
    ADD COLUMN IF NOT EXISTS `course_id` VARCHAR(50) DEFAULT NULL
    COMMENT 'Entrance/interview scoped to one NVQ course; NULL = all courses at level'
    AFTER `application_level`;
