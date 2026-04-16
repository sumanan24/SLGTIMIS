<?php
/**
 * Online student applications (Level 04 / 05)
 */

class StudentApplicationModel extends Model {
    protected $table = 'student_applications';
    private static $tableEnsured = false;

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
        . '`nic_document_path`, `birth_certificate_path`, `ol_certificate_path`, `al_certificate_path`, `nvq_certificate_path`, `bank_receipt_path`, `created_at`';

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
    private const APPLICATION_LIST_SELECT = '`application_id`, `application_level`, `student_full_name`, `student_nic`, `student_district`, '
        . '`student_email`, `student_phone`, `created_at`';

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
            'course_priority_1', 'course_priority_2', 'course_priority_3', 'ol_index_number', 'ol_exam_year',
            'ol_subject_name_01', 'ol_subject_01_marks', 'ol_subject_name_02', 'ol_subject_02_marks', 'ol_subject_name_03', 'ol_subject_03_marks',
            'ol_subject_name_04', 'ol_subject_04_marks', 'ol_subject_name_05', 'ol_subject_05_marks', 'ol_subject_name_06', 'ol_subject_06_marks',
            'ol_subject_name_07', 'ol_subject_07_marks', 'ol_subject_name_08', 'ol_subject_08_marks', 'ol_subject_name_09', 'ol_subject_09_marks',
            'al_index_number', 'al_exam_year', 'al_stream',
            'al_subject_name_01', 'al_subject_01_marks', 'al_subject_name_02', 'al_subject_02_marks', 'al_subject_name_03', 'al_subject_03_marks',
            'nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed',
            'created_at',
        ];
        return $cache;
    }

    /**
     * @return array{total: int, by_level: list<array{level: string, count: int}>, by_district: list<array{label: string, count: int}>, by_course: list<array{label: string, count: int}>}
     */
    public function getDashboardStats(): array {
        $this->ensureTable();
        $out = [
            'total' => 0,
            'by_level' => [],
            'by_district' => [],
            'by_course' => [],
        ];
        $t = $this->table;
        $res = $this->db->query("SELECT COUNT(*) AS `c` FROM `{$t}`");
        if ($res && $row = $res->fetch_assoc()) {
            $out['total'] = (int) ($row['c'] ?? 0);
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
        $sqlCourse = "SELECT COALESCE(NULLIF(TRIM(`course_priority_1`), ''), '(Not specified)') AS `lbl`, COUNT(*) AS `cnt` "
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
        return $out;
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
        if (str_starts_with($s, '//')) {
            $scheme = parse_url(APP_URL, PHP_URL_SCHEME) ?: 'http';
            return $scheme . ':' . $s;
        }
        while (str_starts_with($s, './')) {
            $s = substr($s, 2);
        }
        if (preg_match('#(^|/)uploads/student_applications/(.+)$#i', $s, $m)) {
            $s = 'uploads/student_applications/' . $m[2];
        }
        $base = rtrim(APP_URL, '/');
        if (str_starts_with($s, '/')) {
            $parts = parse_url(APP_URL);
            if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
                return $base . $s;
            }
            $pathPrefix = isset($parts['path']) ? rtrim(str_replace('\\', '/', (string) $parts['path']), '/') : '';
            if ($pathPrefix !== '' && str_starts_with($s, '/uploads/') && !str_starts_with($s, $pathPrefix . '/')) {
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
