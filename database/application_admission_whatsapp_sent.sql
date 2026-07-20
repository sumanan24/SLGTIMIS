-- WhatsApp "link sent" flag per schedule applicant row
ALTER TABLE `application_admission_schedule_entry`
    ADD COLUMN `whatsapp_sent` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Staff marked schedule link sent via WhatsApp'
    AFTER `notes`;
