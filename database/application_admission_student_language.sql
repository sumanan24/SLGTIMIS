-- Language filter on admission schedules (matches student_applications.student_language).
ALTER TABLE `application_admission_schedule`
    ADD COLUMN `student_language` VARCHAR(50) DEFAULT NULL
    COMMENT 'Tamil/Sinhala/English — only matching approved applicants on this schedule'
    AFTER `admission_pathway`;
