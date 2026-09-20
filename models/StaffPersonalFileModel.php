<?php
/**
 * Staff personal-file related records (qualifications, training, documents, etc.).
 */

class StaffPersonalFileModel extends Model {
    protected $table = 'staff_document';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    public static function kinds() {
        return [
            'qualification' => [
                'table' => 'staff_qualification',
                'tab' => 'qualifications',
                'fields' => ['qual_type', 'title', 'institute', 'year_completed', 'result', 'notes'],
                'required' => ['qual_type', 'title'],
            ],
            'training' => [
                'table' => 'staff_training',
                'tab' => 'training',
                'fields' => ['train_type', 'title', 'organizer', 'start_date', 'end_date', 'notes'],
                'required' => ['train_type', 'title'],
            ],
            'service' => [
                'table' => 'staff_service_history',
                'tab' => 'service',
                'fields' => ['event_type', 'title', 'from_value', 'to_value', 'effective_date', 'notes'],
                'required' => ['event_type', 'title'],
            ],
            'leave' => [
                'table' => 'staff_leave_record',
                'tab' => 'attendance',
                'fields' => ['leave_type', 'start_date', 'end_date', 'days', 'reason', 'status'],
                'required' => ['leave_type', 'start_date', 'end_date'],
            ],
            'family' => [
                'table' => 'staff_family',
                'tab' => 'family',
                'fields' => ['member_type', 'full_name', 'relationship', 'nic', 'phone', 'dob', 'notes'],
                'required' => ['member_type', 'full_name'],
            ],
            'admin' => [
                'table' => 'staff_admin_record',
                'tab' => 'administrative',
                'fields' => ['record_type', 'title', 'record_date', 'notes'],
                'required' => ['record_type', 'title'],
            ],
        ];
    }

    public static function labels() {
        return [
            'qual_type' => [
                'ol' => 'O/L', 'al' => 'A/L', 'diploma' => 'Diploma', 'degree' => 'Degree',
                'postgraduate' => 'Postgraduate', 'professional' => 'Professional',
            ],
            'train_type' => [
                'program' => 'Training Program', 'workshop' => 'Workshop', 'certification' => 'Certification',
                'cpd' => 'CPD Record', 'professional_development' => 'Professional Development',
            ],
            'event_type' => [
                'previous_employment' => 'Previous Employment', 'promotion' => 'Promotion',
                'transfer' => 'Transfer', 'designation_change' => 'Designation Change',
                'salary_revision' => 'Salary Revision', 'increment' => 'Increment',
            ],
            'leave_type' => [
                'casual' => 'Casual Leave (CL)',
                'annual' => 'Vacation Leave (VL)',
                'medical' => 'Sick Leave (SL)',
                'short_m' => 'Short Leave (Morning)',
                'short_e' => 'Short Leave (Evening)',
                'half_m' => 'Half Day (Morning)',
                'half_e' => 'Half Day (Evening)',
                'maternity' => 'Maternity Leave',
                'duty' => 'Duty Leave',
                'nopay' => 'No-Pay Leave',
                'other' => 'Other Leave',
            ],
            'leave_status' => [
                'Recorded' => 'Recorded',
                'Recommended' => 'Recommended',
                'Approved' => 'Approved',
                'Rejected' => 'Rejected',
            ],
            'doc_type' => [
                'appointment_letter' => 'Appointment Letter', 'nic_copy' => 'NIC Copy',
                'certificate' => 'Certificate', 'qualification' => 'Qualification Document',
                'service_letter' => 'Service Letter', 'promotion_letter' => 'Promotion Letter',
                'transfer_letter' => 'Transfer Letter', 'salary_revision_letter' => 'Salary Revision Letter',
                'other' => 'Other Official Document',
            ],
            'member_type' => [
                'spouse' => 'Spouse', 'child' => 'Child', 'dependent' => 'Dependent', 'nominee' => 'Nominee',
            ],
            'record_type' => [
                'award' => 'Award / Commendation', 'warning' => 'Warning',
                'inquiry' => 'Inquiry', 'action' => 'Administrative Action',
            ],
        ];
    }

