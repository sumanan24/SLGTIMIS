<?php
/**
 * Student Registration Checklist — seven stations, fee calculation, persistence.
 */
class StudentRegistrationChecklistModel extends Model {
    protected $table = 'student_registration_checklist';
    private static $ensured = false;

    public function ensureTable(): void {
        if (self::$ensured) {
            return;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(50) NOT NULL,
            `academic_year` VARCHAR(20) NOT NULL,
            `station_data` LONGTEXT NOT NULL,
            `applicable_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `outstanding_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `receipt_number` VARCHAR(80) NOT NULL DEFAULT '',
            `payment_status` VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
            `remarks` TEXT NULL,
            `final_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
            `officer_name` VARCHAR(120) NOT NULL DEFAULT '',
            `coordinator_name` VARCHAR(120) NOT NULL DEFAULT '',
            `created_by` INT NULL,
            `updated_by` INT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_reg_checklist_student_year` (`student_id`, `academic_year`),
            KEY `idx_reg_checklist_year` (`academic_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
        self::$ensured = true;
    }

    /**
     * @return array<int, array{title:string,items:array<string,string>}>
     */
    public static function stations(): array {
        return [
            1 => [
                'title' => 'NIC & Registration Verification',
                'items' => [
                    'nic_verified' => 'NIC verified',
                    'admission_letter_checked' => 'Admission letter checked',
                    'student_details_verified' => 'Student details verified',
                    'course_nvq_confirmed' => 'Course and NVQ level confirmed',
                ],
            ],
            2 => [
                'title' => 'Document Verification & Personal File',
                'items' => [
                    'nic_copy_collected' => 'NIC copy collected',
                    'edu_certs_collected' => 'Educational certificates collected',
                    'required_docs_verified' => 'Required documents verified',
                    'personal_file_opened' => 'Personal file opened and labelled',
                ],
            ],
            3 => [
                'title' => 'Financial Clearance',
                'items' => [
                    'province_nvq_verified' => 'Province and NVQ level verified',
                    'fee_calculated' => 'Correct registration fee calculated',
                    'payment_received' => 'Payment received',
                    'receipt_issued' => 'Receipt issued',
                    'payment_recorded_mis' => 'Payment recorded in MIS',
                ],
            ],
            4 => [
                'title' => 'Student Agreement & Code of Conduct',
                'items' => [
                    'agreement_provided' => 'Agreement provided',
                    'rules_explained' => 'Institute rules explained',
                    'student_signature' => 'Student signature obtained',
                    'parent_signature' => 'Parent / guardian signature obtained',
                ],
            ],
            5 => [
                'title' => 'Student ID Photo Taking',
                'items' => [
                    'photo_captured' => 'Photograph captured',
                    'photo_verified' => 'Photograph verified',
                    'photo_uploaded' => 'Photograph uploaded to MIS',
                ],
            ],
            6 => [
                'title' => 'Hostel Allocation & Agreement',
                'items' => [
                    'hostel_requirement' => 'Hostel requirement confirmed',
                    'hostel_form_collected' => 'Hostel request form collected',
                    'hostel_availability' => 'Hostel availability checked',
                    'room_allocated' => 'Room / bed allocated',
                    'hostel_agreement' => 'Hostel agreement signed',
                ],
            ],
            7 => [
                'title' => 'Orientation Pack & File Collection',
                'items' => [
                    'handbook_distributed' => 'Student handbook distributed',
                    'record_book_distributed' => 'Student record book distributed',
                    'orientation_schedule' => 'Orientation schedule provided',
                    'personal_file_checked' => 'Personal file checked',
                    'final_registration' => 'Final registration confirmed',
                ],
            ],
        ];
    }

    /**
     * @return array{applicable_fee:float,category:string,is_northern:bool,nvq:string}
     */
    public static function calculateFee(?string $province, ?string $nvqLevel): array {
        $nvq = self::normalizeNvq($nvqLevel);
        $isNorthern = self::isNorthernProvince($province);

        if ($nvq === '05') {
            $fee = $isNorthern ? 2500.00 : 4500.00;
        } else {
            // Default / Level 04 schedule
            $fee = $isNorthern ? 2450.00 : 4450.00;
            if ($nvq === '') {
                $nvq = '04';
            }
        }

        $category = ($isNorthern ? 'Northern Province' : 'Outside Northern Province') . ' — NVQ ' . $nvq;

        return [
            'applicable_fee' => $fee,
            'category' => $category,
            'is_northern' => $isNorthern,
            'nvq' => $nvq,
        ];
    }

    public static function normalizeNvq(?string $raw): string {
        $v = strtoupper(trim((string) $raw));
        $v = preg_replace('/^0+/', '', $v) ?? $v;
        $v = trim($v);
        if ($v === '4' || $v === '04') {
            return '04';
        }
        if ($v === '5' || $v === '05') {
            return '05';
        }
        if (preg_match('/4/', $v) && !preg_match('/5/', $v)) {
            return '04';
        }
        if (preg_match('/5/', $v)) {
            return '05';
        }
        return $v !== '' ? str_pad($v, 2, '0', STR_PAD_LEFT) : '';
    }

    public static function isNorthernProvince(?string $province): bool {
        $p = strtolower(trim((string) $province));
        $p = preg_replace('/\s+province$/i', '', $p) ?? $p;
        $p = trim($p);
        return $p === 'northern' || $p === 'north';
    }

    public static function paymentStatus(float $fee, float $paid): string {
        if ($paid <= 0.009) {
            return 'Unpaid';
        }
        if ($paid + 0.009 >= $fee) {
            return 'Paid';
        }
        return 'Partial';
    }

    /**
     * Empty station payload for new checklists.
     * @return array<string, array{items:array<string,bool>,staff:string,completed_at:string}>
     */
    public static function emptyStationData(): array {
        $out = [];
        foreach (self::stations() as $num => $station) {
            $items = [];
            foreach (array_keys($station['items']) as $key) {
                $items[$key] = false;
            }
            $out[(string) $num] = [
                'items' => $items,
                'staff' => '',
                'completed_at' => '',
            ];
        }
        return $out;
    }

    /**
     * @param mixed $raw
     * @return array<string, array{items:array<string,bool>,staff:string,completed_at:string}>
     */
    public static function normalizeStationData($raw): array {
        $base = self::emptyStationData();
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return $base;
        }
        foreach ($base as $num => $station) {
            $src = $raw[$num] ?? $raw[(int) $num] ?? null;
            if (!is_array($src)) {
                continue;
            }
            $itemsSrc = is_array($src['items'] ?? null) ? $src['items'] : $src;
            foreach ($station['items'] as $key => $_) {
                $val = $itemsSrc[$key] ?? false;
                $base[$num]['items'][$key] = !empty($val) && $val !== '0' && $val !== 'false';
            }
            $base[$num]['staff'] = trim((string) ($src['staff'] ?? ''));
            $base[$num]['completed_at'] = trim((string) ($src['completed_at'] ?? ''));
        }
        return $base;
    }

    public function findByStudentYear(string $studentId, string $academicYear): ?array {
        $this->ensureTable();
        $sql = "SELECT * FROM `{$this->table}` WHERE `student_id` = ? AND `academic_year` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $studentId, $academicYear);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        $row['station_data'] = self::normalizeStationData($row['station_data'] ?? []);
        return $row;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function saveChecklist(array $payload): bool {
        $this->ensureTable();
        $studentId = trim((string) ($payload['student_id'] ?? ''));
        $year = trim((string) ($payload['academic_year'] ?? ''));
        if ($studentId === '' || $year === '') {
            return false;
        }

        $stationJson = json_encode(self::normalizeStationData($payload['station_data'] ?? []), JSON_UNESCAPED_UNICODE);
        $fee = (float) ($payload['applicable_fee'] ?? 0);
        $paid = (float) ($payload['amount_paid'] ?? 0);
        $balance = max(0, round($fee - $paid, 2));
        $receipt = trim((string) ($payload['receipt_number'] ?? ''));
        $status = trim((string) ($payload['payment_status'] ?? self::paymentStatus($fee, $paid)));
        $remarks = trim((string) ($payload['remarks'] ?? ''));
        $final = !empty($payload['final_confirmed']) ? 1 : 0;
        $officer = trim((string) ($payload['officer_name'] ?? ''));
        $coordinator = trim((string) ($payload['coordinator_name'] ?? ''));
        $userId = isset($payload['updated_by']) ? (int) $payload['updated_by'] : null;

        $existing = $this->findByStudentYear($studentId, $year);
        if ($existing) {
            $sql = "UPDATE `{$this->table}` SET
                `station_data` = ?, `applicable_fee` = ?, `amount_paid` = ?, `outstanding_balance` = ?,
                `receipt_number` = ?, `payment_status` = ?, `remarks` = ?, `final_confirmed` = ?,
                `officer_name` = ?, `coordinator_name` = ?, `updated_by` = ?
                WHERE `student_id` = ? AND `academic_year` = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param(
                'sdddsssississ',
                $stationJson,
                $fee,
                $paid,
                $balance,
                $receipt,
                $status,
                $remarks,
                $final,
                $officer,
                $coordinator,
                $userId,
                $studentId,
                $year
            );
            $ok = $stmt->execute();
            $stmt->close();
            return (bool) $ok;
        }

        $sql = "INSERT INTO `{$this->table}`
            (`student_id`, `academic_year`, `station_data`, `applicable_fee`, `amount_paid`, `outstanding_balance`,
             `receipt_number`, `payment_status`, `remarks`, `final_confirmed`, `officer_name`, `coordinator_name`,
             `created_by`, `updated_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param(
            'sssdddsssissii',
            $studentId,
            $year,
            $stationJson,
            $fee,
            $paid,
            $balance,
            $receipt,
            $status,
            $remarks,
            $final,
            $officer,
            $coordinator,
            $userId,
            $userId
        );
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }

    /**
     * Progress summary for list view.
     * @param array<string, array{items:array<string,bool>,staff:string,completed_at:string}> $stationData
     */
    public static function progressSummary(array $stationData): array {
        $total = 0;
        $done = 0;
        $stationsDone = 0;
        foreach (self::stations() as $num => $station) {
            $all = true;
            foreach (array_keys($station['items']) as $key) {
                $total++;
                $checked = !empty($stationData[(string) $num]['items'][$key]);
                if ($checked) {
                    $done++;
                } else {
                    $all = false;
                }
            }
            if ($all) {
                $stationsDone++;
            }
        }
        return [
            'items_done' => $done,
            'items_total' => $total,
            'stations_done' => $stationsDone,
            'stations_total' => count(self::stations()),
        ];
    }
}
