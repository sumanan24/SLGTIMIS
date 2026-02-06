<?php
/**
 * Course Model
 */

class CourseModel extends Model {
    protected $table = 'course';
    
    protected function getPrimaryKey() {
        return 'course_id';
    }
    
    /**
     * Get courses with department info
     */
    public function getCoursesWithDepartment($filters = []) {
        $sql = "SELECT c.*, d.`department_name` 
                FROM `{$this->table}` c 
                LEFT JOIN `department` d ON c.`department_id` = d.`department_id`
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Filter by department
        if (!empty($filters['department_id'])) {
            $sql .= " AND c.`department_id` = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        // Filter by NVQ level
        if (!empty($filters['nvq_level'])) {
            $sql .= " AND c.`course_nvq_level` = ?";
            $params[] = $filters['nvq_level'];
            $types .= 's';
        }
        
        // Search by course name or ID
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (c.`course_name` LIKE ? OR c.`course_id` LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY c.`course_name`";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("SQL Error in getCoursesWithDepartment: " . $this->db->error);
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get all courses
     */
    public function getAll() {
        return $this->getCoursesWithDepartment();
    }
    
    /**
     * Get course by ID
     */
    public function getById($id) {
        $course = $this->find($id);
        if ($course) {
            // Get department name
            $sql = "SELECT d.`department_name` 
                    FROM `department` d 
                    WHERE d.`department_id` = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $course['department_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $dept = $result->fetch_assoc();
                    $course['department_name'] = $dept['department_name'];
                }
            }
        }
        return $course;
    }
    
    /**
     * Create new course
     */
    public function createCourse($data) {
        return $this->create($data);
    }
    
    /**
     * Update course
     */
    public function updateCourse($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete course
     */
    public function deleteCourse($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if course exists
     */
    public function exists($id) {
        $course = $this->find($id);
        return $course !== null;
    }
}