    public function ensureSchema() {
        $this->ensureStaffColumns();
        $this->ensureRelatedTables();
        $this->ensureUploadDir();
    }

    private function ensureStaffColumns() {
        $columns = [
            'staff_civil_status' => "ALTER TABLE `staff` ADD COLUMN `staff_civil_status` VARCHAR(20) NULL DEFAULT NULL",
            'staff_nationality' => "ALTER TABLE `staff` ADD COLUMN `staff_nationality` VARCHAR(50) NULL DEFAULT 'Sri Lankan'",
            'staff_photo_path' => "ALTER TABLE `staff` ADD COLUMN `staff_photo_path` VARCHAR(255) NULL DEFAULT NULL",
            'staff_current_address' => "ALTER TABLE `staff` ADD COLUMN `staff_current_address` VARCHAR(255) NULL DEFAULT NULL",
            'staff_emergency_name' => "ALTER TABLE `staff` ADD COLUMN `staff_emergency_name` VARCHAR(100) NULL DEFAULT NULL",
            'staff_emergency_phone' => "ALTER TABLE `staff` ADD COLUMN `staff_emergency_phone` VARCHAR(20) NULL DEFAULT NULL",
            'staff_emergency_relation' => "ALTER TABLE `staff` ADD COLUMN `staff_emergency_relation` VARCHAR(50) NULL DEFAULT NULL",
            'staff_designation' => "ALTER TABLE `staff` ADD COLUMN `staff_designation` VARCHAR(100) NULL DEFAULT NULL",
            'staff_confirmation_date' => "ALTER TABLE `staff` ADD COLUMN `staff_confirmation_date` DATE NULL DEFAULT NULL",
            'staff_retirement_date' => "ALTER TABLE `staff` ADD COLUMN `staff_retirement_date` DATE NULL DEFAULT NULL",
            'staff_bank_name' => "ALTER TABLE `staff` ADD COLUMN `staff_bank_name` VARCHAR(100) NULL DEFAULT NULL",
            'staff_bank_branch' => "ALTER TABLE `staff` ADD COLUMN `staff_bank_branch` VARCHAR(100) NULL DEFAULT NULL",
            'staff_bank_account' => "ALTER TABLE `staff` ADD COLUMN `staff_bank_account` VARCHAR(50) NULL DEFAULT NULL",
            'staff_etf' => "ALTER TABLE `staff` ADD COLUMN `staff_etf` VARCHAR(20) NULL DEFAULT NULL",
        ];
        foreach ($columns as $name => $sql) {
            try {
                $result = $this->db->query("SHOW COLUMNS FROM `staff` LIKE '{$name}'");
                if ($result && $result->num_rows === 0) {
                    $this->db->query($sql);
                }
            } catch (Throwable $e) {
                error_log('StaffPersonalFileModel column ' . $name . ': ' . $e->getMessage());
            }
        }
    }

