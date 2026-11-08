-- Backup of the `module` table structure (schema only, no data).
-- Uses IF NOT EXISTS, so running this on a database that already has `module` changes nothing.
-- Requires the `course` table to exist first (foreign key `module_ibfk_1`).

CREATE TABLE IF NOT EXISTS `module` (
  `module_id` varchar(11) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `credit` decimal(4,2) DEFAULT NULL,
  `semester` tinyint unsigned DEFAULT NULL COMMENT 'Academic semester (e.g. 1, 2)',
  `module_aim` varchar(255) DEFAULT NULL,
  `module_learning_hours` int DEFAULT NULL,
  `module_resources` varchar(255) DEFAULT NULL,
  `module_learning_outcomes` varchar(255) DEFAULT NULL,
  `semester_id` enum('1','2') DEFAULT NULL,
  `module_reference` varchar(255) DEFAULT NULL,
  `module_relative_unit` varchar(255) DEFAULT NULL,
  `module_lecture_hours` int DEFAULT NULL,
  `module_practical_hours` int DEFAULT NULL,
  `module_self_study_hours` int DEFAULT NULL,
  `course_id` varchar(11) NOT NULL,
  `course_version` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`module_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `module_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
