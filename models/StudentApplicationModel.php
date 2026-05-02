<?php
/**
 * Online student applications (Level 04 / 05)
 */

class StudentApplicationModel extends Model {
    protected $table = 'student_applications';
    private static $tableEnsured = false;
    private static $schemaMigrated = false;

    /**
     * Staff detail view — must match `student_applications` columns (same order as reporting SELECT).
     */
    private const APPLICATION_DETAIL_SELECT = '`application_id`, `application_level`, `student_title`, `student_full_name`, `student_initial_name`, '
        . '`student_gender`, `student_civil_status`, `student_email`, `student_phone`, `student_whatsapp`, `student_nic`, `student_dob`, '
        . '`student_language`, `student_religion`, `student_blood_group`, `student_address`, `student_zip_code`, `student_district`, `student_province`, '
        . '`course_priority_1`, `course_priority_2`, `course_priority_3`, `ol_index_number`, `ol_exam_year`, '
        . '`ol_subject_name_01`, `ol_subject_01_marks`, `ol_subject_name_02`, `ol_subject_02_marks`, `ol_subject_name_03`, `ol_subject_03_marks`, '
        . '`ol_subject_name_04`, `ol_subject_04_marks`, `ol_subject_name_05`, `ol_subject_05_marks`, `ol_subject_name_06`, `ol_subject_06_marks`, '
        . '`ol_subject_name_07`, `ol_subject_07_marks`, `ol_subject_name_08`, `ol_subject_08_marks`, `ol_subject_name_09`, `ol_subject_09_marks`, '
        . '`al_index_number`, `al_exam_year`, `al_stream`, '
        . '`al_subject_name_01`, `al_subject_01_marks`, `al_subject_name_02`, `al_subject_02_marks`, `al_subject_name_03`, `al_subject_03_marks`, '
        . '`nvq_level`, `nvq_course_name`, `nvq_institute_name`, `nvq_year_completed`, '
        . '`nic_document_path`, `birth_certificate_path`, `ol_certificate_path`, `al_certificate_path`, `nvq_certificate_path`, `bank_receipt_path`, '
        . '`status`, `created_at`';

    /** Upload path columns — excluded from staff data-only exports (CSV, etc.). */
    public const DOCUMENT_PATH_COLUMNS = [
        'nic_document_path',
        'birth_certificate_path',
        'ol_certificate_path',
        'al_certificate_path',
        'nvq_certificate_path',
        'bank_receipt_path',
    ];