    private function ensureRelatedTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS `staff_qualification` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `qual_type` VARCHAR(40) NOT NULL, `title` VARCHAR(255) NOT NULL, `institute` VARCHAR(255) DEFAULT NULL, `year_completed` VARCHAR(10) DEFAULT NULL, `result` VARCHAR(100) DEFAULT NULL, `notes` VARCHAR(255) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_qual_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `staff_training` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `train_type` VARCHAR(40) NOT NULL, `title` VARCHAR(255) NOT NULL, `organizer` VARCHAR(255) DEFAULT NULL, `start_date` DATE DEFAULT NULL, `end_date` DATE DEFAULT NULL, `notes` VARCHAR(255) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_train_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `staff_service_history` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `event_type` VARCHAR(40) NOT NULL, `title` VARCHAR(255) NOT NULL, `from_value` VARCHAR(255) DEFAULT NULL, `to_value` VARCHAR(255) DEFAULT NULL, `effective_date` DATE DEFAULT NULL, `notes` VARCHAR(255) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_service_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `staff_leave_record` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `leave_type` VARCHAR(40) NOT NULL, `start_date` DATE NOT NULL, `end_date` DATE NOT NULL, `days` DECIMAL(6,1) DEFAULT NULL, `reason` VARCHAR(255) DEFAULT NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'Recorded', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_leave_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `staff_document` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `doc_type` VARCHAR(40) NOT NULL, `title` VARCHAR(255) NOT NULL, `original_name` VARCHAR(255) DEFAULT NULL, `stored_path` VARCHAR(255) NOT NULL, `mime_type` VARCHAR(100) DEFAULT NULL, `file_size` INT(11) DEFAULT 0, `approved` TINYINT(1) NOT NULL DEFAULT 0, `approved_by` INT(11) DEFAULT NULL, `approved_at` DATETIME DEFAULT NULL, `uploaded_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_doc_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `staff_family` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `member_type` VARCHAR(40) NOT NULL, `full_name` VARCHAR(150) NOT NULL, `relationship` VARCHAR(50) DEFAULT NULL, `nic` VARCHAR(20) DEFAULT NULL, `phone` VARCHAR(20) DEFAULT NULL, `dob` DATE DEFAULT NULL, `notes` VARCHAR(255) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_family_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `staff_admin_record` (`id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` VARCHAR(64) NOT NULL, `record_type` VARCHAR(40) NOT NULL, `title` VARCHAR(255) NOT NULL, `record_date` DATE DEFAULT NULL, `notes` VARCHAR(500) DEFAULT NULL, `created_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_staff_admin_staff` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }

    private function ensureUploadDir() {
        $dir = BASE_PATH . '/uploads/staff';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $deny = $dir . '/.htaccess';
        if (!is_file($deny)) {
            @file_put_contents($deny, "Require all denied\n");
        }
    }

    public function listKind($kind, $staffId) {
        $meta = self::kinds()[$kind] ?? null;
        if (!$meta) {
            return [];
        }
        $table = $meta['table'];
        $stmt = $this->db->prepare("SELECT * FROM `{$table}` WHERE `staff_id` = ? ORDER BY `id` DESC");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function saveKind($kind, $staffId, array $posted, $createdBy = null) {
        $meta = self::kinds()[$kind] ?? null;
        if (!$meta) {
            return [false, 'Unknown record type.'];
        }
        $data = ['staff_id' => $staffId];
        foreach ($meta['fields'] as $field) {
            $value = trim((string) ($posted[$field] ?? ''));
            $data[$field] = $value === '' ? null : $value;
        }
        foreach ($meta['required'] as $field) {
            if (empty($data[$field])) {
                return [false, 'Please complete the required fields.'];
            }
        }
        if ($kind === 'admin') {
            $data['created_by'] = (string) ((int) $createdBy);
        }
        $id = (int) ($posted['record_id'] ?? 0);
        $table = $meta['table'];
        if ($id > 0) {
            $sets = [];
            $values = [];
            foreach ($data as $col => $val) {
                if ($col === 'staff_id') {
                    continue;
                }
                $sets[] = "`{$col}` = ?";
                $values[] = $val;
            }
            $values[] = $staffId;
            $values[] = $id;
            $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `staff_id` = ? AND `id` = ?";
            $stmt = $this->db->prepare($sql);
            $types = str_repeat('s', count($values) - 1) . 'i';
            $stmt->bind_param($types, ...$values);
            $ok = $stmt->execute();
            return [$ok, $ok ? null : ($stmt->error ?: 'Update failed.')];
        }
        $cols = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $cols) . "`) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $vals = array_values($data);
        $stmt->bind_param(str_repeat('s', count($vals)), ...$vals);
        $ok = $stmt->execute();
        return [$ok, $ok ? null : ($stmt->error ?: 'Save failed.')];
    }

    public function deleteKind($kind, $staffId, $id) {
        $meta = self::kinds()[$kind] ?? null;
        if (!$meta) {
            return false;
        }
        $id = (int) $id;
        $table = $meta['table'];
        $stmt = $this->db->prepare("DELETE FROM `{$table}` WHERE `staff_id` = ? AND `id` = ?");
        $stmt->bind_param('si', $staffId, $id);
        return $stmt->execute();
    }

    public function listDocuments($staffId) {
        $stmt = $this->db->prepare("SELECT * FROM `staff_document` WHERE `staff_id` = ? ORDER BY `id` DESC");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function getDocument($staffId, $id) {
        $id = (int) $id;
        $stmt = $this->db->prepare("SELECT * FROM `staff_document` WHERE `staff_id` = ? AND `id` = ? LIMIT 1");
        $stmt->bind_param('si', $staffId, $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function addDocument($staffId, array $row) {
        $sql = "INSERT INTO `staff_document` (`staff_id`, `doc_type`, `title`, `original_name`, `stored_path`, `mime_type`, `file_size`, `uploaded_by`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $size = (int) ($row['file_size'] ?? 0);
        $uploadedBy = (int) ($row['uploaded_by'] ?? 0);
        $stmt->bind_param('ssssssii', $staffId, $row['doc_type'], $row['title'], $row['original_name'], $row['stored_path'], $row['mime_type'], $size, $uploadedBy);
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

    public function approveDocument($staffId, $id, $userId) {
        $id = (int) $id;
        $userId = (int) $userId;
        $stmt = $this->db->prepare("UPDATE `staff_document` SET `approved` = 1, `approved_by` = ?, `approved_at` = NOW() WHERE `staff_id` = ? AND `id` = ?");
        $stmt->bind_param('isi', $userId, $staffId, $id);
        return $stmt->execute();
    }

    public function deleteDocument($staffId, $id) {
        $doc = $this->getDocument($staffId, $id);
        if (!$doc) {
            return false;
        }
        $full = BASE_PATH . '/' . ltrim((string) $doc['stored_path'], '/');
        if (is_file($full)) {
            @unlink($full);
        }
        $id = (int) $id;
        $stmt = $this->db->prepare("DELETE FROM `staff_document` WHERE `staff_id` = ? AND `id` = ?");
        $stmt->bind_param('si', $staffId, $id);
        return $stmt->execute();
    }

    public function storageDir($staffId) {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $staffId);
        $dir = BASE_PATH . '/uploads/staff/' . $safe;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function attendanceSummary($staffId, $fingerNo = '') {
        $fingerNo = trim((string) $fingerNo);
        $summary = [
            'days' => 0,
            'punches' => 0,
            'from' => date('Y-m-d', strtotime('-90 days')),
            'to' => date('Y-m-d'),
            'matched' => $fingerNo !== '',
        ];
        if ($fingerNo === '') {
            return $summary;
        }
        try {
            $check = $this->db->query("SHOW TABLES LIKE 'staff_attendance'");
            if (!$check || $check->num_rows === 0) {
                return $summary;
            }
            $from = $summary['from'] . ' 00:00:00';
            $to = $summary['to'] . ' 23:59:59';
            $sql = "SELECT COUNT(*) AS punches, COUNT(DISTINCT DATE(`attendance_time`)) AS days
                    FROM `staff_attendance`
                    WHERE `attendance_time` BETWEEN ? AND ?
                      AND `employee_no` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sss', $from, $to, $fingerNo);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $summary['punches'] = (int) ($row['punches'] ?? 0);
            $summary['days'] = (int) ($row['days'] ?? 0);
        } catch (Throwable $e) {
            error_log('StaffPersonalFileModel::attendanceSummary: ' . $e->getMessage());
        }
        return $summary;
    }

    public const WORK_START = '08:40:00';
    public const WORK_END = '16:15:00';
    public const SHORT_MORNING_END = '10:10:00';
    public const SHORT_EVENING_START = '14:45:00';
    public const MIDDAY = '12:15:00';

    /**
     * Device punches for one staff member: staff_attendance.employee_no = finger_machine_no.
     * Official hours: 08:40–16:15 (Sri Lanka government office).
     */
    public function deviceMonthAttendance($fingerNo, $month, array $leaveRows = []) {
        $fingerNo = trim((string) $fingerNo);
        $month = trim((string) $month);
        $result = [
            'finger_no' => $fingerNo,
            'month' => $month,
            'month_label' => '',
            'hours' => '08:40 – 16:15',
            'days' => 0,
            'present' => 0,
            'late' => 0,
            'early' => 0,
            'absent' => 0,
            'holiday' => 0,
            'punches' => 0,
            'rows' => [],
            'matched' => $fingerNo !== '',
            'dbError' => null,
        ];
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $result;
        }
        $result['month_label'] = date('F Y', strtotime($month . '-01 12:00:00'));
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from . ' 12:00:00'));
        $leaveByDate = $this->leaveDatesMap($leaveRows, $from, $to);
        $punchesByDate = [];
        if ($fingerNo === '') {
            return $result;
        }
        try {
            $check = $this->db->query("SHOW TABLES LIKE 'staff_attendance'");
            if (!$check || $check->num_rows === 0) {
                $result['dbError'] = 'staff_attendance table is missing.';
                return $result;
            }
            require_once BASE_PATH . '/staff_attendance/config.php';
            $sql = "SELECT DATE(`attendance_time`) AS d,
                           GROUP_CONCAT(DATE_FORMAT(`attendance_time`, '%H:%i:%s') ORDER BY `attendance_time` SEPARATOR ',') AS times_csv,
                           COUNT(*) AS punches
                    FROM `staff_attendance`
                    WHERE DATE(`attendance_time`) BETWEEN ? AND ?
                      AND `employee_no` = ?
                    GROUP BY DATE(`attendance_time`)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sss', $from, $to, $fingerNo);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $d = (string) ($row['d'] ?? '');
                if ($d === '') {
                    continue;
                }
                $split = attendance_split_day_times((string) ($row['times_csv'] ?? ''));
                $punchesByDate[$d] = [
                    'in' => $split['in'],
                    'out' => $split['out'],
                    'punches' => (int) ($row['punches'] ?? 0),
                ];
                $result['punches'] += (int) ($row['punches'] ?? 0);
            }
        } catch (Throwable $e) {
            $result['dbError'] = $e->getMessage();
            error_log('StaffPersonalFileModel::deviceMonthAttendance: ' . $e->getMessage());
            return $result;
        }

        $cursor = strtotime($from . ' 12:00:00');
        $endTs = strtotime($to . ' 12:00:00');
        $rows = [];
        $present = 0;
        $late = 0;
        $early = 0;
        $absent = 0;
        $holiday = 0;
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        while ($cursor <= $endTs) {
            $d = date('Y-m-d', $cursor);
            $dow = (int) date('N', $cursor);
            $cursor = strtotime('+1 day', $cursor);
            if ($dow >= 6) {
                continue;
            }
            $holidayLabel = SriLankaPublicHolidays::isPublicHoliday($d) ? SriLankaPublicHolidays::label($d) : '';
            $punch = $punchesByDate[$d] ?? ['in' => '', 'out' => '', 'punches' => 0];
            $classified = self::classifyOfficeDay($punch['in'], $punch['out'], $leaveByDate[$d] ?? '', $holidayLabel);
            if ($classified['status'] === 'present') {
                $present++;
            } elseif (in_array($classified['status'], ['late', 'short_m', 'late_early'], true)) {
                $late++;
            }
            if (in_array($classified['status'], ['early', 'short_e', 'late_early'], true)) {
                $early++;
            }
            if ($classified['status'] === 'holiday') {
                $holiday++;
            }
            if ($classified['status'] === 'absent') {
                $absent++;
            }
            $rows[] = [
                'date' => $d,
                'day' => date('D', strtotime($d . ' 12:00:00')),
                'in' => $classified['in'],
                'out' => $classified['out'],
                'late' => $classified['late'],
                'early' => $classified['early'],
                'remark' => $classified['remark'],
                'status' => $classified['status'],
                'punches' => (int) ($punch['punches'] ?? 0),
            ];
        }
        $result['rows'] = $rows;
        $result['days'] = $present;
        $result['present'] = $present;
        $result['late'] = $late;
        $result['early'] = $early;
        $result['absent'] = $absent;
        $result['holiday'] = $holiday;
        return $result;
    }

    private function leaveDatesMap(array $leaveRows, $from, $to) {
        $map = [];
        $labels = self::labels()['leave_type'];
        foreach ($leaveRows as $row) {
            $start = (string) ($row['start_date'] ?? '');
            $end = (string) ($row['end_date'] ?? '');
            if ($start === '' || $end === '') {
                continue;
            }
            $label = $labels[$row['leave_type'] ?? ''] ?? 'Leave';
            $ts = strtotime($start . ' 12:00:00');
            $endTs = strtotime($end . ' 12:00:00');
            if ($ts === false || $endTs === false) {
                continue;
            }
            while ($ts <= $endTs) {
                $d = date('Y-m-d', $ts);
                if ($d >= $from && $d <= $to) {
                    $map[$d] = $label;
                }
                $ts = strtotime('+1 day', $ts);
            }
        }
        return $map;
    }

    public static function classifyOfficeDay($inTime, $outTime, $leaveLabel = '', $holidayLabel = '') {
        $in = self::normalizeOfficeTime($inTime);
        $out = self::normalizeOfficeTime($outTime);
        $lateMin = ($in !== '' && $in > self::WORK_START) ? self::minutesBetween(self::WORK_START, $in) : 0;
        $earlyMin = ($out !== '' && $out < self::WORK_END) ? self::minutesBetween($out, self::WORK_END) : 0;
        $displayIn = $in !== '' ? substr($in, 0, 5) : '—';
        $displayOut = $out !== '' ? substr($out, 0, 5) : '—';
        $lateText = $lateMin > 0 ? self::formatMinutes($lateMin) : '—';
        $earlyText = $earlyMin > 0 ? self::formatMinutes($earlyMin) : '—';

        if ($holidayLabel !== '') {
            return [
                'status' => 'holiday',
                'remark' => $holidayLabel,
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => '—',
                'early' => '—',
            ];
        }
        if ($leaveLabel !== '') {
            return [
                'status' => 'leave',
                'remark' => $leaveLabel,
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => $lateText,
                'early' => $earlyText,
            ];
        }
        if ($in === '' && $out === '') {
            return [
                'status' => 'absent',
                'remark' => 'Absent',
                'in' => '—',
                'out' => '—',
                'late' => '—',
                'early' => '—',
            ];
        }
        if ($in !== '' && $out !== '' && $in <= self::WORK_START && $out >= self::WORK_END) {
            return [
                'status' => 'present',
                'remark' => 'Present',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => '—',
                'early' => '—',
            ];
        }
        if ($in !== '' && $out !== '' && $in > self::WORK_START && $in <= self::SHORT_MORNING_END && $out >= self::WORK_END) {
            return [
                'status' => 'short_m',
                'remark' => 'Short Leave (Morning)',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => $lateText,
                'early' => '—',
            ];
        }
        if ($in !== '' && $out !== '' && $in <= self::WORK_START && $out >= self::SHORT_EVENING_START && $out < self::WORK_END) {
            return [
                'status' => 'short_e',
                'remark' => 'Short Leave (Evening)',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => '—',
                'early' => $earlyText,
            ];
        }
        if ($out !== '' && $out < self::MIDDAY) {
            return [
                'status' => 'half_m',
                'remark' => 'Half Day (Morning)',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => $lateText,
                'early' => $earlyText,
            ];
        }
        if ($in !== '' && $in >= self::MIDDAY) {
            return [
                'status' => 'half_e',
                'remark' => 'Half Day (Evening)',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => $lateText,
                'early' => $earlyText,
            ];
        }
        if ($lateMin > 0 && $earlyMin > 0) {
            return [
                'status' => 'late_early',
                'remark' => 'Late coming / Early departure',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => $lateText,
                'early' => $earlyText,
            ];
        }
        if ($lateMin > 0) {
            return [
                'status' => 'late',
                'remark' => 'Late coming',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => $lateText,
                'early' => '—',
            ];
        }
        if ($earlyMin > 0) {
            return [
                'status' => 'early',
                'remark' => 'Early departure',
                'in' => $displayIn,
                'out' => $displayOut,
                'late' => '—',
                'early' => $earlyText,
            ];
        }
        return [
            'status' => 'incomplete',
            'remark' => 'Incomplete punch',
            'in' => $displayIn,
            'out' => $displayOut,
            'late' => $lateText,
            'early' => $earlyText,
        ];
    }

    private static function normalizeOfficeTime($time) {
        $time = trim((string) $time);
        if ($time === '' || $time === '—') {
            return '';
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            $parts = explode(':', $time);
            $h = str_pad((string) ((int) $parts[0]), 2, '0', STR_PAD_LEFT);
            $m = str_pad((string) ((int) ($parts[1] ?? 0)), 2, '0', STR_PAD_LEFT);
            $s = str_pad((string) ((int) ($parts[2] ?? 0)), 2, '0', STR_PAD_LEFT);
            return $h . ':' . $m . ':' . $s;
        }
        return '';
    }

    private static function minutesBetween($from, $to) {
        $a = strtotime('1970-01-01 ' . $from);
        $b = strtotime('1970-01-01 ' . $to);
        if ($a === false || $b === false) {
            return 0;
        }
        return max(0, (int) round(($b - $a) / 60));
    }

    private static function formatMinutes($minutes) {
        $minutes = (int) $minutes;
        if ($minutes <= 0) {
            return '—';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0) {
            return $h . ' h ' . $m . ' min';
        }
        return $m . ' min';
    }

    public function leaveDaysInMonth($staffId, $month) {
        $month = trim((string) $month);
        $total = 0.0;
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $total;
        }
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from . ' 12:00:00'));
        $stmt = $this->db->prepare(
            "SELECT `start_date`, `end_date`, `days`
             FROM `staff_leave_record`
             WHERE `staff_id` = ? AND `start_date` <= ? AND `end_date` >= ?"
        );
        $stmt->bind_param('sss', $staffId, $to, $from);
        $stmt->execute();
        $res = $stmt->get_result();
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        while ($row = $res->fetch_assoc()) {
            $start = (string) ($row['start_date'] ?? '');
            $end = (string) ($row['end_date'] ?? '');
            if ($start === '' || $end === '') {
                continue;
            }
            $overlapStart = max($start, $from);
            $overlapEnd = min($end, $to);
            $days = 0;
            $ts = strtotime($overlapStart . ' 12:00:00');
            $endTs = strtotime($overlapEnd . ' 12:00:00');
            if ($ts === false || $endTs === false || $ts > $endTs) {
                continue;
            }
            while ($ts <= $endTs) {
                $dow = (int) date('N', $ts);
                $ymd = date('Y-m-d', $ts);
                if ($dow < 6 && !SriLankaPublicHolidays::isPublicHoliday($ymd)) {
                    $days++;
                }
                $ts = strtotime('+1 day', $ts);
            }
            $span = (int) round((strtotime($end . ' 12:00:00') - strtotime($start . ' 12:00:00')) / 86400) + 1;
            $recorded = $row['days'] !== null && $row['days'] !== '' ? (float) $row['days'] : (float) $span;
            $fullWorking = 0;
            $fullTs = strtotime($start . ' 12:00:00');
            $fullEnd = strtotime($end . ' 12:00:00');
            while ($fullTs !== false && $fullEnd !== false && $fullTs <= $fullEnd) {
                $dow = (int) date('N', $fullTs);
                $ymd = date('Y-m-d', $fullTs);
                if ($dow < 6 && !SriLankaPublicHolidays::isPublicHoliday($ymd)) {
                    $fullWorking++;
                }
                $fullTs = strtotime('+1 day', $fullTs);
            }
            if ($fullWorking > 0 && $recorded > 0) {
                $total += $recorded * ($days / $fullWorking);
            } else {
                $total += $days;
            }
        }
        return round($total, 1);
    }

    public function leaveTotals($staffId) {
        $totals = [
            'casual' => 0, 'annual' => 0, 'medical' => 0,
            'short_m' => 0, 'short_e' => 0, 'half_m' => 0, 'half_e' => 0,
            'maternity' => 0, 'duty' => 0, 'nopay' => 0, 'other' => 0,
        ];
        $stmt = $this->db->prepare("SELECT `leave_type`, SUM(`days`) AS days FROM `staff_leave_record` WHERE `staff_id` = ? GROUP BY `leave_type`");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $type = (string) ($row['leave_type'] ?? 'other');
            $days = (float) ($row['days'] ?? 0);
            if (isset($totals[$type])) {
                $totals[$type] += $days;
            } else {
                $totals['other'] += $days;
            }
        }
        $totals['vl'] = $totals['annual'];
        $totals['cl'] = $totals['casual'];
        $totals['sl'] = $totals['medical'];
        return $totals;
    }

    /**
     * Sri Lanka government-style leave balances for a calendar year.
     * CL / VL / SL = 21 days. Short leave = 2 occasions in the selected month.
     */
    public function leaveBalances($staffId, $year, $month = '') {
        $year = (int) $year;
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);
        $monthOk = preg_match('/^\d{4}-\d{2}$/', (string) $month);
        $monthFrom = $monthOk ? $month . '-01' : '';
        $monthTo = $monthOk ? date('Y-m-t', strtotime($monthFrom . ' 12:00:00')) : '';

        $availed = ['cl' => 0.0, 'vl' => 0.0, 'sl' => 0.0, 'short' => 0.0];
        $pending = ['cl' => 0.0, 'vl' => 0.0, 'sl' => 0.0, 'short' => 0.0];
        $shortMonth = 0.0;

        $stmt = $this->db->prepare(
            "SELECT `leave_type`, `status`, `days`, `start_date`
             FROM `staff_leave_record`
             WHERE `staff_id` = ? AND `start_date` BETWEEN ? AND ?"
        );
        $stmt->bind_param('sss', $staffId, $from, $to);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if ($status === 'rejected') {
                continue;
            }
            $group = $this->leaveBalanceGroup((string) ($row['leave_type'] ?? ''));
            if ($group === '') {
                continue;
            }
            $amount = $row['days'] !== null && $row['days'] !== '' ? (float) $row['days'] : 1.0;
            if ($group === 'short' && $amount <= 0) {
                $amount = 1.0;
            }
            $bucket = ($status === 'approved') ? 'availed' : 'pending';
            if ($bucket === 'availed') {
                $availed[$group] += $amount;
            } else {
                $pending[$group] += $amount;
            }
            $start = (string) ($row['start_date'] ?? '');
            if ($group === 'short' && $monthFrom !== '' && $start >= $monthFrom && $start <= $monthTo) {
                $shortMonth += $amount;
            }
        }

        $mk = static function ($key, $code, $title, $entitled, $availedAmt, $pendingAmt, $note) {
            $balance = round($entitled - $availedAmt, 1);
            return [
                'key' => $key,
                'code' => $code,
                'title' => $title,
                'entitled' => $entitled,
                'availed' => round($availedAmt, 1),
                'pending' => round($pendingAmt, 1),
                'balance' => $balance,
                'note' => $note,
            ];
        };

        return [
            'year' => $year,
            'month' => $month,
            'items' => [
                $mk('cl', 'CL', 'Casual Leave', 21, $availed['cl'], $pending['cl'], (string) $year),
                $mk('vl', 'VL', 'Vacation Leave', 21, $availed['vl'], $pending['vl'], (string) $year),
                $mk('sl', 'SL', 'Sick Leave', 21, $availed['sl'], $pending['sl'], (string) $year),
                $mk('short', 'SHL', 'Short Leave', 2, $shortMonth, 0, 'this month'),
            ],
        ];
    }

    private function leaveBalanceGroup($type) {
        $type = strtolower(trim((string) $type));
        if (in_array($type, ['casual', 'half_m', 'half_e'], true)) {
            return 'cl';
        }
        if (in_array($type, ['annual', 'vacation'], true)) {
            return 'vl';
        }
        if (in_array($type, ['medical', 'sick'], true)) {
            return 'sl';
        }
        if (in_array($type, ['short_m', 'short_e'], true)) {
            return 'short';
        }
        return '';
    }
}
