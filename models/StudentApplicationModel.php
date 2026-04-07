<?php
/**
 * Online student applications (Level 04 / 05)
 */

class StudentApplicationModel extends Model {
    protected $table = 'student_applications';
    private static $tableEnsured = false;

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
    }

    /**
     * Create table from database/student_applications.sql if missing.
     */
    public function ensureTable(): void {
        if (self::$tableEnsured) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/student_applications.sql';
        if (!is_readable($sqlFile)) {
            self::$tableEnsured = true;
            return;
        }
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            self::$tableEnsured = true;
            return;
        }
        try {
            $conn = $this->db->getConnection();
            $conn->multi_query($sql);
            while ($conn->more_results() && $conn->next_result()) {
                /* flush */
            }
        } catch (Throwable $e) {
            error_log('StudentApplicationModel::ensureTable: ' . $e->getMessage());
        }
        self::$tableEnsured = true;
    }

    /**
     * @param array<string, mixed> $data Column => value (strings/null; ints as numeric strings ok)
     * @return int|false New application_id
     */
    public function insertApplication(array $data, &$sqlError = null) {
        $this->ensureTable();
        return $this->create($data, $sqlError);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAllForAdmin(): array {
        $this->ensureTable();
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `created_at` DESC";
        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array {
        $this->ensureTable();
        $sql = "SELECT * FROM `{$this->table}` WHERE `application_id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    public function updateDocumentPaths(int $applicationId, array $paths): bool {
        if (empty($paths)) {
            return true;
        }
        $sets = [];
        $params = [];
        $types = '';
        foreach ($paths as $col => $relPath) {
            if (!preg_match('/^[a-z_]+$/', $col)) {
                continue;
            }
            $sets[] = "`{$col}` = ?";
            $params[] = $relPath;
            $types .= 's';
        }
        if (empty($sets)) {
            return true;
        }
        $params[] = $applicationId;
        $types .= 'i';
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . " WHERE `application_id` = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
