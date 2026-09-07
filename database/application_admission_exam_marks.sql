-- Exam / interview marks per schedule applicant (number or "ab" for absent)
ALTER TABLE `application_admission_schedule_entry`
    ADD COLUMN `exam_marks` VARCHAR(10) DEFAULT NULL
    COMMENT 'Entrance/interview marks; ab = absent'
    AFTER `whatsapp_sent`;
