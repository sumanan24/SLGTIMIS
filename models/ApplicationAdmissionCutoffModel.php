<?php
/**
 * Course- and medium-wise entrance exam cutoff marks
 * (Northern province vs other provinces).
 */

class ApplicationAdmissionCutoffModel extends Model {
    protected $table = 'application_admission_cutoff';
    private static $tableEnsured = false;

    public const PROVINCE_NORTHERN = 'Northern';
    public const MEDIUMS = ['Tamil', 'Sinhala', 'English'];
    /** Shared cutoff for courses that are not split by exam language. */
    public const MEDIUM_ALL = 'All';
    /** Minimum marks to be considered for 2nd / 3rd choice (below this is a fail). */
    public const MARKS_MIN_SECOND_OPTION = 30;

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
        $this->migrateSchema();
    }

    protected function getPrimaryKey() {
        return 'cutoff_id';
    }

    public function ensureTable(): void {
        if (self::$tableEnsured) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/application_admission_cutoff.sql';
        if (is_readable($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql !== false) {
                try {
                    $conn = $this->db->getConnection();
                    $conn->query($sql);
                } catch (Throwable $e) {
                    error_log('ApplicationAdmissionCutoffModel::ensureTable: ' . $e->getMessage());
                }
            }
        }
        self::$tableEnsured = true;
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

            $col = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'medium'");
            $hasMedium = $col && $col->num_rows > 0;
            if ($col) {
                $col->free();
            }
            if (!$hasMedium) {
                if (!$conn->query(
                    "ALTER TABLE `{$this->table}` ADD COLUMN `medium` VARCHAR(20) NOT NULL DEFAULT 'English' "
                    . "COMMENT 'Tamil, Sinhala, or English' AFTER `course_id`"
                )) {
                    error_log('ApplicationAdmissionCutoffModel::migrateSchema medium: ' . $conn->error);
                    return;
                }
                $oldIdx = $conn->query("SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'uq_cutoff_level_course'");
                if ($oldIdx && $oldIdx->num_rows > 0) {
                    $conn->query("ALTER TABLE `{$this->table}` DROP INDEX `uq_cutoff_level_course`");
                }
                if ($oldIdx) {
                    $oldIdx->free();
                }
                $newIdx = $conn->query("SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'uq_cutoff_level_course_medium'");
                $hasNew = $newIdx && $newIdx->num_rows > 0;
                if ($newIdx) {
                    $newIdx->free();
                }
                if (!$hasNew) {
                    $conn->query(
                        "ALTER TABLE `{$this->table}` ADD UNIQUE KEY `uq_cutoff_level_course_medium` "
                        . "(`application_level`, `course_id`, `medium`)"
                    );
                }
                $this->copyLegacyCutoffsToAllMediums();
            }
        } catch (Throwable $e) {
            error_log('ApplicationAdmissionCutoffModel::migrateSchema: ' . $e->getMessage());
        }
    }

    /**
     * After adding medium, copy each existing (level, course) cutoff to Tamil and Sinhala
     * so previously saved Northern/Other values still apply until staff edit them.
     */
    private function copyLegacyCutoffsToAllMediums(): void {
        $stmt = $this->db->prepare(
            'SELECT `application_level`, `course_id`, `cutoff_northern`, `cutoff_other`, `updated_by` '
            . 'FROM `application_admission_cutoff` WHERE `medium` = ?'
        );
        if (!$stmt) {
            return;
        }
        $english = 'English';
        $stmt->bind_param('s', $english);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
        foreach ($rows as $row) {
            $level = (string) ($row['application_level'] ?? '');
            $courseId = trim((string) ($row['course_id'] ?? ''));
            if ($courseId === '' || $level !== '04') {
                continue;
            }
            $n = $row['cutoff_northern'] !== null ? (string) $row['cutoff_northern'] : null;
            $o = $row['cutoff_other'] !== null ? (string) $row['cutoff_other'] : null;
            $uid = isset($row['updated_by']) && $row['updated_by'] !== null ? (int) $row['updated_by'] : null;
            foreach (['Tamil', 'Sinhala'] as $medium) {
                $this->upsertCutoff($level, $courseId, $medium, $n, $o, $uid);
            }
        }
    }

    /**
     * Only NVQ 04 Automobile Technician uses Tamil / Sinhala / English cutoffs.
     *
     * @param array<string, mixed> $course
     */
    public static function isAutomobileLevel04(string $level, array $course): bool {
        if ($level !== '04') {
            return false;
        }
        $cid = strtoupper(trim((string) ($course['course_id'] ?? '')));
        $dept = strtoupper(trim((string) ($course['department_id'] ?? '')));
        $name = mb_strtolower(trim((string) ($course['course_name'] ?? '')), 'UTF-8');
        if ($cid === '4AT' || $dept === 'AUT') {
            return true;
        }

        return strpos($name, 'automobile') !== false || strpos($name, 'automotive') !== false;
    }

    /**
     * These courses are not used as 2nd-option fallbacks. If 2nd is one of them, consider 3rd
     * instead (unless 3rd is the same restricted course).
     * Level 04: Automobile Technician, Computer Hardware and Network Technician.
     * Level 05: Diploma in Information and Communication Technology, Diploma in Automotive Technology.
     *
     * @param array<string, mixed>|null $course
     */
    public static function isRestrictedSecondOptionCourse(?array $course, ?string $nameFallback = '', ?string $level = null): bool {
        $course = $course ?? [];
        $cid = strtoupper(trim((string) ($course['course_id'] ?? '')));
        $dept = strtoupper(trim((string) ($course['department_id'] ?? '')));
        $fromCourse = trim((string) ($course['course_name'] ?? ''));
        $name = mb_strtolower($fromCourse !== '' ? $fromCourse : trim((string) $nameFallback), 'UTF-8');
        $nvq = self::normalizeApplicationLevel($level !== null && trim($level) !== ''
            ? $level
            : (string) ($course['application_level'] ?? $course['course_nvq_level'] ?? ''));

        if ($nvq === '05') {
            return self::isLevel05RestrictedSecondOption($cid, $name);
        }
        if ($nvq === '04') {
            return self::isLevel04RestrictedSecondOption($cid, $dept, $name);
        }

        return self::isLevel04RestrictedSecondOption($cid, $dept, $name)
            || self::isLevel05RestrictedSecondOption($cid, $name);
    }

    public static function restrictedSecondOptionLabel(string $level): string {
        $nvq = self::normalizeApplicationLevel($level);
        if ($nvq === '05') {
            return 'Diploma in Information and Communication Technology or Diploma in Automotive Technology';
        }

        return 'Automobile Technician or Computer Hardware and Network Technician';
    }

    private static function normalizeApplicationLevel(string $level): string {
        $level = trim($level);
        if ($level === '4' || $level === '04') {
            return '04';
        }
        if ($level === '5' || $level === '05') {
            return '05';
        }

        return $level;
    }

    private static function isLevel04RestrictedSecondOption(string $cid, string $dept, string $name): bool {
        if ($cid === '4AT' || $dept === 'AUT') {
            return true;
        }
        if (strpos($name, 'automobile') !== false || strpos($name, 'automotive') !== false) {
            return true;
        }
        if (in_array($cid, ['4IT', '4CHN', '4CHNT', 'CHN', '4HNT', '4CNT', '4HW'], true)) {
            return true;
        }
        if (strpos($name, 'computer hardware') !== false) {
            return true;
        }

        return strpos($name, 'hardware') !== false && strpos($name, 'network') !== false;
    }

    private static function isLevel05RestrictedSecondOption(string $cid, string $name): bool {
        if ($cid === '5IT' || $cid === '5AT') {
            return true;
        }
        if (strpos($name, 'information and communication') !== false
            || strpos($name, 'information & communication') !== false
        ) {
            return true;
        }

        return strpos($name, 'automotive') !== false || strpos($name, 'automobile') !== false;
    }

    public static function choiceOrdinal(int $n): string {
        if ($n === 1) {
            return '1st choice';
        }
        if ($n === 2) {
            return '2nd choice';
        }
        if ($n === 3) {
            return '3rd choice';
        }

        return '';
    }

    /**
     * Display names for 1st, 2nd and 3rd course preferences.
     *
     * @param array<string, mixed> $entry
     * @return array{1:string,2:string,3:string}
     */
    public static function preferenceCourseNames(array $entry): array {
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        $level = trim((string) ($entry['application_level'] ?? ''));
        $appModel = new StudentApplicationModel();
        $out = [1 => '', 2 => '', 3 => ''];
        foreach ([1, 2, 3] as $n) {
            $stored = trim((string) ($entry['course_priority_' . $n] ?? ''));
            if ($stored === '') {
                continue;
            }
            $resolved = $appModel->resolveCourseDepartmentForPreference(
                $stored,
                $level !== '' ? $level : null
            );
            $name = trim((string) ($resolved['course_name'] ?? ''));
            if ($name === '') {
                $name = StudentApplicationModel::displayCourseNameFromStoredPreference($stored);
            }
            $out[$n] = $name !== '' ? $name : $stored;
        }

        return $out;
    }

    /**
     * Which preference (1, 2, or 3) matches this course. 0 if none.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $course
     */
    public function preferenceRankForCourse(array $row, string $courseId, array $course): int {
        $courseId = trim($courseId);
        if ($courseId === '') {
            return 0;
        }
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        $appModel = new StudentApplicationModel();
        foreach ([1, 2, 3] as $rank) {
            if ($this->preferenceMatchesCourse($row, 'course_priority_' . $rank, $courseId, $course, $appModel)) {
                return $rank;
            }
        }

        return 0;
    }

    /**
     * Course the applicant is eligible to interview for, from exam marks vs cutoff
     * (1st if they met cutoff; otherwise 2nd/3rd using the restricted-course rule).
     *
     * @param array<string, mixed> $entry
     * @return array{choice:int,choice_label:string,course_id:string,course_name:string}|null
     */
    public function eligibleChoiceForApplicant(array $entry, string $level): ?array {
        if (!in_array($level, ['04', '05'], true)) {
            return null;
        }
        $appId = (int) ($entry['application_id'] ?? 0);
        $marks = $appId > 0 ? $this->bestMarksForApplication($appId, $level) : self::numericMarks($entry['exam_marks'] ?? null);
        if ($marks === null) {
            return null;
        }
        require_once BASE_PATH . '/models/CourseModel.php';
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        $nvq = $level === '05' ? '5' : '4';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $appModel = new StudentApplicationModel();
        $cutoffMap = $this->getCutoffMap($level);
        $row = $entry;
        $row['marks_num'] = $marks;
        $row['medium'] = self::mediumFromEntry($row, $level);
        $first = $this->findCourseForPreference($row, 'course_priority_1', $courses, $appModel);
        if ($first === null) {
            return null;
        }
        $firstCutoff = $this->appliedCutoffForStudent($row, $first, $level, $cutoffMap);
        if ($firstCutoff !== null && $marks + 0.00001 >= $firstCutoff) {
            return $this->choicePayload(1, $first);
        }
        if ($marks + 0.00001 < (float) self::MARKS_MIN_SECOND_OPTION) {
            return null;
        }
        if ($firstCutoff === null) {
            return null;
        }
        $second = $this->findCourseForPreference($row, 'course_priority_2', $courses, $appModel);
        $third = $this->findCourseForPreference($row, 'course_priority_3', $courses, $appModel);
        $picked = $this->consideredSecondOption($row, $second, $third, $level);
        $consider = (int) ($picked['consider_choice'] ?? 0);
        if ($consider === 2 && $second !== null) {
            return $this->choicePayload(2, $second);
        }
        if ($consider === 3 && $third !== null) {
            return $this->choicePayload(3, $third);
        }

        return null;
    }

    /**
     * Applicants eligible to interview for this course: met 1st-choice cutoff for it,
     * or 2nd/3rd-option (marks 30 to below 1st cutoff) with this course as the considered choice.
     *
     * @return array<int, array{exam_marks_num:float,cutoff_applied:?float,choice:int,choice_label:string,course_id:string,course_name:string}>
     */
    public function interviewEligibleForCourse(string $level, string $courseId): array {
        $courseId = trim($courseId);
        if ($courseId === '' || !in_array($level, ['04', '05'], true)) {
            return [];
        }
        $out = [];
        foreach ($this->qualifyingStudentsByCourse($level) as $group) {
            if (strcasecmp((string) ($group['course_id'] ?? ''), $courseId) !== 0) {
                continue;
            }
            $courseName = trim((string) ($group['course_name'] ?? ''));
            foreach (($group['by_medium'] ?? []) as $block) {
                foreach (($block['students'] ?? []) as $row) {
                    $aid = (int) ($row['application_id'] ?? 0);
                    if ($aid < 1) {
                        continue;
                    }
                    $out[$aid] = [
                        'exam_marks_num' => (float) ($row['marks_num'] ?? 0),
                        'cutoff_applied' => isset($row['cutoff_applied']) ? (float) $row['cutoff_applied'] : null,
                        'choice' => 1,
                        'choice_label' => self::choiceOrdinal(1),
                        'course_id' => $courseId,
                        'course_name' => $courseName,
                    ];
                }
            }
        }
        $second = $this->secondOptionStudents($level);
        foreach (($second['students'] ?? []) as $row) {
            $consider = (int) ($row['consider_choice'] ?? 0);
            $cid = $consider === 2
                ? trim((string) ($row['second_course_id'] ?? ''))
                : ($consider === 3 ? trim((string) ($row['third_course_id'] ?? '')) : '');
            if ($consider < 2 || strcasecmp($cid, $courseId) !== 0) {
                continue;
            }
            $aid = (int) ($row['application_id'] ?? 0);
            if ($aid < 1 || isset($out[$aid])) {
                continue;
            }
            $cut = $consider === 2 ? ($row['second_cutoff'] ?? null) : ($row['third_cutoff'] ?? null);
            $out[$aid] = [
                'exam_marks_num' => (float) ($row['marks_num'] ?? 0),
                'cutoff_applied' => $cut !== null && $cut !== '' ? (float) $cut : null,
                'choice' => $consider,
                'choice_label' => self::choiceOrdinal($consider),
                'course_id' => $courseId,
                'course_name' => $consider === 2
                    ? trim((string) ($row['second_course_name'] ?? ''))
                    : trim((string) ($row['third_course_name'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $course
     * @return array{choice:int,choice_label:string,course_id:string,course_name:string}
     */
    private function choicePayload(int $choice, array $course): array {
        return [
            'choice' => $choice,
            'choice_label' => self::choiceOrdinal($choice),
            'course_id' => trim((string) ($course['course_id'] ?? '')),
            'course_name' => trim((string) ($course['course_name'] ?? '')),
        ];
    }

    private function bestMarksForApplication(int $applicationId, string $level): ?float {
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';
        $sql = 'SELECT e.`exam_marks` FROM `application_admission_schedule_entry` e'
            . ' INNER JOIN `application_admission_schedule` s ON s.`schedule_id` = e.`schedule_id`'
            . ' WHERE e.`application_id` = ? AND s.`schedule_type` = ? AND s.`application_level` = ?'
            . ' AND e.`exam_marks` IS NOT NULL AND TRIM(e.`exam_marks`) <> \'\'';
        $type = ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('iss', $applicationId, $type, $level);
        $stmt->execute();
        $res = $stmt->get_result();
        $best = null;
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $marks = self::numericMarks($row['exam_marks'] ?? null);
                if ($marks === null) {
                    continue;
                }
                if ($best === null || $marks > $best) {
                    $best = $marks;
                }
            }
        }
        $stmt->close();

        return $best;
    }

    /**
     * @param array<string, mixed> $course
     * @return list<string>
     */
    public static function mediumsForCourse(string $level, array $course): array {
        if (self::isAutomobileLevel04($level, $course)) {
            return self::MEDIUMS;
        }

        return [self::MEDIUM_ALL];
    }

    /**
     * Automobile L04 form / list groups: Tamil is separate; Sinhala and English share one cutoff.
     *
     * @return list<array{key: string, label: string, store: list<string>}>
     */
    public static function automobileCutoffGroups(): array {
        return [
            [
                'key' => 'Tamil',
                'label' => 'Tamil',
                'store' => ['Tamil'],
            ],
            [
                'key' => 'Sinhala_English',
                'label' => 'Sinhala / English',
                'store' => ['Sinhala', 'English'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function mediumsForLevel(string $level): array {
        if ($level === '05') {
            return [self::MEDIUM_ALL];
        }

        return self::MEDIUMS;
    }

    public static function isNorthernProvince(?string $province): bool {
        return strcasecmp(trim((string) $province), self::PROVINCE_NORTHERN) === 0;
    }

    public static function normalizeMedium(?string $raw): ?string {
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        foreach (self::MEDIUMS as $medium) {
            if (strcasecmp($s, $medium) === 0) {
                return $medium;
            }
        }

        return null;
    }

    /**
     * Exam medium for a marked student: Level 05 is always English;
     * otherwise schedule language, then the applicant language.
     */
    public static function mediumFromEntry(array $row, string $level): ?string {
        if ($level === '05') {
            return 'English';
        }
        $fromSchedule = self::normalizeMedium($row['schedule_language'] ?? null);
        if ($fromSchedule !== null) {
            return $fromSchedule;
        }

        return self::normalizeMedium($row['student_language'] ?? null);
    }

    /**
     * @return string|null|false
     */
    public static function normalizeCutoff(?string $raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $raw)) {
            return false;
        }
        $num = (float) $raw;
        if ($num < 0 || $num > 999.99) {
            return false;
        }
        if (abs($num - round($num)) < 0.00001) {
            return (string) (int) round($num);
        }

        return rtrim(rtrim(sprintf('%.2f', $num), '0'), '.');
    }

    public static function numericMarks(?string $raw): ?float {
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';
        if (ApplicationAdmissionScheduleModel::isAbsentMarks($raw)) {
            return null;
        }
        $raw = trim((string) $raw);
        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    /**
     * @return array<string, array<string, array<string, mixed>>> course_id => medium => row
     */
    public function getCutoffMap(string $level): array {
        $this->ensureTable();
        if (!in_array($level, ['04', '05'], true)) {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT * FROM `application_admission_cutoff` WHERE `application_level` = ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $level);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cid = trim((string) ($row['course_id'] ?? ''));
                $medium = self::normalizeMedium($row['medium'] ?? null) ?? trim((string) ($row['medium'] ?? ''));
                if ($cid === '' || $medium === '') {
                    continue;
                }
                $out[$cid][$medium] = $row;
            }
        }
        $stmt->close();

        return $out;
    }

    /**
     * @param array<string, array<string, array{northern?: string, other?: string}>> $posted
     * @return array{saved: int, invalid: int}
     */
    public function saveLevelCutoffs(string $level, array $posted, ?int $userId = null): array {
        $this->ensureTable();
        if (!in_array($level, ['04', '05'], true)) {
            return ['saved' => 0, 'invalid' => 0];
        }
        require_once BASE_PATH . '/models/CourseModel.php';
        $nvq = $level === '05' ? '5' : '4';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $courseById = [];
        foreach ($courses as $course) {
            $cid = trim((string) ($course['course_id'] ?? ''));
            if ($cid !== '') {
                $courseById[$cid] = $course;
            }
        }
        $saved = 0;
        $invalid = 0;
        foreach ($posted as $courseId => $byMedium) {
            $courseId = trim((string) $courseId);
            if ($courseId === '' || !is_array($byMedium)) {
                continue;
            }
            $course = $courseById[$courseId] ?? ['course_id' => $courseId];
            $languageSplit = self::isAutomobileLevel04($level, $course);
            if ($languageSplit) {
                $shared = $byMedium['Sinhala_English'] ?? $byMedium['Sinhala / English'] ?? null;
                if (is_array($shared)) {
                    $byMedium['Sinhala'] = $shared;
                    $byMedium['English'] = $shared;
                } elseif (isset($byMedium['Sinhala']) && is_array($byMedium['Sinhala'])) {
                    $byMedium['English'] = $byMedium['Sinhala'];
                } elseif (isset($byMedium['English']) && is_array($byMedium['English'])) {
                    $byMedium['Sinhala'] = $byMedium['English'];
                }
            }
            $allowedMediums = self::mediumsForCourse($level, $course);
            foreach ($allowedMediums as $medium) {
                $vals = $byMedium[$medium] ?? [];
                if (!is_array($vals)) {
                    $vals = [];
                }
                $northern = self::normalizeCutoff(isset($vals['northern']) ? (string) $vals['northern'] : '');
                $other = self::normalizeCutoff(isset($vals['other']) ? (string) $vals['other'] : '');
                if ($northern === false || $other === false) {
                    $invalid++;
                    continue;
                }
                if ($northern === null && $other === null) {
                    $this->deleteCutoff($level, $courseId, $medium);
                    $saved++;
                    continue;
                }
                if ($this->upsertCutoff($level, $courseId, $medium, $northern, $other, $userId)) {
                    $saved++;
                }
            }
            if ($languageSplit) {
                $this->deleteCutoff($level, $courseId, self::MEDIUM_ALL);
            } else {
                foreach (self::MEDIUMS as $extra) {
                    $this->deleteCutoff($level, $courseId, $extra);
                }
            }
        }

        return ['saved' => $saved, 'invalid' => $invalid];
    }

    /**
     * Course-wise students who sat the entrance exam and met the medium + province cutoff.
     *
     * @return list<array<string, mixed>>
     */
    public function qualifyingStudentsByCourse(string $level): array {
        $this->ensureTable();
        if (!in_array($level, ['04', '05'], true)) {
            return [];
        }
        require_once BASE_PATH . '/models/CourseModel.php';
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';

        $nvq = $level === '05' ? '5' : '4';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $cutoffMap = $this->getCutoffMap($level);
        $bestByApp = $this->bestMarkedByApplication($level);
        $appModel = new StudentApplicationModel();

        $groups = [];
        foreach ($courses as $course) {
            $cid = trim((string) ($course['course_id'] ?? ''));
            if ($cid === '') {
                continue;
            }
            $languageSplit = self::isAutomobileLevel04($level, $course);
            $byMedium = [];
            $anyCutoff = false;
            $qualifyTotal = 0;
            $satTotal = 0;
            $groupsSpec = $languageSplit
                ? self::automobileCutoffGroups()
                : [[
                    'key' => self::MEDIUM_ALL,
                    'label' => 'All languages',
                    'store' => [self::MEDIUM_ALL],
                ]];
            foreach ($groupsSpec as $groupSpec) {
                $mediumKey = (string) ($groupSpec['key'] ?? '');
                $storeMediums = is_array($groupSpec['store'] ?? null) ? $groupSpec['store'] : [];
                $cut = [];
                foreach ($storeMediums as $storeMedium) {
                    $found = $this->cutoffRowForMedium($cutoffMap, $cid, (string) $storeMedium);
                    if ($found !== []) {
                        $cut = $found;
                        break;
                    }
                }
                $northernCut = $this->cutoffValue($cut['cutoff_northern'] ?? null);
                $otherCut = $this->cutoffValue($cut['cutoff_other'] ?? null);
                $hasCutoff = $northernCut !== null || $otherCut !== null;
                if ($hasCutoff) {
                    $anyCutoff = true;
                }
                $students = [];
                $sat = 0;
                foreach ($bestByApp as $row) {
                    if (!ApplicationAdmissionScheduleModel::applicationMatchesCourse($row, $cid, $course, $appModel)) {
                        continue;
                    }
                    if ($languageSplit) {
                        $rowMedium = (string) ($row['medium'] ?? '');
                        if (!in_array($rowMedium, $storeMediums, true)) {
                            continue;
                        }
                    }
                    $sat++;
                    $isNorth = self::isNorthernProvince($row['student_province'] ?? null);
                    $applied = $isNorth ? $northernCut : $otherCut;
                    if ($applied === null) {
                        continue;
                    }
                    if ((float) $row['marks_num'] + 0.00001 < $applied) {
                        continue;
                    }
                    $row['cutoff_applied'] = $applied;
                    $row['region'] = $isNorth ? 'Northern' : 'Other';
                    $row['course_id'] = $cid;
                    $students[] = $row;
                }
                usort($students, static function (array $a, array $b): int {
                    $cmp = ($b['marks_num'] <=> $a['marks_num']);
                    if ($cmp !== 0) {
                        return $cmp;
                    }

                    return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
                });
                $satTotal += $sat;
                $qualifyTotal += count($students);
                $byMedium[$mediumKey] = [
                    'medium' => $mediumKey,
                    'label' => (string) ($groupSpec['label'] ?? $mediumKey),
                    'cutoff_northern' => $northernCut,
                    'cutoff_other' => $otherCut,
                    'has_cutoff' => $hasCutoff,
                    'sat_count' => $sat,
                    'qualify_count' => count($students),
                    'students' => $students,
                ];
            }
            $groups[] = [
                'course_id' => $cid,
                'course_name' => trim((string) ($course['course_name'] ?? $cid)),
                'department_id' => trim((string) ($course['department_id'] ?? '')),
                'department_name' => trim((string) ($course['department_name'] ?? '')),
                'uses_language' => $languageSplit,
                'has_cutoff' => $anyCutoff,
                'sat_count' => $satTotal,
                'qualify_count' => $qualifyTotal,
                'by_medium' => $byMedium,
            ];
        }

        return $groups;
    }

    /**
     * Students who sat the exam, scored at least 30, but did not meet their 1st-choice cutoff.
     * They are listed for 2nd and 3rd choice consideration.
     *
     * @return array{min_marks:int,students:list<array<string,mixed>>,groups:list<array<string,mixed>>}
     */
    public function secondOptionStudents(string $level): array {
        $this->ensureTable();
        $empty = ['min_marks' => self::MARKS_MIN_SECOND_OPTION, 'students' => [], 'groups' => []];
        if (!in_array($level, ['04', '05'], true)) {
            return $empty;
        }
        require_once BASE_PATH . '/models/CourseModel.php';
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';

        $nvq = $level === '05' ? '5' : '4';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $cutoffMap = $this->getCutoffMap($level);
        $bestByApp = $this->bestMarkedByApplication($level);
        $appModel = new StudentApplicationModel();
        $minMarks = (float) self::MARKS_MIN_SECOND_OPTION;

        $students = [];
        foreach ($bestByApp as $row) {
            $marks = (float) ($row['marks_num'] ?? 0);
            if ($marks + 0.00001 < $minMarks) {
                continue;
            }
            $first = $this->findCourseForPreference($row, 'course_priority_1', $courses, $appModel);
            if ($first === null) {
                continue;
            }
            $firstCutoff = $this->appliedCutoffForStudent($row, $first, $level, $cutoffMap);
            if ($firstCutoff === null) {
                continue;
            }
            if ($marks + 0.00001 >= $firstCutoff) {
                continue;
            }
            $second = $this->findCourseForPreference($row, 'course_priority_2', $courses, $appModel);
            $third = $this->findCourseForPreference($row, 'course_priority_3', $courses, $appModel);
            $secondCutoff = $second !== null ? $this->appliedCutoffForStudent($row, $second, $level, $cutoffMap) : null;
            $thirdCutoff = $third !== null ? $this->appliedCutoffForStudent($row, $third, $level, $cutoffMap) : null;
            $isNorth = self::isNorthernProvince($row['student_province'] ?? null);
            $row['region'] = $isNorth ? 'Northern' : 'Other';
            $row['first_course_id'] = trim((string) ($first['course_id'] ?? ''));
            $row['first_course_name'] = trim((string) ($first['course_name'] ?? ''));
            $row['first_department_id'] = trim((string) ($first['department_id'] ?? ''));
            $row['first_department_name'] = trim((string) ($first['department_name'] ?? ''));
            $row['first_cutoff'] = $firstCutoff;
            $row['second_course_id'] = $second !== null ? trim((string) ($second['course_id'] ?? '')) : '';
            $row['second_course_name'] = $second !== null
                ? trim((string) ($second['course_name'] ?? ''))
                : trim((string) ($row['course_priority_2'] ?? ''));
            $row['second_department_id'] = $second !== null ? trim((string) ($second['department_id'] ?? '')) : '';
            $row['second_department_name'] = $second !== null ? trim((string) ($second['department_name'] ?? '')) : '';
            $row['second_cutoff'] = $secondCutoff;
            $row['second_meets'] = $secondCutoff !== null && $marks + 0.00001 >= $secondCutoff;
            $row['third_course_id'] = $third !== null ? trim((string) ($third['course_id'] ?? '')) : '';
            $row['third_course_name'] = $third !== null
                ? trim((string) ($third['course_name'] ?? ''))
                : trim((string) ($row['course_priority_3'] ?? ''));
            $row['third_department_id'] = $third !== null ? trim((string) ($third['department_id'] ?? '')) : '';
            $row['third_department_name'] = $third !== null ? trim((string) ($third['department_name'] ?? '')) : '';
            $row['third_cutoff'] = $thirdCutoff;
            $row['third_meets'] = $thirdCutoff !== null && $marks + 0.00001 >= $thirdCutoff;
            $picked = $this->consideredSecondOption($row, $second, $third, $level);
            $row['second_restricted'] = $picked['second_restricted'];
            $row['third_restricted'] = $picked['third_restricted'];
            $row['consider_choice'] = $picked['consider_choice'];
            $students[] = $row;
        }
        usort($students, static function (array $a, array $b): int {
            $cmp = ((float) ($b['marks_num'] ?? 0)) <=> ((float) ($a['marks_num'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
        });

        $groups = [];
        foreach ($courses as $course) {
            $cid = trim((string) ($course['course_id'] ?? ''));
            if ($cid === '') {
                continue;
            }
            $list = [];
            foreach ($students as $row) {
                if (strcasecmp((string) ($row['first_course_id'] ?? ''), $cid) !== 0) {
                    continue;
                }
                $list[] = $row;
            }
            if ($list === []) {
                continue;
            }
            $groups[] = [
                'course_id' => $cid,
                'course_name' => trim((string) ($course['course_name'] ?? $cid)),
                'department_id' => trim((string) ($course['department_id'] ?? '')),
                'department_name' => trim((string) ($course['department_name'] ?? '')),
                'count' => count($list),
                'students' => $list,
            ];
        }

        return [
            'min_marks' => self::MARKS_MIN_SECOND_OPTION,
            'students' => $students,
            'groups' => $groups,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bestMarkedByApplication(string $level): array {
        $entries = $this->entranceMarkedEntries($level);
        $bestByApp = [];
        foreach ($entries as $row) {
            $marks = self::numericMarks($row['exam_marks'] ?? null);
            if ($marks === null) {
                continue;
            }
            $appId = (int) ($row['application_id'] ?? 0);
            if ($appId < 1) {
                continue;
            }
            $row['marks_num'] = $marks;
            $row['medium'] = self::mediumFromEntry($row, $level);
            if (!isset($bestByApp[$appId]) || $marks > (float) $bestByApp[$appId]['marks_num']) {
                $bestByApp[$appId] = $row;
            }
        }

        return $bestByApp;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $second
     * @param array<string, mixed>|null $third
     * @return array{second_restricted:bool,third_restricted:bool,consider_choice:int}
     */
    private function consideredSecondOption(array $row, ?array $second, ?array $third, ?string $level = null): array {
        $level = $level !== null && trim($level) !== ''
            ? $level
            : (string) ($row['application_level'] ?? '');
        $secondName = $second !== null
            ? trim((string) ($second['course_name'] ?? ''))
            : trim((string) ($row['course_priority_2'] ?? ''));
        $thirdName = $third !== null
            ? trim((string) ($third['course_name'] ?? ''))
            : trim((string) ($row['course_priority_3'] ?? ''));
        $secondRestricted = $secondName !== '' && self::isRestrictedSecondOptionCourse($second, $secondName, $level);
        $thirdRestricted = $thirdName !== '' && self::isRestrictedSecondOptionCourse($third, $thirdName, $level);
        $secondId = $second !== null ? trim((string) ($second['course_id'] ?? '')) : '';
        $thirdId = $third !== null ? trim((string) ($third['course_id'] ?? '')) : '';
        $same = ($secondId !== '' && $thirdId !== '' && strcasecmp($secondId, $thirdId) === 0)
            || ($secondName !== '' && $thirdName !== '' && strcasecmp($secondName, $thirdName) === 0);

        $consider = 0;
        if ($secondName !== '' && !$secondRestricted) {
            $consider = 2;
        } elseif ($secondRestricted) {
            if ($thirdName !== '' && !$thirdRestricted && !$same) {
                $consider = 3;
            }
        } elseif ($thirdName !== '' && !$thirdRestricted) {
            $consider = 3;
        }

        return [
            'second_restricted' => $secondRestricted,
            'third_restricted' => $thirdRestricted,
            'consider_choice' => $consider,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, mixed>> $courses
     * @return array<string, mixed>|null
     */
    private function findCourseForPreference(
        array $row,
        string $field,
        array $courses,
        StudentApplicationModel $appModel
    ): ?array {
        if (trim((string) ($row[$field] ?? '')) === '') {
            return null;
        }
        foreach ($courses as $course) {
            $cid = trim((string) ($course['course_id'] ?? ''));
            if ($cid === '') {
                continue;
            }
            if ($this->preferenceMatchesCourse($row, $field, $cid, $course, $appModel)) {
                return $course;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $course
     * @param array<string, array<string, array<string, mixed>>> $cutoffMap
     */
    private function appliedCutoffForStudent(array $row, array $course, string $level, array $cutoffMap): ?float {
        $cid = trim((string) ($course['course_id'] ?? ''));
        if ($cid === '') {
            return null;
        }
        $languageSplit = self::isAutomobileLevel04($level, $course);
        $mediumsToTry = [self::MEDIUM_ALL];
        if ($languageSplit) {
            $medium = (string) ($row['medium'] ?? '');
            if ($medium === 'Tamil') {
                $mediumsToTry = ['Tamil'];
            } elseif (in_array($medium, ['Sinhala', 'English'], true)) {
                $mediumsToTry = ['Sinhala', 'English'];
            } else {
                $mediumsToTry = ['Tamil', 'Sinhala', 'English'];
            }
        }
        $cut = [];
        foreach ($mediumsToTry as $medium) {
            $found = $this->cutoffRowForMedium($cutoffMap, $cid, (string) $medium);
            if ($found !== []) {
                $cut = $found;
                break;
            }
        }
        $northern = $this->cutoffValue($cut['cutoff_northern'] ?? null);
        $other = $this->cutoffValue($cut['cutoff_other'] ?? null);
        $isNorth = self::isNorthernProvince($row['student_province'] ?? null);

        return $isNorth ? $northern : $other;
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $cutoffMap
     * @return array<string, mixed>
     */
    private function cutoffRowForMedium(array $cutoffMap, string $courseId, string $medium): array {
        if (isset($cutoffMap[$courseId][$medium]) && is_array($cutoffMap[$courseId][$medium])) {
            return $cutoffMap[$courseId][$medium];
        }
        if ($medium === self::MEDIUM_ALL) {
            foreach (self::MEDIUMS as $fallback) {
                if (isset($cutoffMap[$courseId][$fallback]) && is_array($cutoffMap[$courseId][$fallback])) {
                    return $cutoffMap[$courseId][$fallback];
                }
            }
        }

        return [];
    }

    /**
     * Match a stored preference field (e.g. 2nd choice) to a course, using the same
     * name / legacy-id rules as 1st preference.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $course
     */
    private function preferenceMatchesCourse(
        array $row,
        string $field,
        string $courseId,
        array $course,
        StudentApplicationModel $appModel
    ): bool {
        $stored = trim((string) ($row[$field] ?? ''));
        if ($stored === '') {
            return false;
        }
        $clone = $row;
        $clone['course_priority_1'] = $stored;

        return ApplicationAdmissionScheduleModel::applicationMatchesCourse($clone, $courseId, $course, $appModel);
    }

    private function cutoffValue($raw): ?float {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (!is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entranceMarkedEntries(string $level): array {
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';
        $sql = 'SELECT e.`entry_id`, e.`schedule_id`, e.`application_id`, e.`roll_number`, e.`exam_marks`,'
            . ' sa.`student_full_name`, sa.`student_nic`, sa.`student_province`, sa.`student_district`,'
            . ' sa.`course_priority_1`, sa.`course_priority_2`, sa.`course_priority_3`, sa.`application_level`, sa.`student_phone`, sa.`student_language`,'
            . ' s.`title` AS schedule_title, s.`schedule_date`, s.`student_language` AS schedule_language'
            . ' FROM `application_admission_schedule_entry` e'
            . ' INNER JOIN `application_admission_schedule` s ON s.`schedule_id` = e.`schedule_id`'
            . ' INNER JOIN `student_applications` sa ON sa.`application_id` = e.`application_id`'
            . ' WHERE s.`schedule_type` = ? AND s.`application_level` = ?'
            . ' AND e.`exam_marks` IS NOT NULL AND TRIM(e.`exam_marks`) <> \'\'';
        $type = ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $type, $level);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = $row;
            }
        }
        $stmt->close();

        return $out;
    }

    private function upsertCutoff(
        string $level,
        string $courseId,
        string $medium,
        ?string $northern,
        ?string $other,
        ?int $userId
    ): bool {
        $sql = 'INSERT INTO `application_admission_cutoff`'
            . ' (`application_level`, `course_id`, `medium`, `cutoff_northern`, `cutoff_other`, `updated_by`)'
            . ' VALUES (?, ?, ?, ?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE'
            . ' `cutoff_northern` = VALUES(`cutoff_northern`),'
            . ' `cutoff_other` = VALUES(`cutoff_other`),'
            . ' `updated_by` = VALUES(`updated_by`)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('ApplicationAdmissionCutoffModel::upsertCutoff prepare: ' . $this->db->getConnection()->error);
            return false;
        }
        $uidVal = $userId !== null && $userId > 0 ? (string) $userId : null;
        $stmt->bind_param('ssssss', $level, $courseId, $medium, $northern, $other, $uidVal);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('ApplicationAdmissionCutoffModel::upsertCutoff: ' . $stmt->error);
        }
        $stmt->close();

        return (bool) $ok;
    }

    private function deleteCutoff(string $level, string $courseId, string $medium): void {
        $stmt = $this->db->prepare(
            'DELETE FROM `application_admission_cutoff` '
            . 'WHERE `application_level` = ? AND `course_id` = ? AND `medium` = ? LIMIT 1'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $level, $courseId, $medium);
        $stmt->execute();
        $stmt->close();
    }
}
