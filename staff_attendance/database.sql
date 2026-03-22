-- Staff Attendance (single table) — import into MySQL (phpMyAdmin or mysql CLI)
-- Timezone is handled in PHP (Asia/Colombo)

CREATE TABLE IF NOT EXISTS staff_attendance (
  attendance_id INT AUTO_INCREMENT PRIMARY KEY,
  employee_no VARCHAR(50) NOT NULL,
  staff_name VARCHAR(100) DEFAULT '',
  department VARCHAR(100) DEFAULT '',
  attendance_time DATETIME NOT NULL,
  device_ip VARCHAR(50) DEFAULT '',
  event_type VARCHAR(20) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_employee_attendance_time (employee_no, attendance_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
