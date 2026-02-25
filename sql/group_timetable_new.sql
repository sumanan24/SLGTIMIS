-- Group Timetable - group_id from URL, time_slot (fixed), module_id, staff_id
-- Time slots: 08:30-10:00, 10:30-12:00, 13:00-14:30, 14:45-16:15

DROP TABLE IF EXISTS `group_timetable`;

CREATE TABLE `group_timetable` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT DEFAULT NULL,
    `group_name` VARCHAR(100) DEFAULT NULL,
    `day` VARCHAR(20) DEFAULT NULL,
    `time_slot` VARCHAR(30) DEFAULT NULL,
    `subject` VARCHAR(100) DEFAULT NULL,
    `session_type` ENUM('Theory','Practical') DEFAULT 'Theory',
    `module_id` VARCHAR(20) DEFAULT NULL,
    `staff_id` VARCHAR(64) DEFAULT NULL,
    `lecturer` VARCHAR(100) DEFAULT NULL,
    `room` VARCHAR(50) DEFAULT NULL,
    KEY `group_id` (`group_id`),
    KEY `time_slot` (`time_slot`),
    UNIQUE KEY `unique_group_day_slot` (`group_id`, `day`, `time_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example: list all timetable entries for a group (e.g. group_id = 8)
-- SELECT * FROM `group_timetable` WHERE group_id = 8;
