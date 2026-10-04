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
            $bounds = $this->readerQueryBounds($startImm, $endImm);
            $fetch = $machine->fetchEvents($bounds['start'], $bounds['end_exclusive']);
            $events = is_array($fetch['events'] ?? null) ? $fetch['events'] : [];
            if ($events === [] && empty($fetch['ok'])) {
                $out['message'] = (string) ($fetch['message'] ?? 'Fetch failed');
                return $out;
            }

            $out['ok'] = !empty($fetch['ok']);
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

            $imported = $this->importEvents($events, $host, $typeByEmp);
            foreach ($imported as $key => $value) {
                if (isset($out[$key]) && is_int($value)) {
                    $out[$key] += $value;
                }
            }

            if (empty($fetch['ok'])) {
                $out['message'] = (string) ($fetch['message'] ?? 'Fetch failed');
            } else {
                $out['message'] = 'OK — ' . $out['records_retrieved'] . ' event(s), saved ' . $out['saved'];
            }
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
     * Pull one page-batch from one of the three readers for the selected dates.
     * A failed reader is marked finished so the other readers still run.
     * The selected range is queried on the device; this does not use last_sync_at.
     *
     * @param array{reader?:int,minor_index?:int,position?:int,search_id?:string} $cursor
     * @return array<string,mixed>
     */
    public function syncReaderRangeSlice(
        DateTimeInterface $start,
        DateTimeInterface $end,
        array $cursor,
        int $pageBudget = 6,
        int $deviceTimeoutSec = 18
    ): array {
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        $tzName = !empty($cfg['timezone']) ? (string) $cfg['timezone'] : 'Asia/Colombo';
        $tz = new DateTimeZone($tzName);
        $bounds = $this->readerQueryBounds($this->toImmutable($start, $tz), $this->toImmutable($end, $tz));
        $readers = $this->attendanceReaders($cfg);
        $readerIndex = max(0, (int) ($cursor['reader'] ?? 0));
        $total = count($readers);
        $base = [
            'ok' => false,
            'done' => true,
            'reader_done' => true,
            'readers_total' => $total,
            'reader_index' => $readerIndex,
            'readers_finished' => $total,
            'host' => '',
            'label' => '',
            'role' => 'reader',
            'window_from' => $bounds['start']->format('Y-m-d H:i:s'),
            'window_to' => $bounds['end_exclusive']->format('Y-m-d H:i:s'),
            'records_retrieved' => 0,
            'saved' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'valid_student' => 0,
            'staff_ignored' => 0,
            'unmatched' => 0,
            'finger_ids_linked' => 0,
            'device_total' => null,
            'pages_fetched' => 0,
            'cursor' => ['reader' => $total, 'minor_index' => 0, 'position' => 0, 'search_id' => ''],
            'message' => $total === 0 ? 'No readers configured' : 'All readers complete',
            'skipped' => true,
        ];
        if ($readerIndex >= $total) {
            return $base;
        }

        $device = $readers[$readerIndex];
        $host = (string) ($device['host'] ?? '');
        $label = (string) ($device['label'] ?? $host);
        $slice = $base;
        $slice['done'] = false;
        $slice['reader_done'] = false;
        $slice['readers_finished'] = $readerIndex;
        $slice['host'] = $host;
        $slice['label'] = $label;
        $slice['skipped'] = false;
        $slice['cursor'] = [
            'reader' => $readerIndex,
            'minor_index' => max(0, (int) ($cursor['minor_index'] ?? 0)),
            'position' => max(0, (int) ($cursor['position'] ?? 0)),
            'search_id' => trim((string) ($cursor['search_id'] ?? '')),
        ];

        try {
            $machineCfg = array_merge($cfg, [
                'host' => $host,
                'username' => (string) ($device['username'] ?? $cfg['username'] ?? 'admin'),
                'password' => (string) ($device['password'] ?? $cfg['password'] ?? ''),
                'ssl' => !empty($device['ssl']),
                'port' => (int) ($device['port'] ?? $cfg['port'] ?? 0),
                'timeout' => max(12, min(25, $deviceTimeoutSec)),
            ]);
            $machine = new MachineAttendanceService($machineCfg);
            $fetch = $machine->fetchEvents(
                $bounds['start'],
                $bounds['end_exclusive'],
                false,
                max(1, $pageBudget),
                [
                    'minor_index' => (int) $slice['cursor']['minor_index'],
                    'position' => (int) $slice['cursor']['position'],
                    'search_id' => (string) $slice['cursor']['search_id'],
                ]
            );
            $imported = $this->importEvents(is_array($fetch['events'] ?? null) ? $fetch['events'] : [], $host, []);
            $slice['records_retrieved'] = (int) ($fetch['retrieved'] ?? 0);
            $slice['saved'] = (int) ($imported['saved'] ?? 0);
            $slice['duplicates'] = (int) ($imported['duplicates'] ?? 0);
            $slice['failed'] = (int) ($imported['failed'] ?? 0);
            $slice['valid_student'] = (int) ($imported['valid_student'] ?? 0);
            $slice['staff_ignored'] = (int) ($imported['staff_ignored'] ?? 0);
            $slice['unmatched'] = (int) ($imported['unmatched'] ?? 0);
            $slice['device_total'] = $fetch['device_total'] ?? null;
            $slice['pages_fetched'] = (int) ($fetch['pages_fetched'] ?? 0);
            $slice['ok'] = !empty($fetch['ok']);
            $slice['message'] = $label . ' ' . $host . ' · ' . ($fetch['message'] ?? '');

            if (empty($fetch['ok'])) {
                $slice['reader_done'] = true;
                $slice['message'] = $label . ' ' . $host . ' connection failed — ' . ($fetch['message'] ?? 'unavailable');
                error_log('[AttendanceSync] Reader ' . $host . ' FAILED: ' . $slice['message']);
            } elseif (!empty($fetch['complete'])) {
                $slice['reader_done'] = true;
                $slice['message'] = $label . ' ' . $host . ' range complete';
            } else {
                $next = is_array($fetch['resume'] ?? null) ? $fetch['resume'] : [];
                $slice['cursor'] = [
                    'reader' => $readerIndex,
                    'minor_index' => (int) ($next['minor_index'] ?? 0),
                    'position' => (int) ($next['position'] ?? 0),
                    'search_id' => (string) ($next['search_id'] ?? ''),
                ];
                $slice['message'] = $label . ' ' . $host
                    . ' · pages ' . $slice['pages_fetched']
                    . ' · position ' . (int) $slice['cursor']['position'];
            }
        } catch (Throwable $e) {
            $slice['ok'] = false;
            $slice['reader_done'] = true;
            $slice['failed']++;
            $slice['message'] = $label . ' ' . $host . ' failed — ' . $e->getMessage();
            error_log('[AttendanceSync] Reader ' . $host . ' exception: ' . $e->getMessage());
        }

        if (!empty($slice['reader_done'])) {
            $slice['cursor'] = [
                'reader' => $readerIndex + 1,
                'minor_index' => 0,
                'position' => 0,
                'search_id' => '',
            ];
            $slice['readers_finished'] = $readerIndex + 1;
            $slice['done'] = ($readerIndex + 1) >= $total;
        }

        return $slice;
    }

    /**
     * @return array{start: DateTimeImmutable, end_exclusive: DateTimeImmutable}
     */
    private function readerQueryBounds(DateTimeImmutable $start, DateTimeImmutable $end): array {
        $tz = $start->getTimezone();
        $from = $start->format('Y-m-d');
        $to = $end->format('Y-m-d');
        if ($to < $from) {
            $swap = $from;
            $from = $to;
            $to = $swap;
        }
        return MachineAttendanceService::inclusiveRangeToExclusive($from, $to, $tz->getName());
    }

    /**
     * The three student biometric readers, in a fixed order.
     *
     * @param array<string,mixed> $cfg
     * @return list<array<string,mixed>>
     */
    private function attendanceReaders(array $cfg): array {
        $order = [
            '172.16.0.29' => 'Reader 1',
            '172.16.0.28' => 'Reader 2',
            '172.16.0.27' => 'Reader 3',
        ];
        $byHost = [];
        foreach ($this->configuredDevices($cfg) as $device) {
            $byHost[(string) ($device['host'] ?? '')] = $device;
        }
        $out = [];
        foreach ($order as $ip => $label) {
            $device = $byHost[$ip] ?? [
                'host' => $ip,
                'role' => 'reader',
                'label' => $label,
                'username' => (string) ($cfg['username'] ?? 'admin'),
                'password' => (string) ($cfg['password'] ?? ''),
                'ssl' => !empty($cfg['ssl']),
                'port' => (int) ($cfg['port'] ?? 0),
                'timeout' => (int) ($cfg['timeout'] ?? 20),
            ];
            $device['host'] = $ip;
            $device['role'] = 'reader';
            $device['label'] = $label;
            $out[] = $device;
        }
        return $out;
    }

    /**
     * Insert missing punches. Existing device_id + event_id rows stay as duplicates.
     *
     * @param list<array<string,mixed>> $events
     * @param array<string,string> $typeByEmp
     * @return array{saved:int,duplicates:int,failed:int,valid_student:int,staff_ignored:int,unmatched:int,empty_person_id:int}
     */
    private function importEvents(array $events, string $host, array $typeByEmp): array {
        $out = [
            'saved' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'valid_student' => 0,
            'staff_ignored' => 0,
            'unmatched' => 0,
            'empty_person_id' => 0,
        ];
        foreach ($events as $ev) {
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
                    'machine_id' => ($ev['machine_id'] ?? '') !== '' ? $ev['machine_id'] : $host,
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
