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
     * Base select with course and staff counts.
     */
    private function selectWithCountsSql() {
        return "SELECT d.*,
                    COALESCE(c.cnt, 0) AS course_count,
                    COALESCE(s.cnt, 0) AS staff_count
                FROM `{$this->table}` d
                LEFT JOIN (
                    SELECT `department_id`, COUNT(*) AS cnt
                    FROM `course`
                    GROUP BY `department_id`
                ) c ON c.`department_id` = d.`department_id`
                LEFT JOIN (
                    SELECT `department_id`, COUNT(*) AS cnt
                    FROM `staff`
                    GROUP BY `department_id`
                ) s ON s.`department_id` = d.`department_id`";
    }

    /**
     * Get departments with pagination and optional search.
     */
    public function getDepartmentsPage($page = 1, $perPage = 20, $search = '') {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) $search);

        $sql = $this->selectWithCountsSql();
        if ($search !== '') {
            $sql .= " WHERE d.`department_id` LIKE ? OR d.`department_name` LIKE ?";
        }
        $sql .= " ORDER BY d.`department_name` ASC LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt->bind_param('ssii', $like, $like, $perPage, $offset);
        } else {
            $stmt->bind_param('ii', $perPage, $offset);
        }

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
     * Get total department count, optionally filtered by search.
     */
    public function getTotalDepartments($search = '') {
        $search = trim((string) $search);
        if ($search === '') {
            return (int) $this->count();
        }

        $sql = "SELECT COUNT(*) AS total FROM `{$this->table}`
                WHERE `department_id` LIKE ? OR `department_name` LIKE ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $like = '%' . $search . '%';
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = 0;
        if ($result && ($row = $result->fetch_assoc())) {
            $total = (int) ($row['total'] ?? 0);
        }
        $stmt->close();
        return $total;
    }

    /**
     * Get one department with course and staff counts.
     */
    public function getByIdWithCounts($id) {
        $sql = $this->selectWithCountsSql() . " WHERE d.`department_id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return $this->getById($id);
        }
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
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

