-- Selection-result WhatsApp sent log on interview selection entries.
-- The application also adds these columns automatically on first use.

ALTER TABLE `application_admission_schedule_entry`
    ADD COLUMN `selection_result_wa_sent` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Staff marked selection-result WhatsApp as sent' AFTER `exam_marks`,
    ADD COLUMN `selection_result_wa_sent_at` DATETIME DEFAULT NULL AFTER `selection_result_wa_sent`,
    ADD COLUMN `selection_result_wa_sent_by` INT DEFAULT NULL AFTER `selection_result_wa_sent_at`;
