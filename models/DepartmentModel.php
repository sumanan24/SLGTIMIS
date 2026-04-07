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
     * Departments that have at least one course at the given NVQ level (e.g. '4' or '5').
     *
     * @return list<array<string, mixed>>
     */
    public function getDepartmentsWithNvqCourses(string $nvqLevel): array {
        if (!in_array($nvqLevel, ['4', '5'], true)) {
            return [];
        }
        $sql = "SELECT DISTINCT d.* FROM `{$this->table}` d
                INNER JOIN `course` c ON c.`department_id` = d.`department_id`
                WHERE c.`course_nvq_level` = ?
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

