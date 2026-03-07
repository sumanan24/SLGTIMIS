<?php

/**
 * Staff Module Enrollment Model
 */
class StaffModuleEnrollmentModel extends Model
{
    protected $table = 'staff_module_enrollment';

    protected function getPrimaryKey()
    {
        return 'staff_module_enrollment_id';
    }
    
    /**
     * Ensure staff_module_enrollment table exists with correct structure.
     */
    public function ensureTableStructure()
    {
        // Check if table exists
        $check = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
        if ($check && $check->num_rows === 0) {
            // Create table with a clean schema
            $sql = "CREATE TABLE `{$this->table}` (
                        `staff_module_enrollment_id` INT(11) NOT NULL AUTO_INCREMENT,
                        `staff_id` VARCHAR(50) NOT NULL,
                        `course_id` VARCHAR(50) NOT NULL,
                        `module_id` VARCHAR(50) NOT NULL,
                        `academic_year` VARCHAR(20) NOT NULL,
                        `staff_module_enrollment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`staff_module_enrollment_id`),
                        KEY `idx_staff` (`staff_id`),
                        KEY `idx_course` (`course_id`),
                        KEY `idx_module` (`module_id`),
                        KEY `idx_academic_year` (`academic_year`),
                        UNIQUE KEY `uniq_staff_course_module_year` (`staff_id`, `course_id`, `module_id`, `academic_year`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$this->db->query($sql)) {
                error_log('StaffModuleEnrollmentModel::ensureTableStructure create failed: ' . $this->db->error);
            }
        }
        
        return true;
    }

    /**
     * Enroll staff to a module
     */
    public function enrollStaffToModule($data)
    {
        $this->ensureTableStructure();
        
        // Determine enrollment date (allow manual override)
        $enrollDate = !empty($data['staff_module_enrollment_date'])
            ? $data['staff_module_enrollment_date']
            : date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO `{$this->table}` 
                (`staff_id`, `course_id`, `module_id`, `academic_year`, `staff_module_enrollment_date`)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('StaffModuleEnrollmentModel::enrollStaffToModule prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "sssss",
            $data['staff_id'],
            $data['course_id'],
            $data['module_id'],
            $data['academic_year'],
            $enrollDate
        );

        if (!$stmt->execute()) {
            error_log('StaffModuleEnrollmentModel::enrollStaffToModule execute failed: ' . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Get enrollments for a department (optional filters)
     */
    public function getEnrollmentsByDepartment($departmentId, $filters = [])
    {
        $this->ensureTableStructure();
        
        if (empty($departmentId)) {
            return [];
        }

        $sql = "SELECT sme.*, s.staff_name, c.course_name, m.module_name
                FROM `{$this->table}` sme
                INNER JOIN `staff` s ON s.staff_id = sme.staff_id
                INNER JOIN `course` c ON c.course_id = sme.course_id
                INNER JOIN `module` m ON m.module_id = sme.module_id AND m.course_id = sme.course_id
                WHERE s.department_id = ?";

        $params = [$departmentId];
        $types = 's';

        if (!empty($filters['academic_year'])) {
            $sql .= " AND sme.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }

        $sql .= " ORDER BY sme.staff_module_enrollment_date DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('StaffModuleEnrollmentModel::getEnrollmentsByDepartment prepare failed: ' . $this->db->error);
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }
}

