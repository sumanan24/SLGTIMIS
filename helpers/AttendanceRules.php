<?php
/**
 * Institutional attendance rules for the SLGTI MIS (Asia/Colombo).
 *
 * Pure calculation helpers (no database access except for the configuration, which is stored in
 * `system_setting`). The original fingerprint punches (`student_attendance`) are never changed:
 * these helpers only turn a list of punch times into a calculated daily status.
 *
 * Default rules
 *   Morning window  06:00 - 08:50  -> MORNING PRESENT (earliest valid punch is the IN time)
 *   Evening OUT     after 15:30    -> EVENING PRESENT (latest valid punch is the OUT time)
 *   Morning + evening              -> FULL DAY  (1.0)
 *   One qualifying session, or punches that do not meet the full-day rule -> HALF DAY (0.5)
 *   No valid punch                 -> ABSENT (0), pending verification when nothing was recorded
 * All times can be changed from Attendance Management > Settings.
 */
class AttendanceRules {
    const TIMEZONE = 'Asia/Colombo';
    const SETTING_PREFIX = 'att_';

    const STATUS_FULL = 'full';
    const STATUS_HALF = 'half';
    const STATUS_ABSENT = 'absent';

    /** 2026/2027 intake: attendance is counted from this date (inclusive). Earlier days are ignored. */
    const INTAKE_2026_ATTENDANCE_FROM = '2026-11-18';

    /**
     * Whether a calendar day counts toward attendance for this academic year.
     * 2026/2027 students are counted only from 18 November 2026.
     */
    public static function attendanceCountsOn(string $date, ?string $academicYear): bool
    {
        if (trim((string) $academicYear) !== '2026/2027') {
            return true;
        }
        return $date >= self::INTAKE_2026_ATTENDANCE_FROM;
    }

    /** @var array<string,string>|null */
    private static $configCache = null;

    /** @return array<string,string> */
    public static function defaults() {
        return [
            'morning_start' => '06:00',
            'morning_end' => '08:50',
            'evening_start' => '15:30',
            'valid_from' => '06:00',
            'valid_to' => '22:00',
            'duplicate_seconds' => '120',
            'period_P1_start' => '08:30', 'period_P1_end' => '10:00',
            'period_P2_start' => '10:15', 'period_P2_end' => '11:45',
            'period_P3_start' => '13:00', 'period_P3_end' => '14:30',
            'period_P4_start' => '14:45', 'period_P4_end' => '16:15',
        ];
    }

    /** @return array<string,string> */
    public static function config() {
        if (self::$configCache !== null) {
            return self::$configCache;
        }
        $cfg = self::defaults();
        try {
            require_once BASE_PATH . '/models/SystemSettingModel.php';
            $model = new SystemSettingModel();
            foreach ($cfg as $key => $default) {
                $val = $model->get(self::SETTING_PREFIX . $key, null);
                if ($val !== null && $val !== '') {
                    $cfg[$key] = (string) $val;
                }
            }
        } catch (Throwable $e) {
            error_log('AttendanceRules::config ' . $e->getMessage());
        }
        self::$configCache = $cfg;
        return $cfg;
    }

    public static function resetCache() {
        self::$configCache = null;
    }

