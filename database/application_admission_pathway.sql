-- Per-schedule admission pathway (set when creating exam/interview schedule).
ALTER TABLE `application_admission_schedule`
    ADD COLUMN `admission_pathway` ENUM('exam_and_interview','interview_only') NOT NULL DEFAULT 'exam_and_interview'
    COMMENT 'exam_and_interview = entrance then interview; interview_only = direct interview'
    AFTER `course_id`;
