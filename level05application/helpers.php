<?php
/**
 * helpers.php — validation, table ensure, upload helpers for Level 05 wizard → student_applications.
 */
declare(strict_types=1);

/** NVQ Level for this wizard (must match DB ENUM). */
const L05_APP_LEVEL = '05';

/** Placeholder full name for a row created at NIC step; must be replaced before submit. */
const L05_DRAFT_FULL_NAME_PLACEHOLDER = '(Pending)';

const L05_UPLOAD_MAX = 5242880; // 5 MB (incoming); images are recompressed toward target below
/** Target max size after server-side image compression (bytes). */
const L05_COMPRESSED_TARGET_BYTES = 102400; // 100 KB
/** Longest edge (px) before extra scaling; keeps scans readable. */
const L05_IMAGE_MAX_EDGE_PIXELS = 1920;
/** Do not shrink shorter edge below this (readability floor). */
const L05_IMAGE_MIN_SHORT_EDGE_PIXELS = 400;

const L05_ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png'];

const L05_FILE_FIELDS = [
    'nic_document' => 'nic_document_path',
    'birth_certificate' => 'birth_certificate_path',
    'ol_certificate' => 'ol_certificate_path',
    'al_certificate' => 'al_certificate_path',
    'nvq_certificate' => 'nvq_certificate_path',
    'bank_receipt' => 'bank_receipt_path',
];

/** Input name => safe stored basename (no extension). Files live under uploads/student_applications/{NIC}/ */
const L05_UPLOAD_FILE_BASENAMES = [
    'nic_document' => 'nic_document',
    'birth_certificate' => 'birth_certificate',
    'ol_certificate' => 'ol_certificate',
    'al_certificate' => 'al_certificate',
    'nvq_certificate' => 'nvq_certificate',
    'bank_receipt' => 'bank_receipt',
];

/**
 * Normalize NIC: uppercase, strip spaces / hyphen / underscore.
 */
function nic_normalize(string $nic): string {
    $nic = strtoupper(trim($nic));
    return (string) preg_replace('/[\s\-_]+/', '', $nic);
}

/**
 * Sri Lanka NIC: 9 digits + V or X, or 12 digits.
 */
function nic_valid(string $nic): bool {
    return (bool) preg_match('/^(\d{9}[VX]|\d{12})$/', $nic);
}

/**
 * Ensure student_applications table exists (from project SQL file).
 */
