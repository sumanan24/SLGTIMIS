<?php
/**
 * Academic year master (table `academic`).
 */

class AcademicYearModel extends Model {
    protected $table = 'academic';

    protected function getPrimaryKey() {
        return 'academic_year';
    }

    public static function formatDate($value) {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d M Y', $ts) : $value;
    }

    public function getAll() {
        return $this->all('academic_year DESC');
    }

    public function getById($id) {
        return $this->find($id);
    }

    public function exists($id) {
        return $this->find($id) !== null;
    }

    public function createYear(array $data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }

    public function updateYear($id, array $data, &$sqlError = null) {
        return $this->update($id, $data, $sqlError);
    }

    public function deleteYear($id) {
        return $this->delete($id);
    }

    /**
     * How many related records use this academic year.
     *
     * @return array{total:int, details:list<string>}
     */
    public function usageSummary($year) {
        $checks = [
            ['student_enroll', 'academic_year', 'student enrollment(s)'],
            ['groups', 'academic_year', 'group(s)'],
            ['staff_module_enrollment', 'academic_year', 'staff module enrollment(s)'],
        ];
        $total = 0;
        $details = [];
        $conn = $this->db->getConnection();

        foreach ($checks as [$table, $column, $label]) {
            $tbl = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
            if (!$tbl || $tbl->num_rows === 0) {
                continue;
            }
            $col = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $conn->real_escape_string($column) . "'");
            if (!$col || $col->num_rows === 0) {
                continue;
            }
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ?");
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $year);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $count = (int) ($row['c'] ?? 0);
            if ($count > 0) {
                $total += $count;
                $details[] = $count . ' ' . $label;
            }
        }

        return ['total' => $total, 'details' => $details];
    }
}
