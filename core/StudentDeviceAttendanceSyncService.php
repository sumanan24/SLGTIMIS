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
     * Quick sync: one device per HTTP request (today only). Use chunk 0..N-1.
     *
     * @return array{
     *   ok:bool,done:bool,chunk:int,next_chunk:int,total:int,
     *   host:string,label:string,role:string,
     *   records_retrieved:int,saved:int,duplicates:int,finger_ids_linked:int,
     *   message:string,skipped?:bool
     * }
     */
    public function syncTodayChunk(int $chunkIndex, int $deviceTimeoutSec = 35): array {
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        $tzName = !empty($cfg['timezone']) ? (string) $cfg['timezone'] : 'Asia/Colombo';
        $tz = new DateTimeZone($tzName);
        $today = new DateTimeImmutable('now', $tz);
        $startImm = $today->setTime(0, 0, 0);
        $endImm = $today->setTime(23, 59, 59);

        $devices = $this->configuredDevices($cfg);
        if ($devices === []) {
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

        $total = count($devices);
        if ($total === 0) {
            return [
                'ok' => false,
                'done' => true,
                'chunk' => 0,
                'next_chunk' => 0,
                'total' => 0,
                'host' => '',
                'label' => '',
                'role' => '',
                'records_retrieved' => 0,
                'saved' => 0,
                'duplicates' => 0,
                'finger_ids_linked' => 0,
                'message' => 'No devices configured',
                'skipped' => true,
            ];
        }

        if ($chunkIndex < 0 || $chunkIndex >= $total) {
            return [
                'ok' => true,
                'done' => true,
                'chunk' => $chunkIndex,
                'next_chunk' => $total,
                'total' => $total,
                'host' => '',
                'label' => '',
                'role' => '',
                'records_retrieved' => 0,
                'saved' => 0,
                'duplicates' => 0,
                'finger_ids_linked' => 0,
                'message' => 'All chunks complete',
                'skipped' => true,
            ];
        }

        $device = $devices[$chunkIndex];
        $part = $this->syncOneDevice(
            $device,
            $cfg,
            $startImm,
            $endImm,
            max(20, min(50, $deviceTimeoutSec))
        );

        $next = $chunkIndex + 1;
        return [
            'ok' => !empty($part['ok']),
            'done' => $next >= $total,
            'chunk' => $chunkIndex,
            'next_chunk' => $next,
            'total' => $total,
            'host' => (string) ($part['host'] ?? ''),
            'label' => (string) ($part['label'] ?? ''),
            'role' => (string) ($part['role'] ?? ''),
            'records_retrieved' => (int) ($part['records_retrieved'] ?? 0),
            'saved' => (int) ($part['saved'] ?? 0),
            'duplicates' => (int) ($part['duplicates'] ?? 0),
            'finger_ids_linked' => (int) ($part['finger_ids_linked'] ?? 0),
            'valid_student' => (int) ($part['valid_student'] ?? 0),
            'staff_ignored' => (int) ($part['staff_ignored'] ?? 0),
            'unmatched' => (int) ($part['unmatched'] ?? 0),
            'failed' => (int) ($part['failed'] ?? 0),
            'message' => (string) ($part['message'] ?? ''),
            'skipped' => false,
        ];
    }

    /**
     * One machine and one week-sized window per request, so a 1-week, 1-month,
     * or 2-month finger/face pull does not hit the PHP time limit.
     *
     * @return array{
     *   ok:bool,done:bool,chunk:int,next_chunk:int,total:int,
     *   host:string,label:string,role:string,
     *   window_from:string,window_to:string,
     *   records_retrieved:int,saved:int,duplicates:int,finger_ids_linked:int,
     *   valid_student:int,staff_ignored:int,unmatched:int,failed:int,
     *   message:string,skipped?:bool
     * }
     */
    public function syncRangeChunk(
        DateTimeInterface $start,
        DateTimeInterface $end,
        int $chunkIndex,
        int $windowDays = 7,
        int $deviceTimeoutSec = 45
    ): array {
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        $tzName = !empty($cfg['timezone']) ? (string) $cfg['timezone'] : 'Asia/Colombo';
        $tz = new DateTimeZone($tzName);
        $startDay = $this->toImmutable($start, $tz)->setTime(0, 0, 0);
        $endDay = $this->toImmutable($end, $tz)->setTime(0, 0, 0);
        if ($endDay < $startDay) {
            $swap = $startDay;
            $startDay = $endDay;
            $endDay = $swap;
        }

        $devices = $this->configuredDevices($cfg);
        if ($devices === []) {
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

        $jobs = $this->rangeJobs($devices, $startDay, $endDay, $windowDays);
        $total = count($jobs);
        $empty = [
            'ok' => $total === 0,
            'done' => true,
            'chunk' => $chunkIndex,
            'next_chunk' => $total,
            'total' => $total,
            'host' => '',
            'label' => '',
            'role' => '',
            'window_from' => $startDay->format('Y-m-d'),
            'window_to' => $endDay->format('Y-m-d'),
            'records_retrieved' => 0,
            'saved' => 0,
            'duplicates' => 0,
            'finger_ids_linked' => 0,
            'valid_student' => 0,
            'staff_ignored' => 0,
            'unmatched' => 0,
            'failed' => 0,
            'message' => $total === 0 ? 'No devices configured' : 'All chunks complete',
            'skipped' => true,
        ];
        if ($total === 0 || $chunkIndex < 0 || $chunkIndex >= $total) {
            return $empty;
        }

        $job = $jobs[$chunkIndex];
        $device = $job['device'];
        $winStart = $job['start'];
        $winEnd = $job['end'];
        $part = $this->syncOneDevice(
            $device,
            $cfg,
            $winStart,
            $winEnd,
            max(20, min(55, $deviceTimeoutSec))
        );
        $next = $chunkIndex + 1;
        $windowLabel = $winStart->format('Y-m-d') . ' to ' . $winEnd->format('Y-m-d');

        return [
            'ok' => !empty($part['ok']),
            'done' => $next >= $total,
            'chunk' => $chunkIndex,
            'next_chunk' => $next,
            'total' => $total,
            'host' => (string) ($part['host'] ?? ''),
            'label' => (string) ($part['label'] ?? ''),
            'role' => (string) ($part['role'] ?? ''),
            'window_from' => $winStart->format('Y-m-d'),
            'window_to' => $winEnd->format('Y-m-d'),
            'records_retrieved' => (int) ($part['records_retrieved'] ?? 0),
            'saved' => (int) ($part['saved'] ?? 0),
            'duplicates' => (int) ($part['duplicates'] ?? 0),
            'finger_ids_linked' => (int) ($part['finger_ids_linked'] ?? 0),
            'valid_student' => (int) ($part['valid_student'] ?? 0),
            'staff_ignored' => (int) ($part['staff_ignored'] ?? 0),
            'unmatched' => (int) ($part['unmatched'] ?? 0),
            'failed' => (int) ($part['failed'] ?? 0),
            'message' => trim((string) ($part['message'] ?? '') . ' · ' . $windowLabel),
            'skipped' => false,
        ];
    }

    /**
     * @param list<array<string,mixed>> $devices
     * @return list<array{device:array<string,mixed>,start:DateTimeImmutable,end:DateTimeImmutable}>
     */
    private function rangeJobs(
        array $devices,
        DateTimeImmutable $startDay,
        DateTimeImmutable $endDay,
        int $windowDays
    ): array {
        $windowDays = max(1, min(14, $windowDays));
        $jobs = [];
        $cursor = $startDay;
        while ($cursor <= $endDay) {
            $winEnd = $cursor->modify('+' . ($windowDays - 1) . ' days');
            if ($winEnd > $endDay) {
                $winEnd = $endDay;
            }
            foreach ($devices as $device) {
                $jobs[] = [
                    'device' => $device,
                    'start' => $cursor,
                    'end' => $winEnd,
                ];
            }
            $cursor = $winEnd->modify('+1 day');
        }
        return $jobs;
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
