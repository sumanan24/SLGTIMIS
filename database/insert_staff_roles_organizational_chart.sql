-- Staff Roles based on Organizational Chart
-- Position numbers: Lower = Higher position in hierarchy

-- Top Level (Position 1)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('BOG', 'Board of Governors', 1)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 2 - Executive (Position 2)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('DPR', 'Director Principal', 2),
('DPE', 'Deputy Principal Education', 2),
('MRST', 'Manager Research, Services and Technology Transfer', 2),
('RSA', 'Registrar (Student Affairs)', 2),
('MHF', 'Manager HR and Finance', 2),
('IAU', 'Internal Auditor', 2)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 3 - Department Heads/Coordinators (Position 3)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('HOD', 'Head of Department', 3),
('COE', 'Coordinator CT, OJT, Exam', 3),
('CFE', 'Coordinator Facility & Environment', 3),
('CTS', 'Coordinator (Technical Services)', 3),
('CGC', 'Carrier Guidance, Counseling', 3)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 4 - Senior Academic Staff (Position 4)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('SLE', 'Senior Lecturer', 4),
('SIN', 'Senior Instructor', 4),
('STE', 'Senior Technician', 4)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 5 - Academic Staff (Position 5)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('LE1', 'Lecturer', 5),
('IN1', 'Instructor', 5),
('LBN', 'Librarian', 5)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 6 - Officers (Position 6)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('POE', 'Program Officer, Entrepreneurship and Tracer Study', 6),
('PLO', 'Planning Officer', 6),
('HEO', 'Health Officer', 6),
('NAD', 'Network Administrator', 6),
('HRO', 'HR Officer', 6),
('ACC', 'Accountant', 6),
('ACO', 'Accountant Officer', 6),
('PRO', 'Procurement Officer', 6)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 7 - Quality and Support Staff (Position 7)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('QMA', 'Quality Manager', 7),
('MAR', 'Marketing Office', 7),
('WAR', 'Warden', 7),
('SEC', 'Secretary', 7),
('STK', 'Store Keeper, Purchaser', 7)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 8 - Support Staff (Position 8)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('MAN', 'Management Assistant Non Technical', 8),
('SOR', 'SOR', 8),
('HM2', 'HM 2-1', 8),
('HM1', 'HM 1-1', 8),
('MM2', 'MM 2 AR2', 8),
('AR1', 'AR1', 8),
('MA4', 'MA 4', 8),
('JM1', 'JM1', 8),
('MA2', 'MA 2-2', 8),
('MA1', 'MA 1-2', 8),
('PL3', 'PL-3', 8),
('PL2', 'PL-2 (4)', 8),
('PL1', 'PL-1', 8)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 9 - Technical Support (Position 9)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('SC2', '2 Skilled Craftsmen', 9),
('DRV', 'Driver', 9),
('TRA', 'Trainee Instructor Lab Assistant (contract base)', 9)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

-- Level 10 - Office Support (Position 10)
INSERT INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('OAR', 'Office Aid Receptionist', 10),
('OAA', 'Office Aid', 10)
ON DUPLICATE KEY UPDATE `staff_position_type_name` = VALUES(`staff_position_type_name`), `staff_position` = VALUES(`staff_position`);

