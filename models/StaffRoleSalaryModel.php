<?php
/**
 * Staff role salary scales, 18-year increments, revisions, and staff placement.
 */

class StaffRoleSalaryModel extends Model {
    protected $table = 'staff_role_salary_scale';
    private static $tablesEnsured = false;

    const GRADE_1 = 1;
    const GRADE_2 = 2;
    const GRADE_3 = 3;
    const MIN_YEAR = 1;
    const MAX_YEAR = 18;

    /** UI / processing order: Grade 3 (entry) → Grade 2 → Grade 1 */
    public static $grades = [3, 2, 1];

    public static $gradeLabels = [
        3 => 'Grade 3',
        2 => 'Grade 2',
        1 => 'Grade 1',
    ];

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
    }

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureTables() {
        if (self::$tablesEnsured) {
            return;
        }

        $sqlFile = BASE_PATH . '/database/staff_role_salary.sql';
        if (is_readable($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql !== false) {
                try {
                    $conn = $this->db->getConnection();
                    if ($conn->multi_query($sql)) {
                        while ($conn->more_results()) {
                            $conn->next_result();
                        }
                    } else {
                        error_log('StaffRoleSalaryModel::ensureTables multi_query: ' . $conn->error);
                    }
                } catch (Throwable $e) {
                    error_log('StaffRoleSalaryModel::ensureTables: ' . $e->getMessage());
                }
            }
        }

        $this->ensureStaffGradeColumn();
        $this->ensureAllowanceTables();
        $this->ensureForeignKeys();
        self::$tablesEnsured = true;
    }

    private function ensureAllowanceTables() {
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `staff_role_salary_allowance` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `salary_scale_id` INT UNSIGNED NOT NULL,
                `sort_order` TINYINT UNSIGNED NOT NULL,
                `allowance_name` VARCHAR(100) NOT NULL DEFAULT '',
                `allowance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_scale_allowance_order` (`salary_scale_id`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $this->db->query("CREATE TABLE IF NOT EXISTS `staff_role_salary_revision_allowance` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `revision_id` INT UNSIGNED NOT NULL,
                `sort_order` TINYINT UNSIGNED NOT NULL,
                `allowance_name` VARCHAR(100) NOT NULL DEFAULT '',
                `old_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `new_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                PRIMARY KEY (`id`),
                KEY `idx_rev_allowance_revision` (`revision_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $this->db->query("CREATE TABLE IF NOT EXISTS `staff_role_salary_increment_slab` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `salary_scale_id` INT UNSIGNED NOT NULL,
                `sort_order` TINYINT UNSIGNED NOT NULL,
                `years` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `increment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_scale_increment_slab` (`salary_scale_id`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('StaffRoleSalaryModel::ensureAllowanceTables: ' . $e->getMessage());
        }
    }

    private function ensureStaffGradeColumn() {
        try {
            $result = $this->db->query("SHOW COLUMNS FROM `staff` LIKE 'salary_grade'");
            if ($result && $result->num_rows === 0) {
                $this->db->query("ALTER TABLE `staff` ADD COLUMN `salary_grade` TINYINT UNSIGNED DEFAULT NULL COMMENT '1=Grade 1, 2=Grade 2, 3=Grade 3' AFTER `staff_position`");
            }
        } catch (Throwable $e) {
            error_log('StaffRoleSalaryModel::ensureStaffGradeColumn: ' . $e->getMessage());
        }
    }

    private function ensureForeignKeys() {
        $keys = [
            [
                'table' => 'staff_role_salary_scale',
                'name' => 'fk_role_salary_scale_role',
                'sql' => 'ALTER TABLE `staff_role_salary_scale`
                    ADD CONSTRAINT `fk_role_salary_scale_role`
                    FOREIGN KEY (`staff_position_type_id`) REFERENCES `staff_position_type` (`staff_position_type_id`)
                    ON UPDATE CASCADE ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_role_salary_increment',
                'name' => 'fk_role_salary_increment_scale',
                'sql' => 'ALTER TABLE `staff_role_salary_increment`
                    ADD CONSTRAINT `fk_role_salary_increment_scale`
                    FOREIGN KEY (`salary_scale_id`) REFERENCES `staff_role_salary_scale` (`id`)
                    ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_role_salary_revision',
                'name' => 'fk_role_salary_revision_role',
                'sql' => 'ALTER TABLE `staff_role_salary_revision`
                    ADD CONSTRAINT `fk_role_salary_revision_role`
                    FOREIGN KEY (`staff_position_type_id`) REFERENCES `staff_position_type` (`staff_position_type_id`)
                    ON UPDATE CASCADE ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_role_salary_revision',
                'name' => 'fk_role_salary_revision_scale',
                'sql' => 'ALTER TABLE `staff_role_salary_revision`
                    ADD CONSTRAINT `fk_role_salary_revision_scale`
                    FOREIGN KEY (`salary_scale_id`) REFERENCES `staff_role_salary_scale` (`id`)
                    ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_salary',
                'name' => 'fk_staff_salary_staff',
                'sql' => 'ALTER TABLE `staff_salary`
                    ADD CONSTRAINT `fk_staff_salary_staff`
                    FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`)
                    ON UPDATE CASCADE ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_salary',
                'name' => 'fk_staff_salary_role',
                'sql' => 'ALTER TABLE `staff_salary`
                    ADD CONSTRAINT `fk_staff_salary_role`
                    FOREIGN KEY (`staff_position_type_id`) REFERENCES `staff_position_type` (`staff_position_type_id`)
                    ON UPDATE CASCADE',
            ],
            [
                'table' => 'staff_salary_history',
                'name' => 'fk_staff_salary_hist_staff',
                'sql' => 'ALTER TABLE `staff_salary_history`
                    ADD CONSTRAINT `fk_staff_salary_hist_staff`
                    FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`)
                    ON UPDATE CASCADE ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_role_salary_allowance',
                'name' => 'fk_role_salary_allowance_scale',
                'sql' => 'ALTER TABLE `staff_role_salary_allowance`
                    ADD CONSTRAINT `fk_role_salary_allowance_scale`
                    FOREIGN KEY (`salary_scale_id`) REFERENCES `staff_role_salary_scale` (`id`)
                    ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_role_salary_revision_allowance',
                'name' => 'fk_role_salary_rev_allowance',
                'sql' => 'ALTER TABLE `staff_role_salary_revision_allowance`
                    ADD CONSTRAINT `fk_role_salary_rev_allowance`
                    FOREIGN KEY (`revision_id`) REFERENCES `staff_role_salary_revision` (`id`)
                    ON DELETE CASCADE',
            ],
            [
                'table' => 'staff_role_salary_increment_slab',
                'name' => 'fk_role_salary_inc_slab_scale',
                'sql' => 'ALTER TABLE `staff_role_salary_increment_slab`
                    ADD CONSTRAINT `fk_role_salary_inc_slab_scale`
                    FOREIGN KEY (`salary_scale_id`) REFERENCES `staff_role_salary_scale` (`id`)
                    ON DELETE CASCADE',
            ],
        ];

        foreach ($keys as $key) {
            if ($this->foreignKeyExists($key['table'], $key['name'])) {
                continue;
            }
            try {
                $this->db->query($key['sql']);
            } catch (Throwable $e) {
                error_log('StaffRoleSalaryModel FK ' . $key['name'] . ': ' . $e->getMessage());
            }
        }
    }

    private function foreignKeyExists($table, $name) {
        $sql = "SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_NAME = ?
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $table, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public static function isValidGrade($grade) {
        $grade = (int) $grade;
        return in_array($grade, self::$grades, true);
    }

    public static function roundMoney($amount) {
        return round((float) $amount, 2);
    }

    public static function moneyEquals($a, $b) {
        return abs(self::roundMoney($a) - self::roundMoney($b)) < 0.005;
    }

    public static function emptyIncrements() {
        $years = [];
        for ($year = self::MIN_YEAR; $year <= self::MAX_YEAR; $year++) {
            $years[$year] = 0.00;
        }
        return $years;
    }

    public static function defaultAllowanceNames() {
        return [
            1 => 'Cost of Living Allowance',
            2 => 'Special Allowance',
            3 => 'Other Allowance',
        ];
    }

    public static function emptyAllowanceLines() {
        $lines = [];
        foreach (self::defaultAllowanceNames() as $order => $name) {
            $lines[$order] = [
                'sort_order' => $order,
                'allowance_name' => $name,
                'allowance_amount' => 0.00,
            ];
        }
        return $lines;
    }

    public static function emptySlabs() {
        return [
            1 => ['sort_order' => 1, 'years' => 0, 'increment_amount' => 0.00],
            2 => ['sort_order' => 2, 'years' => 0, 'increment_amount' => 0.00],
            3 => ['sort_order' => 3, 'years' => 0, 'increment_amount' => 0.00],
        ];
    }

    public static function expandSlabsToIncrements($slabs) {
        $increments = self::emptyIncrements();
        $year = self::MIN_YEAR;
        foreach ($slabs as $slab) {
            $count = max(0, (int) ($slab['years'] ?? 0));
            $amount = self::roundMoney($slab['increment_amount'] ?? 0);
            for ($i = 0; $i < $count && $year <= self::MAX_YEAR; $i++, $year++) {
                $increments[$year] = $amount;
            }
        }
        return $increments;
    }

    public static function serviceYear($appointmentDate, $asOfDate = null) {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        if ($appointmentDate === null || trim((string) $appointmentDate) === '') {
            return 0;
        }
        try {
            $start = new DateTime($appointmentDate);
            $asOf = new DateTime($asOfDate);
        } catch (Exception $e) {
            return 0;
        }
        if ($asOf < $start) {
            return 0;
        }
        $years = (int) $start->diff($asOf)->y;
        if ($years < 0) {
            return 0;
        }
        return min(self::MAX_YEAR, $years);
    }

    public static function anniversaryDate($appointmentDate, $year) {
        try {
            $date = new DateTime($appointmentDate);
            $date->modify('+' . (int) $year . ' years');
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoleSalaryBundle($roleId) {
        $bundle = [];
        foreach (self::$grades as $grade) {
            $bundle[$grade] = $this->getGradeBundle($roleId, $grade);
        }
        return $bundle;
    }

    public function getGradeBundle($roleId, $grade) {
        $scale = $this->getScale($roleId, $grade);
        $increments = $scale ? $this->getIncrements((int) $scale['id']) : self::emptyIncrements();
        $incrementSlabs = $scale
            ? $this->getIncrementSlabs((int) $scale['id'], $increments)
            : self::emptySlabs();
        $allowanceItems = $scale
            ? $this->getAllowanceLines((int) $scale['id'], $scale['allowances'])
            : self::emptyAllowanceLines();
        $revisions = $this->getRevisions($roleId, $grade);
        $projection = $this->projectScale($scale, $increments);

        return [
            'grade' => (int) $grade,
            'label' => self::$gradeLabels[$grade] ?? ('Grade ' . $grade),
            'scale' => $scale,
            'increments' => $increments,
            'increment_slabs' => $incrementSlabs,
            'allowance_items' => $allowanceItems,
            'revisions' => $revisions,
            'projection' => $projection,
        ];
    }

    public function getScale($roleId, $grade) {
        $sql = "SELECT * FROM `staff_role_salary_scale`
                WHERE `staff_position_type_id` = ? AND `grade` = ?
                LIMIT 1";
        $rows = $this->fetchAll($sql, 'si', [$roleId, (int) $grade]);
        return $rows ? $rows[0] : null;
    }

    public function getIncrements($scaleId) {
        $increments = self::emptyIncrements();
        $sql = "SELECT `service_year`, `increment_amount`
                FROM `staff_role_salary_increment`
                WHERE `salary_scale_id` = ?
                ORDER BY `service_year` ASC";
        $rows = $this->fetchAll($sql, 'i', [(int) $scaleId]);
        foreach ($rows as $row) {
            $year = (int) $row['service_year'];
            if ($year >= self::MIN_YEAR && $year <= self::MAX_YEAR) {
                $increments[$year] = self::roundMoney($row['increment_amount']);
            }
        }
        return $increments;
    }

    public function getIncrementSlabs($scaleId, $fallbackIncrements = null) {
        $slabs = self::emptySlabs();
        $scaleId = (int) $scaleId;
        if ($scaleId > 0) {
            $rows = $this->fetchAll(
                "SELECT `sort_order`, `years`, `increment_amount`
                 FROM `staff_role_salary_increment_slab`
                 WHERE `salary_scale_id` = ?
                 ORDER BY `sort_order` ASC",
                'i',
                [$scaleId]
            );
            if ($rows) {
                foreach ($rows as $row) {
                    $order = (int) $row['sort_order'];
                    if ($order < 1 || $order > 3) {
                        continue;
                    }
                    $slabs[$order]['years'] = (int) $row['years'];
                    $slabs[$order]['increment_amount'] = self::roundMoney($row['increment_amount']);
                }
                return $slabs;
            }
        }
        if (is_array($fallbackIncrements)) {
            return $this->incrementsToSlabs($fallbackIncrements);
        }
        return $slabs;
    }

    private function incrementsToSlabs($increments) {
        $slabs = self::emptySlabs();
        $groups = [];
        $currentAmount = null;
        $currentYears = 0;
        for ($year = self::MIN_YEAR; $year <= self::MAX_YEAR; $year++) {
            $amount = self::roundMoney(isset($increments[$year]) ? $increments[$year] : 0);
            if ($currentYears === 0) {
                $currentAmount = $amount;
                $currentYears = 1;
                continue;
            }
            if (self::moneyEquals($amount, $currentAmount)) {
                $currentYears++;
                continue;
            }
            $groups[] = ['years' => $currentYears, 'increment_amount' => $currentAmount];
            $currentAmount = $amount;
            $currentYears = 1;
        }
        if ($currentYears > 0) {
            $groups[] = ['years' => $currentYears, 'increment_amount' => $currentAmount];
        }
        while (count($groups) > 3) {
            $last = array_pop($groups);
            $prev = array_pop($groups);
            $groups[] = [
                'years' => (int) $prev['years'] + (int) $last['years'],
                'increment_amount' => $prev['increment_amount'],
            ];
        }
        foreach ($groups as $index => $group) {
            $order = $index + 1;
            $slabs[$order]['years'] = (int) $group['years'];
            $slabs[$order]['increment_amount'] = self::roundMoney($group['increment_amount']);
        }
        return $slabs;
    }

    public function getRevisions($roleId, $grade) {
        $sql = "SELECT r.*, u.`user_name`
                FROM `staff_role_salary_revision` r
                LEFT JOIN `user` u ON u.`user_id` = r.`created_by`
                WHERE r.`staff_position_type_id` = ? AND r.`grade` = ?
                ORDER BY r.`effective_date` DESC, r.`id` DESC";
        $revisions = $this->fetchAll($sql, 'si', [$roleId, (int) $grade]);
        return $this->attachRevisionAllowances($revisions);
    }

    public function getAllowanceLines($scaleId, $fallbackTotal = null) {
        $lines = self::emptyAllowanceLines();
        $scaleId = (int) $scaleId;
        if ($scaleId > 0) {
            $rows = $this->fetchAll(
                "SELECT `id`, `sort_order`, `allowance_name`, `allowance_amount`
                 FROM `staff_role_salary_allowance`
                 WHERE `salary_scale_id` = ?
                 ORDER BY `sort_order` ASC",
                'i',
                [$scaleId]
            );
            if ($rows) {
                foreach ($rows as $row) {
                    $order = (int) $row['sort_order'];
                    if ($order < 1 || $order > 3) {
                        continue;
                    }
                    $lines[$order]['id'] = (int) $row['id'];
                    $lines[$order]['allowance_name'] = (string) $row['allowance_name'];
                    $lines[$order]['allowance_amount'] = self::roundMoney($row['allowance_amount']);
                }
                return $lines;
            }
        }

        if ($fallbackTotal !== null && (float) $fallbackTotal > 0) {
            $lines[1]['allowance_name'] = 'Allowances';
            $lines[1]['allowance_amount'] = self::roundMoney($fallbackTotal);
        }
        return $lines;
    }

    /**
     * Validate and normalize posted scale + 18 increment amounts.
     * @return array{ok:bool,errors:string[],data:array}
     */
    public function validateGradeInput($roleId, $grade, $input) {
        $errors = [];
        $grade = (int) $grade;
        if (!self::isValidGrade($grade)) {
            $errors[] = 'Invalid salary grade.';
        }
        if ($roleId === '' || $roleId === null) {
            $errors[] = 'Role ID is required.';
        }

        $basic = $this->parseMoney(isset($input['basic_salary']) ? $input['basic_salary'] : 0);
        $allowanceItems = $this->normalizePostedAllowances($input);
        $allowances = 0.00;
        $namedCount = 0;
        foreach ($allowanceItems as $item) {
            if ($item['allowance_amount'] < 0) {
                $errors[] = 'Allowance amounts cannot be negative.';
                break;
            }
            $allowances = self::roundMoney($allowances + $item['allowance_amount']);
            if (trim($item['allowance_name']) !== '') {
                $namedCount++;
            }
        }
        if ($namedCount < 2) {
            $errors[] = 'Enter at least two allowances, each with a name.';
        }
        $maxBasic = $this->parseMoney(isset($input['max_basic_salary']) ? $input['max_basic_salary'] : 0);
        $effectiveDate = trim((string) (isset($input['effective_date']) ? $input['effective_date'] : ''));
        $remarks = trim((string) (isset($input['remarks']) ? $input['remarks'] : ''));

        if ($basic < 0) {
            $errors[] = 'Basic salary cannot be negative.';
        }
        if ($maxBasic < 0) {
            $errors[] = 'Maximum basic salary cannot be negative.';
        }
        if ($maxBasic > 0 && $basic > $maxBasic) {
            $errors[] = 'Basic salary cannot exceed maximum basic salary.';
        }
        if ($effectiveDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveDate)) {
            $errors[] = 'A valid effective date is required.';
        } else {
            $dt = DateTime::createFromFormat('Y-m-d', $effectiveDate);
            $ok = $dt && $dt->format('Y-m-d') === $effectiveDate;
            if (!$ok) {
                $errors[] = 'Effective date is not a valid calendar date.';
            }
        }
        if (strlen($remarks) > 255) {
            $errors[] = 'Remarks must be 255 characters or fewer.';
        }

        $increments = self::emptyIncrements();
        $slabs = $this->normalizePostedSlabs($input);
        $totalSlabYears = 0;
        foreach ($slabs as $slab) {
            $years = (int) $slab['years'];
            $amount = self::roundMoney($slab['increment_amount']);
            if ($years < 0) {
                $errors[] = 'Year count cannot be negative.';
            }
            if ($amount < 0) {
                $errors[] = 'Increment amount cannot be negative.';
            }
            $totalSlabYears += $years;
        }
        if ($totalSlabYears > self::MAX_YEAR) {
            $errors[] = 'Total increment years cannot exceed ' . self::MAX_YEAR . '.';
        }
        $increments = self::expandSlabsToIncrements($slabs);

        $gross = self::roundMoney($basic + $allowances);

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'data' => [
                'staff_position_type_id' => $roleId,
                'grade' => $grade,
                'basic_salary' => $basic,
                'allowances' => $allowances,
                'allowance_items' => $allowanceItems,
                'gross_salary' => $gross,
                'max_basic_salary' => $maxBasic,
                'effective_date' => $effectiveDate,
                'remarks' => $remarks,
                'increments' => $increments,
                'increment_slabs' => $slabs,
            ],
        ];
    }

    /**
     * Save current scale + 18-year increments.
     * If basic/allowance/max already existed and changed, a revision row is written first.
     *
     * @return array{ok:bool,message:string,revision:bool,scale_id:int|null}
     */
    public function saveGradeSalary($roleId, $grade, $input, $userId = null) {
        $validated = $this->validateGradeInput($roleId, $grade, $input);
        if (!$validated['ok']) {
            return [
                'ok' => false,
                'message' => implode(' ', $validated['errors']),
                'revision' => false,
                'scale_id' => null,
            ];
        }

        $data = $validated['data'];
        $conn = $this->db->getConnection();
        $conn->begin_transaction();

        try {
            $existing = $this->getScale($roleId, $grade);
            $isRevision = false;
            $scaleId = $existing ? (int) $existing['id'] : 0;

            if ($existing && $this->isSalaryRevisionChange($existing, $data)) {
                $isRevision = true;
                $this->insertRevisionRow($existing, $data, $userId);
            }

            if ($existing) {
                $ok = $this->update($scaleId, [
                    'basic_salary' => $this->moneyString($data['basic_salary']),
                    'allowances' => $this->moneyString($data['allowances']),
                    'gross_salary' => $this->moneyString($data['gross_salary']),
                    'max_basic_salary' => $this->moneyString($data['max_basic_salary']),
                    'effective_date' => $data['effective_date'],
                    'updated_by' => $userId,
                ]);
                if (!$ok) {
                    throw new RuntimeException('Failed to update salary scale.');
                }
            } else {
                $inserted = $this->create([
                    'staff_position_type_id' => $roleId,
                    'grade' => (int) $grade,
                    'basic_salary' => $this->moneyString($data['basic_salary']),
                    'allowances' => $this->moneyString($data['allowances']),
                    'gross_salary' => $this->moneyString($data['gross_salary']),
                    'max_basic_salary' => $this->moneyString($data['max_basic_salary']),
                    'effective_date' => $data['effective_date'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                if ($inserted === false) {
                    throw new RuntimeException('Failed to create salary scale.');
                }
                $scale = $this->getScale($roleId, $grade);
                if (!$scale) {
                    throw new RuntimeException('Salary scale was not created.');
                }
                $scaleId = (int) $scale['id'];
            }

            $this->replaceIncrements($scaleId, $data['increments']);
            $this->replaceIncrementSlabs($scaleId, $data['increment_slabs']);
            $this->replaceAllowances($scaleId, $data['allowance_items']);
            $conn->commit();

            return [
                'ok' => true,
                'message' => $isRevision
                    ? 'Salary revision saved. Previous basic, allowance and gross were preserved.'
                    : 'Salary structure saved successfully.',
                'revision' => $isRevision,
                'scale_id' => $scaleId,
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('StaffRoleSalaryModel::saveGradeSalary: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to save salary structure. ' . $e->getMessage(),
                'revision' => false,
                'scale_id' => null,
            ];
        }
    }

    public function assignStaffGrade($staffId, $grade, $userId = null) {
        $grade = ($grade === '' || $grade === null) ? null : (int) $grade;
        if ($grade !== null && !self::isValidGrade($grade)) {
            return ['ok' => false, 'message' => 'Invalid salary grade.'];
        }

        $staff = $this->fetchAll("SELECT `staff_id`, `staff_position` FROM `staff` WHERE `staff_id` = ? LIMIT 1", 's', [$staffId]);
        if (!$staff) {
            return ['ok' => false, 'message' => 'Staff member not found.'];
        }

        if ($grade === null) {
            $stmt = $this->db->prepare("UPDATE `staff` SET `salary_grade` = NULL WHERE `staff_id` = ?");
            if (!$stmt) {
                return ['ok' => false, 'message' => 'Failed to assign grade.'];
            }
            $stmt->bind_param('s', $staffId);
        } else {
            $stmt = $this->db->prepare("UPDATE `staff` SET `salary_grade` = ? WHERE `staff_id` = ?");
            if (!$stmt) {
                return ['ok' => false, 'message' => 'Failed to assign grade.'];
            }
            $stmt->bind_param('is', $grade, $staffId);
        }
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'message' => 'Failed to assign salary grade.'];
        }

        return ['ok' => true, 'message' => 'Salary grade updated.'];
    }

    /**
     * Recalculate and persist salaries for staff on this role (optionally one grade).
     *
     * @return array{ok:bool,message:string,updated:int}
     */
    public function applyToRoleStaff($roleId, $grade = null, $userId = null, $asOfDate = null) {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $staffRows = $this->getRoleStaff($roleId, $grade);
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        $updated = 0;

        try {
            foreach ($staffRows as $staff) {
                if (empty($staff['salary_grade']) || !self::isValidGrade($staff['salary_grade'])) {
                    continue;
                }
                $calc = $this->calculateStaffSalary($staff, $asOfDate);
                if (empty($calc['has_scale'])) {
                    continue;
                }
                $this->persistStaffSalary($staff, $calc, $userId);
                $updated++;
            }
            $conn->commit();
            return [
                'ok' => true,
                'message' => $updated > 0
                    ? 'Applied calculated salaries to ' . $updated . ' staff member(s). Historical rows were preserved.'
                    : 'No staff with an assigned grade and salary scale were updated.',
                'updated' => $updated,
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('StaffRoleSalaryModel::applyToRoleStaff: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to apply staff salaries. ' . $e->getMessage(),
                'updated' => 0,
            ];
        }
    }

    public function getRoleStaff($roleId, $grade = null) {
        $sql = "SELECT s.`staff_id`, s.`staff_name`, s.`staff_date_of_join`, s.`staff_position`,
                       s.`salary_grade`, s.`staff_status`, d.`department_name`,
                       ss.`current_basic`, ss.`current_allowance`, ss.`current_gross`,
                       ss.`last_applied_service_year`, ss.`last_event_type`, ss.`effective_date` AS salary_effective_date
                FROM `staff` s
                LEFT JOIN `department` d ON d.`department_id` = s.`department_id`
                LEFT JOIN `staff_salary` ss ON ss.`staff_id` = s.`staff_id`
                WHERE s.`staff_position` = ?";
        $types = 's';
        $params = [$roleId];
        if ($grade !== null && $grade !== '' && self::isValidGrade($grade)) {
            $sql .= " AND s.`salary_grade` = ?";
            $types .= 'i';
            $params[] = (int) $grade;
        }
        $sql .= " ORDER BY s.`staff_name` ASC, s.`staff_id` ASC";
        return $this->fetchAll($sql, $types, $params);
    }

    public function getRoleStaffWithCalculation($roleId, $asOfDate = null) {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $rows = $this->getRoleStaff($roleId);
        $out = [];
        foreach ($rows as $staff) {
            $staff['service_year'] = self::serviceYear($staff['staff_date_of_join'] ?? null, $asOfDate);
            $staff['calculation'] = $this->calculateStaffSalary($staff, $asOfDate);
            $staff['history'] = $this->getStaffHistory($staff['staff_id']);
            $out[] = $staff;
        }
        return $out;
    }

    public function getStaffHistory($staffId) {
        $sql = "SELECT * FROM `staff_salary_history`
                WHERE `staff_id` = ?
                ORDER BY `effective_date` DESC, `id` DESC";
        return $this->fetchAll($sql, 's', [$staffId]);
    }

    /**
     * Place an employee on the scale in effect on $asOfDate.
     *
     * New Basic = Previous Basic + Applicable Year Increment
     * After a revision, increments are applied from the revised/new basic (new scale start),
     * never from the overwritten old salary.
     */
    public function calculateStaffSalary($staff, $asOfDate = null) {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $roleId = (string) ($staff['staff_position'] ?? $staff['staff_position_type_id'] ?? '');
        $grade = isset($staff['salary_grade']) ? (int) $staff['salary_grade'] : 0;
        $appointment = $staff['staff_date_of_join'] ?? null;

        $empty = [
            'has_scale' => false,
            'grade' => $grade,
            'service_year' => self::serviceYear($appointment, $asOfDate),
            'appointment_date' => $appointment,
            'as_of_date' => $asOfDate,
            'start_basic' => 0.00,
            'increment_total' => 0.00,
            'basic' => 0.00,
            'allowance' => 0.00,
            'gross' => 0.00,
            'max_basic' => 0.00,
            'capped' => false,
            'scale_effective_date' => null,
            'revision_applied' => false,
            'year_steps' => [],
        ];

        if ($roleId === '' || !self::isValidGrade($grade)) {
            return $empty;
        }

        $scale = $this->getScale($roleId, $grade);
        if (!$scale) {
            return $empty;
        }

        $increments = $this->getIncrements((int) $scale['id']);
        $revisions = array_reverse($this->getRevisions($roleId, $grade)); // oldest first
        $inEffect = $this->scaleInEffect($scale, $revisions, $asOfDate);
        $serviceYear = self::serviceYear($appointment, $asOfDate);

        return $this->placeOnScale(
            $inEffect['basic'],
            $inEffect['allowance'],
            $inEffect['max_basic'],
            $increments,
            $serviceYear,
            [
                'has_scale' => true,
                'grade' => $grade,
                'appointment_date' => $appointment,
                'as_of_date' => $asOfDate,
                'scale_effective_date' => $inEffect['effective_date'],
                'revision_applied' => $inEffect['revision_applied'],
            ]
        );
    }

    /**
     * Year-by-year projection from current (or in-effect) starting basic.
     */
    public function projectScale($scale, $increments) {
        $rows = [];
        $basic = $scale ? self::roundMoney($scale['basic_salary']) : 0.00;
        $allowance = $scale ? self::roundMoney($scale['allowances']) : 0.00;
        $maxBasic = $scale ? self::roundMoney($scale['max_basic_salary']) : 0.00;
        $increments = $increments ?: self::emptyIncrements();

        $rows[0] = [
            'year' => 0,
            'increment' => 0.00,
            'basic' => $basic,
            'allowance' => $allowance,
            'gross' => self::roundMoney($basic + $allowance),
            'capped' => false,
        ];

        $running = $basic;
        for ($year = self::MIN_YEAR; $year <= self::MAX_YEAR; $year++) {
            $inc = self::roundMoney(isset($increments[$year]) ? $increments[$year] : 0);
            $next = self::roundMoney($running + $inc);
            $capped = false;
            if ($maxBasic > 0 && $next > $maxBasic) {
                $next = $maxBasic;
                $capped = true;
            }
            $rows[$year] = [
                'year' => $year,
                'increment' => $inc,
                'basic' => $next,
                'allowance' => $allowance,
                'gross' => self::roundMoney($next + $allowance),
                'capped' => $capped,
            ];
            $running = $next;
        }

        return $rows;
    }

    /**
     * Public calculator used by tests: start + year increments, revision-aware.
     */
    public function calculateFromParts($startBasic, $allowance, $maxBasic, $increments, $serviceYear, $revisedBasic = null) {
        if ($revisedBasic !== null) {
            $startBasic = $revisedBasic;
        }
        return $this->placeOnScale($startBasic, $allowance, $maxBasic, $increments, $serviceYear, [
            'has_scale' => true,
            'revision_applied' => $revisedBasic !== null,
        ]);
    }

    private function placeOnScale($startBasic, $allowance, $maxBasic, $increments, $serviceYear, $extra = []) {
        $startBasic = self::roundMoney($startBasic);
        $allowance = self::roundMoney($allowance);
        $maxBasic = self::roundMoney($maxBasic);
        $serviceYear = max(0, min(self::MAX_YEAR, (int) $serviceYear));
        $increments = $increments ?: self::emptyIncrements();

        $running = $startBasic;
        $incrementTotal = 0.00;
        $capped = false;
        $yearSteps = [];

        for ($year = self::MIN_YEAR; $year <= $serviceYear; $year++) {
            $inc = self::roundMoney(isset($increments[$year]) ? $increments[$year] : 0);
            $previous = $running;
            $next = self::roundMoney($previous + $inc);
            if ($maxBasic > 0 && $next > $maxBasic) {
                $next = $maxBasic;
                $capped = true;
            }
            $yearSteps[] = [
                'year' => $year,
                'increment' => $inc,
                'previous_basic' => $previous,
                'new_basic' => $next,
            ];
            $incrementTotal = self::roundMoney($incrementTotal + ($next - $previous));
            $running = $next;
        }

        $basic = $running;
        $gross = self::roundMoney($basic + $allowance);

        return array_merge([
            'has_scale' => true,
            'service_year' => $serviceYear,
            'start_basic' => $startBasic,
            'increment_total' => $incrementTotal,
            'basic' => $basic,
            'allowance' => $allowance,
            'gross' => $gross,
            'max_basic' => $maxBasic,
            'capped' => $capped,
            'year_steps' => $yearSteps,
            'revision_applied' => false,
        ], $extra);
    }

    /**
     * Reconstruct the scale start that was in effect on $asOfDate from current scale + revision history.
     */
    private function scaleInEffect($currentScale, $revisionsOldestFirst, $asOfDate) {
        $basic = self::roundMoney($currentScale['basic_salary']);
        $allowance = self::roundMoney($currentScale['allowances']);
        $maxBasic = self::roundMoney($currentScale['max_basic_salary']);
        $effective = $currentScale['effective_date'];
        $revisionApplied = false;

        $future = [];
        $applied = [];
        foreach ($revisionsOldestFirst as $revision) {
            if (($revision['effective_date'] ?? '') <= $asOfDate) {
                $applied[] = $revision;
            } else {
                $future[] = $revision;
            }
        }

        if ($future) {
            $firstFuture = $future[0];
            $basic = self::roundMoney($firstFuture['old_basic']);
            $allowance = self::roundMoney($firstFuture['old_allowance']);
            $maxBasic = self::roundMoney($firstFuture['old_max_basic']);
            $effective = $firstFuture['effective_date'];
        }

        if ($applied) {
            $latest = $applied[count($applied) - 1];
            $basic = self::roundMoney($latest['new_basic']);
            $allowance = self::roundMoney($latest['new_allowance']);
            $maxBasic = self::roundMoney($latest['new_max_basic']);
            $effective = $latest['effective_date'];
            $revisionApplied = true;
        }

        return [
            'basic' => $basic,
            'allowance' => $allowance,
            'max_basic' => $maxBasic,
            'effective_date' => $effective,
            'revision_applied' => $revisionApplied,
        ];
    }

    private function isSalaryRevisionChange($existing, $data) {
        if (!self::moneyEquals($existing['basic_salary'], $data['basic_salary'])
            || !self::moneyEquals($existing['allowances'], $data['allowances'])
            || !self::moneyEquals($existing['max_basic_salary'], $data['max_basic_salary'])
        ) {
            return true;
        }
        $oldItems = $this->getAllowanceLines((int) $existing['id'], $existing['allowances']);
        $newItems = isset($data['allowance_items']) ? $data['allowance_items'] : self::emptyAllowanceLines();
        for ($order = 1; $order <= 3; $order++) {
            $oldName = trim((string) ($oldItems[$order]['allowance_name'] ?? ''));
            $newName = trim((string) ($newItems[$order]['allowance_name'] ?? ''));
            $oldAmt = isset($oldItems[$order]['allowance_amount']) ? $oldItems[$order]['allowance_amount'] : 0;
            $newAmt = isset($newItems[$order]['allowance_amount']) ? $newItems[$order]['allowance_amount'] : 0;
            if ($oldName !== $newName || !self::moneyEquals($oldAmt, $newAmt)) {
                return true;
            }
        }
        return false;
    }

    private function insertRevisionRow($existing, $data, $userId) {
        $sql = "INSERT INTO `staff_role_salary_revision` (
                    `staff_position_type_id`, `grade`, `salary_scale_id`, `revision_date`, `effective_date`,
                    `old_basic`, `old_allowance`, `old_gross`, `old_max_basic`,
                    `new_basic`, `new_allowance`, `new_gross`, `new_max_basic`,
                    `remarks`, `created_by`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare salary revision insert.');
        }

        $roleId = $existing['staff_position_type_id'];
        $grade = (int) $existing['grade'];
        $scaleId = (int) $existing['id'];
        $revisionDate = date('Y-m-d');
        $effectiveDate = $data['effective_date'];
        $oldBasic = $this->moneyString($existing['basic_salary']);
        $oldAllowance = $this->moneyString($existing['allowances']);
        $oldGross = $this->moneyString($existing['gross_salary']);
        $oldMax = $this->moneyString($existing['max_basic_salary']);
        $newBasic = $this->moneyString($data['basic_salary']);
        $newAllowance = $this->moneyString($data['allowances']);
        $newGross = $this->moneyString($data['gross_salary']);
        $newMax = $this->moneyString($data['max_basic_salary']);
        $remarks = $data['remarks'] !== '' ? $data['remarks'] : 'Salary revision';
        $createdBy = $userId;

        $stmt->bind_param(
            'siisssssssssssi',
            $roleId,
            $grade,
            $scaleId,
            $revisionDate,
            $effectiveDate,
            $oldBasic,
            $oldAllowance,
            $oldGross,
            $oldMax,
            $newBasic,
            $newAllowance,
            $newGross,
            $newMax,
            $remarks,
            $createdBy
        );

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Failed to preserve salary revision history: ' . $err);
        }
        $stmt->close();
        $revisionId = (int) $this->db->lastInsertId();
        $this->insertRevisionAllowances($revisionId, $existing, $data);
    }

    private function insertRevisionAllowances($revisionId, $existing, $data) {
        $oldItems = $this->getAllowanceLines((int) $existing['id'], $existing['allowances']);
        $newItems = isset($data['allowance_items']) ? $data['allowance_items'] : self::emptyAllowanceLines();
        $sql = "INSERT INTO `staff_role_salary_revision_allowance`
                    (`revision_id`, `sort_order`, `allowance_name`, `old_amount`, `new_amount`)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare allowance revision insert.');
        }
        for ($order = 1; $order <= 3; $order++) {
            $name = trim((string) ($newItems[$order]['allowance_name'] ?? $oldItems[$order]['allowance_name'] ?? 'Allowance ' . $order));
            if ($name === '') {
                $name = trim((string) ($oldItems[$order]['allowance_name'] ?? ''));
            }
            if ($name === '') {
                $name = 'Allowance ' . $order;
            }
            $oldAmt = $this->moneyString($oldItems[$order]['allowance_amount'] ?? 0);
            $newAmt = $this->moneyString($newItems[$order]['allowance_amount'] ?? 0);
            $stmt->bind_param('iisss', $revisionId, $order, $name, $oldAmt, $newAmt);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Failed to preserve allowance revision: ' . $err);
            }
        }
        $stmt->close();
    }

    private function replaceAllowances($scaleId, $items) {
        $items = $items ? $items : self::emptyAllowanceLines();
        $sql = "INSERT INTO `staff_role_salary_allowance`
                    (`salary_scale_id`, `sort_order`, `allowance_name`, `allowance_amount`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `allowance_name` = VALUES(`allowance_name`),
                    `allowance_amount` = VALUES(`allowance_amount`)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare allowance upsert.');
        }
        for ($order = 1; $order <= 3; $order++) {
            $name = trim((string) ($items[$order]['allowance_name'] ?? ''));
            $amount = $this->moneyString($items[$order]['allowance_amount'] ?? 0);
            $stmt->bind_param('iiss', $scaleId, $order, $name, $amount);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Failed to save allowance ' . $order . ': ' . $err);
            }
        }
        $stmt->close();
    }

    private function normalizePostedAllowances($input) {
        $lines = self::emptyAllowanceLines();
        $posted = [];
        if (isset($input['allowance_items']) && is_array($input['allowance_items'])) {
            $posted = $input['allowance_items'];
        } elseif (isset($input['allowance_name']) && is_array($input['allowance_name'])) {
            foreach ($input['allowance_name'] as $order => $name) {
                $posted[$order] = [
                    'allowance_name' => $name,
                    'allowance_amount' => isset($input['allowance_amount'][$order]) ? $input['allowance_amount'][$order] : 0,
                ];
            }
        }

        $hasPostedLines = false;
        foreach ($posted as $order => $row) {
            $order = (int) $order;
            if ($order < 1 || $order > 3) {
                continue;
            }
            $hasPostedLines = true;
            $name = trim((string) (isset($row['allowance_name']) ? $row['allowance_name'] : (isset($row['name']) ? $row['name'] : '')));
            $amount = $this->parseMoney(isset($row['allowance_amount']) ? $row['allowance_amount'] : (isset($row['amount']) ? $row['amount'] : 0));
            if ($name === '' && $amount > 0) {
                $defaults = self::defaultAllowanceNames();
                $name = $defaults[$order];
            }
            $lines[$order]['allowance_name'] = $name;
            $lines[$order]['allowance_amount'] = $amount;
        }

        if (!$hasPostedLines && isset($input['allowances'])) {
            $lines[1]['allowance_name'] = 'Allowances';
            $lines[1]['allowance_amount'] = $this->parseMoney($input['allowances']);
            $lines[2]['allowance_name'] = 'Special Allowance';
            $lines[2]['allowance_amount'] = 0.00;
        }

        return $lines;
    }

    private function attachRevisionAllowances($revisions) {
        if (!$revisions) {
            return $revisions;
        }
        $ids = [];
        foreach ($revisions as $revision) {
            $ids[] = (int) $revision['id'];
        }
        $ids = array_filter($ids);
        if (!$ids) {
            return $revisions;
        }
        $in = implode(',', $ids);
        $rows = [];
        $result = $this->db->query(
            "SELECT `revision_id`, `sort_order`, `allowance_name`, `old_amount`, `new_amount`
             FROM `staff_role_salary_revision_allowance`
             WHERE `revision_id` IN ({$in})
             ORDER BY `sort_order` ASC"
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[(int) $row['revision_id']][] = $row;
            }
        }
        foreach ($revisions as $index => $revision) {
            $revisions[$index]['allowance_items'] = isset($rows[(int) $revision['id']]) ? $rows[(int) $revision['id']] : [];
        }
        return $revisions;
    }

    private function replaceIncrements($scaleId, $increments) {
        $sql = "INSERT INTO `staff_role_salary_increment` (`salary_scale_id`, `service_year`, `increment_amount`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `increment_amount` = VALUES(`increment_amount`)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare increment upsert.');
        }
        for ($year = self::MIN_YEAR; $year <= self::MAX_YEAR; $year++) {
            $amount = $this->moneyString(isset($increments[$year]) ? $increments[$year] : 0);
            $stmt->bind_param('iis', $scaleId, $year, $amount);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Failed to save year ' . $year . ' increment: ' . $err);
            }
        }
        $stmt->close();
    }

    private function replaceIncrementSlabs($scaleId, $slabs) {
        $slabs = $slabs ? $slabs : self::emptySlabs();
        $sql = "INSERT INTO `staff_role_salary_increment_slab`
                    (`salary_scale_id`, `sort_order`, `years`, `increment_amount`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `years` = VALUES(`years`),
                    `increment_amount` = VALUES(`increment_amount`)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare increment slab upsert.');
        }
        for ($order = 1; $order <= 3; $order++) {
            $years = (int) ($slabs[$order]['years'] ?? 0);
            $amount = $this->moneyString($slabs[$order]['increment_amount'] ?? 0);
            $stmt->bind_param('iiis', $scaleId, $order, $years, $amount);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Failed to save increment slab ' . $order . ': ' . $err);
            }
        }
        $stmt->close();
    }

    private function normalizePostedSlabs($input) {
        $slabs = self::emptySlabs();
        $posted = [];
        if (isset($input['increment_slabs']) && is_array($input['increment_slabs'])) {
            $posted = $input['increment_slabs'];
        } elseif (isset($input['slab_years']) && is_array($input['slab_years'])) {
            $amounts = isset($input['slab_amount']) && is_array($input['slab_amount']) ? $input['slab_amount'] : [];
            foreach ($input['slab_years'] as $order => $years) {
                $posted[$order] = [
                    'years' => $years,
                    'increment_amount' => isset($amounts[$order]) ? $amounts[$order] : 0,
                ];
            }
        } elseif (isset($input['increments']) && is_array($input['increments'])) {
            return $this->incrementsToSlabs($input['increments']);
        }

        foreach ($posted as $order => $row) {
            $order = (int) $order;
            if ($order < 1 || $order > 3) {
                continue;
            }
            $slabs[$order]['years'] = max(0, (int) ($row['years'] ?? 0));
            $slabs[$order]['increment_amount'] = $this->parseMoney(
                isset($row['increment_amount']) ? $row['increment_amount'] : (isset($row['amount']) ? $row['amount'] : 0)
            );
        }
        return $slabs;
    }

    private function persistStaffSalary($staff, $calc, $userId) {
        $staffId = $staff['staff_id'];
        $roleId = $staff['staff_position'];
        $grade = (int) $staff['salary_grade'];
        $current = $this->fetchAll("SELECT * FROM `staff_salary` WHERE `staff_id` = ? LIMIT 1", 's', [$staffId]);
        $existing = $current ? $current[0] : null;

        $oldBasic = $existing ? self::roundMoney($existing['current_basic']) : 0.00;
        $oldAllowance = $existing ? self::roundMoney($existing['current_allowance']) : 0.00;
        $oldGross = $existing ? self::roundMoney($existing['current_gross']) : 0.00;

        $eventType = 'INITIAL';
        if ($existing) {
            if (self::moneyEquals($oldBasic, $calc['basic'])
                && self::moneyEquals($oldAllowance, $calc['allowance'])
                && self::moneyEquals($oldGross, $calc['gross'])
            ) {
                return;
            }
            if (!empty($calc['revision_applied']) && !self::moneyEquals($oldBasic, $calc['basic'])) {
                $eventType = 'REVISION';
            } elseif ((int) $calc['service_year'] > (int) ($existing['last_applied_service_year'] ?? 0)) {
                $eventType = 'INCREMENT';
            } else {
                $eventType = !empty($calc['revision_applied']) ? 'REVISION' : 'INCREMENT';
            }
        }

        $incrementAmount = self::roundMoney($calc['basic'] - $oldBasic);
        $effectiveDate = $calc['as_of_date'];

        $hist = $this->db->prepare("INSERT INTO `staff_salary_history` (
                `staff_id`, `staff_position_type_id`, `grade`, `event_type`, `service_year`,
                `old_basic`, `old_allowance`, `old_gross`,
                `new_basic`, `new_allowance`, `new_gross`,
                `increment_amount`, `effective_date`, `remarks`, `created_by`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$hist) {
            throw new RuntimeException('Failed to prepare staff salary history insert.');
        }
        $serviceYear = (int) $calc['service_year'];
        $oldBasicS = $this->moneyString($oldBasic);
        $oldAllowanceS = $this->moneyString($oldAllowance);
        $oldGrossS = $this->moneyString($oldGross);
        $newBasicS = $this->moneyString($calc['basic']);
        $newAllowanceS = $this->moneyString($calc['allowance']);
        $newGrossS = $this->moneyString($calc['gross']);
        $incS = $this->moneyString($incrementAmount);
        $remarks = $eventType === 'REVISION'
            ? 'Applied salary revision; subsequent increments use the revised basic.'
            : ($eventType === 'INCREMENT'
                ? 'Applied year ' . $serviceYear . ' increment from previous basic.'
                : 'Initial placement on salary scale.');
        $hist->bind_param(
            'ssisisssssssssi',
            $staffId,
            $roleId,
            $grade,
            $eventType,
            $serviceYear,
            $oldBasicS,
            $oldAllowanceS,
            $oldGrossS,
            $newBasicS,
            $newAllowanceS,
            $newGrossS,
            $incS,
            $effectiveDate,
            $remarks,
            $userId
        );
        if (!$hist->execute()) {
            $err = $hist->error;
            $hist->close();
            throw new RuntimeException('Failed to write staff salary history: ' . $err);
        }
        $hist->close();

        if ($existing) {
            $upd = $this->db->prepare("UPDATE `staff_salary` SET
                    `staff_position_type_id` = ?, `grade` = ?,
                    `current_basic` = ?, `current_allowance` = ?, `current_gross` = ?,
                    `last_applied_service_year` = ?, `last_event_type` = ?,
                    `last_increment_date` = ?, `effective_date` = ?, `updated_by` = ?
                WHERE `staff_id` = ?");
            if (!$upd) {
                throw new RuntimeException('Failed to prepare staff salary update.');
            }
            $upd->bind_param(
                'sisssisssis',
                $roleId,
                $grade,
                $newBasicS,
                $newAllowanceS,
                $newGrossS,
                $serviceYear,
                $eventType,
                $effectiveDate,
                $effectiveDate,
                $userId,
                $staffId
            );
            if (!$upd->execute()) {
                $err = $upd->error;
                $upd->close();
                throw new RuntimeException('Failed to update staff salary: ' . $err);
            }
            $upd->close();
        } else {
            $ins = $this->db->prepare("INSERT INTO `staff_salary` (
                    `staff_id`, `staff_position_type_id`, `grade`,
                    `current_basic`, `current_allowance`, `current_gross`,
                    `last_applied_service_year`, `last_event_type`, `last_increment_date`,
                    `effective_date`, `created_by`, `updated_by`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$ins) {
                throw new RuntimeException('Failed to prepare staff salary insert.');
            }
            $ins->bind_param(
                'ssisssisssii',
                $staffId,
                $roleId,
                $grade,
                $newBasicS,
                $newAllowanceS,
                $newGrossS,
                $serviceYear,
                $eventType,
                $effectiveDate,
                $effectiveDate,
                $userId,
                $userId
            );
            if (!$ins->execute()) {
                $err = $ins->error;
                $ins->close();
                throw new RuntimeException('Failed to insert staff salary: ' . $err);
            }
            $ins->close();
        }
    }

    private function parseMoney($value) {
        if ($value === null || $value === '') {
            return 0.00;
        }
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        if (!is_numeric($value)) {
            return 0.00;
        }
        return self::roundMoney($value);
    }

    private function moneyString($amount) {
        return number_format(self::roundMoney($amount), 2, '.', '');
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    private function fetchAll($sql, $types = '', $params = []) {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('StaffRoleSalaryModel::fetchAll prepare: ' . $this->db->getConnection()->error);
            return [];
        }
        if ($types !== '' && $params) {
            $refs = [];
            foreach ($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
        }
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }
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
}