    /** Staff list at `/student-applications`. */
    private const APPLICATION_LIST_SELECT = '`application_id`, `application_level`, `status`, `student_full_name`, `student_nic`, `student_district`, '
        . '`student_email`, `student_phone`, `created_at`';

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
        $this->migrateSchema();
    }

    protected function getPrimaryKey() {
        return 'application_id';
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
     * Migrate schema for older installs (PHP 7.4 compatible).
     */
    public function migrateSchema(): void {
        if (self::$schemaMigrated) {
            return;
        }
        try {
            $conn = $this->db->getConnection();
            $tbl = $conn->query("SHOW TABLES LIKE 'student_applications'");
            if (!$tbl || $tbl->num_rows === 0) {
                if ($tbl) {
                    $tbl->free();
                }
                self::$schemaMigrated = true;
                return;
            }
            $tbl->free();

            // Add status column if missing.
            $col = $conn->query("SHOW COLUMNS FROM `student_applications` LIKE 'status'");
            $has = $col && $col->num_rows > 0;
            if ($col) {
                $col->free();
            }
            if (!$has) {
                $conn->query("ALTER TABLE `student_applications` ADD COLUMN `status` ENUM('new','approved','rejected') NOT NULL DEFAULT 'new' AFTER `bank_receipt_path`");
            } else {
                $col2 = $conn->query("SHOW COLUMNS FROM `student_applications` WHERE Field = 'status'");
                $type = '';
                if ($col2 && ($r2 = $col2->fetch_assoc())) {
                    $type = strtolower((string) ($r2['Type'] ?? ''));
                }
                if ($col2) {
                    $col2->free();
                }
                if ($type !== '' && strpos($type, 'rejected') === false) {
                    $conn->query("ALTER TABLE `student_applications` MODIFY COLUMN `status` ENUM('new','approved','rejected') NOT NULL DEFAULT 'new' COMMENT 'Application workflow status'");
                }
            }

            // Add index for status+created_at if missing.
            $ix = $conn->query("SHOW INDEX FROM `student_applications` WHERE Key_name = 'idx_status_created'");
            if ($ix && $ix->num_rows === 0) {
                $ix->free();
                $conn->query("ALTER TABLE `student_applications` ADD KEY `idx_status_created` (`status`, `created_at`)");
            } elseif ($ix) {
                $ix->free();
            }
        } catch (Throwable $e) {
            error_log('StudentApplicationModel::migrateSchema: ' . $e->getMessage());
        }
        self::$schemaMigrated = true;
    }

    public function setStatus(int $id, string $status): bool {
        $this->ensureTable();
        $this->migrateSchema();
        if (!in_array($status, ['new', 'approved', 'rejected'], true)) {
            return false;
        }
        $sql = "UPDATE `{$this->table}` SET `status` = ? WHERE `application_id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
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
        $sql = 'SELECT ' . self::APPLICATION_LIST_SELECT . " FROM `{$this->table}` ORDER BY `created_at` DESC";
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
     * First course preference matches `course` row (same rules as dashboard / exports).
     *
     * @return array{active: bool, join: string, whereSuffix: string, suffixTypes: string, suffixParams: list<string>}
     */
    private function adminListFirstCourseFilterParts(?string $departmentId, ?string $courseId): array {
        $dept = $departmentId !== null ? trim((string) $departmentId) : '';
        $crs = $courseId !== null ? trim((string) $courseId) : '';
        if ($dept === '' && $crs === '') {
            return ['active' => false, 'join' => '', 'whereSuffix' => '', 'suffixTypes' => '', 'suffixParams' => []];
        }
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $join = ' INNER JOIN `course` sa_fc ON ('
            . self::sqlTrimUtf8mb4('sa_fc', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('sa_fc', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ')';
        $whereSuffix = '';
        $suffixTypes = '';
        $suffixParams = [];
        if ($dept !== '') {
            $whereSuffix .= ' AND sa_fc.`department_id` = ?';
            $suffixTypes .= 's';
            $suffixParams[] = $dept;
        }
        if ($crs !== '') {
            $whereSuffix .= ' AND sa_fc.`course_id` = ?';
            $suffixTypes .= 's';
            $suffixParams[] = $crs;
        }
        return ['active' => true, 'join' => $join, 'whereSuffix' => $whereSuffix, 'suffixTypes' => $suffixTypes, 'suffixParams' => $suffixParams];
    }

    /**
     * EXISTS (…) for staff export query (avoids extra JOIN aliases on `sa`).
     *
     * @return array{sql: string, types: string, params: list<string>}
     */
    private function adminExportFirstPreferenceExistsParts(?string $departmentId, ?string $courseId): array {
        $parts = $this->adminListFirstCourseFilterParts($departmentId, $courseId);
        if (!$parts['active']) {
            return ['sql' => '', 'types' => '', 'params' => []];
        }
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $inner = '(' . self::sqlTrimUtf8mb4('sa_fc', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('sa_fc', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ')';
        $sql = ' AND EXISTS (SELECT 1 FROM `course` sa_fc WHERE ' . $inner . $parts['whereSuffix'] . ')';
        return ['sql' => $sql, 'types' => $parts['suffixTypes'], 'params' => $parts['suffixParams']];
    }

    /**
     * Count applications for staff list (optional NVQ level 04 / 05; optional 1st preference department / course).
     */
    public function countListForAdmin(string $status, ?string $level = null, ?string $departmentId = null, ?string $courseId = null): int {
        $this->ensureTable();
        $this->migrateSchema();
        if (!in_array($status, ['new', 'approved', 'rejected'], true)) {
            return 0;
        }
        $frag = $this->adminListFirstCourseFilterParts($departmentId, $courseId);
        $sql = "SELECT COUNT(*) AS `c` FROM `{$this->table}` `sa` {$frag['join']} WHERE `sa`.`status` = ?";
        $types = 's';
        $params = [$status];
        if ($level !== null && $level !== '' && in_array($level, ['04', '05'], true)) {
            $sql .= ' AND `sa`.`application_level` = ?';
            $types .= 's';
            $params[] = $level;
        }
        $sql .= $frag['whereSuffix'];
        $types .= $frag['suffixTypes'];
        foreach ($frag['suffixParams'] as $p) {
            $params[] = $p;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if (!$this->bindParamsTyped($stmt, $types, $params)) {
            $stmt->close();
            return 0;
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $n = 0;
        if ($res && ($row = $res->fetch_assoc())) {
            $n = (int) ($row['c'] ?? 0);
        }
        $stmt->close();
        return $n;
    }

    /**
     * One page of applications for staff list (new or approved panel).
     *
     * @return list<array<string, mixed>>
     */
    public function getListPageForAdmin(string $status, ?string $level, int $page, int $perPage, ?string $departmentId = null, ?string $courseId = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        if (!in_array($status, ['new', 'approved', 'rejected'], true)) {
            return [];
        }
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $frag = $this->adminListFirstCourseFilterParts($departmentId, $courseId);
        $sql = 'SELECT ' . self::APPLICATION_LIST_SELECT . " FROM `{$this->table}` `sa` {$frag['join']} WHERE `sa`.`status` = ?";
        $types = 's';
        $params = [$status];
        if ($level !== null && $level !== '' && in_array($level, ['04', '05'], true)) {
            $sql .= ' AND `sa`.`application_level` = ?';
            $types .= 's';
            $params[] = $level;
        }
        $sql .= $frag['whereSuffix'];
        $types .= $frag['suffixTypes'];
        foreach ($frag['suffixParams'] as $p) {
            $params[] = $p;
        }
        $sql .= ' ORDER BY `sa`.`created_at` DESC LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!$this->bindParamsTyped($stmt, $types, $params)) {
            $stmt->close();
            return [];
        }
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
     * Departments that appear on at least one application (1st preference resolved to `course` / `department`).
     *
     * @return list<array{department_id: string, department_name: string}>
     */
    public function getAdminFilterDepartments(?string $level = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $t = $this->table;
        $joinC = 'INNER JOIN `course` c ON (' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('c', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ')';
        $sql = "SELECT DISTINCT d.`department_id`, d.`department_name` FROM `{$t}` sa {$joinC} "
            . "INNER JOIN `department` d ON d.`department_id` = c.`department_id` "
            . "WHERE TRIM(IFNULL(d.`department_name`,'')) <> ''";
        $types = '';
        $params = [];
        if ($level !== null && $level !== '' && in_array($level, ['04', '05'], true)) {
            $sql .= ' AND sa.`application_level` = ?';
            $types = 's';
            $params[] = $level;
        }
        $sql .= ' ORDER BY d.`department_name` ASC';
        if ($types === '') {
            $res = $this->db->query($sql);
            if (!$res) {
                return [];
            }
            $out = [];
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'department_id' => (string) ($row['department_id'] ?? ''),
                    'department_name' => (string) ($row['department_name'] ?? ''),
                ];
            }
            return $out;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!$this->bindParamsTyped($stmt, $types, $params)) {
            $stmt->close();
            return [];
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'department_id' => (string) ($row['department_id'] ?? ''),
                    'department_name' => (string) ($row['department_name'] ?? ''),
                ];
            }
        }
        $stmt->close();
        return $out;
    }

    /**
     * Courses that appear on at least one application (1st preference). Optionally scoped by department and NVQ level.
     *
     * @return list<array{course_id: string, course_name: string}>
     */
    public function getAdminFilterCourses(?string $level = null, ?string $departmentId = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $t = $this->table;
        $joinC = 'INNER JOIN `course` c ON (' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('c', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ')';
        $sql = "SELECT DISTINCT c.`course_id`, c.`course_name` FROM `{$t}` sa {$joinC} WHERE TRIM(IFNULL(c.`course_name`,'')) <> ''";
        $types = '';
        $params = [];
        if ($level !== null && $level !== '' && in_array($level, ['04', '05'], true)) {
            $sql .= ' AND sa.`application_level` = ?';
            $types .= 's';
            $params[] = $level;
        }
        $dept = $departmentId !== null ? trim((string) $departmentId) : '';
        if ($dept !== '') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $dept;
        }
        $sql .= ' ORDER BY c.`course_name` ASC';
        if ($types === '') {
            $res = $this->db->query($sql);
            if (!$res) {
                return [];
            }
            $out = [];
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'course_id' => (string) ($row['course_id'] ?? ''),
                    'course_name' => (string) ($row['course_name'] ?? ''),
                ];
            }
            return $out;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!$this->bindParamsTyped($stmt, $types, $params)) {
            $stmt->close();
            return [];
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'course_id' => (string) ($row['course_id'] ?? ''),
                    'course_name' => (string) ($row['course_name'] ?? ''),
                ];
            }
        }
        $stmt->close();
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array {
        $this->ensureTable();
        $sql = 'SELECT ' . self::APPLICATION_DETAIL_SELECT . " FROM `{$this->table}` WHERE `application_id` = ? LIMIT 1";
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

    /**
     * Space + Unicode em dash + space — same as legacy public form stored value `course_id + sep + course_name`.
     */
    private static function legacyCourseIdNameSeparator(): string {
        return ' ' . "\u{2014}" . ' ';
    }

    /**
     * mysqli_stmt::bind_param requires each bound value by reference; do not use variadic spread on a plain array.
     *
     * @param list<string|int|float> $params
     */
    private function bindParamsTyped(mysqli_stmt $stmt, string $types, array $params): bool {
        if ($types === '') {
            return true;
        }
        $bind = [$types];
        for ($i = 0, $n = count($params); $i < $n; $i++) {
            $bind[] = &$params[$i];
        }
        return (bool) call_user_func_array([$stmt, 'bind_param'], $bind);
    }

    /** Collation used when comparing course strings across mixed latin1/utf8mb4 columns. */
    private static function sqlUtf8mb4Collation(): string {
        return 'utf8mb4_unicode_ci';
    }

    /**
     * TRIM(CONVERT(col USING utf8mb4)) COLLATE … — avoids "Illegal mix of collations" in CONCAT / = with bound UTF-8 strings.
     *
     * @param string $tableAlias e.g. `c` or `sa`
     */
    private static function sqlTrimUtf8mb4(string $tableAlias, string $column): string {
        $coll = self::sqlUtf8mb4Collation();
        return 'TRIM(CONVERT(' . $tableAlias . '.`' . $column . '` USING utf8mb4)) COLLATE ' . $coll;
    }

    /**
     * TRIM(CONVERT(? USING utf8mb4)) COLLATE … for prepared statement placeholders.
     */
    private static function sqlTrimBoundUtf8mb4(): string {
        $coll = self::sqlUtf8mb4Collation();
        return 'TRIM(CONVERT(? USING utf8mb4)) COLLATE ' . $coll;
    }

    /**
     * Legacy row fingerprint: CONCAT(id, sep, name) with every piece utf8mb4 (sep is bound ? in prepared SQL).
     */
    private static function sqlLegacyCourseRowConcatBound(string $cAlias): string {
        $coll = self::sqlUtf8mb4Collation();
        return 'TRIM(CONCAT('
            . 'CONVERT(TRIM(' . $cAlias . '.`course_id`) USING utf8mb4), '
            . 'CONVERT(? USING utf8mb4), '
            . 'CONVERT(TRIM(' . $cAlias . '.`course_name`) USING utf8mb4)'
            . ')) COLLATE ' . $coll;
    }

    /**
     * Same as sqlLegacyCourseRowConcatBound but with literal separator (for dynamic SQL; $sepSql must be escaped for quotes).
     */
    private static function sqlLegacyCourseRowConcatLiteral(string $cAlias, string $sepSqlLiteral): string {
        $coll = self::sqlUtf8mb4Collation();
        return 'TRIM(CONCAT('
            . 'CONVERT(TRIM(' . $cAlias . '.`course_id`) USING utf8mb4), '
            . "CONVERT('" . $sepSqlLiteral . "' USING utf8mb4), "
            . 'CONVERT(TRIM(' . $cAlias . '.`course_name`) USING utf8mb4)'
            . ')) COLLATE ' . $coll;
    }

    /**
     * Resolve department + course name from stored `course_priority_N` (course name only, or legacy "id — name").
     *
     * @return array{department_name: string, course_name: string}
     */
    public function resolveCourseDepartmentForPreference(?string $stored): array {
        $this->ensureTable();
        $stored = trim((string) $stored);
        if ($stored === '') {
            return ['department_name' => '', 'course_name' => ''];
        }
        $sep = self::legacyCourseIdNameSeparator();
        $sql = 'SELECT c.`course_name`, d.`department_name` FROM `course` c '
            . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id` '
            . 'WHERE ' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimBoundUtf8mb4() . ' '
            . 'OR ' . self::sqlLegacyCourseRowConcatBound('c') . ' = ' . self::sqlTrimBoundUtf8mb4() . ' '
            . 'LIMIT 1';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['department_name' => '', 'course_name' => self::displayCourseNameFromStoredPreference($stored)];
        }
        $stmt->bind_param('sss', $stored, $sep, $stored);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row) {
            $cn = trim((string) ($row['course_name'] ?? ''));
            $dn = trim((string) ($row['department_name'] ?? ''));
            if ($cn !== '' || $dn !== '') {
                return ['department_name' => $dn, 'course_name' => $cn !== '' ? $cn : self::displayCourseNameFromStoredPreference($stored)];
            }
        }
        return ['department_name' => '', 'course_name' => self::displayCourseNameFromStoredPreference($stored)];
    }

    /**
     * Course title for display when there is no `course` row match (legacy concatenated value).
     */
    private static function displayCourseNameFromStoredPreference(string $stored): string {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }
        $sep = self::legacyCourseIdNameSeparator();
        $pos = strpos($stored, $sep);
        if ($pos !== false) {
            return trim(substr($stored, $pos + strlen($sep)));
        }
        if (($pos = strpos($stored, ' — ')) !== false) {
            return trim(substr($stored, $pos + strlen(' — ')));
        }
        return $stored;
    }

    /**
     * @return array<string, string> Keys department_1..3, course_1..3
     */
    public function getCourseDepartmentDisplayFields(array $app): array {
        $out = [];
        for ($n = 1; $n <= 3; $n++) {
            $r = $this->resolveCourseDepartmentForPreference(isset($app['course_priority_' . $n]) ? (string) $app['course_priority_' . $n] : null);
            $out['department_' . $n] = $r['department_name'];
            $out['course_' . $n] = $r['course_name'];
        }
        return $out;
    }

    /**
     * Adds department_* and course_* for staff CSV/PDF/detail (DB still holds course_priority_*).
     *
     * @param array<string, mixed> $app
     * @return array<string, mixed>
     */
    public function enrichApplicationForStaffExport(array $app): array {
        return array_merge($app, $this->getCourseDepartmentDisplayFields($app));
    }

    /**
     * Column order for staff export of application data (same as detail view, without file paths).
     *
     * @return list<string>
     */
    public static function getStaffExportColumnOrder(): array {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [
            'application_id', 'application_level', 'student_title', 'student_full_name', 'student_initial_name',
            'student_gender', 'student_civil_status', 'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
            'student_language', 'student_religion', 'student_blood_group', 'student_address', 'student_zip_code', 'student_district', 'student_province',
            'department_1', 'department_2', 'department_3', 'course_1', 'course_2', 'course_3', 'ol_index_number', 'ol_exam_year',
            'ol_subject_name_01', 'ol_subject_01_marks', 'ol_subject_name_02', 'ol_subject_02_marks', 'ol_subject_name_03', 'ol_subject_03_marks',
            'ol_subject_name_04', 'ol_subject_04_marks', 'ol_subject_name_05', 'ol_subject_05_marks', 'ol_subject_name_06', 'ol_subject_06_marks',
            'ol_subject_name_07', 'ol_subject_07_marks', 'ol_subject_name_08', 'ol_subject_08_marks', 'ol_subject_name_09', 'ol_subject_09_marks',
            'al_index_number', 'al_exam_year', 'al_stream',
            'al_subject_name_01', 'al_subject_01_marks', 'al_subject_name_02', 'al_subject_02_marks', 'al_subject_name_03', 'al_subject_03_marks',
            'nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed',
            'status',
            'created_at',
        ];
        return $cache;
    }

    /**
     * Dashboard aggregates. Optional NVQ level and 1st-preference department/course match the staff list filters.
     *
     * @return array{total: int, by_status: array{new: int, approved: int, rejected: int}, by_level: list<array{level: string, count: int}>, by_district: list<array{label: string, count: int}>, by_course: list<array{label: string, count: int}>, by_department: list<array{label: string, count: int}>}
     */
    public function getDashboardStats(?string $level = null, ?string $departmentId = null, ?string $courseId = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        $out = [
            'total' => 0,
            'by_status' => ['new' => 0, 'approved' => 0, 'rejected' => 0],
            'by_level' => [],
            'by_district' => [],
            'by_course' => [],
            'by_department' => [],
        ];
        $t = $this->table;
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());

        $existsPart = $this->adminExportFirstPreferenceExistsParts($departmentId, $courseId);
        $levelPart = '';
        $filterTypes = '';
        $filterParams = [];
        if ($level !== null && $level !== '' && in_array($level, ['04', '05'], true)) {
            $levelPart = ' AND sa.`application_level` = ?';
            $filterTypes .= 's';
            $filterParams[] = $level;
        }
        $filterTail = ' WHERE 1=1' . $levelPart . $existsPart['sql'];
        $filterTypes .= $existsPart['types'];
        foreach ($existsPart['params'] as $ep) {
            $filterParams[] = $ep;
        }
        $filtered = ($levelPart !== '' || $existsPart['sql'] !== '');

        $db = $this->db;
        $self = $this;
        $runFiltered = function (string $sql) use ($db, $self, $filterTypes, $filterParams) {
            if ($filterTypes === '') {
                return $db->query($sql);
            }
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                return false;
            }
            if (!$self->bindParamsTyped($stmt, $filterTypes, $filterParams)) {
                $stmt->close();
                return false;
            }
            if (!$stmt->execute()) {
                $stmt->close();
                return false;
            }
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        };

        if (!$filtered) {
            $res = $this->db->query("SELECT COUNT(*) AS `c` FROM `{$t}`");
            if ($res && $row = $res->fetch_assoc()) {
                $out['total'] = (int) ($row['c'] ?? 0);
            }
            $res = $this->db->query("SELECT `status`, COUNT(*) AS `cnt` FROM `{$t}` GROUP BY `status`");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $st = strtolower(trim((string) ($row['status'] ?? '')));
                    if ($st === 'new') {
                        $out['by_status']['new'] = (int) ($row['cnt'] ?? 0);
                    } elseif ($st === 'approved') {
                        $out['by_status']['approved'] = (int) ($row['cnt'] ?? 0);
                    } elseif ($st === 'rejected') {
                        $out['by_status']['rejected'] = (int) ($row['cnt'] ?? 0);
                    }
                }
            }
            $sqlLevel = "SELECT TRIM(`application_level`) AS `lvl`, COUNT(*) AS `cnt` FROM `{$t}` GROUP BY `lvl` ORDER BY `lvl` ASC";
            $res = $this->db->query($sqlLevel);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $lv = (string) ($row['lvl'] ?? '');
                    if ($lv === '') {
                        $lv = '(unspecified)';
                    }
                    $out['by_level'][] = ['level' => $lv, 'count' => (int) ($row['cnt'] ?? 0)];
                }
            }
            $sqlDist = "SELECT COALESCE(NULLIF(TRIM(`student_district`), ''), '(Not specified)') AS `lbl`, COUNT(*) AS `cnt` "
                . "FROM `{$t}` GROUP BY `lbl` ORDER BY (`lbl` = '(Not specified)'), `lbl` ASC";
            $res = $this->db->query($sqlDist);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out['by_district'][] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }
            $sqlCourse = "SELECT COALESCE(NULLIF(TRIM(IF(LOCATE(CONVERT('{$sepEsc}' USING utf8mb4), TRIM(CONVERT(`course_priority_1` USING utf8mb4))) > 0, "
                . "SUBSTRING_INDEX(TRIM(CONVERT(`course_priority_1` USING utf8mb4)), CONVERT('{$sepEsc}' USING utf8mb4), -1), TRIM(CONVERT(`course_priority_1` USING utf8mb4)))), ''), '(Not specified)') AS `lbl`, COUNT(*) AS `cnt` "
                . "FROM `{$t}` GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40";
            $res = $this->db->query($sqlCourse);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out['by_course'][] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }
            $sqlDept = "SELECT COALESCE(NULLIF(TRIM(d.`department_name`), ''), '(Not matched)') AS `lbl`, COUNT(*) AS `cnt` "
                . "FROM `{$t}` sa "
                . 'LEFT JOIN `course` c ON (' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ' OR '
                . self::sqlLegacyCourseRowConcatLiteral('c', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ') '
                . "LEFT JOIN `department` d ON d.`department_id` = c.`department_id` "
                . "GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40";
            $res = $this->db->query($sqlDept);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out['by_department'][] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }
            return $out;
        }

        $sqlTotal = "SELECT COUNT(*) AS `c` FROM `{$t}` sa" . $filterTail;
        $res = $runFiltered($sqlTotal);
        if ($res && $row = $res->fetch_assoc()) {
            $out['total'] = (int) ($row['c'] ?? 0);
        }
        $sqlStatus = "SELECT sa.`status`, COUNT(*) AS `cnt` FROM `{$t}` sa" . $filterTail . ' GROUP BY sa.`status`';
        $res = $runFiltered($sqlStatus);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $st = strtolower(trim((string) ($row['status'] ?? '')));
                if ($st === 'new') {
                    $out['by_status']['new'] = (int) ($row['cnt'] ?? 0);
                } elseif ($st === 'approved') {
                    $out['by_status']['approved'] = (int) ($row['cnt'] ?? 0);
                } elseif ($st === 'rejected') {
                    $out['by_status']['rejected'] = (int) ($row['cnt'] ?? 0);
                }
            }
        }
        $sqlLevel = 'SELECT TRIM(sa.`application_level`) AS `lvl`, COUNT(*) AS `cnt` FROM `' . $t . '` sa' . $filterTail
            . ' GROUP BY `lvl` ORDER BY `lvl` ASC';
        $res = $runFiltered($sqlLevel);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $lv = (string) ($row['lvl'] ?? '');
                if ($lv === '') {
                    $lv = '(unspecified)';
                }
                $out['by_level'][] = ['level' => $lv, 'count' => (int) ($row['cnt'] ?? 0)];
            }
        }
        $sqlDist = 'SELECT COALESCE(NULLIF(TRIM(sa.`student_district`), \'\'), \'(Not specified)\') AS `lbl`, COUNT(*) AS `cnt` '
            . "FROM `{$t}` sa" . $filterTail . " GROUP BY `lbl` ORDER BY (`lbl` = '(Not specified)'), `lbl` ASC";
        $res = $runFiltered($sqlDist);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out['by_district'][] = [
                    'label' => (string) ($row['lbl'] ?? ''),
                    'count' => (int) ($row['cnt'] ?? 0),
                ];
            }
        }
        $sqlCourse = "SELECT COALESCE(NULLIF(TRIM(IF(LOCATE(CONVERT('{$sepEsc}' USING utf8mb4), TRIM(CONVERT(sa.`course_priority_1` USING utf8mb4))) > 0, "
            . "SUBSTRING_INDEX(TRIM(CONVERT(sa.`course_priority_1` USING utf8mb4)), CONVERT('{$sepEsc}' USING utf8mb4), -1), TRIM(CONVERT(sa.`course_priority_1` USING utf8mb4)))), ''), '(Not specified)') AS `lbl`, COUNT(*) AS `cnt` "
            . "FROM `{$t}` sa" . $filterTail . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40';
        $res = $runFiltered($sqlCourse);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out['by_course'][] = [
                    'label' => (string) ($row['lbl'] ?? ''),
                    'count' => (int) ($row['cnt'] ?? 0),
                ];
            }
        }
        $sqlDept = "SELECT COALESCE(NULLIF(TRIM(d.`department_name`), ''), '(Not matched)') AS `lbl`, COUNT(*) AS `cnt` "
            . "FROM `{$t}` sa "
            . 'LEFT JOIN `course` c ON (' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('c', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', 'course_priority_1') . ') '
            . "LEFT JOIN `department` d ON d.`department_id` = c.`department_id` "
            . $filterTail . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40';
        $res = $runFiltered($sqlDept);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out['by_department'][] = [
                    'label' => (string) ($row['lbl'] ?? ''),
                    'count' => (int) ($row['cnt'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /**
     * Staff export: applications matching optional status and NVQ level (04 / 05).
     *
     * @param string|null $status 'new', 'approved', 'rejected', or null for all
     * @param string|null $level '04', '05', or null / empty for all levels
     * @param string|null $departmentId optional: 1st preference course belongs to this department
     * @param string|null $courseId optional: 1st preference matches this `course`.`course_id`
     * @return list<array<string, mixed>>
     */
    public function getAllForStaffExport(?string $status = null, ?string $level = null, ?string $departmentId = null, ?string $courseId = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());

        $courseExpr = static function (int $n, string $cAlias) use ($sepEsc): string {
            $fp = 'course_priority_' . $n;
            return 'COALESCE(NULLIF(TRIM(' . $cAlias . '.`course_name`),\'\'), TRIM(IF(LOCATE(CONVERT(\'' . $sepEsc . '\' USING utf8mb4), TRIM(CONVERT(sa.`' . $fp . '` USING utf8mb4))) > 0, '
                . 'SUBSTRING_INDEX(TRIM(CONVERT(sa.`' . $fp . '` USING utf8mb4)), CONVERT(\'' . $sepEsc . '\' USING utf8mb4), -1), TRIM(CONVERT(sa.`' . $fp . '` USING utf8mb4))))) AS `course_' . $n . '`';
        };

        $selectParts = [];
        foreach (self::getStaffExportColumnOrder() as $col) {
            if ($col === 'department_1') {
                $selectParts[] = 'NULLIF(TRIM(d1.`department_name`),\'\') AS `department_1`';
            } elseif ($col === 'department_2') {
                $selectParts[] = 'NULLIF(TRIM(d2.`department_name`),\'\') AS `department_2`';
            } elseif ($col === 'department_3') {
                $selectParts[] = 'NULLIF(TRIM(d3.`department_name`),\'\') AS `department_3`';
            } elseif ($col === 'course_1') {
                $selectParts[] = $courseExpr(1, 'c1');
            } elseif ($col === 'course_2') {
                $selectParts[] = $courseExpr(2, 'c2');
            } elseif ($col === 'course_3') {
                $selectParts[] = $courseExpr(3, 'c3');
            } elseif (preg_match('/^[a-z0-9_]+$/', $col)) {
                $selectParts[] = 'sa.`' . $col . '`';
            }
        }

        $joins = '';
        foreach ([1 => ['c1', 'd1'], 2 => ['c2', 'd2'], 3 => ['c3', 'd3']] as $n => $aliases) {
            $c = $aliases[0];
            $d = $aliases[1];
            $fp = 'course_priority_' . $n;
            $joins .= ' LEFT JOIN `course` ' . $c . ' ON (' . self::sqlTrimUtf8mb4($c, 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ' OR '
                . self::sqlLegacyCourseRowConcatLiteral($c, $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ')'
                . ' LEFT JOIN `department` ' . $d . ' ON ' . $d . '.`department_id` = ' . $c . '.`department_id`';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM `' . $this->table . '` sa ' . $joins . ' WHERE 1=1';
        $types = '';
        $params = [];
        if ($status !== null && $status !== '' && in_array($status, ['new', 'approved', 'rejected'], true)) {
            $sql .= ' AND sa.`status` = ?';
            $types .= 's';
            $params[] = $status;
        }
        if ($level !== null && $level !== '' && in_array($level, ['04', '05'], true)) {
            $sql .= ' AND sa.`application_level` = ?';
            $types .= 's';
            $params[] = $level;
        }
        $existsParts = $this->adminExportFirstPreferenceExistsParts($departmentId, $courseId);
        $sql .= $existsParts['sql'];
        $types .= $existsParts['types'];
        foreach ($existsParts['params'] as $ep) {
            $params[] = $ep;
        }
        $sql .= ' ORDER BY sa.`created_at` DESC';

        if ($types === '') {
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

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!$this->bindParamsTyped($stmt, $types, $params)) {
            $stmt->close();
            return [];
        }
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
        if (!$this->bindParamsTyped($stmt, $types, $params)) {
            $stmt->close();
            return false;
        }
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Public URL for a stored path under `uploads/student_applications/…` (relative, absolute, full URL, or legacy filesystem path).
     */
    public static function storedUploadPublicUrl(?string $stored): ?string {
        if (!defined('APP_URL')) {
            return null;
        }
        if ($stored === null) {
            return null;
        }
        $s = trim(str_replace('\\', '/', $stored));
        if ($s === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $s)) {
            return $s;
        }
        // PHP 7.4 compatibility: no str_starts_with().
        if (strpos($s, '//') === 0) {
            $scheme = parse_url(APP_URL, PHP_URL_SCHEME) ?: 'http';
            return $scheme . ':' . $s;
        }
        while (strpos($s, './') === 0) {
            $s = substr($s, 2);
        }
        if (preg_match('#(^|/)uploads/student_applications/(.+)$#i', $s, $m)) {
            $s = 'uploads/student_applications/' . $m[2];
        }
        $base = rtrim(APP_URL, '/');
        if (strpos($s, '/') === 0) {
            $parts = parse_url(APP_URL);
            if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
                return $base . $s;
            }
            $pathPrefix = isset($parts['path']) ? rtrim(str_replace('\\', '/', (string) $parts['path']), '/') : '';
            if ($pathPrefix !== '' && strpos($s, '/uploads/') === 0 && strpos($s, $pathPrefix . '/') !== 0) {
                $s = $pathPrefix . $s;
            }
            $auth = $parts['host'];
            if (!empty($parts['port'])) {
                $auth .= ':' . $parts['port'];
            }
            return $parts['scheme'] . '://' . $auth . $s;
        }
        $pathPart = self::encodeUrlPathSegments($s);
        if ($pathPart === '') {
            return null;
        }
        return $base . '/' . $pathPart;
    }

    private static function encodeUrlPathSegments(string $relativePath): string {
        $relativePath = ltrim(preg_replace('#/+#', '/', $relativePath), '/');
        if ($relativePath === '') {
            return '';
        }
        $out = [];
        foreach (explode('/', $relativePath) as $seg) {
            if ($seg === '') {
                continue;
            }
            $out[] = rawurlencode($seg);
        }
        return implode('/', $out);
    }
}
