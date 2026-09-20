-- Fingerprint machine employee number on staff

ALTER TABLE `staff`
    ADD COLUMN `finger_machine_no` VARCHAR(50) NULL DEFAULT NULL
    COMMENT 'Fingerprint machine employee number'
    AFTER `staff_id`;

ALTER TABLE `staff`
    ADD UNIQUE KEY `uq_staff_finger_machine_no` (`finger_machine_no`);
