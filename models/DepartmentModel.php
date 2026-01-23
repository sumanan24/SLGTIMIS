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
     * Get department by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new department
     */
    public function createDepartment($data) {
        return $this->create($data);
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

