-- Staff name with initials and a second appointment (Cover-up / Acting).
-- One person may hold only one permanent post. Cover-up / Acting is for Permanent staff only.

ALTER TABLE `staff`
    ADD COLUMN `staff_ininame` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Name with initials'
    AFTER `staff_name`;

ALTER TABLE `staff`
    ADD COLUMN `appoint_type` VARCHAR(20) NOT NULL DEFAULT 'none'
    COMMENT 'none, cover_up, acting'
    AFTER `staff_type`;

ALTER TABLE `staff`
    ADD COLUMN `appoint_position` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Cover-up or Acting position'
    AFTER `appoint_type`;

ALTER TABLE `staff`
    ADD COLUMN `appoint_department_id` VARCHAR(6) NULL DEFAULT NULL
    COMMENT 'Department for Cover-up or Acting'
    AFTER `appoint_position`;
