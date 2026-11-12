<?php
/**
 * Student month calendar: fingerprint (In–Out > 7h = Present) + manual staff marks.
 */
declare(strict_types=1);

final class StudentAttendanceCalendarHelper
{
    /**
     * @return array{calendar:list<list<array<string,mixed>>>,stats:array<string,float|int>,day_map:array<string,array<string,mixed>>}
     */
    public static function buildMonth(string $studentId, string $month, AttendanceModel $attendanceModel): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate . ' 12:00:00'));

        $manual = $attendanceModel->getAttendanceByStudentAndDateRange($studentId, $startDate, $endDate);
        $finger = self::loadFingerDayDetails($studentId, $startDate, $endDate);
        $dayMap = self::buildDayMap($month, $manual, $finger);
        $calendar = self::weeksFromDayMap($month, $dayMap);
        $stats = self::statsFromDayMap($dayMap);

        return [
            'calendar' => $calendar,
            'stats' => $stats,
            'day_map' => $dayMap,
        ];
    }

    /**
     * @return array<string, array{status:string,label:string,time_in:string,time_out:string,duration_seconds:int}>
     */
    public static function loadFingerDayDetails(string $studentId, string $startDate, string $endDate): array
    {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return [];
        }

        require_once BASE_PATH . '/models/StudentDeviceAttendanceModel.php';

        $db = Database::getInstance()->getConnection();
        $exists = $db->query("SHOW TABLES LIKE 'student_attendance'");
        if (!$exists || $exists->num_rows === 0) {
            return [];
        }

        $sql = "SELECT `attendance_date`,
                       GROUP_CONCAT(`attendance_time` ORDER BY `attendance_time` ASC SEPARATOR ',') AS times_csv
                FROM `student_attendance`
                WHERE `student_id` = ? AND `attendance_date` >= ? AND `attendance_date` <= ?
                GROUP BY `attendance_date`";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sss', $studentId, $startDate, $endDate);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $date = (string) ($row['attendance_date'] ?? '');
            if ($date === '') {
                continue;
            }
            $split = StudentDeviceAttendanceModel::splitDayTimes(explode(',', (string) ($row['times_csv'] ?? '')));
            $class = StudentDeviceAttendanceModel::classifyDayStatus(
                $split['in'],
                $split['out'],
                $split['others']
            );
            $rawStatus = (string) ($class['status'] ?? 'absent');
            $isPresent = ($rawStatus === 'present');
            $out[$date] = [
                'status' => $isPresent ? 'present' : 'absent',
                'raw_status' => $rawStatus,
                'label' => $isPresent
                    ? 'Present (>7h)'
                    : (string) (($class['label'] ?? '') !== '' ? $class['label'] : 'Absent'),
                'time_in' => (string) ($class['time_in'] ?? ''),
                'time_out' => (string) ($class['time_out'] ?? ''),
                'duration_seconds' => (int) ($class['duration_seconds'] ?? 0),
            ];
        }
        $stmt->close();
        return $out;
    }

    /**
     * @param array<string,mixed> $manualRecords
     * @param array<string,array<string,mixed>> $fingerDays
     * @return array<string, array<string,mixed>>
     */
    public static function buildDayMap(string $month, array $manualRecords, array $fingerDays): array
    {
        $firstDay = strtotime($month . '-01');
        $daysInMonth = (int) date('t', $firstDay);
        $today = date('Y-m-d');
        $map = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $dow = (int) date('w', strtotime($date));
            $isWeekend = ($dow === 0 || $dow === 6);

            $cell = [
                'day' => $day,
                'date' => $date,
                'isWeekend' => $isWeekend,
                'dayName' => date('D', strtotime($date)),
                'status' => null,
                'source' => null,
                'time_in' => '',
                'time_out' => '',
                'label' => '',
            ];

            if ($isWeekend) {
                $cell['status'] = 'weekend';
                $cell['label'] = 'Weekend';
            } else {
                $manualRaw = $manualRecords[$date] ?? null;
                $hasManual = ($manualRaw !== null && $manualRaw !== '');
                $finger = $fingerDays[$date] ?? null;

                if ($hasManual) {
                    $manualStatus = (int) $manualRaw;
                    if ($manualStatus === -1) {
                        $cell['status'] = 'holiday';
                        $cell['source'] = 'manual';
                        $cell['label'] = 'Holiday';
                    } elseif ($manualStatus === 1) {
                        $cell['status'] = 'present';
                        $cell['source'] = 'manual';
                        $cell['label'] = 'Present (manual)';
                    } else {
                        $cell['status'] = 'absent';
                        $cell['source'] = 'manual';
                        $cell['label'] = 'Absent (manual)';
                    }
                } elseif (is_array($finger)) {
                    // 7-hour rule: Present only when In→Out duration > 7 hours
                    $fingerStatus = (string) ($finger['status'] ?? 'absent');
                    $rawStatus = (string) ($finger['raw_status'] ?? $fingerStatus);
                    $cell['time_in'] = (string) ($finger['time_in'] ?? '');
                    $cell['time_out'] = (string) ($finger['time_out'] ?? '');
                    $cell['duration_seconds'] = (int) ($finger['duration_seconds'] ?? 0);
                    $cell['source'] = 'finger';
                    if ($fingerStatus === 'present') {
                        $cell['status'] = 'present';
                        $cell['label'] = 'Present (>7h)';
                    } else {
                        $cell['status'] = 'absent';
                        $rawLabel = trim((string) ($finger['label'] ?? ''));
                        if ($rawLabel === 'Under 7 hours' || $rawStatus === 'late') {
                            $cell['label'] = 'Absent (<7h)';
                        } elseif ($rawLabel === 'Incomplete punch' || $rawStatus === 'incomplete') {
                            $cell['label'] = 'Absent (incomplete)';
                        } else {
                            $cell['label'] = $rawLabel !== '' ? $rawLabel : 'Absent (finger)';
                        }
                    }
                } elseif ($date <= $today) {
                    $cell['status'] = null;
                    $cell['label'] = 'No record';
                }
            }

            $map[$date] = $cell;
        }

        return $map;
    }

    /**
     * @param array<string,array<string,mixed>> $dayMap
     * @return list<list<array<string,mixed>>>
     */
    public static function weeksFromDayMap(string $month, array $dayMap): array
    {
        $firstDay = strtotime($month . '-01');
        $firstDow = (int) date('w', $firstDay);
        $daysInMonth = (int) date('t', $firstDay);

        $calendar = [];
        $week = [];
        for ($i = 0; $i < $firstDow; $i++) {
            $week[] = self::emptyCell();
        }
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $week[] = $dayMap[$date] ?? self::emptyCell();
            if (count($week) === 7) {
                $calendar[] = $week;
                $week = [];
            }
        }
        while (count($week) < 7) {
            $week[] = self::emptyCell();
        }
        if ($week !== []) {
            $calendar[] = $week;
        }
        return $calendar;
    }

    /** @return array<string,mixed> */
    private static function emptyCell(): array
    {
        return [
            'day' => null,
            'date' => null,
            'status' => null,
            'isWeekend' => false,
            'dayName' => '',
            'source' => null,
            'time_in' => '',
            'time_out' => '',
            'label' => '',
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $dayMap
     * @return array{totalDays:int,presentDays:int,absentDays:int,holidayDays:int,attendancePercentage:float}
     */
    public static function statsFromDayMap(array $dayMap): array
    {
        $totalDays = 0;
        $presentDays = 0;
        $absentDays = 0;
        $holidayDays = 0;
        $today = date('Y-m-d');

        foreach ($dayMap as $date => $day) {
            if (!empty($day['isWeekend'])) {
                continue;
            }
            if ((string) $date > $today) {
                continue;
            }
            $status = $day['status'] ?? null;
            if ($status === 'holiday') {
                $holidayDays++;
                continue;
            }
            $totalDays++;
            if ($status === 'present') {
                $presentDays++;
            } elseif ($status === 'absent') {
                $absentDays++;
            }
        }

        return [
            'totalDays' => $totalDays,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'holidayDays' => $holidayDays,
            'attendancePercentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0.0,
        ];
    }
}
