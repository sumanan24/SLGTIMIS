<?php
/**
 * Course Model
 */

class CourseModel extends Model {
    protected $table = 'course';
    protected $courseVersionTable = 'course_version';
    
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
     * Create new course and ensure version 1 exists in course_version.
     */
    public function createCourse($data) {
        $ok = $this->create($data);
        if ($ok && !empty($data['course_id'])) {
            $this->ensureCourseVersionTable();
            $this->getLatestVersionForCourse($data['course_id']); // creates version 1 if missing
        }
        return $ok;
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

    /**
     * Ensure course_version table exists for tracking course versions.
     * Columns: id (PK), course_id, version_no, is_active, created_at.
     */
    public function ensureCourseVersionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->courseVersionTable}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `course_id` VARCHAR(11) NOT NULL,
                    `version_no` INT(11) NOT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_course` (`course_id`),
                    UNIQUE KEY `uniq_course_version` (`course_id`, `version_no`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->query($sql);
    }

    /**
     * Get latest version number for a course.
     * If no version exists, return 0 (default).
     */
    public function getLatestVersionForCourse($courseId) {
        $this->ensureCourseVersionTable();

        $sql = "SELECT MAX(version_no) AS max_version
                FROM `{$this->courseVersionTable}`
                WHERE course_id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $courseId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc()) && $row['max_version'] !== null) {
                return (int)$row['max_version'];
            }
        }

        return 0;
    }

    /**
     * Get all versions for a course (version_no, is_active, created_at).
     */
    public function getVersionsForCourse($courseId) {
        $this->ensureCourseVersionTable();
        $sql = "SELECT id, course_id, version_no, is_active, created_at
                FROM `{$this->courseVersionTable}`
                WHERE course_id = ?
                ORDER BY version_no DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("s", $courseId);
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

    /**
     * Create a new version for a course (increments version number).
     * Returns the new version number or 0 on failure.
     */
    public function createNewVersion($courseId) {
        $this->ensureCourseVersionTable();
        $next = $this->getLatestVersionForCourse($courseId) + 1;
        $sql = "INSERT INTO `{$this->courseVersionTable}` (course_id, version_no, is_active)
                VALUES (?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param("si", $courseId, $next);
        return $stmt->execute() ? $next : 0;
    }

    /**
     * Remove a course version by course_id and version_no.
     * Returns true if deleted.
     */
    public function deleteVersion($courseId, $versionNo) {
        $this->ensureCourseVersionTable();
        $sql = "DELETE FROM `{$this->courseVersionTable}` WHERE course_id = ? AND version_no = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $courseId, $versionNo);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}

