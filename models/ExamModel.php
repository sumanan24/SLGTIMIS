<?php
/**
 * Exams, exam_students, marks — mysqli prepared statements (same stack as the rest of the app).
 */

class ExamModel extends Model {
    protected $table = 'exams';

    protected function getPrimaryKey(): string {
        return 'id';
    }

    /**
     * Create exams / exam_students / marks if missing (see database/exam_module.sql).
     */
    public function ensureExamTablesStructure(): void {
        $conn = $this->db->getConnection();
        $check = $this->db->query("SHOW TABLES LIKE 'exams'");
        if ($check && $check->num_rows > 0) {
            return;
        }
        $conn->query("CREATE TABLE IF NOT EXISTS `exams` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `course_id` VARCHAR(50) NOT NULL,
            `group_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Batch (groups.id)',
            `semester` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Semester used when creating exam (module filter)',
            `exam_date` DATE NOT NULL,
            `exam_modules` TEXT NOT NULL COMMENT 'JSON: [{module_id, exam_date, exam_time, location}, ...]',
            `exam_time` VARCHAR(80) NOT NULL COMMENT 'Summary: single time or Various',
            `location` VARCHAR(255) NOT NULL COMMENT 'Summary: single venue or Various',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_exams_course` (`course_id`),
            KEY `idx_exams_group` (`group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $conn->query("CREATE TABLE IF NOT EXISTS `exam_students` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `exam_id` INT UNSIGNED NOT NULL,
            `student_id` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_exam_student` (`exam_id`, `student_id`),
            KEY `idx_es_student` (`student_id`),
            CONSTRAINT `fk_exam_students_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $conn->query("CREATE TABLE IF NOT EXISTS `marks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `exam_id` INT UNSIGNED NOT NULL,
            `module_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Module this mark row belongs to',
            `student_id` VARCHAR(50) NOT NULL,
            `marks` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Legacy: mirror of marks_final',
            `marks_second` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Legacy: mirror of marks_second_final',
            `marks_q1` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_q2` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_q3` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_q4` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_q5` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_q6` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_q7` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_final` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'First marking final total',
            `marks_second_q1` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_q2` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_q3` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_q4` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_q5` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_q6` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_q7` DECIMAL(7,2) NULL DEFAULT NULL,
            `marks_second_final` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Second marking final total',
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_marks_exam_module_student` (`exam_id`, `module_id`, `student_id`),
            KEY `idx_marks_exam` (`exam_id`),
            CONSTRAINT `fk_marks_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Optional semester column on exams (for filtering / reporting).
     */
    public function ensureExamsSemesterColumn(): void {
        $this->ensureExamTablesStructure();
        $conn = $this->db->getConnection();
        $r = $conn->query("SHOW COLUMNS FROM `exams` LIKE 'semester'");
        if ($r && $r->num_rows > 0) {
            return;
        }
        $conn->query("ALTER TABLE `exams` ADD COLUMN `semester` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Semester used when creating exam (module filter)' AFTER `group_id`");
    }

    /**
     * Per-module marks: module_id + marks_second on `marks`, unique (exam_id, module_id, student_id).
     */
    public function ensureMarksModuleColumns(): void {
        $conn = $this->db->getConnection();
        $r = $conn->query("SHOW COLUMNS FROM `marks` LIKE 'module_id'");
        if (!$r || $r->num_rows === 0) {
            $conn->query("ALTER TABLE `marks` ADD COLUMN `module_id` VARCHAR(50) NOT NULL DEFAULT '' AFTER `exam_id`");
        }
        $r2 = $conn->query("SHOW COLUMNS FROM `marks` LIKE 'marks_second'");
        if (!$r2 || $r2->num_rows === 0) {
            $conn->query("ALTER TABLE `marks` ADD COLUMN `marks_second` DECIMAL(7,2) NULL DEFAULT NULL AFTER `marks`");
        }
        $idx = $conn->query("SHOW INDEX FROM `marks` WHERE Key_name = 'uq_marks_exam_student'");
        if ($idx && $idx->num_rows > 0) {
            $conn->query("ALTER TABLE `marks` DROP INDEX `uq_marks_exam_student`");
        }
        $idx2 = $conn->query("SHOW INDEX FROM `marks` WHERE Key_name = 'uq_marks_exam_module_student'");
        if (!$idx2 || $idx2->num_rows === 0) {
            $conn->query("ALTER TABLE `marks` ADD UNIQUE KEY `uq_marks_exam_module_student` (`exam_id`, `module_id`, `student_id`)");
        }
        $this->migrateLegacyMarksModuleIds();
    }

    /**
     * Seven question scores + final for 1st and 2nd marking (columns on `marks`).
     */
    public function ensureMarksQuestionColumns(): void {
        $this->ensureMarksModuleColumns();
        $conn = $this->db->getConnection();
        $after = 'marks_second';
        $first = ['marks_q1', 'marks_q2', 'marks_q3', 'marks_q4', 'marks_q5', 'marks_q6', 'marks_q7', 'marks_final'];
        foreach ($first as $col) {
            $this->addMarksTableColumnIfMissing($conn, $col, 'DECIMAL(7,2) NULL DEFAULT NULL', $after);
            $after = $col;
        }
        $second = ['marks_second_q1', 'marks_second_q2', 'marks_second_q3', 'marks_second_q4', 'marks_second_q5', 'marks_second_q6', 'marks_second_q7', 'marks_second_final'];
        foreach ($second as $col) {
            $this->addMarksTableColumnIfMissing($conn, $col, 'DECIMAL(7,2) NULL DEFAULT NULL', $after);
            $after = $col;
        }
        $conn->query('UPDATE `marks` SET `marks_final` = `marks` WHERE `marks_final` IS NULL AND `marks` IS NOT NULL');
        $conn->query('UPDATE `marks` SET `marks_second_final` = `marks_second` WHERE `marks_second_final` IS NULL AND `marks_second` IS NOT NULL');
    }

    /**
     * @param mysqli $conn
     */
    private function addMarksTableColumnIfMissing($conn, string $col, string $definition, string $after): void {
        $allowed = [
            'marks_q1', 'marks_q2', 'marks_q3', 'marks_q4', 'marks_q5', 'marks_q6', 'marks_q7', 'marks_final',
            'marks_second_q1', 'marks_second_q2', 'marks_second_q3', 'marks_second_q4', 'marks_second_q5', 'marks_second_q6', 'marks_second_q7', 'marks_second_final',
        ];
        $allowedAfter = [
            'marks_second',
            'marks_q1', 'marks_q2', 'marks_q3', 'marks_q4', 'marks_q5', 'marks_q6', 'marks_q7', 'marks_final',
            'marks_second_q1', 'marks_second_q2', 'marks_second_q3', 'marks_second_q4', 'marks_second_q5', 'marks_second_q6', 'marks_second_q7',
        ];
        if (!in_array($col, $allowed, true) || !in_array($after, $allowedAfter, true)) {
            return;
        }
        $r = $conn->query("SHOW COLUMNS FROM `marks` LIKE '" . $conn->real_escape_string($col) . "'");
        if ($r && $r->num_rows > 0) {
            return;
        }
        $conn->query('ALTER TABLE `marks` ADD COLUMN `' . $col . '` ' . $definition . ' AFTER `' . $after . '`');
    }

    /**
     * Assign legacy exam-wide marks (module_id '') to the first module in the exam JSON.
     */
    private function migrateLegacyMarksModuleIds(): void {
        $conn = $this->db->getConnection();
        $res = $conn->query(
            "SELECT m.`id`, m.`exam_id`, e.`exam_modules` FROM `marks` m
             INNER JOIN `exams` e ON e.`id` = m.`exam_id`
             WHERE m.`module_id` = ''"
        );
        if (!$res) {
            return;
        }
        while ($row = $res->fetch_assoc()) {
            $first = $this->firstModuleIdFromJson((string) ($row['exam_modules'] ?? '[]'));
            if ($first === '') {
                continue;
            }
            $stmt = $conn->prepare('UPDATE `marks` SET `module_id` = ? WHERE `id` = ?');
            if (!$stmt) {
                continue;
            }
            $mid = $first;
            $id = (int) $row['id'];
            $stmt->bind_param('si', $mid, $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function firstModuleIdFromJson(string $json): string {
        $a = json_decode($json, true);
        if (!is_array($a)) {
            return '';
        }
        foreach ($a as $item) {
            if (is_string($item) && $item !== '') {
                return trim($item);
            }
            if (is_array($item) && !empty($item['module_id'])) {
                return trim((string) $item['module_id']);
            }
        }
        return '';
    }

    /**
     * @return list<array{module_id: string, exam_date: string, exam_time: string, location: string}>
     */
    public function decodeExamModulesList(array $exam): array {
        $raw = $exam['exam_modules'] ?? '[]';
        $a = json_decode((string) $raw, true);
        if (!is_array($a)) {
            return [];
        }
        $out = [];
        foreach ($a as $item) {
            if (is_string($item)) {
                $out[] = [
                    'module_id' => trim($item),
                    'exam_date' => '',
                    'exam_time' => '',
                    'location' => '',
                ];
                continue;
            }
            if (is_array($item) && isset($item['module_id'])) {
                $out[] = [
                    'module_id' => trim((string) $item['module_id']),
                    'exam_date' => (string) ($item['exam_date'] ?? ''),
                    'exam_time' => (string) ($item['exam_time'] ?? ''),
                    'location' => (string) ($item['location'] ?? ''),
                ];
            }
        }
        return $out;
    }

    /**
     * True if module_id appears in this exam's schedule JSON.
     */
    public function examHasModule(array $exam, string $moduleId): bool {
        $moduleId = trim($moduleId);
        if ($moduleId === '') {
            return false;
        }
        foreach ($this->decodeExamModulesList($exam) as $row) {
            if ($row['module_id'] === $moduleId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getModuleScheduleEntry(array $exam, string $moduleId): ?array {
        $moduleId = trim($moduleId);
        foreach ($this->decodeExamModulesList($exam) as $row) {
            if ($row['module_id'] === $moduleId) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @return list<string>
     */
    public function getStudentIdsForExam(int $examId): array {
        $sql = 'SELECT `student_id` FROM `exam_students` WHERE `exam_id` = ? ORDER BY `student_id` ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $examId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (string) $row['student_id'];
        }
        return $ids;
    }

    /**
     * Registered students for an exam with display names (for admission selection UI).
     *
     * @return list<array{student_id: string, student_fullname: string}>
     */
    public function getRegisteredStudentsBasicForExam(int $examId): array {
        $sql = 'SELECT s.`student_id`, s.`student_fullname`
                FROM `exam_students` es
                INNER JOIN `student` s ON s.`student_id` = es.`student_id`
                WHERE es.`exam_id` = ?
                ORDER BY s.`student_id` ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $examId);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'student_id' => (string) ($row['student_id'] ?? ''),
                'student_fullname' => (string) ($row['student_fullname'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listExamsWithCourse(): array {
        $this->ensureExamTablesStructure();
        $sql = "SELECT e.*, c.course_name,
                (SELECT COUNT(*) FROM `exam_students` es WHERE es.exam_id = e.id) AS student_count
                FROM `exams` e
                LEFT JOIN `course` c ON c.course_id = e.course_id
                ORDER BY e.exam_date DESC, e.id DESC";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findWithCourse(int $examId): ?array {
        $this->ensureExamTablesStructure();
        $sql = "SELECT e.*, c.course_name, c.department_id
                FROM `exams` e
                LEFT JOIN `course` c ON c.course_id = e.course_id
                WHERE e.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $examId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        return $row ?: null;
    }

    /**
     * @param list<array{module_id: string, exam_date: string, exam_time: string, location: string}> $modulesSchedule
     * @param list<string> $studentIds
     */
    public function createExamAndAssignStudents(
        string $courseId,
        ?int $groupId,
        ?int $semester,
        array $modulesSchedule,
        array $studentIds
    ): int {
        $this->ensureExamsSemesterColumn();
        $modulesSchedule = array_values($modulesSchedule);
        $modulesJson = json_encode($modulesSchedule, JSON_UNESCAPED_UNICODE);

        $dates = array_column($modulesSchedule, 'exam_date');
        $dates = array_filter($dates, static function ($d) {
            return is_string($d) && $d !== '';
        });
        $examDate = !empty($dates) ? min($dates) : date('Y-m-d');

        $times = array_unique(array_map('strval', array_column($modulesSchedule, 'exam_time')));
        $locs = array_unique(array_map('strval', array_column($modulesSchedule, 'location')));
        $examTime = count($times) === 1 ? reset($times) : 'Various';
        $location = count($locs) === 1 ? reset($locs) : 'Various';

        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO `exams` (`course_id`, `group_id`, `semester`, `exam_date`, `exam_modules`, `exam_time`, `location`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException($conn->error ?: 'Prepare failed');
            }
            $gid = $groupId;
            $sem = $semester;
            $stmt->bind_param('siissss', $courseId, $gid, $sem, $examDate, $modulesJson, $examTime, $location);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error ?: 'Failed to insert exam');
            }
            $examId = (int) $conn->insert_id;

            $insEs = $conn->prepare("INSERT INTO `exam_students` (`exam_id`, `student_id`) VALUES (?, ?)");
            foreach ($studentIds as $sid) {
                $sid = trim((string) $sid);
                if ($sid === '') {
                    continue;
                }
                $insEs->bind_param('is', $examId, $sid);
                if (!$insEs->execute()) {
                    throw new RuntimeException($insEs->error ?: 'Failed to assign student');
                }
            }
            $insEs->close();

            $conn->commit();
            return $examId;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * @param list<array{module_id: string, exam_date: string, exam_time: string, location: string}> $modulesSchedule
     * @param list<string> $studentIds
     */
    public function updateExamAndAssignStudents(
        int $examId,
        string $courseId,
        ?int $groupId,
        ?int $semester,
        array $modulesSchedule,
        array $studentIds
    ): void {
        $this->ensureExamsSemesterColumn();
        $this->ensureMarksModuleColumns();
        $modulesSchedule = array_values($modulesSchedule);
        $modulesJson = json_encode($modulesSchedule, JSON_UNESCAPED_UNICODE);

        $dates = array_column($modulesSchedule, 'exam_date');
        $dates = array_filter($dates, static function ($d) {
            return is_string($d) && $d !== '';
        });
        $examDate = !empty($dates) ? min($dates) : date('Y-m-d');

        $times = array_unique(array_map('strval', array_column($modulesSchedule, 'exam_time')));
        $locs = array_unique(array_map('strval', array_column($modulesSchedule, 'location')));
        $examTime = count($times) === 1 ? reset($times) : 'Various';
        $location = count($locs) === 1 ? reset($locs) : 'Various';

        $newModuleIds = [];
        foreach ($modulesSchedule as $row) {
            $mid = trim((string) ($row['module_id'] ?? ''));
            if ($mid !== '') {
                $newModuleIds[] = $mid;
            }
        }

        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            if (!empty($newModuleIds)) {
                $ph = implode(',', array_fill(0, count($newModuleIds), '?'));
                $sqlDel = "DELETE FROM `marks` WHERE `exam_id` = ? AND `module_id` NOT IN ($ph)";
                $stmt = $conn->prepare($sqlDel);
                if (!$stmt) {
                    throw new RuntimeException($conn->error ?: 'Prepare failed');
                }
                $types = 'i' . str_repeat('s', count($newModuleIds));
                $params = array_merge([$examId], $newModuleIds);
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error ?: 'Failed to prune marks');
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare('DELETE FROM `marks` WHERE `exam_id` = ?');
                $stmt->bind_param('i', $examId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare('DELETE FROM `exam_students` WHERE `exam_id` = ?');
            $stmt->bind_param('i', $examId);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error ?: 'Failed to clear students');
            }
            $stmt->close();

            $insEs = $conn->prepare("INSERT INTO `exam_students` (`exam_id`, `student_id`) VALUES (?, ?)");
            foreach ($studentIds as $sid) {
                $sid = trim((string) $sid);
                if ($sid === '') {
                    continue;
                }
                $insEs->bind_param('is', $examId, $sid);
                if (!$insEs->execute()) {
                    throw new RuntimeException($insEs->error ?: 'Failed to assign student');
                }
            }
            $insEs->close();

            $sqlUp = "UPDATE `exams` SET `course_id` = ?, `group_id` = ?, `semester` = ?, `exam_date` = ?, `exam_modules` = ?, `exam_time` = ?, `location` = ? WHERE `id` = ?";
            $stmt = $conn->prepare($sqlUp);
            if (!$stmt) {
                throw new RuntimeException($conn->error ?: 'Prepare failed');
            }
            $gid = $groupId;
            $sem = $semester;
            $stmt->bind_param('siissssi', $courseId, $gid, $sem, $examDate, $modulesJson, $examTime, $location, $examId);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error ?: 'Failed to update exam');
            }
            $stmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function deleteExam(int $examId): bool {
        $stmt = $this->db->prepare('DELETE FROM `exams` WHERE `id` = ?');
        $stmt->bind_param('i', $examId);
        return $stmt->execute();
    }

    /**
     * Students on this exam with marks for one module (1st and 2nd, incl. Q1–Q7 + final).
     *
     * @return list<array<string, mixed>>
     */
    public function getStudentsWithMarksForModule(int $examId, string $moduleId): array {
        $this->ensureMarksQuestionColumns();
        $moduleId = trim($moduleId);
        $sql = "SELECT s.`student_id`, s.`student_fullname`, m.*
                FROM `exam_students` es
                INNER JOIN `student` s ON s.`student_id` = es.`student_id`
                LEFT JOIN `marks` m ON m.`exam_id` = es.`exam_id` AND m.`student_id` = es.`student_id` AND m.`module_id` = ?
                WHERE es.`exam_id` = ?
                ORDER BY s.`student_fullname` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $moduleId, $examId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Upsert first and second marking per student (Q1–Q7 + final each).
     *
     * @param array<string, array<string, mixed>> $marksFirstByStudent keys q1..q7, final
     * @param array<string, array<string, mixed>> $marksSecondByStudent keys q1..q7, final
     */
    public function saveModuleMarks(int $examId, string $moduleId, array $marksFirstByStudent, array $marksSecondByStudent): bool {
        $this->ensureMarksQuestionColumns();
        $moduleId = trim($moduleId);
        if ($moduleId === '') {
            return false;
        }
        $conn = $this->db->getConnection();
        $sqlSel = 'SELECT * FROM `marks` WHERE `exam_id` = ? AND `module_id` = ? AND `student_id` = ? LIMIT 1';
        $sqlIns = "INSERT INTO `marks` (
            `exam_id`, `module_id`, `student_id`,
            `marks_q1`, `marks_q2`, `marks_q3`, `marks_q4`, `marks_q5`, `marks_q6`, `marks_q7`, `marks_final`,
            `marks_second_q1`, `marks_second_q2`, `marks_second_q3`, `marks_second_q4`, `marks_second_q5`, `marks_second_q6`, `marks_second_q7`, `marks_second_final`,
            `marks`, `marks_second`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `marks_q1` = VALUES(`marks_q1`), `marks_q2` = VALUES(`marks_q2`), `marks_q3` = VALUES(`marks_q3`),
            `marks_q4` = VALUES(`marks_q4`), `marks_q5` = VALUES(`marks_q5`), `marks_q6` = VALUES(`marks_q6`), `marks_q7` = VALUES(`marks_q7`), `marks_final` = VALUES(`marks_final`),
            `marks_second_q1` = VALUES(`marks_second_q1`), `marks_second_q2` = VALUES(`marks_second_q2`), `marks_second_q3` = VALUES(`marks_second_q3`),
            `marks_second_q4` = VALUES(`marks_second_q4`), `marks_second_q5` = VALUES(`marks_second_q5`), `marks_second_q6` = VALUES(`marks_second_q6`), `marks_second_q7` = VALUES(`marks_second_q7`), `marks_second_final` = VALUES(`marks_second_final`),
            `marks` = VALUES(`marks`), `marks_second` = VALUES(`marks_second`)";

        $firstKeys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'final'];
        $secondKeys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'final'];

        $dbFirst = ['q1' => 'marks_q1', 'q2' => 'marks_q2', 'q3' => 'marks_q3', 'q4' => 'marks_q4', 'q5' => 'marks_q5', 'q6' => 'marks_q6', 'q7' => 'marks_q7', 'final' => 'marks_final'];
        $dbSecond = ['q1' => 'marks_second_q1', 'q2' => 'marks_second_q2', 'q3' => 'marks_second_q3', 'q4' => 'marks_second_q4', 'q5' => 'marks_second_q5', 'q6' => 'marks_second_q6', 'q7' => 'marks_second_q7', 'final' => 'marks_second_final'];

        $allIds = array_unique(array_merge(array_keys($marksFirstByStudent), array_keys($marksSecondByStudent)));
        foreach ($allIds as $studentId) {
            $studentId = trim((string) $studentId);
            if ($studentId === '') {
                continue;
            }

            $stmtSel = $conn->prepare($sqlSel);
            $stmtSel->bind_param('iss', $examId, $moduleId, $studentId);
            $stmtSel->execute();
            $res = $stmtSel->get_result();
            $cur = $res ? $res->fetch_assoc() : null;
            $stmtSel->close();

            $valsFirst = [];
            foreach ($dbFirst as $pk => $dk) {
                $valsFirst[$dk] = $cur[$dk] ?? null;
            }
            $valsSecond = [];
            foreach ($dbSecond as $pk => $dk) {
                $valsSecond[$dk] = $cur[$dk] ?? null;
            }

            $postF = $marksFirstByStudent[$studentId] ?? [];
            if (!is_array($postF)) {
                $postF = [];
            }
            foreach ($firstKeys as $pk) {
                $dk = $dbFirst[$pk];
                if (!array_key_exists($pk, $postF)) {
                    continue;
                }
                $v = $postF[$pk];
                if ($v !== null && $v !== '' && is_numeric($v)) {
                    $valsFirst[$dk] = (string) ((float) $v);
                }
            }

            $postS = $marksSecondByStudent[$studentId] ?? [];
            if (!is_array($postS)) {
                $postS = [];
            }
            foreach ($secondKeys as $pk) {
                $dk = $dbSecond[$pk];
                if (!array_key_exists($pk, $postS)) {
                    continue;
                }
                $v = $postS[$pk];
                if ($v !== null && $v !== '' && is_numeric($v)) {
                    $valsSecond[$dk] = (string) ((float) $v);
                }
            }

            $hasAny = false;
            foreach (array_merge($valsFirst, $valsSecond) as $v) {
                if ($v !== null && $v !== '') {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                continue;
            }

            $mf = $valsFirst['marks_final'] ?? null;
            $ms = $valsSecond['marks_second_final'] ?? null;

            $mq1 = $valsFirst['marks_q1'];
            $mq2 = $valsFirst['marks_q2'];
            $mq3 = $valsFirst['marks_q3'];
            $mq4 = $valsFirst['marks_q4'];
            $mq5 = $valsFirst['marks_q5'];
            $mq6 = $valsFirst['marks_q6'];
            $mq7 = $valsFirst['marks_q7'];
            $mfin = $valsFirst['marks_final'];
            $sq1 = $valsSecond['marks_second_q1'];
            $sq2 = $valsSecond['marks_second_q2'];
            $sq3 = $valsSecond['marks_second_q3'];
            $sq4 = $valsSecond['marks_second_q4'];
            $sq5 = $valsSecond['marks_second_q5'];
            $sq6 = $valsSecond['marks_second_q6'];
            $sq7 = $valsSecond['marks_second_q7'];
            $sfin = $valsSecond['marks_second_final'];

            $stmt = $conn->prepare($sqlIns);
            if (!$stmt) {
                return false;
            }
            $types = 'iss' . str_repeat('s', 18);
            $stmt->bind_param(
                $types,
                $examId,
                $moduleId,
                $studentId,
                $mq1,
                $mq2,
                $mq3,
                $mq4,
                $mq5,
                $mq6,
                $mq7,
                $mfin,
                $sq1,
                $sq2,
                $sq3,
                $sq4,
                $sq5,
                $sq6,
                $sq7,
                $sfin,
                $mf,
                $ms
            );
            if (!$stmt->execute()) {
                $stmt->close();
                return false;
            }
            $stmt->close();

            $sync = $conn->prepare('UPDATE `marks` SET `marks` = `marks_final`, `marks_second` = `marks_second_final` WHERE `exam_id` = ? AND `module_id` = ? AND `student_id` = ?');
            $sync->bind_param('iss', $examId, $moduleId, $studentId);
            $sync->execute();
            $sync->close();
        }
        return true;
    }

    /**
     * Verify student belongs to exam (for admission PDF).
     */
    public function isStudentOnExam(int $examId, string $studentId): bool {
        $sql = 'SELECT 1 FROM `exam_students` WHERE `exam_id` = ? AND `student_id` = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $examId, $studentId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }
}
