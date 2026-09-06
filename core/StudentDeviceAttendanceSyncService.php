<?php
/**
 * Orchestrates machine fetch → finger_id link → student match → duplicate-safe insert.
 * Collects AcsEvent punches from MAIN + all reader terminals.
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
     * Sync punches from MAIN + all configured readers for the date range.
     *
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
     *   failed: int,
     *   devices_online: int,
     *   devices_total: int,
     *   devices: list<array{host:string,role:string,label:string,ok:bool,records_retrieved:int,saved:int,duplicates:int,machine_users:int,finger_ids_linked:int,message:string}>
     * }
     */
    public function syncRange(
        DateTimeInterface $start,
        DateTimeInterface $end,
        ?int $userId = null,
        string $username = 'cli',
        int $timeBudgetSec = 160
    ): array {
        $tzName = 'Asia/Colombo';
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        if (!empty($cfg['timezone'])) {
            $tzName = (string) $cfg['timezone'];
        }
        $tz = new DateTimeZone($tzName);
        $startImm = $this->toImmutable($start, $tz);
        $endImm = $this->toImmutable($end, $tz);
        $dateFrom = $startImm->format('Y-m-d');
        $dateTo = $endImm->format('Y-m-d');

        $devices = $this->configuredDevices($cfg);
        if ($devices === []) {
            // Fallback: single default machine client
            $devices = [[
                'host' => $this->machine->getHost(),
                'role' => 'main',
                'label' => 'Main',
                'username' => (string) ($cfg['username'] ?? 'admin'),
                'password' => (string) ($cfg['password'] ?? ''),
                'ssl' => !empty($cfg['ssl']),
                'port' => (int) ($cfg['port'] ?? 0),
                'timeout' => (int) ($cfg['timeout'] ?? 60),
            ]];
        }

        $hostsLabel = implode(',', array_map(
            static fn (array $d): string => (string) ($d['host'] ?? ''),
            $devices
        ));
        $logId = $this->logs->startLog($userId, $username, $dateFrom, $dateTo, $hostsLabel);

        $summary = $this->emptySummary();
        $summary['devices_total'] = count($devices);
        $started = time();
        $timeBudgetSec = max(40, min(280, $timeBudgetSec));

        foreach ($devices as $device) {
            if ((time() - $started) >= $timeBudgetSec) {
                $summary['devices'][] = [
                    'host' => (string) ($device['host'] ?? ''),
                    'role' => (string) ($device['role'] ?? ''),
                    'label' => (string) ($device['label'] ?? ''),
                    'ok' => false,
                    'records_retrieved' => 0,
                    'saved' => 0,
                    'duplicates' => 0,
                    'machine_users' => 0,
                    'finger_ids_linked' => 0,
                    'message' => 'Skipped (time budget)',
                ];
                continue;
            }

            $remaining = max(20, $timeBudgetSec - (time() - $started));
            $part = $this->syncOneDevice(
                $device,
                $cfg,
                $startImm,
                $endImm,
                min(55, $remaining)
            );
            $summary['devices'][] = $part;
            if (!empty($part['ok'])) {
                $summary['devices_online']++;
            }
            $summary['records_retrieved'] += (int) ($part['records_retrieved'] ?? 0);
            $summary['machine_users'] += (int) ($part['machine_users'] ?? 0);
            $summary['finger_ids_linked'] += (int) ($part['finger_ids_linked'] ?? 0);
            $summary['valid_student'] += (int) ($part['valid_student'] ?? 0);
            $summary['staff_ignored'] += (int) ($part['staff_ignored'] ?? 0);
            $summary['empty_person_id'] += (int) ($part['empty_person_id'] ?? 0);
            $summary['unmatched'] += (int) ($part['unmatched'] ?? 0);
            $summary['duplicates'] += (int) ($part['duplicates'] ?? 0);
            $summary['saved'] += (int) ($part['saved'] ?? 0);
            $summary['failed'] += (int) ($part['failed'] ?? 0);
        }

        $online = (int) $summary['devices_online'];
        $total = (int) $summary['devices_total'];
        if ($online === 0) {
            $summary['ok'] = false;
            $summary['message'] = 'No machines responded. Check network / credentials.';
            $this->logs->finishLog($logId, array_merge($summary, [
                'status' => 'error',
                'error_message' => $summary['message'],
            ]));
            return $summary;
        }

        $summary['ok'] = true;
        $summary['message'] = "Synchronization Completed from {$online}/{$total} machine(s)"
            . " — retrieved {$summary['records_retrieved']}, saved {$summary['saved']}"
            . ", finger IDs linked {$summary['finger_ids_linked']}.";
        $this->logs->finishLog($logId, array_merge($summary, [
            'status' => 'ok',
            'error_message' => '',
        ]));

        return $summary;
    }

    /**
     * @param array<string,mixed> $device
     * @param array<string,mixed> $baseCfg
     * @return array{
     *   host:string,role:string,label:string,ok:bool,records_retrieved:int,saved:int,duplicates:int,
     *   machine_users:int,finger_ids_linked:int,valid_student:int,staff_ignored:int,empty_person_id:int,
     *   unmatched:int,failed:int,message:string
     * }
     */
    private function syncOneDevice(
        array $device,
        array $baseCfg,
        DateTimeImmutable $startImm,
        DateTimeImmutable $endImm,
        int $deviceTimeoutSec
    ): array {
        $host = trim((string) ($device['host'] ?? ''));
        $role = (string) ($device['role'] ?? '');
        $label = (string) ($device['label'] ?? $host);
        $out = [
            'host' => $host,
            'role' => $role,
            'label' => $label,
            'ok' => false,
            'records_retrieved' => 0,
            'saved' => 0,
            'duplicates' => 0,
            'machine_users' => 0,
            'finger_ids_linked' => 0,
            'valid_student' => 0,
            'staff_ignored' => 0,
            'empty_person_id' => 0,
            'unmatched' => 0,
            'failed' => 0,
            'message' => '',
        ];
        if ($host === '') {
            $out['message'] = 'Missing host';
            return $out;
        }

        $cfg = array_merge($baseCfg, [
            'host' => $host,
            'username' => (string) ($device['username'] ?? $baseCfg['username'] ?? 'admin'),
            'password' => (string) ($device['password'] ?? $baseCfg['password'] ?? ''),
            'ssl' => !empty($device['ssl']),
            'port' => (int) ($device['port'] ?? $baseCfg['port'] ?? 0),
            'timeout' => max(15, min(60, $deviceTimeoutSec)),
        ]);

        try {
            $machine = new MachineAttendanceService($cfg);
            $fetch = $machine->fetchEvents(
                $startImm->setTime(0, 0, 0),
                $endImm->setTime(23, 59, 59)
            );
            if (empty($fetch['ok'])) {
                $out['message'] = (string) ($fetch['message'] ?? 'Fetch failed');
                return $out;
            }

            $out['ok'] = true;
            $out['records_retrieved'] = (int) ($fetch['retrieved'] ?? 0);
            $users = $fetch['users'] ?? [];
            if (is_array($users) && $users !== []) {
                $out['machine_users'] = $this->attendance->upsertMachineUsers($users, $host);
            }

            // Update student.finger_id from this machine's directory (same Person ID on all terminals)
            $link = $this->attendance->linkFingerIdsFromMachineUsers($host);
            $out['finger_ids_linked'] = (int) ($link['linked'] ?? 0);

            $typeByEmp = [];
            foreach ($this->attendance->listMachineUsers(5000) as $mu) {
                if ((string) ($mu['machine_id'] ?? '') !== $host) {
                    continue;
                }
                $typeByEmp[trim((string) ($mu['employee_no'] ?? ''))] = (string) ($mu['user_type'] ?? 'normal');
            }

            foreach ($fetch['events'] as $ev) {
                try {
                    $employeeNo = trim((string) ($ev['person_id'] ?? ''));
                    if ($employeeNo === '') {
                        $out['empty_person_id']++;
                        continue;
                    }

                    $userType = strtolower((string) ($ev['user_type'] ?? ''));
                    if ($userType === '' && isset($typeByEmp[$employeeNo])) {
                        $userType = strtolower($typeByEmp[$employeeNo]);
                    }

                    if (StudentDeviceAttendanceModel::isStaffUserType($userType)) {
                        $out['staff_ignored']++;
                        continue;
                    }
                    if ($this->attendance->isStaffPersonId($employeeNo)) {
                        $out['staff_ignored']++;
                        continue;
                    }
                    if ($userType !== '' && !StudentDeviceAttendanceModel::isStudentUserType($userType)) {
                        $out['staff_ignored']++;
                        continue;
                    }

                    $student = $this->attendance->findStudentByFingerId($employeeNo);
                    if ($student === null) {
                        $out['unmatched']++;
                        continue;
                    }

                    $out['valid_student']++;
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
                        'machine_id' => $ev['machine_id'] !== '' ? $ev['machine_id'] : $host,
                        'event_id' => $ev['event_id'],
                        'source' => 'hikvision',
                    ]);
                    if ($ins['inserted']) {
                        $out['saved']++;
                    } elseif ($ins['duplicate']) {
                        $out['duplicates']++;
                    } else {
                        $out['failed']++;
                    }
                } catch (Throwable $e) {
                    $out['failed']++;
                    error_log('[StudentDeviceAttendanceSync] row failed: ' . $e->getMessage());
                }
            }

            $out['message'] = 'OK — ' . $out['records_retrieved'] . ' event(s), saved ' . $out['saved'];
        } catch (Throwable $e) {
            $out['message'] = $e->getMessage();
            error_log('[StudentDeviceAttendanceSync] device ' . $host . ': ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $cfg
     * @return list<array<string,mixed>>
     */
    private function configuredDevices(array $cfg): array {
        $devices = $cfg['devices'] ?? [];
        if (!is_array($devices) || $devices === []) {
            return [];
        }
        $out = [];
        foreach ($devices as $d) {
            if (!is_array($d)) {
                continue;
            }
            $host = trim((string) ($d['host'] ?? ''));
            if ($host === '') {
                continue;
            }
            $out[] = $d;
        }
        return $out;
    }

    /** @return array<string,mixed> */
    private function emptySummary(): array {
        return [
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
            'devices_online' => 0,
            'devices_total' => 0,
            'devices' => [],
        ];
    }

    private function toImmutable(DateTimeInterface $dt, DateTimeZone $tz): DateTimeImmutable {
        if ($dt instanceof DateTimeImmutable) {
            return $dt->setTimezone($tz);
        }
        if ($dt instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($dt)->setTimezone($tz);
        }
        return new DateTimeImmutable($dt->format('Y-m-d H:i:s'), $tz);
    }
}