function l05_ensure_student_applications_table(mysqli $conn): void {
    $sqlFile = dirname(__DIR__) . '/database/student_applications.sql';
    if (is_readable($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if ($sql !== false && $sql !== '') {
            $lines = preg_split('/\R/', $sql);
            $kept = [];
            foreach ($lines as $line) {
                $t = trim($line);
                if ($t === '' || strpos($t, '--') === 0) {
                    continue;
                }
                $kept[] = $line;
            }
            $sql = trim(implode("\n", $kept));
            if ($sql !== '') {
                try {
                    // Same pattern as StudentApplicationModel::ensureTable (handles multi-statement SQL reliably).
                    $conn->multi_query($sql);
                    while ($conn->more_results() && $conn->next_result()) {
                        /* flush */
                    }
                } catch (Throwable $e) {
                    error_log('l05_ensure_student_applications_table CREATE: ' . $e->getMessage());
                }
            }
        }
    }

    l05_migrate_student_applications_schema($conn);
}

/**
 * Older installs have student_applications without application_level and with single-column UNIQUE on NIC/email.
 * Level 04/05 flows require application_level and composite uniques.
 */
function l05_migrate_student_applications_schema(mysqli $conn): void {
    try {
        $tbl = $conn->query("SHOW TABLES LIKE 'student_applications'");
        if (!$tbl || $tbl->num_rows === 0) {
            if ($tbl) {
                $tbl->free();
            }
            return;
        }
        $tbl->free();

        $col = $conn->query("SHOW COLUMNS FROM `student_applications` LIKE 'application_level'");
        if ($col && $col->num_rows > 0) {
            $col->free();
            return;
        }
        if ($col) {
            $col->free();
        }

        try {
            $conn->query(
                "ALTER TABLE `student_applications` ADD COLUMN `application_level` ENUM('04','05') NOT NULL DEFAULT '05' "
                . "COMMENT 'NVQ Level applied for' AFTER `application_id`"
            );
        } catch (Throwable $e) {
            error_log('l05_migrate: add application_level: ' . $e->getMessage());
            return;
        }

        foreach (['student_nic', 'student_email'] as $idx) {
            try {
                $idxEsc = str_replace('`', '``', $idx);
                $ix = $conn->query("SHOW INDEX FROM `student_applications` WHERE Key_name = '" . $conn->real_escape_string($idx) . "'");
                if ($ix && $ix->num_rows > 0) {
                    $ix->free();
                    $conn->query("ALTER TABLE `student_applications` DROP INDEX `" . $idxEsc . "`");
                } elseif ($ix) {
                    $ix->free();
                }
            } catch (Throwable $e) {
                error_log('l05_migrate: drop index ' . $idx . ': ' . $e->getMessage());
            }
        }

        foreach (
            [
                'uq_nic_level' => 'ALTER TABLE `student_applications` ADD UNIQUE KEY `uq_nic_level` (`student_nic`, `application_level`)',
                'uq_email_level' => 'ALTER TABLE `student_applications` ADD UNIQUE KEY `uq_email_level` (`student_email`, `application_level`)',
                'idx_level_created' => 'ALTER TABLE `student_applications` ADD KEY `idx_level_created` (`application_level`, `created_at`)',
            ] as $keyName => $alterSql
        ) {
            try {
                $ix = $conn->query("SHOW INDEX FROM `student_applications` WHERE Key_name = '" . $conn->real_escape_string($keyName) . "'");
                if ($ix && $ix->num_rows === 0) {
                    $ix->free();
                    $conn->query($alterSql);
                } elseif ($ix) {
                    $ix->free();
                }
            } catch (Throwable $e) {
                error_log('l05_migrate: add ' . $keyName . ': ' . $e->getMessage());
            }
        }
    } catch (Throwable $e) {
        error_log('l05_migrate_student_applications_schema: ' . $e->getMessage());
    }
}

/**
 * Ordered columns for INSERT (excluding application_id, created_at).
 *
 * @return list<string>
 */
function l05_insert_column_order(): array {
    return [
        'application_level',
        'student_title', 'student_full_name', 'student_initial_name', 'student_gender', 'student_civil_status',
        'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
        'student_language', 'student_religion', 'student_blood_group',
        'student_address', 'student_zip_code', 'student_district', 'student_province',
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
        'nic_document_path', 'birth_certificate_path', 'ol_certificate_path', 'al_certificate_path',
        'nvq_certificate_path', 'bank_receipt_path',
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, string>
 */
function l05_row_for_json(array $row): array {
    $out = [];
    foreach ($row as $k => $v) {
        if ($v === null) {
            $out[$k] = '';
        } elseif (is_scalar($v)) {
            $out[$k] = (string) $v;
        } else {
            $out[$k] = '';
        }
    }
    return $out;
}

/**
 * Column list for SELECT (same shape as check_nic / fetch). Does not use mysqli_stmt::get_result() (works without mysqlnd).
 */
function l05_select_application_columns_sql(): string {
    return '`application_id`, `student_title`, `student_full_name`, `student_initial_name`, `student_gender`, `student_civil_status`, '
        . '`student_email`, `student_phone`, `student_whatsapp`, `student_nic`, `student_dob`, `student_language`, `student_religion`, `student_blood_group`, '
        . '`student_address`, `student_zip_code`, `student_district`, `student_province`, `course_priority_1`, `course_priority_2`, `course_priority_3`, '
        . '`ol_index_number`, `ol_exam_year`, '
        . '`ol_subject_name_01`, `ol_subject_01_marks`, `ol_subject_name_02`, `ol_subject_02_marks`, `ol_subject_name_03`, `ol_subject_03_marks`, '
        . '`ol_subject_name_04`, `ol_subject_04_marks`, `ol_subject_name_05`, `ol_subject_05_marks`, `ol_subject_name_06`, `ol_subject_06_marks`, '
        . '`ol_subject_name_07`, `ol_subject_07_marks`, `ol_subject_name_08`, `ol_subject_08_marks`, `ol_subject_name_09`, `ol_subject_09_marks`, '
        . '`al_index_number`, `al_exam_year`, `al_stream`, '
        . '`al_subject_name_01`, `al_subject_01_marks`, `al_subject_name_02`, `al_subject_02_marks`, `al_subject_name_03`, `al_subject_03_marks`, '
        . '`nvq_level`, `nvq_course_name`, `nvq_institute_name`, `nvq_year_completed`, '
        . '`nic_document_path`, `birth_certificate_path`, `ol_certificate_path`, `al_certificate_path`, `nvq_certificate_path`, `bank_receipt_path`, '
        . '`created_at`';
}

function l05_application_level_valid(string $level): bool {
    return $level === '04' || $level === '05';
}

/**
 * Fetch one application row by NIC + level using mysqli::query (WAMP-safe without mysqlnd).
 *
 * @return array<string, mixed>|null
 */
function l05_fetch_application_by_nic_level(mysqli $conn, string $nic, string $level): ?array {
    $nic = nic_normalize($nic);
    if (!nic_valid($nic) || !l05_application_level_valid($level)) {
        return null;
    }
    $cols = l05_select_application_columns_sql();
    $sql = 'SELECT ' . $cols . ' FROM `student_applications` WHERE `student_nic` = \''
        . $conn->real_escape_string($nic) . '\' AND `application_level` = \''
        . $conn->real_escape_string($level) . '\' LIMIT 1';
    $res = $conn->query($sql);
    if ($res === false) {
        throw new RuntimeException($conn->error);
    }
    $row = $res->fetch_assoc();
    $res->free();
    return $row ?: null;
}

/**
 * @return array<string, mixed>|null
 */
function l05_fetch_application_by_id_nic_level(mysqli $conn, int $applicationId, string $nic, string $level): ?array {
    $nic = nic_normalize($nic);
    if ($applicationId < 1 || !nic_valid($nic) || !l05_application_level_valid($level)) {
        return null;
    }
    $sql = 'SELECT * FROM `student_applications` WHERE `application_id` = ' . $applicationId
        . ' AND `student_nic` = \'' . $conn->real_escape_string($nic) . '\''
        . ' AND `application_level` = \'' . $conn->real_escape_string($level) . '\' LIMIT 1';
    $res = $conn->query($sql);
    if ($res === false) {
        throw new RuntimeException($conn->error);
    }
    $row = $res->fetch_assoc();
    $res->free();
    return $row ?: null;
}

/** Parse php.ini size values (e.g. 8M, 512K) to bytes. */
function l05_php_ini_bytes(string $val): int {
    $val = trim($val);
    if ($val === '') {
        return 0;
    }
    $last = strtolower(substr($val, -1));
    $num = (int) $val;
    switch ($last) {
        case 'g':
            return $num * 1073741824;
        case 'm':
            return $num * 1048576;
        case 'k':
            return $num * 1024;
        default:
            return (int) $val;
    }
}

/** True when the request body is larger than post_max_size (PHP discards $_POST and $_FILES). */
function l05_multipart_exceeded_post_max(): bool {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }
    $cl = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($cl <= 0) {
        return false;
    }
    $max = l05_php_ini_bytes((string) ini_get('post_max_size'));
    return $max > 0 && $cl > $max;
}

function l05_value_empty_for_merge(mixed $v): bool {
    if ($v === null) {
        return true;
    }
    if (is_string($v) && trim($v) === '') {
        return true;
    }
    return false;
}

/**
 * Keep stored values when the form posts empty fields (other steps not filled yet).
 *
 * @param array<string, mixed> $newRow from l05_post_to_row()
 * @param array<string, mixed> $existing DB row
 * @return array<string, mixed>
 */
function l05_merge_post_row_with_existing(array $newRow, array $existing): array {
    foreach ($newRow as $k => $v) {
        if ($k === 'application_level' || $k === 'student_nic') {
            continue;
        }
        if (!l05_value_empty_for_merge($v)) {
            continue;
        }
        if (!array_key_exists($k, $existing)) {
            continue;
        }
        $ev = $existing[$k];
        if ($ev === null) {
            continue;
        }
        if (is_string($ev) && trim($ev) === '') {
            continue;
        }
        $newRow[$k] = $ev;
    }
    return $newRow;
}

/**
 * Persist all settable columns (same rules as update.php).
 *
 * @param array<string, string|null> $row
 */
function l05_execute_application_update(mysqli $conn, int $appId, string $nic, string $level, array $row): void {
    $updateCols = array_values(array_filter(l05_insert_column_order(), static function ($c) {
        return $c !== 'application_level';
    }));
    $bindVals = [];
    $assign = [];
    foreach ($updateCols as $col) {
        $assign[] = '`' . $col . '` = ?';
        $bindVals[] = $row[$col] ?? null;
    }
    $sqlU = 'UPDATE `student_applications` SET ' . implode(', ', $assign) . ' WHERE `application_id` = ? AND `student_nic` = ? AND `application_level` = ?';
    $stmt = $conn->prepare($sqlU);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $types = str_repeat('s', count($bindVals)) . 'iss';
    $bindAll = array_merge($bindVals, [$appId, $nic, $level]);
    $params = array_merge([$types], $bindAll);
    $refs = [];
    foreach ($params as $k => $_) {
        $refs[$k] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
    $stmt->close();
}

/**
 * @param array<string, mixed> $p POST trimmed values
 */
function l05_ol_complete(array $p): bool {
    foreach (['ol_index_number', 'ol_exam_year'] as $k) {
        if (trim((string) ($p[$k] ?? '')) === '') {
            return false;
        }
    }
    for ($i = 1; $i <= 9; $i++) {
        $s = sprintf('%02d', $i);
        if (trim((string) ($p['ol_subject_name_' . $s] ?? '')) === '') {
            return false;
        }
        if (trim((string) ($p['ol_subject_' . $s . '_marks'] ?? '')) === '') {
            return false;
        }
    }
    return true;
}

/**
 * @param array<string, mixed> $p POST trimmed values
 */
function l05_al_complete(array $p): bool {
    foreach (['al_index_number', 'al_exam_year', 'al_stream'] as $k) {
        if (trim((string) ($p[$k] ?? '')) === '') {
            return false;
        }
    }
    for ($i = 1; $i <= 3; $i++) {
        $s = sprintf('%02d', $i);
        if (trim((string) ($p['al_subject_name_' . $s] ?? '')) === '') {
            return false;
        }
        if (trim((string) ($p['al_subject_' . $s . '_marks'] ?? '')) === '') {
            return false;
        }
    }
    return true;
}

/**
 * True if any G.C.E. A/L field is non-empty (used to reject partial A/L).
 *
 * @param array<string, mixed> $p
 */
function l05_al_any_filled(array $p): bool {
    $keys = ['al_index_number', 'al_exam_year', 'al_stream'];
    for ($i = 1; $i <= 3; $i++) {
        $s = sprintf('%02d', $i);
        $keys[] = 'al_subject_name_' . $s;
        $keys[] = 'al_subject_' . $s . '_marks';
    }
    foreach ($keys as $k) {
        if (trim((string) ($p[$k] ?? '')) !== '') {
            return true;
        }
    }
    return false;
}

/**
 * @param array<string, mixed> $p
 */
function l05_nvq_any_filled(array $p): bool {
    foreach (['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'] as $k) {
        if (trim((string) ($p[$k] ?? '')) !== '') {
            return true;
        }
    }
    return false;
}

/**
 * @param array<string, mixed> $p
 */
function l05_nvq_complete(array $p): bool {
    foreach (['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'] as $k) {
        if (trim((string) ($p[$k] ?? '')) === '') {
            return false;
        }
    }
    return true;
}

/**
 * Sri Lanka phone check (9-digit NSN after 0 or 94).
 */
function l05_phone_valid(string $raw): bool {
    $d = preg_replace('/\D+/', '', $raw);
    if ($d === '') {
        return false;
    }
    if (strpos($d, '94') === 0 && strlen($d) > 2) {
        $d = substr($d, 2);
    } elseif (strpos($d, '0') === 0 && strlen($d) > 1) {
        $d = substr($d, 1);
    }
    return strlen($d) === 9 && (bool) preg_match('/^[1-9]\d{8}$/', $d);
}

/**
 * O/L or A/L result: letter A–F/S/W ± or digits 0–100.
 */
function l05_exam_result_valid(string $raw): bool {
    $m = trim($raw);
    if ($m === '') {
        return false;
    }
    if ((bool) preg_match('/^[A-FSW][+-]?$/i', $m)) {
        return true;
    }
    if (ctype_digit($m)) {
        $n = (int) $m;
        return $n >= 0 && $n <= 100;
    }
    return false;
}

/**
 * @param array<string, mixed> $p
 * @param array<string, mixed> $files $_FILES
 * @param bool $isUpdate if true, uploads optional when a stored document path already exists
 * @param array<string, string|null>|null $existingDocumentPaths DB path columns; if all empty on update, files are required like a new application
 * @return list<string>
 */
function l05_validate_application(array $p, array $files, bool $isUpdate, ?array $existingDocumentPaths = null): array {
    $errors = [];
    $nic = nic_normalize((string) ($p['student_nic'] ?? ''));
    if (!nic_valid($nic)) {
        $errors[] = 'Invalid NIC.';
    }

    $required = [
        'student_title', 'student_full_name', 'student_initial_name', 'student_gender', 'student_civil_status',
        'student_email', 'student_phone', 'student_whatsapp', 'student_dob',
        'student_language', 'student_religion', 'student_blood_group',
        'student_address', 'student_zip_code', 'student_district', 'student_province',
        'course_priority_1',
    ];
    $basicsOk = true;
    foreach ($required as $k) {
        if (trim((string) ($p[$k] ?? '')) === '') {
            $errors[] = 'Please fill all required fields (including first course choice).';
            $basicsOk = false;
            break;
        }
    }
    if ($basicsOk && trim((string) ($p['student_full_name'] ?? '')) === L05_DRAFT_FULL_NAME_PLACEHOLDER) {
        $errors[] = 'Please enter your full name.';
        $basicsOk = false;
    }
    if (!$basicsOk) {
        return array_values(array_unique($errors));
    }

    $email = trim((string) ($p['student_email'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email.';
    }
    if (!in_array((string) ($p['student_gender'] ?? ''), ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Invalid gender.';
    }
    if (!in_array((string) ($p['student_civil_status'] ?? ''), ['Single', 'Married'], true)) {
        $errors[] = 'Invalid civil status.';
    }
    $titles = ['Mr', 'Miss', 'Mrs'];
    if (!in_array((string) ($p['student_title'] ?? ''), $titles, true)) {
        $errors[] = 'Invalid title.';
    }
    $langs = ['Sinhala', 'Tamil', 'English'];
    if (!in_array((string) ($p['student_language'] ?? ''), $langs, true)) {
        $errors[] = 'Invalid language.';
    }
    $rels = ['Buddhism', 'Hinduism', 'Islam', 'Christianity'];
    if (!in_array((string) ($p['student_religion'] ?? ''), $rels, true)) {
        $errors[] = 'Invalid religion.';
    }
    $bloods = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array((string) ($p['student_blood_group'] ?? ''), $bloods, true)) {
        $errors[] = 'Invalid blood group.';
    }

    if (!l05_phone_valid((string) ($p['student_phone'] ?? ''))) {
        $errors[] = 'Invalid phone number.';
    }
    if (!l05_phone_valid((string) ($p['student_whatsapp'] ?? ''))) {
        $errors[] = 'Invalid WhatsApp number.';
    }

    $dob = trim((string) ($p['student_dob'] ?? ''));
    if ($dob === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $errors[] = 'Invalid date of birth.';
    } else {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dob);
        if ($dt === false || $dt->format('Y-m-d') !== $dob) {
            $errors[] = 'Invalid date of birth.';
        } else {
            $cut = (new DateTimeImmutable('today'))->modify('-16 years');
            if ($dt > $cut) {
                $errors[] = 'You must be at least 16 years old.';
            }
        }
    }

    $mapFile = dirname(__DIR__) . '/config/sl_provinces_districts.php';
    $provMap = is_file($mapFile) ? require $mapFile : [];
    $prov = (string) ($p['student_province'] ?? '');
    $dist = (string) ($p['student_district'] ?? '');
    if (!isset($provMap[$prov]) || !in_array($dist, $provMap[$prov], true)) {
        $errors[] = 'Province and district must match.';
    }

    $olOk = l05_ol_complete($p);
    $alOk = l05_al_complete($p);
    $nvqOk = l05_nvq_complete($p);
    $alAny = l05_al_any_filled($p);
    $nvqAny = l05_nvq_any_filled($p);

    if (!$olOk) {
        $errors[] = 'G.C.E. O/L is required: complete index, year, all nine subjects and results (A–F, S, W ± or 0–100).';
    }
    if ($alAny && !$alOk) {
        $errors[] = 'Either complete all G.C.E. A/L fields or clear them if you use NVQ only.';
    }
    if ($nvqAny && !$nvqOk) {
        $errors[] = 'Either complete all NVQ fields or clear them if you use A/L only.';
    }
    if ($olOk && !$alOk && !$nvqOk) {
        $errors[] = 'Provide either full G.C.E. A/L details or full NVQ details in addition to O/L.';
    }

    if ($olOk) {
        $yo = (int) ($p['ol_exam_year'] ?? 0);
        if ($yo < 1990 || $yo > 2100) {
            $errors[] = 'O/L year must be between 1990 and 2100.';
        }
        for ($i = 1; $i <= 9; $i++) {
            $s = sprintf('%02d', $i);
            if (!l05_exam_result_valid((string) ($p['ol_subject_' . $s . '_marks'] ?? ''))) {
                $errors[] = 'O/L results must be valid (A–F, S, W ± or 0–100) for every subject.';
                break;
            }
        }
    }

    if ($alOk) {
        $ya = (int) ($p['al_exam_year'] ?? 0);
        if ($ya < 1990 || $ya > 2100) {
            $errors[] = 'A/L year must be between 1990 and 2100.';
        }
        for ($i = 1; $i <= 3; $i++) {
            $s = sprintf('%02d', $i);
            if (!l05_exam_result_valid((string) ($p['al_subject_' . $s . '_marks'] ?? ''))) {
                $errors[] = 'A/L results must be valid (A–F, S, W ± or 0–100) for every subject.';
                break;
            }
        }
    }

    if ($nvqOk) {
        $yn = (int) ($p['nvq_year_completed'] ?? 0);
        if ($yn < 1900 || $yn > 2100) {
            $errors[] = 'NVQ year must be between 1900 and 2100.';
        }
    }

    $requireEveryDocumentFile = !$isUpdate;
    if ($isUpdate && $existingDocumentPaths !== null) {
        $requireEveryDocumentFile = true;
        foreach (L05_FILE_FIELDS as $_fn => $dbCol) {
            if (trim((string) ($existingDocumentPaths[$dbCol] ?? '')) !== '') {
                $requireEveryDocumentFile = false;
                break;
            }
        }
    }

    foreach (L05_FILE_FIELDS as $fieldName => $_dbCol) {
        if (!$requireEveryDocumentFile) {
            if (!empty($files[$fieldName]['tmp_name']) && is_uploaded_file((string) $files[$fieldName]['tmp_name'])) {
                if (($files[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $errors[] = 'A file failed to upload.';
                    break;
                }
                if ((int) ($files[$fieldName]['size'] ?? 0) > L05_UPLOAD_MAX) {
                    $errors[] = 'Each file must be 5 MB or smaller.';
                    break;
                }
            }
            continue;
        }
        if (empty($files[$fieldName]['tmp_name']) || !is_uploaded_file((string) $files[$fieldName]['tmp_name'])) {
            $errors[] = 'Please upload all required documents.';
            break;
        }
        if (($files[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'A file failed to upload.';
            break;
        }
        if ((int) ($files[$fieldName]['size'] ?? 0) > L05_UPLOAD_MAX) {
            $errors[] = 'Each file must be 5 MB or smaller.';
            break;
        }
    }

    return array_values(array_unique($errors));
}

/**
 * Build data array for INSERT/UPDATE from POST (trim strings).
 *
 * @param array<string, string> $p
 * @param array<string, string|null> $paths
 * @return array<string, string|null>
 */
function l05_post_to_row(array $p, array $paths): array {
    $nic = nic_normalize((string) ($p['student_nic'] ?? ''));
    $row = [
        'application_level' => L05_APP_LEVEL,
        'student_title' => trim((string) ($p['student_title'] ?? '')) ?: null,
        'student_full_name' => trim((string) ($p['student_full_name'] ?? '')),
        'student_initial_name' => trim((string) ($p['student_initial_name'] ?? '')) ?: null,
        'student_gender' => trim((string) ($p['student_gender'] ?? '')) ?: null,
        'student_civil_status' => trim((string) ($p['student_civil_status'] ?? '')) ?: null,
        'student_email' => trim((string) ($p['student_email'] ?? '')) ?: null,
        'student_phone' => preg_replace('/\D+/', '', (string) ($p['student_phone'] ?? '')),
        'student_whatsapp' => preg_replace('/\D+/', '', (string) ($p['student_whatsapp'] ?? '')),
        'student_nic' => $nic,
        'student_dob' => trim((string) ($p['student_dob'] ?? '')) ?: null,
        'student_language' => trim((string) ($p['student_language'] ?? '')) ?: null,
        'student_religion' => trim((string) ($p['student_religion'] ?? '')) ?: null,
        'student_blood_group' => trim((string) ($p['student_blood_group'] ?? '')) ?: null,
        'student_address' => trim((string) ($p['student_address'] ?? '')) ?: null,
        'student_zip_code' => trim((string) ($p['student_zip_code'] ?? '')) ?: null,
        'student_district' => trim((string) ($p['student_district'] ?? '')) ?: null,
        'student_province' => trim((string) ($p['student_province'] ?? '')) ?: null,
        'course_priority_1' => trim((string) ($p['course_priority_1'] ?? '')) ?: null,
        'course_priority_2' => trim((string) ($p['course_priority_2'] ?? '')) ?: null,
        'course_priority_3' => trim((string) ($p['course_priority_3'] ?? '')) ?: null,
        'ol_index_number' => trim((string) ($p['ol_index_number'] ?? '')) ?: null,
        'ol_exam_year' => l05_year_or_null($p['ol_exam_year'] ?? ''),
        'al_index_number' => trim((string) ($p['al_index_number'] ?? '')) ?: null,
        'al_exam_year' => l05_year_or_null($p['al_exam_year'] ?? ''),
        'al_stream' => trim((string) ($p['al_stream'] ?? '')) ?: null,
        'nvq_level' => trim((string) ($p['nvq_level'] ?? '')) ?: null,
        'nvq_course_name' => trim((string) ($p['nvq_course_name'] ?? '')) ?: null,
        'nvq_institute_name' => trim((string) ($p['nvq_institute_name'] ?? '')) ?: null,
        'nvq_year_completed' => l05_year_or_null($p['nvq_year_completed'] ?? ''),
    ];

    for ($i = 1; $i <= 9; $i++) {
        $s = sprintf('%02d', $i);
        $row['ol_subject_name_' . $s] = trim((string) ($p['ol_subject_name_' . $s] ?? '')) ?: null;
        $row['ol_subject_' . $s . '_marks'] = l05_normalize_exam_result((string) ($p['ol_subject_' . $s . '_marks'] ?? ''));
    }
    for ($i = 1; $i <= 3; $i++) {
        $s = sprintf('%02d', $i);
        $row['al_subject_name_' . $s] = trim((string) ($p['al_subject_name_' . $s] ?? '')) ?: null;
        $row['al_subject_' . $s . '_marks'] = l05_normalize_exam_result((string) ($p['al_subject_' . $s . '_marks'] ?? ''));
    }

    foreach (L05_FILE_FIELDS as $fileKey => $dbCol) {
        $row[$dbCol] = $paths[$dbCol] ?? null;
    }

    return $row;
}

function l05_year_or_null(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '' || !ctype_digit($raw)) {
        return null;
    }
    $y = (int) $raw;
    if ($y < 1970 || $y > 2100) {
        return null;
    }
    return (string) $y;
}

function l05_normalize_exam_result(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if ((bool) preg_match('/^[A-FSW][+-]?$/i', $raw)) {
        return strtoupper($raw);
    }
    if (ctype_digit($raw)) {
        return (string) max(0, min(100, (int) $raw));
    }
    return $raw;
}

/**
 * Recompress JPEG/PNG uploads to JPEG near {@see L05_COMPRESSED_TARGET_BYTES} (requires GD).
 * Returns false if GD is missing, file is not a raster, or compression failed (caller may fall back to original).
 */
function l05_compress_upload_image_to_jpeg(string $tmpPath, string $destJpegPath): bool {
    if (!extension_loaded('gd')) {
        return false;
    }
    $info = @getimagesize($tmpPath);
    if ($info === false) {
        return false;
    }
    $w = $info[0];
    $h = $info[1];
    $type = $info[2];

    $src = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = @imagecreatefromjpeg($tmpPath);
            break;
        case IMAGETYPE_PNG:
            $src = @imagecreatefrompng($tmpPath);
            break;
        default:
            return false;
    }
    if ($src === false) {
        return false;
    }

    $maxEdge = L05_IMAGE_MAX_EDGE_PIXELS;
    $rFit = min(1.0, $maxEdge / (float) max($w, $h));
    $tw = max(1, (int) round($w * $rFit));
    $th = max(1, (int) round($h * $rFit));

    $base = imagecreatetruecolor($tw, $th);
    if ($base === false) {
        imagedestroy($src);
        return false;
    }
    $bg = imagecolorallocate($base, 255, 255, 255);
    imagefilledrectangle($base, 0, 0, $tw, $th, $bg);
    imagecopyresampled($base, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
    imagedestroy($src);

    $tmpOut = $destJpegPath . '.wip';
    $target = L05_COMPRESSED_TARGET_BYTES;
    $shortMin = L05_IMAGE_MIN_SHORT_EDGE_PIXELS;

    for ($dimMul = 1.0; $dimMul >= 0.22; $dimMul *= 0.87) {
        $cw = max(1, (int) round($tw * $dimMul));
        $ch = max(1, (int) round($th * $dimMul));
        if ($dimMul < 1.0 && min($cw, $ch) < $shortMin) {
            break;
        }

        $canvas = imagecreatetruecolor($cw, $ch);
        if ($canvas === false) {
            continue;
        }
        $bgc = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $cw, $ch, $bgc);
        imagecopyresampled($canvas, $base, 0, 0, 0, 0, $cw, $ch, $tw, $th);

        for ($q = 85; $q >= 28; $q -= 4) {
            if (!@imagejpeg($canvas, $tmpOut, $q)) {
                continue;
            }
            $sz = filesize($tmpOut);
            if ($sz !== false && $sz <= $target) {
                imagedestroy($canvas);
                imagedestroy($base);
                if (@rename($tmpOut, $destJpegPath)) {
                    return true;
                }
                $copied = @copy($tmpOut, $destJpegPath);
                @unlink($tmpOut);
                return $copied;
            }
        }
        imagedestroy($canvas);
    }

    $cw = max(1, min($tw, max($shortMin, 720)));
    $ch = max(1, (int) round($th * ($cw / $tw)));
    $canvas = imagecreatetruecolor($cw, $ch);
    if ($canvas !== false) {
        $bgc = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $cw, $ch, $bgc);
        imagecopyresampled($canvas, $base, 0, 0, 0, 0, $cw, $ch, $tw, $th);
        @imagejpeg($canvas, $destJpegPath, 25);
        imagedestroy($canvas);
    }
    imagedestroy($base);
    @unlink($tmpOut);

    return is_file($destJpegPath) && (int) filesize($destJpegPath) > 0;
}

/**
 * Directory name under uploads/student_applications/ (NIC digits + V/X only).
 */
function l05_nic_folder_segment(string $nic): string {
    $nic = nic_normalize($nic);
    $s = strtoupper(preg_replace('/[^0-9VX]/', '', $nic));
    return $s !== '' ? $s : 'unknown';
}

/**
 * Remove a stored file only if it lives under uploads/student_applications/ (path traversal safe).
 */
function l05_safe_delete_stored_upload(?string $relativePath): void {
    if ($relativePath === null) {
        return;
    }
    $relativePath = trim(str_replace(["\0", '\\'], ['', '/'], $relativePath));
    if ($relativePath === '') {
        return;
    }
    if (strpos($relativePath, '..') !== false) {
        return;
    }
    $prefix = 'uploads/student_applications/';
    if (strpos($relativePath, $prefix) !== 0) {
        return;
    }
    $root = realpath(dirname(__DIR__));
    if ($root === false) {
        return;
    }
    $uploadsBase = realpath($root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'student_applications');
    if ($uploadsBase === false) {
        return;
    }
    $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $targetReal = realpath($full);
    if ($targetReal === false || !is_file($targetReal)) {
        return;
    }
    $uploadsBaseNorm = $uploadsBase . DIRECTORY_SEPARATOR;
    if (strpos($targetReal, $uploadsBaseNorm) !== 0 && $targetReal !== $uploadsBase) {
        return;
    }
    @unlink($targetReal);
}

/**
 * Process uploads: folder = NIC; fixed safe names per document; replaces old file on disk when re-uploading.
 *
 * @return array<string, string|null>
 */
function l05_process_uploads(string $nic, array $files, ?array $existingPaths = null): array {
    $nicFolder = l05_nic_folder_segment($nic);
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'student_applications' . DIRECTORY_SEPARATOR . $nicFolder;
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    $relPrefix = 'uploads/student_applications/' . $nicFolder . '/';
    $out = $existingPaths ?? [];

    foreach (L05_FILE_FIELDS as $fieldName => $dbCol) {
        if (empty($files[$fieldName]['tmp_name']) || !is_uploaded_file((string) $files[$fieldName]['tmp_name'])) {
            continue;
        }
        if (($files[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error for ' . $fieldName);
        }
        if ((int) ($files[$fieldName]['size'] ?? 0) > L05_UPLOAD_MAX) {
            throw new RuntimeException('File too large: ' . $fieldName);
        }
        $orig = (string) ($files[$fieldName]['name'] ?? '');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, L05_ALLOWED_EXT, true)) {
            throw new RuntimeException('Invalid file type for ' . $fieldName);
        }

        $baseName = L05_UPLOAD_FILE_BASENAMES[$fieldName] ?? strtolower(preg_replace('/[^a-z0-9_]/', '', $fieldName) ?: 'doc');
        $prevRel = isset($out[$dbCol]) ? trim((string) $out[$dbCol]) : '';
        if ($prevRel !== '') {
            l05_safe_delete_stored_upload($prevRel);
        }

        $tmp = (string) $files[$fieldName]['tmp_name'];

        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $safeFile = $baseName . '.jpg';
            $full = $baseDir . DIRECTORY_SEPARATOR . $safeFile;
            if (!l05_compress_upload_image_to_jpeg($tmp, $full)) {
                $safeFile = $baseName . '.' . $ext;
                $full = $baseDir . DIRECTORY_SEPARATOR . $safeFile;
                if (!move_uploaded_file($tmp, $full)) {
                    throw new RuntimeException('Could not save ' . $fieldName);
                }
            }
        } else {
            $safeFile = $baseName . '.pdf';
            $full = $baseDir . DIRECTORY_SEPARATOR . $safeFile;
            if (!move_uploaded_file($tmp, $full)) {
                throw new RuntimeException('Could not save ' . $fieldName);
            }
        }

        $out[$dbCol] = $relPrefix . $safeFile;
    }

    return $out;
}
