<?php
/**
 * Department Model
 */

class DepartmentModel extends Model {
    protected $table = 'department';
    
    protected function getPrimaryKey() {
        return 'department_id';
    }
    
    /**
     * Get all departments
     */
    public function getAll() {
        return $this->all('department_name ASC');
    }

    /**
     * Get departments with pagination
     */
    public function getDepartmentsPage($page = 1, $perPage = 20) {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM `{$this->table}` ORDER BY `department_name` ASC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $stmt->close();

        return $data;
    }

    /**
     * Get total department count
     */
    public function getTotalDepartments() {
        return (int) $this->count();
    }

    /**
     * Departments that have at least one course at the given NVQ level (e.g. '4' or '5').
     *
     * @return list<array<string, mixed>>
     */
    public function getDepartmentsWithNvqCourses(string $nvqLevel): array {
        if (!in_array($nvqLevel, ['4', '5'], true)) {
            return [];
        }

        require_once BASE_PATH . '/models/CourseModel.php';
        (new CourseModel())->ensureCourseStatusColumn();

        $sql = "SELECT DISTINCT d.* FROM `{$this->table}` d
                INNER JOIN `course` c ON c.`department_id` = d.`department_id`
                    AND c.`course_nvq_level` = ?
                    AND c.`course_status` = 'active'
                ORDER BY d.`department_name` ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('DepartmentModel::getDepartmentsWithNvqCourses prepare failed: ' . $this->db->getConnection()->error);
            return [];
        }
        $stmt->bind_param('s', $nvqLevel);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
        return $rows;
    }
    
    /**
     * Get department by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new department
     */
    public function createDepartment($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }
    
    /**
     * Update department
     */
    public function updateDepartment($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete department
     */
    public function deleteDepartment($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if department exists
     */
    public function exists($id) {
        $dept = $this->find($id);
        return $dept !== null;
    }
}

