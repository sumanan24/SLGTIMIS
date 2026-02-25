<?php
/**
 * Base Model Class
 */

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all records
     */
    public function all($orderBy = null, $limit = null) {
        $sql = "SELECT * FROM `{$this->table}`";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $result = $this->db->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->getPrimaryKey()}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Find records by condition
     */
    public function where($column, $value, $operator = '=') {
        $sql = "SELECT * FROM `{$this->table}` WHERE `$column` $operator ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Insert new record
     */
    public function create($data) {
        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `{$this->table}` (`$columns`) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        
        $types = str_repeat('s', count($data));
        $values = array_values($data);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update record
     * @param mixed $id Primary key value
     * @param array $data Column => value to set
     * @param string|null $sqlError Set to MySQL error message on failure (prepare or execute)
     * @return bool
     */
    public function update($id, $data, &$sqlError = null) {
        $set = [];
        $types = '';
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "`$column` = ?";
            
            if ($value === null) {
                $types .= 's'; // NULL as string type
                $values[] = null;
            } elseif (is_int($value) || is_bool($value)) {
                $types .= 'i';
                $values[] = (int)$value;
            } else {
                $types .= 's';
                $values[] = $value;
            }
        }
        
        $set = implode(', ', $set);
        $sql = "UPDATE `{$this->table}` SET $set WHERE `{$this->getPrimaryKey()}` = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            $sqlError = $this->db->getConnection()->error ?? 'Prepare failed';
            return false;
        }
        
        // Add ID parameter
        $types .= 's';
        $values[] = $id;
        
        // Bind parameters with proper reference handling
        $refs = [];
        foreach ($values as $key => $value) {
            $refs[$key] = &$values[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
        
        if (!$stmt->execute()) {
            $sqlError = $stmt->error ?? 'Execute failed';
            return false;
        }
        return true;
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->getPrimaryKey()}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get primary key column name
     */
    protected function getPrimaryKey() {
        // Default to 'id', override in child classes if needed
        return 'id';
    }
    
    /**
     * Count records
     */
    public function count($condition = null) {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        
        if ($condition) {
            $sql .= " WHERE $condition";
        }
        
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
}