    /**
     * Validate and save configuration. Returns list of error messages (empty = saved).
     *
     * @param array<string,string> $input
     * @return list<string>
     */
    public static function saveConfig(array $input) {
        $clean = [];
        $errors = [];
        foreach (self::defaults() as $key => $default) {
            $val = isset($input[$key]) ? trim((string) $input[$key]) : $default;
            if ($key === 'duplicate_seconds') {
                if (!ctype_digit($val) || (int) $val > 3600) {
                    $errors[] = 'Duplicate scan interval must be 0-3600 seconds.';
                }
                $clean[$key] = $val;
                continue;
            }
            if (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $val)) {
                $errors[] = 'Invalid time for "' . $key . '". Use HH:MM.';
                continue;
            }
            $clean[$key] = str_pad(explode(':', $val)[0], 2, '0', STR_PAD_LEFT) . ':' . explode(':', $val)[1];
        }
        if (!$errors) {
            if ($clean['morning_start'] >= $clean['morning_end']) {
                $errors[] = 'Morning window must end after it starts.';
            }
            if ($clean['morning_end'] >= $clean['evening_start']) {
                $errors[] = 'Evening OUT time must be after the morning window.';
            }
            if ($clean['valid_from'] > $clean['morning_start'] || $clean['valid_to'] < $clean['evening_start']) {
                $errors[] = 'The valid punch range must cover both the morning window and the evening OUT time.';
            }
            foreach (['P1', 'P2', 'P3', 'P4'] as $p) {
                if ($clean['period_' . $p . '_start'] >= $clean['period_' . $p . '_end']) {
                    $errors[] = 'Period ' . $p . ' must end after it starts.';
                }
            }
        }
        if ($errors) {
            return $errors;
        }
        require_once BASE_PATH . '/models/SystemSettingModel.php';
        $model = new SystemSettingModel();
        foreach ($clean as $key => $val) {
            $model->set(self::SETTING_PREFIX . $key, $val);
        }
        self::resetCache();
        return [];
    }

    /** @return DateTimeImmutable */
    public static function now() {
        return new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));
    }

    public static function today() {
        return self::now()->format('Y-m-d');
    }

    /**
     * Start/end of a timetable period or of an explicit slot (HH:MM:SS), from configuration.
     *
     * @return array{0:string,1:string}
     */
    public static function periodTimes($period, $startTime = null, $endTime = null, array $cfg = null) {
        if ($startTime !== null && $startTime !== '' && $endTime !== null && $endTime !== '') {
            return [self::hms($startTime), self::hms($endTime)];
        }
        $cfg = $cfg ?? self::config();
        $p = in_array($period, ['P1', 'P2', 'P3', 'P4'], true) ? $period : 'P1';
        return [self::hms($cfg['period_' . $p . '_start']), self::hms($cfg['period_' . $p . '_end'])];
    }

    /** Normalise "8:5", "08:05", "08:05:00" to HH:MM:SS. Returns '' when invalid. */
    public static function hms($time) {
        $time = trim((string) $time);
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $time, $m)) {
            return '';
        }
        return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':' . str_pad($m[3] ?? '00', 2, '0', STR_PAD_LEFT);
    }

    private static function seconds($hms) {
        $p = explode(':', self::hms($hms));
        return ((int) $p[0]) * 3600 + ((int) $p[1]) * 60 + (int) ($p[2] ?? 0);
    }

    /**
     * Calculate one student's day from fingerprint punch times (HH:MM:SS, any order).
     *
     * @param list<string> $punchTimes
     * @return array{
     *   status:string, value:float, morning_present:bool, evening_present:bool,
     *   first_in:string, last_out:string, punch_count:int, valid_count:int,
     *   duplicate_count:int, invalid_count:int, no_punch:bool, reason:string
     * }
     */
    public static function calculateDay(array $punchTimes, array $cfg = null) {
        $cfg = $cfg ?? self::config();
        $times = [];
        foreach ($punchTimes as $t) {
            $n = self::hms($t);
            if ($n !== '') {
                $times[] = $n;
            }
        }
        sort($times);
        $rawCount = count($times);

        $validFrom = self::seconds($cfg['valid_from'] . ':00');
        $validTo = self::seconds($cfg['valid_to'] . ':00');
        $mStart = self::seconds($cfg['morning_start'] . ':00');
        $mEnd = self::seconds($cfg['morning_end'] . ':59');
        $eStart = self::seconds($cfg['evening_start'] . ':00');
        $dupGap = (int) $cfg['duplicate_seconds'];

        $invalid = 0;
        $dups = 0;
        $kept = [];
        $lastKept = null;
        foreach ($times as $t) {
            $s = self::seconds($t);
            if ($s < $validFrom || $s > $validTo) {
                $invalid++;
                continue;
            }
            if ($lastKept !== null && ($s - $lastKept) <= $dupGap) {
                $dups++;
                continue;
            }
            $kept[] = $t;
            $lastKept = $s;
        }

        $morning = [];
        $evening = [];
        $other = [];
        foreach ($kept as $t) {
            $s = self::seconds($t);
            if ($s >= $mStart && $s <= $mEnd) {
                $morning[] = $t;
            } elseif ($s >= $eStart) {
                $evening[] = $t;
            } else {
                $other[] = $t;
            }
        }
        $firstIn = $morning ? $morning[0] : '';
        $lastOut = $evening ? $evening[count($evening) - 1] : '';

        $out = [
            'status' => self::STATUS_ABSENT,
            'value' => 0.0,
            'morning_present' => $firstIn !== '',
            'evening_present' => $lastOut !== '',
            'first_in' => $firstIn,
            'last_out' => $lastOut,
            'punch_count' => $rawCount,
            'valid_count' => count($kept) + $dups,
            'duplicate_count' => $dups,
            'invalid_count' => $invalid,
            'no_punch' => $rawCount === 0,
            'reason' => '',
        ];

        $mWin = $cfg['morning_start'] . '-' . $cfg['morning_end'];
        if ($firstIn !== '' && $lastOut !== '') {
            $out['status'] = self::STATUS_FULL;
            $out['value'] = 1.0;
            $out['reason'] = 'Morning IN ' . substr($firstIn, 0, 5) . ' (window ' . $mWin . ') and evening OUT '
                . substr($lastOut, 0, 5) . ' (after ' . $cfg['evening_start'] . ') - full day.';
        } elseif ($firstIn !== '') {
            $out['status'] = self::STATUS_HALF;
            $out['value'] = 0.5;
            $out['reason'] = 'Morning IN ' . substr($firstIn, 0, 5) . ' recorded but no evening OUT after '
                . $cfg['evening_start'] . ' - half day.';
        } elseif ($lastOut !== '') {
            $out['status'] = self::STATUS_HALF;
            $out['value'] = 0.5;
            $out['reason'] = 'Evening OUT ' . substr($lastOut, 0, 5) . ' recorded but no morning IN within '
                . $mWin . ' - half day.';
        } elseif ($other) {
            $out['status'] = self::STATUS_HALF;
            $out['value'] = 0.5;
            $out['reason'] = 'Punch at ' . substr($other[0], 0, 5) . ' is outside the morning window (' . $mWin
                . ') and the evening OUT time (' . $cfg['evening_start'] . ') - half day.';
        } elseif ($rawCount === 0) {
            $out['reason'] = 'No fingerprint recorded - pending verification.';
        } else {
            $out['reason'] = 'Only invalid punches recorded (outside ' . $cfg['valid_from'] . '-' . $cfg['valid_to'] . ') - pending verification.';
        }
        if ($dups > 0) {
            $out['reason'] .= ' ' . $dups . ' duplicate scan(s) ignored.';
        }
        return $out;
    }

    public static function valueForStatus($status) {
        switch ($status) {
            case self::STATUS_FULL:
                return 1.0;
            case self::STATUS_HALF:
                return 0.5;
            default:
                return 0.0;
        }
    }

    public static function statusLabel($status) {
        switch ($status) {
            case self::STATUS_FULL:
                return 'Full Day Present';
            case self::STATUS_HALF:
                return 'Half Day Present';
            default:
                return 'Absent';
        }
    }

    /**
     * Month-end rule. A record may only be edited during the calendar month in which its
     * attendance date falls (Asia/Colombo) and never for a future date.
     *
     * @return array{editable:bool, reason:string, locks_on:string}
     */
    public static function editState($attendanceDate, DateTimeImmutable $now = null) {
        $now = $now ?? self::now();
        $attendanceDate = (string) $attendanceDate;
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $attendanceDate, new DateTimeZone(self::TIMEZONE));
        if (!$d || $d->format('Y-m-d') !== $attendanceDate) {
            return ['editable' => false, 'reason' => 'Invalid attendance date.', 'locks_on' => ''];
        }
        $locksOn = $d->modify('first day of next month')->format('Y-m-d');
        if ($attendanceDate > $now->format('Y-m-d')) {
            return ['editable' => false, 'reason' => 'Attendance for a future date cannot be edited.', 'locks_on' => $locksOn];
        }
        if ($d->format('Y-m') !== $now->format('Y-m')) {
            return [
                'editable' => false,
                'reason' => 'Locked: attendance for ' . $d->format('F Y') . ' was closed on ' . $d->format('t F Y') . '.',
                'locks_on' => $locksOn,
            ];
        }
        return ['editable' => true, 'reason' => '', 'locks_on' => $locksOn];
    }

    public static function isEditable($attendanceDate, DateTimeImmutable $now = null) {
        return self::editState($attendanceDate, $now)['editable'];
    }
}
