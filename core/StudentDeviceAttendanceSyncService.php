<?php
/**
 * Orchestrates machine fetch → finger_id link → student match → duplicate-safe insert.
 */
declare(strict_types=1);

require_once BASE_PATH . '/core/MachineAttendanceService.php';
require_once BASE_PATH . '/models/StudentDeviceAttendanceModel.php';
require_once BASE_PATH . '/models/StudentAttendanceSyncLogModel.php';

class StudentDeviceAttendanceSyncService {
    private MachineAttendanceService $machine;
    private StudentDeviceAttendanceModel $attendance;
    private StudentAttendanceSyncLogModel $logs;

    public function __construct(?MachineAttendanceService $machine = null) {
        $this->machine = $machine ?? new MachineAttendanceService();
        $this->attendance = new StudentDeviceAttendanceModel();
        $this->logs = new StudentAttendanceSyncLogModel();
        $this->attendance->ensureTable();
        $this->logs->ensureTable();
    }

    public function machine(): MachineAttendanceService {
        return $this->machine;
    }

    public function attendanceModel(): StudentDeviceAttendanceModel {
        return $this->attendance;
    }

    public function logModel(): StudentAttendanceSyncLogModel {
        return $this->logs;
    }

    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   records_retrieved: int,
     *   machine_users: int,
     *   finger_ids_linked: int,
     *   valid_student: int,
     *   staff_ignored: int,
     *   empty_person_id: int,
     *   unmatched: int,
     *   duplicates: int,
     *   saved: int,
     *   failed: int
     * }
     */
    public function syncRange(
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $userId = null,
        string $username = 'cli'
    ): array {
        $tzName = 'Asia/Colombo';
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        if (!empty($cfg['timezone'])) {
            $tzName = (string) $cfg['timezone'];
        }
        $tz = new DateTimeZone($tzName);
        if ($start instanceof DateTimeImmutable) {
            $startImm = $start->setTimezone($tz);
        } elseif ($start instanceof DateTime) {
            $startImm = DateTimeImmutable::createFromMutable($start)->setTimezone($tz);
        } else {
            $startImm = new DateTimeImmutable($start->format('Y-m-d H:i:s'), $tz);
        }
        if ($end instanceof DateTimeImmutable) {
            $endImm = $end->setTimezone($tz);
        } elseif ($end instanceof DateTime) {
            $endImm = DateTimeImmutable::createFromMutable($end)->setTimezone($tz);
        } else {
            $endImm = new DateTimeImmutable($end->format('Y-m-d H:i:s'), $tz);
        }

        $dateFrom = $startImm->format('Y-m-d');
        $dateTo = $endImm->format('Y-m-d');
        $machineId = $this->machine->getHost();

        $logId = $this->logs->startLog($userId, $username, $dateFrom, $dateTo, $machineId);

        $summary = [
            'ok' => false,
            'message' => '',
            'records_retrieved' => 0,
            'machine_users' => 0,
            'finger_ids_linked' => 0,
            'valid_student' => 0,
            'staff_ignored' => 0,
            'empty_person_id' => 0,
            'unmatched' => 0,
            'duplicates' => 0,
            'saved' => 0,
            'failed' => 0,
        ];

        $fetch = $this->machine->fetchEvents(
            $startImm->setTime(0, 0, 0),
            $endImm->setTime(23, 59, 59)
        );

        if (!$fetch['ok']) {
            $summary['message'] = $fetch['message'];
            $this->logs->finishLog($logId, array_merge($summary, [
                'status' => 'error',
                'error_message' => $fetch['message'],
            ]));
            return $summary;
        }

        $summary['records_retrieved'] = (int) $fetch['retrieved'];
        $users = $fetch['users'] ?? [];
        if (is_array($users) && $users !== []) {
            $summary['machine_users'] = $this->attendance->upsertMachineUsers($users, $machineId);
        }

        // Link: student_id 2022/MET/4MA010 → employee_no 224MA010 → student.finger_id
        $link = $this->attendance->linkFingerIdsFromMachineUsers($machineId);
        $summary['finger_ids_linked'] = (int) ($link['linked'] ?? 0);

        // Lookup machine user types by employee_no for this machine
        $typeByEmp = [];
        foreach ($this->attendance->listMachineUsers(1000) as $mu) {
            if ((string) ($mu['machine_id'] ?? '') !== '' && (string) $mu['machine_id'] !== $machineId) {
                continue;
            }
            $typeByEmp[trim((string) ($mu['employee_no'] ?? ''))] = (string) ($mu['user_type'] ?? 'normal');
        }

        foreach ($fetch['events'] as $ev) {
            try {
                $employeeNo = trim((string) ($ev['person_id'] ?? ''));
                if ($employeeNo === '') {
                    $summary['empty_person_id']++;
                    continue;
                }

                $userType = strtolower((string) ($ev['user_type'] ?? ''));
                if ($userType === '' && isset($typeByEmp[$employeeNo])) {
                    $userType = strtolower($typeByEmp[$employeeNo]);
                }

                if (StudentDeviceAttendanceModel::isStaffUserType($userType)) {
                    $summary['staff_ignored']++;
                    continue;
                }

                if ($this->attendance->isStaffPersonId($employeeNo)) {
                    $summary['staff_ignored']++;
                    continue;
                }

                // Only student-type machine users
                if ($userType !== '' && !StudentDeviceAttendanceModel::isStudentUserType($userType)) {
                    $summary['staff_ignored']++;
                    continue;
                }

                $student = $this->attendance->findStudentByFingerId($employeeNo);
                if ($student === null) {
                    // Ignore unmatched (do not invent students)
                    $summary['unmatched']++;
                    continue;
                }

                $summary['valid_student']++;
                $name = $student['student_name'] !== ''
                    ? $student['student_name']
                    : trim((string) ($ev['machine_name'] ?? ''));

                $ins = $this->attendance->insertEvent([
                    'student_id' => $student['student_id'],
                    'employee_no' => $employeeNo,
                    'person_id' => $employeeNo,
                    'student_name' => $name,
                    'attendance_date' => $ev['attendance_date'],
                    'attendance_time' => $ev['attendance_time'],
                    'attendance_datetime' => $ev['attendance_datetime'],
                    'machine_id' => $ev['machine_id'],
                    'event_id' => $ev['event_id'],
                    'source' => 'hikvision',
                ]);
                if ($ins['inserted']) {
                    $summary['saved']++;
                } elseif ($ins['duplicate']) {
                    $summary['duplicates']++;
                } else {
                    $summary['failed']++;
                }
            } catch (Throwable $e) {
                $summary['failed']++;
                error_log('[StudentDeviceAttendanceSync] row failed: ' . $e->getMessage());
            }
        }

        $summary['ok'] = true;
        $summary['message'] = 'Synchronization Completed';
        $this->logs->finishLog($logId, array_merge($summary, [
            'status' => 'ok',
            'error_message' => '',
        ]));

        return $summary;
    }
}
