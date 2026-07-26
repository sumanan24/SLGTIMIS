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
        . '`status`, `rejection_reason`, `created_at`';

    /** NIC-step draft rows (`insert_draft.php`) until the applicant enters a real name. */
    public const DRAFT_FULL_NAME_PLACEHOLDER = '(Pending)';

    /** Upload path columns — excluded from staff data-only exports (CSV, etc.). */
    public const DOCUMENT_PATH_COLUMNS = [
        'nic_document_path',
        'birth_certificate_path',
        'ol_certificate_path',
        'al_certificate_path',
        'nvq_certificate_path',
        'bank_receipt_path',
    ];

    /**
     * Columns ADM / system admin may change on {@see self::updateApplicationFromStaffPost} (not uploads or keys).
     *
     * @return list<string>
     */
    public static function getStaffEditableColumnNames(): array {
        return [
            'application_level', 'status',
            'student_title', 'student_full_name', 'student_initial_name', 'student_gender', 'student_civil_status',
            'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
            'student_language', 'student_religion', 'student_blood_group', 'student_address', 'student_zip_code',
            'student_district', 'student_province',
            'course_priority_1', 'course_priority_2', 'course_priority_3',
            'ol_index_number', 'ol_exam_year',
            'ol_subject_name_01', 'ol_subject_01_marks', 'ol_subject_name_02', 'ol_subject_02_marks',
            'ol_subject_name_03', 'ol_subject_03_marks', 'ol_subject_name_04', 'ol_subject_04_marks',
            'ol_subject_name_05', 'ol_subject_05_marks', 'ol_subject_name_06', 'ol_subject_06_marks',
            'ol_subject_name_07', 'ol_subject_07_marks', 'ol_subject_name_08', 'ol_subject_08_marks',
            'ol_subject_name_09', 'ol_subject_09_marks',
            'al_index_number', 'al_exam_year', 'al_stream',
            'al_subject_name_01', 'al_subject_01_marks', 'al_subject_name_02', 'al_subject_02_marks',
            'al_subject_name_03', 'al_subject_03_marks',
            'nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed',
        ];
    }

    /** Staff list at `/student-applications`. */
    private const APPLICATION_LIST_SELECT = '`application_id`, `application_level`, `status`, `rejection_reason`, `student_full_name`, `student_nic`, `student_district`, '
        . '`student_email`, `student_phone`, `student_whatsapp`, `student_language`, `created_at`, '
        . '`course_priority_1`, `course_priority_2`, `course_priority_3`';

    /** Applicant language values allowed on online applications (filter + staff edit). */
    public const STAFF_LANGUAGE_FILTER_VALUES = ['Tamil', 'Sinhala', 'English'];

    /**
     * Normalize `lang` query param for staff list / export filters.
     */
    public static function normalizedStaffLanguageFilter(?string $raw): ?string {
        $s = trim((string) ($raw ?? ''));
        if ($s === '') {
            return null;
        }
        return in_array($s, self::STAFF_LANGUAGE_FILTER_VALUES, true) ? $s : null;
    }

    /**
     * @return array{sql: string, types: string, params: list<string>}
     */
    private function adminLanguageFilterParts(?string $language): array {
        $lang = self::normalizedStaffLanguageFilter($language);
        if ($lang === null) {
            return ['sql' => '', 'types' => '', 'params' => []];
        }
        return [
            'sql' => ' AND TRIM(IFNULL(`sa`.`student_language`, \'\')) = ?',
            'types' => 's',
            'params' => [$lang],
        ];
    }

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
        $this->migrateSchema();
    }

    protected function getPrimaryKey() {
        return 'application_id';
    }

    /**
     * True once the applicant has started the form beyond the NIC-only draft.
     * SAO/RSA lists also require NIC copy and birth certificate uploads ({@see self::hasNicAndBirthCertificateUploaded}).
     * ADM / system admin may still open any row via direct URL.
     *
     * @param array<string, mixed> $app Row from `student_applications`
     */
    public static function isSubmittedForStaffReview(array $app): bool {
        $name = trim((string) ($app['student_full_name'] ?? ''));
        return $name !== '' && $name !== self::DRAFT_FULL_NAME_PLACEHOLDER;
    }

    /**
     * Both required identity documents uploaded (staff list uses the same rule for SAO/RSA).
     *
     * @param array<string, mixed> $app Row from `student_applications`
     */
    public static function hasNicAndBirthCertificateUploaded(array $app): bool {
        $nic = trim((string) ($app['nic_document_path'] ?? ''));
        $birth = trim((string) ($app['birth_certificate_path'] ?? ''));
        return $nic !== '' && $birth !== '';
    }

    /**
     * SQL predicate: not NIC-only draft and both `nic_document_path` / `birth_certificate_path` non-empty.
     *
     * @param string|null $tableAlias `sa` or null for bare column names (no alias)
     */
    private static function sqlStaffAffairsListPredicate(?string $tableAlias): string {
        $col = static function (string $name) use ($tableAlias): string {
            if ($tableAlias === null || $tableAlias === '') {
                return '`' . $name . '`';
            }
            return '`' . $tableAlias . '`.`' . $name . '`';
        };
        $ph = addslashes(self::DRAFT_FULL_NAME_PLACEHOLDER);
        return 'TRIM(' . $col('student_full_name') . ") <> '' AND " . $col('student_full_name') . " <> '{$ph}'"
            . ' AND TRIM(IFNULL(' . $col('nic_document_path') . ", '')) <> ''"
            . ' AND TRIM(IFNULL(' . $col('birth_certificate_path') . ", '')) <> ''";
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

            $colRr = $conn->query("SHOW COLUMNS FROM `student_applications` LIKE 'rejection_reason'");
            $hasRr = $colRr && $colRr->num_rows > 0;
            if ($colRr) {
                $colRr->free();
            }
            if (!$hasRr) {
                $conn->query("ALTER TABLE `student_applications` ADD COLUMN `rejection_reason` TEXT DEFAULT NULL COMMENT 'Required when staff reject; cleared on approve' AFTER `status`");
            }

            // Same as /level05application/: NIC and email are unique per `application_level` (04 vs 05), not globally.
            // Level 04 hits this model only; without this call, older DBs may keep a single-column NIC unique.
            $helperPath = BASE_PATH . DIRECTORY_SEPARATOR . 'level05application' . DIRECTORY_SEPARATOR . 'helpers.php';
            if (is_readable($helperPath)) {
                require_once $helperPath;
                if (function_exists('l05_migrate_student_applications_schema')) {
                    l05_migrate_student_applications_schema($conn);
                }
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
        if ($status === 'rejected') {
            return false;
        }
        $clearReason = ($status === 'approved' || $status === 'new') ? 1 : 0;
        if ($clearReason) {
            $sql = "UPDATE `{$this->table}` SET `status` = ?, `rejection_reason` = NULL WHERE `application_id` = ? LIMIT 1";
        } else {
            $sql = "UPDATE `{$this->table}` SET `status` = ? WHERE `application_id` = ? LIMIT 1";
        }
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
     * Reject with a mandatory reason (staff workflow).
     */
    public function setRejected(int $id, string $reason): bool {
        $this->ensureTable();
        $this->migrateSchema();
        $reason = trim($reason);
        if ($reason === '') {
            return false;
        }
        $status = 'rejected';
        $sql = "UPDATE `{$this->table}` SET `status` = ?, `rejection_reason` = ? WHERE `application_id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ssi', $status, $reason, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }

    /**
     * Update stored rejection reason (application must already be rejected).
     */
    public function updateRejectionReason(int $id, string $reason): bool {
        $this->ensureTable();
        $this->migrateSchema();
        $reason = trim($reason);
        if ($reason === '' || strlen($reason) > 2000) {
            return false;
        }
        $existing = $this->findById($id);
        if (!$existing) {
            return false;
        }
        if (strtolower(trim((string) ($existing['status'] ?? ''))) !== 'rejected') {
            return false;
        }
        $sql = "UPDATE `{$this->table}` SET `rejection_reason` = ? WHERE `application_id` = ? AND `status` = 'rejected' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $reason, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }

    /**
     * ADM / system admin: persist allowed columns from POST (upload paths unchanged).
     *
     * @param array<string, mixed> $post
     */
    public function updateApplicationFromStaffPost(int $applicationId, array $post, ?string &$errorMessage = null): bool {
        $existing = $this->findById($applicationId);
        if (!$existing) {
            $errorMessage = 'Application not found.';
            return false;
        }
        $data = [];
        foreach (self::getStaffEditableColumnNames() as $col) {
            if (!array_key_exists($col, $post)) {
                continue;
            }
            $raw = $post[$col];
            $s = is_scalar($raw) || $raw === null ? trim((string) $raw) : '';
            $prev = $existing[$col] ?? null;
            $res = $this->normalizeStaffEditableValue($col, $s, $prev);
            if (!$res[0]) {
                $errorMessage = $res[2] ?? 'Invalid data.';
                return false;
            }
            $data[$col] = $res[1];
        }
        if ($data === []) {
            return true;
        }
        $sqlErr = null;
        $ok = $this->update((string) $applicationId, $data, $sqlErr);
        if (!$ok) {
            $msg = $sqlErr !== null && $sqlErr !== '' ? $sqlErr : 'Could not save changes.';
            if (strpos((string) $sqlErr, '1062') !== false || stripos((string) $sqlErr, 'duplicate') !== false) {
                $msg = 'Another application already uses this NIC or email for the same NVQ level.';
            }
            $errorMessage = $msg;
            return false;
        }
        return true;
    }

    /**
     * @param mixed $previous
     * @return array{0:bool,1:mixed,2?:string} [ ok, value, errorMessage ]
     */
    private function normalizeStaffEditableValue(string $col, string $s, $previous): array {
        $prevStr = $previous === null ? '' : trim((string) $previous);
        switch ($col) {
            case 'application_level':
                if ($s === '') {
                    $fb = in_array($prevStr, ['04', '05'], true) ? $prevStr : '05';
                    return [true, $fb, ''];
                }
                if (!in_array($s, ['04', '05'], true)) {
                    return [false, null, 'NVQ level must be 04 or 05.'];
                }
                return [true, $s, ''];

            case 'status':
                if ($s === '') {
                    $fb = in_array($prevStr, ['new', 'approved', 'rejected'], true) ? $prevStr : 'new';
                    return [true, $fb, ''];
                }
                if (!in_array($s, ['new', 'approved', 'rejected'], true)) {
                    return [false, null, 'Status must be new, approved, or rejected.'];
                }
                if ($s === 'rejected') {
                    if ($prevStr === 'rejected') {
                        return [true, 'rejected', ''];
                    }
                    return [false, null, 'To reject an application, use Reject on the View page and enter a reason.'];
                }
                return [true, $s, ''];

            case 'student_title':
                if ($s === '') {
                    return [true, null, ''];
                }
                if (!in_array($s, ['Mr', 'Miss', 'Mrs'], true)) {
                    return [false, null, 'Title must be Mr, Miss, or Mrs.'];
                }
                return [true, $s, ''];

            case 'student_gender':
                if ($s === '') {
                    return [true, null, ''];
                }
                if (!in_array($s, ['Male', 'Female', 'Other'], true)) {
                    return [false, null, 'Gender must be Male, Female, or Other.'];
                }
                return [true, $s, ''];

            case 'student_civil_status':
                if ($s === '') {
                    return [true, null, ''];
                }
                if (!in_array($s, ['Single', 'Married'], true)) {
                    return [false, null, 'Civil status must be Single or Married.'];
                }
                return [true, $s, ''];

            case 'student_language':
                if ($s === '') {
                    return [true, null, ''];
                }
                if (!in_array($s, ['Tamil', 'Sinhala', 'English'], true)) {
                    return [false, null, 'Language must be Tamil, Sinhala, or English.'];
                }
                return [true, $s, ''];

            case 'student_religion':
                if ($s === '') {
                    return [true, null, ''];
                }
                if (!in_array($s, ['Hinduism', 'Buddhism', 'Islam', 'Christianity'], true)) {
                    return [false, null, 'Choose a religion from the list.'];
                }
                return [true, $s, ''];

            case 'student_blood_group':
                if ($s === '') {
                    return [true, null, ''];
                }
                $bloods = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                if (!in_array($s, $bloods, true)) {
                    return [false, null, 'Blood group must be a standard value or left blank.'];
                }
                return [true, $s, ''];

            case 'student_email':
                if ($s === '') {
                    return [true, null, ''];
                }
                if (!filter_var($s, FILTER_VALIDATE_EMAIL)) {
                    return [false, null, 'Invalid email address.'];
                }
                return [true, $s, ''];

            case 'student_nic':
                $nic = strtoupper(preg_replace('/\s+|-|_/', '', $s));
                if ($nic === '') {
                    return [false, null, 'NIC cannot be empty.'];
                }
                if (!preg_match('/^(\d{9}[VX]|\d{12})$/', $nic)) {
                    return [false, null, 'NIC must be 9 digits + V or X, or 12 digits.'];
                }
                return [true, $nic, ''];

            case 'student_dob':
                if ($s === '') {
                    return [true, null, ''];
                }
                $d = \DateTimeImmutable::createFromFormat('Y-m-d', $s);
                if ($d === false || $d->format('Y-m-d') !== $s) {
                    return [false, null, 'Date of birth must be YYYY-MM-DD.'];
                }
                return [true, $s, ''];

            case 'ol_exam_year':
            case 'al_exam_year':
            case 'nvq_year_completed':
                if ($s === '') {
                    return [true, null, ''];
                }
                $y = (int) $s;
                if ($col === 'nvq_year_completed') {
                    if ($y < 1900 || $y > 2100) {
                        return [false, null, 'Year must be between 1900 and 2100.'];
                    }
                } else {
                    if ($y < 1990 || $y > 2100) {
                        return [false, null, 'Exam year must be between 1990 and 2100.'];
                    }
                }
                return [true, (string) $y, ''];

            case 'student_full_name':
                if ($s === '') {
                    if ($prevStr !== '') {
                        return [true, $prevStr, ''];
                    }
                    return [false, null, 'Full name cannot be empty.'];
                }
                return [true, $s, ''];

            default:
                if ($s === '') {
                    return [true, null, ''];
                }
                return [true, $s, ''];
        }
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
        return $this->adminListCoursePreferenceFilterParts($departmentId, $courseId, 1);
    }

    /**
     * Course preference (1–3) matches `course` row for list / export filters.
     *
     * @return array{active: bool, join: string, whereSuffix: string, suffixTypes: string, suffixParams: list<string>}
     */
    private function adminListCoursePreferenceFilterParts(?string $departmentId, ?string $courseId, int $priority = 1): array {
        $dept = $departmentId !== null ? trim((string) $departmentId) : '';
        $crs = $courseId !== null ? trim((string) $courseId) : '';
        if ($dept === '' && $crs === '') {
            return ['active' => false, 'join' => '', 'whereSuffix' => '', 'suffixTypes' => '', 'suffixParams' => []];
        }
        $priority = $this->normalizeCoursePriority($priority);
        $fp = 'course_priority_' . $priority;
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $join = ' INNER JOIN `course` sa_fc ON ('
            . self::sqlTrimUtf8mb4('sa_fc', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('sa_fc', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ')';
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
     * Dept/course filter on selected preference, or (for 2nd/3rd choice only) require that preference to be filled.
     *
     * @return array{active: bool, join: string, whereSuffix: string, suffixTypes: string, suffixParams: list<string>}
     */
    private function adminListCoursePriorityScopeParts(?string $departmentId, ?string $courseId, int $coursePriority): array {
        $frag = $this->adminListCoursePreferenceFilterParts($departmentId, $courseId, $coursePriority);
        if ($frag['active']) {
            return $frag;
        }
        $priority = $this->normalizeCoursePriority($coursePriority);
        if ($priority === 2 || $priority === 3) {
            $fp = 'course_priority_' . $priority;

            return [
                'active' => true,
                'join' => '',
                'whereSuffix' => ' AND TRIM(IFNULL(`sa`.`' . $fp . '`,\'\')) <> \'\'',
                'suffixTypes' => '',
                'suffixParams' => [],
            ];
        }

        return $frag;
    }

    /**
     * EXISTS scope for dashboard/export — dept/course on preference, or filled 2nd/3rd choice.
     *
     * @return array{sql: string, types: string, params: list<string>}
     */
    private function adminExportCoursePriorityScopeParts(?string $departmentId, ?string $courseId, int $priority): array {
        $parts = $this->adminExportCoursePreferenceExistsParts($departmentId, $courseId, $priority);
        if ($parts['sql'] !== '') {
            return $parts;
        }
        $priority = $this->normalizeCoursePriority($priority);
        if ($priority === 2 || $priority === 3) {
            $fp = 'course_priority_' . $priority;

            return ['sql' => ' AND TRIM(IFNULL(sa.`' . $fp . '`,\'\')) <> \'\'', 'types' => '', 'params' => []];
        }

        return $parts;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function enrichAdminListRowsWithCourseChoices(array $rows): array {
        foreach ($rows as &$row) {
            for ($n = 1; $n <= 3; $n++) {
                $row['course_choice_' . $n] = self::displayCourseNameFromStoredPreference((string) ($row['course_priority_' . $n] ?? ''));
            }
        }
        unset($row);

        return $rows;
    }

    private function normalizeCoursePriority(?int $priority): int {
        $p = (int) ($priority ?? 1);
        return in_array($p, [1, 2, 3], true) ? $p : 1;
    }

    /**
     * SQL expression: display label for a stored course_priority_N value.
     */
    private function sqlCoursePreferenceLabelExpr(int $priority, string $tableAlias = 'sa'): string {
        $priority = $this->normalizeCoursePriority($priority);
        $fp = 'course_priority_' . $priority;
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $col = $tableAlias !== '' ? $tableAlias . '.`' . $fp . '`' : '`' . $fp . '`';
        return "COALESCE(NULLIF(TRIM(IF(LOCATE(CONVERT('{$sepEsc}' USING utf8mb4), TRIM(CONVERT({$col} USING utf8mb4))) > 0, "
            . "SUBSTRING_INDEX(TRIM(CONVERT({$col} USING utf8mb4)), CONVERT('{$sepEsc}' USING utf8mb4), -1), TRIM(CONVERT({$col} USING utf8mb4)))), ''), '(Not specified)')";
    }

    /**
     * EXISTS (…) for staff export / dashboard filters (avoids extra JOIN aliases on `sa`).
     *
     * @return array{sql: string, types: string, params: list<string>}
     */
    private function adminExportFirstPreferenceExistsParts(?string $departmentId, ?string $courseId): array {
        return $this->adminExportCoursePreferenceExistsParts($departmentId, $courseId, 1);
    }

    /**
     * @return array{sql: string, types: string, params: list<string>}
     */
    private function adminExportCoursePreferenceExistsParts(?string $departmentId, ?string $courseId, int $priority = 1): array {
        $parts = $this->adminListCoursePreferenceFilterParts($departmentId, $courseId, $priority);
        if (!$parts['active']) {
            return ['sql' => '', 'types' => '', 'params' => []];
        }
        $priority = $this->normalizeCoursePriority($priority);
        $fp = 'course_priority_' . $priority;
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());
        $inner = '(' . self::sqlTrimUtf8mb4('sa_fc', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ' OR '
            . self::sqlLegacyCourseRowConcatLiteral('sa_fc', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ')';
        $sql = ' AND EXISTS (SELECT 1 FROM `course` sa_fc WHERE ' . $inner . $parts['whereSuffix'] . ')';
        return ['sql' => $sql, 'types' => $parts['suffixTypes'], 'params' => $parts['suffixParams']];
    }

    /**
     * Optional NIC substring filter for staff list, dashboard, and exports.
     * Search term uses digits and V/X only (other characters stripped); matches stored NIC with spaces/dashes/slashes removed.
     *
     * @return array{sql: string, types: string, params: list<string>}
     */
    private function adminNicFilterPartsFromRaw(?string $nicFilterRaw): array {
        $norm = preg_replace('/[^0-9vVxX]/', '', (string) ($nicFilterRaw ?? ''));
        if ($norm === '') {
            return ['sql' => '', 'types' => '', 'params' => []];
        }
        $norm = strtoupper($norm);
        $sql = ' AND UPPER(REPLACE(REPLACE(REPLACE(TRIM(CONVERT(`sa`.`student_nic` USING utf8mb4)), \' \', \'\'), \'-\', \'\'), \'/\', \'\')) LIKE ?';
        return ['sql' => $sql, 'types' => 's', 'params' => ['%' . $norm . '%']];
    }

    /**
     * Count applications for staff list (optional NVQ level 04 / 05; optional 1st preference department / course).
     */
    public function countListForAdmin(string $status, ?string $level = null, ?string $departmentId = null, ?string $courseId = null, bool $onlySubmittedForStaff = false, ?string $nicFilterRaw = null, int $coursePriority = 1, ?string $languageFilter = null): int {
        $this->ensureTable();
        $this->migrateSchema();
        if (!in_array($status, ['new', 'approved', 'rejected'], true)) {
            return 0;
        }
        $frag = $this->adminListCoursePriorityScopeParts($departmentId, $courseId, $coursePriority);
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
        $nicFrag = $this->adminNicFilterPartsFromRaw($nicFilterRaw);
        $sql .= $nicFrag['sql'];
        $types .= $nicFrag['types'];
        foreach ($nicFrag['params'] as $p) {
            $params[] = $p;
        }
        $langFrag = $this->adminLanguageFilterParts($languageFilter);
        $sql .= $langFrag['sql'];
        $types .= $langFrag['types'];
        foreach ($langFrag['params'] as $p) {
            $params[] = $p;
        }
        if ($onlySubmittedForStaff) {
            $sql .= ' AND (' . self::sqlStaffAffairsListPredicate('sa') . ')';
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
    public function getListPageForAdmin(string $status, ?string $level, int $page, int $perPage, ?string $departmentId = null, ?string $courseId = null, bool $onlySubmittedForStaff = false, ?string $nicFilterRaw = null, int $coursePriority = 1, ?string $languageFilter = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        if (!in_array($status, ['new', 'approved', 'rejected'], true)) {
            return [];
        }
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $frag = $this->adminListCoursePriorityScopeParts($departmentId, $courseId, $coursePriority);
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
        $nicFrag = $this->adminNicFilterPartsFromRaw($nicFilterRaw);
        $sql .= $nicFrag['sql'];
        $types .= $nicFrag['types'];
        foreach ($nicFrag['params'] as $p) {
            $params[] = $p;
        }
        $langFrag = $this->adminLanguageFilterParts($languageFilter);
        $sql .= $langFrag['sql'];
        $types .= $langFrag['types'];
        foreach ($langFrag['params'] as $p) {
            $params[] = $p;
        }
        if ($onlySubmittedForStaff) {
            $sql .= ' AND (' . self::sqlStaffAffairsListPredicate('sa') . ')';
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

        return $this->enrichAdminListRowsWithCourseChoices($rows);
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
     * Public lookup for one application by NIC and NVQ level (04 / 05).
     *
     * @return array<string, mixed>|null
     */
    public function findByNicAndLevel(string $nic, string $level): ?array {
        $this->ensureTable();
        $this->migrateSchema();
        $nic = strtoupper(preg_replace('/\s+|-|_/', '', trim($nic)));
        if (!preg_match('/^(\d{9}[VX]|\d{12})$/', $nic) || !in_array($level, ['04', '05'], true)) {
            return null;
        }
        $sql = 'SELECT ' . self::APPLICATION_DETAIL_SELECT . " FROM `{$this->table}` WHERE `student_nic` = ? AND `application_level` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $nic, $level);
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

    /** @var array<string, array{department_id: string, department_name: string, course_name: string}> */
    private static $resolveCourseDeptCache = [];

    /** @var list<array<string, mixed>>|null */
    private static $courseCatalogCache = null;

    /**
     * Resolve department + course name from stored `course_priority_N` (course name only, or legacy "id — name").
     * Falls back to whitespace-normalized and token match so labels like
     * "Technician in Automotive Technology" map to catalog "Automotive Technician" / AUT (not GEN).
     *
     * @return array{department_id: string, department_name: string, course_name: string}
     */
    public function resolveCourseDepartmentForPreference(?string $stored, ?string $applicationLevel = null): array {
        $this->ensureTable();
        $stored = trim((string) $stored);
        $empty = ['department_id' => '', 'department_name' => '', 'course_name' => ''];
        if ($stored === '') {
            return $empty;
        }
        $levelKey = trim((string) $applicationLevel);
        $cacheKey = $stored . "\0" . $levelKey;
        if (isset(self::$resolveCourseDeptCache[$cacheKey])) {
            return self::$resolveCourseDeptCache[$cacheKey];
        }

        $displayName = self::displayCourseNameFromStoredPreference($stored);
        $sep = self::legacyCourseIdNameSeparator();
        $sql = 'SELECT c.`department_id`, c.`course_name`, d.`department_name` FROM `course` c '
            . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id` '
            . 'WHERE ' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimBoundUtf8mb4() . ' '
            . 'OR ' . self::sqlLegacyCourseRowConcatBound('c') . ' = ' . self::sqlTrimBoundUtf8mb4() . ' '
            . 'OR ' . self::sqlTrimUtf8mb4('c', 'course_id') . ' = ' . self::sqlTrimBoundUtf8mb4() . ' '
            . 'LIMIT 1';
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssss', $stored, $sep, $stored, $stored);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            $mapped = self::mapResolvedCourseRow($row, $displayName);
            if ($mapped !== null) {
                return self::$resolveCourseDeptCache[$cacheKey] = $mapped;
            }
        }

        $fuzzy = $this->resolveCourseDepartmentByFuzzyName($displayName !== '' ? $displayName : $stored, $applicationLevel);
        if ($fuzzy !== null) {
            return self::$resolveCourseDeptCache[$cacheKey] = $fuzzy;
        }

        return self::$resolveCourseDeptCache[$cacheKey] = [
            'department_id' => '',
            'department_name' => '',
            'course_name' => $displayName,
        ];
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array{department_id: string, department_name: string, course_name: string}|null
     */
    private static function mapResolvedCourseRow(?array $row, string $fallbackCourseName): ?array {
        if ($row === null) {
            return null;
        }
        $did = trim((string) ($row['department_id'] ?? ''));
        $cn = preg_replace('/\s+/u', ' ', trim((string) ($row['course_name'] ?? '')));
        $dn = trim((string) ($row['department_name'] ?? ''));
        if ($cn === '' && $dn === '' && $did === '') {
            return null;
        }

        return [
            'department_id' => $did,
            'department_name' => $dn,
            'course_name' => $cn !== '' ? $cn : $fallbackCourseName,
        ];
    }

    /**
     * Match preference text to a catalog course when labels differ slightly
     * (extra words, double spaces, NVQ wording variants).
     *
     * @return array{department_id: string, department_name: string, course_name: string}|null
     */
    private function resolveCourseDepartmentByFuzzyName(string $preferenceName, ?string $applicationLevel = null): ?array {
        $prefNorm = self::normalizeCourseLabel($preferenceName);
        $prefTokens = self::significantCourseTokens($preferenceName);
        if ($prefNorm === '' && $prefTokens === []) {
            return null;
        }

        $catalog = $this->courseCatalogForResolve();
        if ($catalog === []) {
            return null;
        }

        $expectedNvq = self::courseNvqLevelFromApplicationLevel($applicationLevel);
        $best = null;
        $bestScore = 0.0;
        foreach ($catalog as $row) {
            // Never map a Level 04 preference onto a Level 05 catalog course (or vice versa).
            if ($expectedNvq !== null) {
                $rowNvq = trim((string) ($row['course_nvq_level'] ?? ''));
                if ($rowNvq !== '' && $rowNvq !== $expectedNvq) {
                    continue;
                }
            }

            $courseName = trim((string) ($row['course_name'] ?? ''));
            $courseNorm = (string) ($row['_norm'] ?? '');
            if ($courseNorm === '') {
                continue;
            }
            $score = 0.0;
            if ($prefNorm !== '' && $courseNorm === $prefNorm) {
                $score = 100.0;
            } else {
                /** @var list<string> $courseTokens */
                $courseTokens = $row['_tokens'] ?? [];
                if ($prefTokens === [] || $courseTokens === []) {
                    continue;
                }
                $overlap = count(array_intersect($prefTokens, $courseTokens));
                if ($overlap < 1) {
                    continue;
                }
                $union = count(array_unique(array_merge($prefTokens, $courseTokens)));
                $jaccard = $union > 0 ? ($overlap / $union) : 0.0;
                // Require a meaningful overlap (e.g. Automobile Technician ↔ Technician in Automotive Technology).
                if ($overlap < 2 && $jaccard < 0.5) {
                    continue;
                }
                $score = ($jaccard * 80.0) + ($overlap * 5.0);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        if ($best === null || $bestScore < 40.0) {
            return null;
        }

        return self::mapResolvedCourseRow($best, $preferenceName);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function courseCatalogForResolve(): array {
        if (self::$courseCatalogCache !== null) {
            return self::$courseCatalogCache;
        }
        self::$courseCatalogCache = [];
        $sql = 'SELECT c.`course_id`, c.`department_id`, c.`course_name`, c.`course_nvq_level`, d.`department_name` '
            . 'FROM `course` c '
            . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id`';
        $res = $this->db->query($sql);
        if (!$res) {
            return self::$courseCatalogCache;
        }
        while ($row = $res->fetch_assoc()) {
            $courseName = trim((string) ($row['course_name'] ?? ''));
            $row['_norm'] = self::normalizeCourseLabel($courseName);
            $row['_tokens'] = self::significantCourseTokens($courseName);
            self::$courseCatalogCache[] = $row;
        }
        $res->free();

        return self::$courseCatalogCache;
    }

    /**
     * Map application level 04/05 → course.course_nvq_level 4/5.
     */
    private static function courseNvqLevelFromApplicationLevel(?string $applicationLevel): ?string {
        $level = trim((string) $applicationLevel);
        if ($level === '04' || $level === '4') {
            return '4';
        }
        if ($level === '05' || $level === '5') {
            return '5';
        }

        return null;
    }

    private static function normalizeCourseLabel(string $label): string {
        $label = mb_strtolower(trim($label), 'UTF-8');
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;
        // Applications often say "Automotive"; catalog may say "Automobile".
        $label = str_replace('automotive', 'automobile', $label);

        return $label;
    }

    /**
     * @return list<string>
     */
    private static function significantCourseTokens(string $label): array {
        $label = self::normalizeCourseLabel($label);
        if ($label === '') {
            return [];
        }
        $parts = preg_split('/[^a-z0-9]+/u', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = [
            'in' => true, 'and' => true, 'the' => true, 'of' => true, 'a' => true, 'an' => true,
            'for' => true, 'to' => true, 'with' => true, 'nvq' => true, 'level' => true,
        ];
        $out = [];
        foreach ($parts as $part) {
            if (isset($stop[$part]) || strlen($part) < 2) {
                continue;
            }
            $out[$part] = $part;
        }

        return array_values($out);
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
     * Human-readable Excel / report headers for staff export columns.
     *
     * @return array<string, string>
     */
    public static function getStaffExportColumnLabels(): array {
        return [
            'application_id' => 'Application ID',
            'application_level' => 'NVQ Level',
            'student_title' => 'Title',
            'student_full_name' => 'Full Name',
            'student_initial_name' => 'Name with Initials',
            'student_gender' => 'Gender',
            'student_civil_status' => 'Civil Status',
            'student_email' => 'Email',
            'student_phone' => 'Phone',
            'student_whatsapp' => 'WhatsApp',
            'student_nic' => 'NIC',
            'student_dob' => 'Date of Birth',
            'student_language' => 'Language',
            'student_religion' => 'Religion',
            'student_blood_group' => 'Blood Group',
            'student_address' => 'Address',
            'student_zip_code' => 'Postal Code',
            'student_district' => 'District',
            'student_province' => 'Province',
            'department_1' => '1st Choice — Department',
            'department_2' => '2nd Choice — Department',
            'department_3' => '3rd Choice — Department',
            'course_1' => '1st Choice — Course',
            'course_2' => '2nd Choice — Course',
            'course_3' => '3rd Choice — Course',
            'ol_index_number' => 'O/L Index No.',
            'ol_exam_year' => 'O/L Year',
            'ol_subject_name_01' => 'O/L Subject 1',
            'ol_subject_01_marks' => 'O/L Marks 1',
            'ol_subject_name_02' => 'O/L Subject 2',
            'ol_subject_02_marks' => 'O/L Marks 2',
            'ol_subject_name_03' => 'O/L Subject 3',
            'ol_subject_03_marks' => 'O/L Marks 3',
            'ol_subject_name_04' => 'O/L Subject 4',
            'ol_subject_04_marks' => 'O/L Marks 4',
            'ol_subject_name_05' => 'O/L Subject 5',
            'ol_subject_05_marks' => 'O/L Marks 5',
            'ol_subject_name_06' => 'O/L Subject 6',
            'ol_subject_06_marks' => 'O/L Marks 6',
            'ol_subject_name_07' => 'O/L Subject 7',
            'ol_subject_07_marks' => 'O/L Marks 7',
            'ol_subject_name_08' => 'O/L Subject 8',
            'ol_subject_08_marks' => 'O/L Marks 8',
            'ol_subject_name_09' => 'O/L Subject 9',
            'ol_subject_09_marks' => 'O/L Marks 9',
            'al_index_number' => 'A/L Index No.',
            'al_exam_year' => 'A/L Year',
            'al_stream' => 'A/L Stream',
            'al_subject_name_01' => 'A/L Subject 1',
            'al_subject_01_marks' => 'A/L Marks 1',
            'al_subject_name_02' => 'A/L Subject 2',
            'al_subject_02_marks' => 'A/L Marks 2',
            'al_subject_name_03' => 'A/L Subject 3',
            'al_subject_03_marks' => 'A/L Marks 3',
            'nvq_level' => 'NVQ Level (prior)',
            'nvq_course_name' => 'NVQ Course',
            'nvq_institute_name' => 'NVQ Institute',
            'nvq_year_completed' => 'NVQ Year',
            'status' => 'Status',
            'created_at' => 'Submitted',
        ];
    }

    /**
     * Dashboard aggregates. Optional NVQ level and course-preference department/course match the staff list filters.
     *
     * @return array{total: int, by_status: array{new: int, approved: int, rejected: int}, by_level: list<array{level: string, count: int}>, by_district: list<array{label: string, count: int}>, by_course: list<array{label: string, count: int}>, by_department: list<array{label: string, count: int}>, by_gender: list<array{label: string, count: int}>, by_course_priority: array<int, array{course: list<array{label: string, count: int}>, department: list<array{label: string, count: int}>}>}
     */
    public function getDashboardStats(?string $level = null, ?string $departmentId = null, ?string $courseId = null, bool $onlySubmittedForStaff = false, ?string $nicFilterRaw = null, int $coursePriority = 1, ?string $languageFilter = null): array {
        $this->ensureTable();
        $this->migrateSchema();
        $coursePriority = $this->normalizeCoursePriority($coursePriority);
        $out = [
            'total' => 0,
            'by_status' => ['new' => 0, 'approved' => 0, 'rejected' => 0],
            'by_level' => [],
            'by_district' => [],
            'by_course' => [],
            'by_department' => [],
            'by_gender' => [],
            'by_course_priority' => [
                1 => ['course' => [], 'department' => []],
                2 => ['course' => [], 'department' => []],
                3 => ['course' => [], 'department' => []],
            ],
            'filter_course_priority' => $coursePriority,
        ];
        $t = $this->table;
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());

        $existsPart = $this->adminExportCoursePriorityScopeParts($departmentId, $courseId, $coursePriority);
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
        $nicPart = $this->adminNicFilterPartsFromRaw($nicFilterRaw);
        $filterTail .= $nicPart['sql'];
        $filterTypes .= $nicPart['types'];
        foreach ($nicPart['params'] as $np) {
            $filterParams[] = $np;
        }
        $langPart = $this->adminLanguageFilterParts($languageFilter);
        $filterTail .= $langPart['sql'];
        $filterTypes .= $langPart['types'];
        foreach ($langPart['params'] as $lp) {
            $filterParams[] = $lp;
        }
        if ($onlySubmittedForStaff) {
            $filterTail .= ' AND (' . self::sqlStaffAffairsListPredicate('sa') . ')';
        }
        $filtered = ($levelPart !== '' || $existsPart['sql'] !== '' || $nicPart['sql'] !== '' || $langPart['sql'] !== '');

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
            $dw = $onlySubmittedForStaff
                ? ' WHERE (' . self::sqlStaffAffairsListPredicate(null) . ')'
                : '';
            $dwSa = $onlySubmittedForStaff
                ? ' WHERE (' . self::sqlStaffAffairsListPredicate('sa') . ')'
                : '';
            $res = $this->db->query("SELECT COUNT(*) AS `c` FROM `{$t}`{$dw}");
            if ($res && $row = $res->fetch_assoc()) {
                $out['total'] = (int) ($row['c'] ?? 0);
            }
            $res = $this->db->query("SELECT `status`, COUNT(*) AS `cnt` FROM `{$t}`{$dw} GROUP BY `status`");
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
            $sqlLevel = "SELECT TRIM(`application_level`) AS `lvl`, COUNT(*) AS `cnt` FROM `{$t}`{$dw} GROUP BY `lvl` ORDER BY `lvl` ASC";
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
                . "FROM `{$t}`{$dw} GROUP BY `lbl` ORDER BY (`lbl` = '(Not specified)'), `lbl` ASC";
            $res = $this->db->query($sqlDist);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out['by_district'][] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }
            $sqlGender = "SELECT COALESCE(NULLIF(TRIM(`student_gender`), ''), '(Not specified)') AS `lbl`, COUNT(*) AS `cnt` "
                . "FROM `{$t}`{$dw} GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC";
            $res = $this->db->query($sqlGender);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out['by_gender'][] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }
            $this->populateDashboardCoursePriorityBreakdown($out, $coursePriority, function (string $sql) {
                return $this->db->query($sql);
            }, $t, $dw, $dwSa, false);
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
        $sqlGender = 'SELECT COALESCE(NULLIF(TRIM(sa.`student_gender`), \'\'), \'(Not specified)\') AS `lbl`, COUNT(*) AS `cnt` '
            . "FROM `{$t}` sa" . $filterTail . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC';
        $res = $runFiltered($sqlGender);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out['by_gender'][] = [
                    'label' => (string) ($row['lbl'] ?? ''),
                    'count' => (int) ($row['cnt'] ?? 0),
                ];
            }
        }
        $this->populateDashboardCoursePriorityBreakdown($out, $coursePriority, $runFiltered, $t, $filterTail, '', true);

        return $out;
    }

    /**
     * Fill by_course_priority (1st / 2nd / 3rd choice) and mirror active filter into by_course / by_department.
     *
     * @param callable(string): mysqli_result|false $runQuery
     */
    private function populateDashboardCoursePriorityBreakdown(
        array &$out,
        int $filterPriority,
        callable $runQuery,
        string $table,
        string $whereSuffix,
        string $saWhereSuffix,
        bool $useSaAlias
    ): void {
        $filterPriority = $this->normalizeCoursePriority($filterPriority);
        $conn = $this->db->getConnection();
        $sepEsc = $conn->real_escape_string(self::legacyCourseIdNameSeparator());

        for ($p = 1; $p <= 3; $p++) {
            $fp = 'course_priority_' . $p;
            if ($useSaAlias) {
                $labelExpr = $this->sqlCoursePreferenceLabelExpr($p, 'sa');
                $sqlCourse = 'SELECT ' . $labelExpr . " AS `lbl`, COUNT(*) AS `cnt` FROM `{$table}` sa" . $whereSuffix
                    . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40';
                $sqlDept = "SELECT COALESCE(NULLIF(TRIM(d.`department_name`), ''), '(Not matched)') AS `lbl`, COUNT(*) AS `cnt` "
                    . "FROM `{$table}` sa "
                    . 'LEFT JOIN `course` c ON (' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ' OR '
                    . self::sqlLegacyCourseRowConcatLiteral('c', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ') '
                    . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id` '
                    . $whereSuffix . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40';
            } else {
                $labelExpr = $this->sqlCoursePreferenceLabelExpr($p, '');
                $sqlCourse = 'SELECT ' . $labelExpr . " AS `lbl`, COUNT(*) AS `cnt` FROM `{$table}`" . $whereSuffix
                    . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40';
                $sqlDept = "SELECT COALESCE(NULLIF(TRIM(d.`department_name`), ''), '(Not matched)') AS `lbl`, COUNT(*) AS `cnt` "
                    . "FROM `{$table}` sa "
                    . 'LEFT JOIN `course` c ON (' . self::sqlTrimUtf8mb4('c', 'course_name') . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ' OR '
                    . self::sqlLegacyCourseRowConcatLiteral('c', $sepEsc) . ' = ' . self::sqlTrimUtf8mb4('sa', $fp) . ') '
                    . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id` '
                    . ($saWhereSuffix !== '' ? $saWhereSuffix : $whereSuffix)
                    . ' GROUP BY `lbl` ORDER BY `cnt` DESC, `lbl` ASC LIMIT 40';
            }

            $courses = [];
            $res = $runQuery($sqlCourse);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $courses[] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }

            $departments = [];
            $res = $runQuery($sqlDept);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $departments[] = [
                        'label' => (string) ($row['lbl'] ?? ''),
                        'count' => (int) ($row['cnt'] ?? 0),
                    ];
                }
            }

            if ($p === 1) {
                $courses = array_values(array_filter($courses, static function (array $row): bool {
                    return ($row['label'] ?? '') !== '(Not specified)';
                }));
                $departments = array_values(array_filter($departments, static function (array $row): bool {
                    return ($row['label'] ?? '') !== '(Not matched)';
                }));
            }

            $out['by_course_priority'][$p] = ['course' => $courses, 'department' => $departments];
        }

        $out['by_course'] = $out['by_course_priority'][$filterPriority]['course'] ?? [];
        $out['by_department'] = $out['by_course_priority'][$filterPriority]['department'] ?? [];
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
    public function getAllForStaffExport(?string $status = null, ?string $level = null, ?string $departmentId = null, ?string $courseId = null, bool $onlySubmittedForStaff = false, ?string $nicFilterRaw = null, int $coursePriority = 1, ?string $languageFilter = null): array {
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
        $existsParts = $this->adminExportCoursePriorityScopeParts($departmentId, $courseId, $coursePriority);
        $sql .= $existsParts['sql'];
        $types .= $existsParts['types'];
        foreach ($existsParts['params'] as $ep) {
            $params[] = $ep;
        }
        if ($onlySubmittedForStaff) {
            $sql .= ' AND (' . self::sqlStaffAffairsListPredicate('sa') . ')';
        }
        $nicFrag = $this->adminNicFilterPartsFromRaw($nicFilterRaw);
        $sql .= $nicFrag['sql'];
        $types .= $nicFrag['types'];
        foreach ($nicFrag['params'] as $p) {
            $params[] = $p;
        }
        $langFrag = $this->adminLanguageFilterParts($languageFilter);
        $sql .= $langFrag['sql'];
        $types .= $langFrag['types'];
        foreach ($langFrag['params'] as $p) {
            $params[] = $p;
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
     * Public URL for a stored path under `uploads/student_applications/` (Level 05 + MVC) or legacy `uploads/students_applications/…`.
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
        } elseif (preg_match('#(^|/)uploads/students_applications/(.+)$#i', $s, $m)) {
            $s = 'uploads/students_applications/' . $m[2];
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
