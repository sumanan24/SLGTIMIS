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
     * Enroll staff to a module
     */
    public function enrollStaffToModule($data)
    {
        $sql = "INSERT INTO `{$this->table}` 
                (`staff_id`, `course_id`, `module_id`, `academic_year`, `staff_module_enrollment_date`)
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('StaffModuleEnrollmentModel::enrollStaffToModule prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "ssss",
            $data['staff_id'],
            $data['course_id'],
            $data['module_id'],
            $data['academic_year']
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

