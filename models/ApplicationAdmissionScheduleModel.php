<?php
/**
 * Entrance exam & interview schedules for online student applications.
 */

class ApplicationAdmissionScheduleModel extends Model {
    protected $table = 'application_admission_schedule';
    private static $tablesEnsured = false;

    public const TYPE_ENTRANCE = 'entrance_exam';
    public const TYPE_INTERVIEW = 'interview';

    public const PATHWAY_EXAM_AND_INTERVIEW = 'exam_and_interview';
    public const PATHWAY_INTERVIEW_ONLY = 'interview_only';

    /** Above this approved-application count, default pathway is exam + interview. */
    public const INTERVIEW_ONLY_DEFAULT_MAX_APPLICANTS = 20;

    public const SELECTION_SCHEDULED = 'scheduled';
    public const SELECTION_SELECTED = 'selected';
    public const SELECTION_NOT_SELECTED = 'not_selected';
    public const SELECTION_WAITLIST = 'waitlist';

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
        $this->migrateSchema();
    }

    public function ensureTables(): void {
        if (self::$tablesEnsured) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/application_admission_schedules.sql';
        if (!is_readable($sqlFile)) {
            self::$tablesEnsured = true;
            return;
        }
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            self::$tablesEnsured = true;
            return;
        }
        try {
            $conn = $this->db->getConnection();
            $conn->multi_query($sql);
            while ($conn->more_results() && $conn->next_result()) {
                /* flush */
            }
        } catch (Throwable $e) {
            error_log('ApplicationAdmissionScheduleModel::ensureTables: ' . $e->getMessage());
        }
        self::$tablesEnsured = true;
    }

    protected function getPrimaryKey() {
        return 'schedule_id';
    }

    public function migrateSchema(): void {
        try {
            $conn = $this->db->getConnection();
            $tbl = $conn->query("SHOW TABLES LIKE '{$this->table}'");
            if (!$tbl || $tbl->num_rows === 0) {
                if ($tbl) {
                    $tbl->free();
                }
                return;
            }
            $tbl->free();

            $col = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'course_id'");
            $has = $col && $col->num_rows > 0;
            if ($col) {
                $col->free();
            }
            if (!$has) {
                if (!$conn->query(
                    "ALTER TABLE `{$this->table}` ADD COLUMN `course_id` VARCHAR(50) DEFAULT NULL "
                    . "COMMENT 'Entrance/interview scoped to one NVQ course; NULL = all courses at level' "
                    . "AFTER `application_level`"
                )) {
                    error_log('ApplicationAdmissionScheduleModel::migrateSchema course_id: ' . $conn->error);
                }
            }
            $colWa = $conn->query("SHOW COLUMNS FROM `application_admission_schedule_entry` LIKE 'whatsapp_sent'");
            $hasWa = $colWa && $colWa->num_rows > 0;
            if ($colWa) {
                $colWa->free();
            }
            if (!$hasWa) {
                if (!$conn->query(
                    "ALTER TABLE `application_admission_schedule_entry` ADD COLUMN `whatsapp_sent` TINYINT(1) NOT NULL DEFAULT 0 "
                    . "COMMENT 'Staff marked schedule link sent via WhatsApp' AFTER `notes`"
                )) {
                    error_log('ApplicationAdmissionScheduleModel::migrateSchema whatsapp_sent: ' . $conn->error);
                }
            }
            $colPath = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'admission_pathway'");
            $hasPath = $colPath && $colPath->num_rows > 0;
            if ($colPath) {
                $colPath->free();
            }
            if (!$hasPath) {
                if (!$conn->query(
                    "ALTER TABLE `{$this->table}` ADD COLUMN `admission_pathway` ENUM('exam_and_interview','interview_only') NOT NULL DEFAULT 'exam_and_interview' "
                    . "COMMENT 'exam_and_interview = entrance then interview; interview_only = direct interview' "
                    . "AFTER `course_id`"
                )) {
                    error_log('ApplicationAdmissionScheduleModel::migrateSchema admission_pathway: ' . $conn->error);
                }
            }
        } catch (Throwable $e) {
            error_log('ApplicationAdmissionScheduleModel::migrateSchema: ' . $e->getMessage());
        }
    }

    public static function generatePublicToken(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Roll / index label: course code + sequential number (e.g. ICT-001).
     */
    public static function formatRollIndex(string $courseCode, int $sequence): string {
        $sequence = max(1, $sequence);
        $code = strtoupper(preg_replace('/[^A-Za-z0-9._-]+/', '', trim($courseCode)));
        $num = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        if ($code === '') {
            return $num;
        }

        return $code . '-' . $num;
    }

    /**
     * @param array<string, mixed> $schedule
     */
    public static function rollIndexCourseCodeFromSchedule(array $schedule): string {
        $cid = trim((string) ($schedule['course_id'] ?? ''));
        if ($cid !== '') {
            return $cid;
        }
        $level = trim((string) ($schedule['application_level'] ?? ''));
        if (in_array($level, ['04', '05'], true)) {
            return 'LV' . $level;
        }

        return 'APP';
    }

    /**
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $entry
     */
    public static function defaultRollIndexForEntry(array $schedule, array $entry, int $sequence): string {
        $existing = trim((string) ($entry['roll_number'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        return self::formatRollIndex(self::rollIndexCourseCodeFromSchedule($schedule), $sequence);
    }

    public static function defaultPathwayForApplicationCount(int $count): string {
        return $count > self::INTERVIEW_ONLY_DEFAULT_MAX_APPLICANTS
            ? self::PATHWAY_EXAM_AND_INTERVIEW
            : self::PATHWAY_INTERVIEW_ONLY;
    }

    public static function pathwayLabel(string $pathway): string {
        $labels = [
            self::PATHWAY_EXAM_AND_INTERVIEW => 'Exam and interview',
            self::PATHWAY_INTERVIEW_ONLY => 'Interview only',
        ];

        return $labels[$pathway] ?? $pathway;
    }

    public static function normalizePathway(?string $pathway, string $default = self::PATHWAY_EXAM_AND_INTERVIEW): string {
        $pathway = strtolower(trim((string) $pathway));
        if ($pathway === '') {
            return $default;
        }
        $valid = [self::PATHWAY_EXAM_AND_INTERVIEW, self::PATHWAY_INTERVIEW_ONLY];

        return in_array($pathway, $valid, true) ? $pathway : $default;
    }

    /**
     * Approved online applications whose 1st preference matches the course.
     */
    public function countApprovedApplicationsForCourse(string $level, string $courseId): int {
        $this->ensureTables();
        $courseId = trim($courseId);
        if ($courseId === '' || !in_array($level, ['04', '05'], true)) {
            return 0;
        }
        require_once BASE_PATH . '/models/CourseModel.php';
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        $course = (new CourseModel())->find($courseId);
        if (!$course) {
            return 0;
        }
        $sql = 'SELECT sa.`application_id`, sa.`course_priority_1` FROM `student_applications` sa '
            . 'WHERE sa.`application_level` = ? AND sa.`status` = ?';
        $rows = $this->fetchAllPrepared($sql, 'ss', [$level, 'approved']);
        $appModel = new StudentApplicationModel();
        $n = 0;
        foreach ($rows as $row) {
            if (self::applicationMatchesCourse($row, $courseId, $course, $appModel)) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSchedules(?string $scheduleType = null, ?string $level = null): array {
        $this->ensureTables();
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $types = '';
        $params = [];
        if ($scheduleType !== null && in_array($scheduleType, [self::TYPE_ENTRANCE, self::TYPE_INTERVIEW], true)) {
            $sql .= ' AND `schedule_type` = ?';
            $types .= 's';
            $params[] = $scheduleType;
        }
        if ($level !== null && in_array($level, ['04', '05'], true)) {
            $sql .= ' AND `application_level` = ?';
            $types .= 's';
            $params[] = $level;
        }
        $sql .= ' ORDER BY `schedule_date` DESC, `schedule_id` DESC';
        $rows = $this->fetchAllPrepared($sql, $types, $params);
        return $this->attachCourseNames($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function attachCourseNames(array $rows): array {
        if ($rows === []) {
            return $rows;
        }
        require_once BASE_PATH . '/models/CourseModel.php';
        $courseModel = new CourseModel();
        foreach ($rows as &$row) {
            $cid = trim((string) ($row['course_id'] ?? ''));
            if ($cid === '') {
                $row['course_name'] = '';
                continue;
            }
            $course = $courseModel->find($cid);
            $row['course_name'] = $course ? trim((string) ($course['course_name'] ?? '')) : $cid;
        }
        unset($row);
        return $rows;
    }

    public function findSchedule(int $id): ?array {
        $this->ensureTables();
        $sql = "SELECT * FROM `{$this->table}` WHERE `schedule_id` = ? LIMIT 1";
        $rows = $this->attachCourseNames($this->fetchAllPrepared($sql, 'i', [$id]));
        return $rows[0] ?? null;
    }

    public function findByPublicToken(string $token): ?array {
        $this->ensureTables();
        $token = trim($token);
        if ($token === '' || strlen($token) > 64) {
            return null;
        }
        $sql = "SELECT * FROM `{$this->table}` WHERE `public_token` = ? AND `is_published` = 1 LIMIT 1";
        $rows = $this->attachCourseNames($this->fetchAllPrepared($sql, 's', [$token]));
        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSchedule(array $data, ?string &$error = null): ?int {
        $this->ensureTables();
        foreach (['start_time', 'end_time'] as $k) {
            if (array_key_exists($k, $data) && trim((string) $data[$k]) === '') {
                unset($data[$k]);
            }
        }
        if (empty($data['public_token'])) {
            $data['public_token'] = self::generatePublicToken();
        }
        if (array_key_exists('course_id', $data) && trim((string) $data['course_id']) === '') {
            unset($data['course_id']);
        }
        $id = $this->create($data, $error);
        return $id === false ? null : (int) $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSchedule(int $id, array $data): bool {
        $this->ensureTables();
        unset($data['schedule_id'], $data['public_token'], $data['created_at']);
        if (array_key_exists('course_id', $data) && trim((string) $data['course_id']) === '') {
            $conn = $this->db->getConnection();
            $conn->query("UPDATE `{$this->table}` SET `course_id` = NULL WHERE `schedule_id` = " . (int) $id . ' LIMIT 1');
            unset($data['course_id']);
        }
        if ($data === []) {
            return true;
        }
        $sets = [];
        $types = '';
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $types .= is_int($val) ? 'i' : 's';
            $params[] = $val;
        }
        $params[] = $id;
        $types .= 'i';
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . ' WHERE `schedule_id` = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $this->bindParams($stmt, $types, $params);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }

    public function deleteSchedule(int $id): bool {
        $this->ensureTables();
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `schedule_id` = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }

    public function setPublished(int $id, bool $published): bool {
        return $this->updateSchedule($id, ['is_published' => $published ? 1 : 0]);
    }

    public function countEntries(int $scheduleId): int {
        $this->ensureTables();
        $sql = 'SELECT COUNT(*) AS c FROM `application_admission_schedule_entry` WHERE `schedule_id` = ?';
        $rows = $this->fetchAllPrepared($sql, 'i', [$scheduleId]);
        return (int) ($rows[0]['c'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getEntriesWithApplications(int $scheduleId): array {
        $this->ensureTables();
        $this->migrateSchema();
        $sql = 'SELECT e.*, sa.`student_full_name`, sa.`student_nic`, sa.`student_phone`, sa.`student_whatsapp`, sa.`student_email`, '
            . 'sa.`course_priority_1`, sa.`course_priority_2`, sa.`course_priority_3`, sa.`application_level`, sa.`status` AS application_status '
            . 'FROM `application_admission_schedule_entry` e '
            . 'INNER JOIN `student_applications` sa ON sa.`application_id` = e.`application_id` '
            . 'WHERE e.`schedule_id` = ? '
            . 'ORDER BY e.`sort_order` ASC, e.`roll_number` ASC, sa.`student_full_name` ASC';
        return $this->fetchAllPrepared($sql, 'i', [$scheduleId]);
    }

    /**
     * Approved applications eligible to add (same NVQ level, not already on this schedule).
     *
     * @return list<array<string, mixed>>
     */
    public function getPickerApplications(
        string $level,
        int $scheduleId,
        bool $onlySubmittedForStaff = false,
        ?string $courseId = null,
        ?string $scheduleType = null,
        ?string $admissionPathway = null
    ): array {
        $this->ensureTables();
        if (!in_array($level, ['04', '05'], true)) {
            return [];
        }
        $sql = 'SELECT sa.`application_id`, sa.`student_full_name`, sa.`student_nic`, sa.`course_priority_1`, sa.`course_priority_2`, sa.`course_priority_3`, sa.`status` '
            . 'FROM `student_applications` sa '
            . 'WHERE sa.`application_level` = ? AND sa.`status` = ? '
            . 'AND sa.`application_id` NOT IN ('
            . 'SELECT `application_id` FROM `application_admission_schedule_entry` WHERE `schedule_id` = ?'
            . ') ';
        if ($onlySubmittedForStaff) {
            $ph = addslashes('(Pending)');
            $sql .= " AND TRIM(sa.`student_full_name`) <> '' AND sa.`student_full_name` <> '{$ph}'"
                . " AND TRIM(IFNULL(sa.`nic_document_path`, '')) <> ''"
                . " AND TRIM(IFNULL(sa.`birth_certificate_path`, '')) <> '' ";
        }
        $sql .= 'ORDER BY sa.`student_full_name` ASC';
        $rows = $this->fetchAllPrepared($sql, 'ssi', [$level, 'approved', $scheduleId]);
        $courseIdTrim = $courseId !== null ? trim($courseId) : '';
        if ($courseIdTrim !== '') {
            require_once BASE_PATH . '/models/StudentApplicationModel.php';
            require_once BASE_PATH . '/models/CourseModel.php';
            $appModel = new StudentApplicationModel();
            $courseModel = new CourseModel();
            $course = $courseModel->find($courseIdTrim);
            if (!$course) {
                return [];
            }
            $rows = array_values(array_filter($rows, function (array $row) use ($appModel, $courseIdTrim, $course): bool {
                return self::applicationMatchesCourse($row, $courseIdTrim, $course, $appModel);
            }));
        }

        return $this->filterInterviewPickerRows($rows, $level, $courseIdTrim !== '' ? $courseIdTrim : null, $scheduleType, $admissionPathway);
    }

    /**
     * Application IDs marked selected on an entrance exam for this course & NVQ level.
     *
     * @return list<int>
     */
    public function getPassedEntranceApplicationIds(string $level, string $courseId): array {
        $this->ensureTables();
        $courseId = trim($courseId);
        if ($courseId === '' || !in_array($level, ['04', '05'], true)) {
            return [];
        }
        $sql = 'SELECT DISTINCT e.`application_id` '
            . 'FROM `application_admission_schedule_entry` e '
            . 'INNER JOIN `application_admission_schedule` s ON s.`schedule_id` = e.`schedule_id` '
            . 'WHERE s.`schedule_type` = ? AND s.`application_level` = ? AND s.`course_id` = ? '
            . 'AND e.`selection_status` = ? '
            . 'ORDER BY e.`application_id` ASC';
        $rows = $this->fetchAllPrepared($sql, 'ssss', [
            self::TYPE_ENTRANCE,
            $level,
            $courseId,
            self::SELECTION_SELECTED,
        ]);
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['application_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Filter interview picker to entrance-qualified applicants when the course requires an exam.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function filterInterviewPickerRows(
        array $rows,
        string $level,
        ?string $courseId,
        ?string $scheduleType,
        ?string $admissionPathway
    ): array {
        if ($scheduleType !== self::TYPE_INTERVIEW || $courseId === null || trim($courseId) === '') {
            return $rows;
        }
        $courseId = trim($courseId);
        $pathway = self::normalizePathway($admissionPathway, self::PATHWAY_EXAM_AND_INTERVIEW);
        if ($pathway === self::PATHWAY_INTERVIEW_ONLY) {
            return $rows;
        }
        $allowed = array_flip($this->getPassedEntranceApplicationIds($level, $courseId));
        if ($allowed === []) {
            return [];
        }

        return array_values(array_filter($rows, function (array $row) use ($allowed): bool {
            return isset($allowed[(int) ($row['application_id'] ?? 0)]);
        }));
    }

    /**
     * Match 1st course preference to schedule course (name or legacy id — name).
     *
     * @param array<string, mixed> $appRow
     * @param array<string, mixed>|null $courseRow
     */
    public static function applicationMatchesCourse(array $appRow, string $courseId, ?array $courseRow, ?StudentApplicationModel $appModel = null): bool {
        $stored = trim((string) ($appRow['course_priority_1'] ?? ''));
        if ($stored === '' || $courseId === '') {
            return false;
        }
        if ($courseRow === null) {
            require_once BASE_PATH . '/models/CourseModel.php';
            $courseRow = (new CourseModel())->find($courseId);
            if (!$courseRow) {
                return false;
            }
        }
        $courseName = trim((string) ($courseRow['course_name'] ?? ''));
        if ($courseName !== '' && strcasecmp($stored, $courseName) === 0) {
            return true;
        }
        $legacySep = ' ' . "\u{2014}" . ' ';
        $legacy = $courseId . $legacySep . $courseName;
        if ($stored === $legacy || $stored === $courseId . ' — ' . $courseName) {
            return true;
        }
        if ($appModel === null) {
            $appModel = new StudentApplicationModel();
        }
        $resolved = $appModel->resolveCourseDepartmentForPreference($stored);
        return $courseName !== '' && strcasecmp(trim($resolved['course_name']), $courseName) === 0;
    }

    /**
     * @param list<int> $applicationIds
     */
    public function addApplications(int $scheduleId, array $applicationIds): int {
        $this->ensureTables();
        $added = 0;
        $maxOrder = 0;
        $rows = $this->fetchAllPrepared(
            'SELECT COALESCE(MAX(`sort_order`), 0) AS m FROM `application_admission_schedule_entry` WHERE `schedule_id` = ?',
            'i',
            [$scheduleId]
        );
        $maxOrder = (int) ($rows[0]['m'] ?? 0);

        foreach ($applicationIds as $appId) {
            $appId = (int) $appId;
            if ($appId <= 0) {
                continue;
            }
            $maxOrder++;
            $stmt = $this->db->prepare(
                'INSERT IGNORE INTO `application_admission_schedule_entry` (`schedule_id`, `application_id`, `sort_order`) VALUES (?, ?, ?)'
            );
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('iii', $scheduleId, $appId, $maxOrder);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $added++;
            }
            $stmt->close();
        }
        return $added;
    }

    public function removeEntry(int $entryId, int $scheduleId): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM `application_admission_schedule_entry` WHERE `entry_id` = ? AND `schedule_id` = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $entryId, $scheduleId);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }

    /**
     * @param array<string, mixed> $fields roll_number, room_or_panel, selection_status, notes, sort_order
     */
    public function updateEntry(int $entryId, int $scheduleId, array $fields): bool {
        $allowed = ['roll_number', 'room_or_panel', 'selection_status', 'notes', 'sort_order', 'whatsapp_sent'];
        $data = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $data[$col] = $fields[$col];
            }
        }
        if ($data === []) {
            return true;
        }
        if (isset($data['selection_status'])) {
            $valid = [self::SELECTION_SCHEDULED, self::SELECTION_SELECTED, self::SELECTION_NOT_SELECTED, self::SELECTION_WAITLIST];
            if (!in_array($data['selection_status'], $valid, true)) {
                unset($data['selection_status']);
            }
        }
        $sets = [];
        $types = '';
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            if ($col === 'sort_order') {
                $types .= 'i';
                $params[] = (int) $val;
            } elseif ($col === 'whatsapp_sent') {
                $types .= 'i';
                $params[] = (int) $val ? 1 : 0;
            } else {
                $types .= 's';
                $params[] = (string) $val;
            }
        }
        $params[] = $entryId;
        $params[] = $scheduleId;
        $types .= 'ii';
        $sql = 'UPDATE `application_admission_schedule_entry` SET ' . implode(', ', $sets)
            . ' WHERE `entry_id` = ? AND `schedule_id` = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $this->bindParams($stmt, $types, $params);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('ApplicationAdmissionScheduleModel::updateEntry: ' . $stmt->error);
        }
        $stmt->close();
        return (bool) $ok;
    }

    public function setWhatsAppSent(int $entryId, int $scheduleId, bool $sent): bool {
        $this->ensureTables();
        $this->migrateSchema();
        return $this->updateEntry($entryId, $scheduleId, ['whatsapp_sent' => $sent ? 1 : 0]);
    }

    public function findEntryByNic(int $scheduleId, string $nic): ?array {
        $nic = strtoupper(preg_replace('/\s+|-|_/', '', trim($nic)));
        if ($nic === '') {
            return null;
        }
        $entries = $this->getEntriesWithApplications($scheduleId);
        foreach ($entries as $row) {
            $rowNic = strtoupper(preg_replace('/\s+|-|_/', '', (string) ($row['student_nic'] ?? '')));
            if ($rowNic === $nic) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @param list<string|int|float> $params
     * @return list<array<string, mixed>>
     */
    private function fetchAllPrepared(string $sql, string $types, array $params): array {
        if ($types === '') {
            $res = $this->db->query($sql);
            if (!$res) {
                return [];
            }
            $out = [];
            while ($row = $res->fetch_assoc()) {
                $out[] = $row;
            }
            return $out;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $this->bindParams($stmt, $types, $params);
        $stmt->execute();
        $result = $stmt->get_result();
        $out = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $out[] = $row;
            }
        }
        $stmt->close();
        return $out;
    }

    /**
     * @param list<string|int|float> $params
     */
    private function bindParams(mysqli_stmt $stmt, string $types, array $params): void {
        $refs = [];
        foreach ($params as $k => $v) {
            $refs[$k] = $params[$k];
        }
        $bind = [$types];
        foreach ($refs as $k => $_) {
            $bind[] = &$refs[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }
}
