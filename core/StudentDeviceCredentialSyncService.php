<?php
/**
 * Multi-device credential sync: enroll once on MAIN, push FP/face to readers via ISAPI.
 * Does not delete existing device users or change attendance event logic.
 */
declare(strict_types=1);

require_once BASE_PATH . '/core/HikvisionIntegration.php';
require_once BASE_PATH . '/models/StudentDeviceAttendanceModel.php';

class StudentDeviceCredentialSyncService {
    private StudentDeviceAttendanceModel $model;
    private array $config;
    private int $maxAttempts = 5;

    public function __construct(?StudentDeviceAttendanceModel $model = null) {
        $this->model = $model ?? new StudentDeviceAttendanceModel();
        $this->model->ensureTable();
        $this->config = require BASE_PATH . '/config/student_attendance_machine.php';
    }

    public function attendanceModel(): StudentDeviceAttendanceModel {
        return $this->model;
    }

    /** @return array{host:string,role:string,label:string,username:string,password:string,ssl:bool,port:int,timeout:int} */
    public function mainDevice(): array {
        foreach ($this->devices() as $d) {
            if (($d['role'] ?? '') === 'main') {
                return $d;
            }
        }
        $host = (string) ($this->config['host'] ?? '');
        return [
            'host' => $host,
            'role' => 'main',
            'label' => 'Main (enrollment)',
            'username' => (string) ($this->config['username'] ?? 'admin'),
            'password' => (string) ($this->config['password'] ?? ''),
            'ssl' => !empty($this->config['ssl']),
            'port' => (int) ($this->config['port'] ?? 0),
            'timeout' => (int) ($this->config['timeout'] ?? 60),
        ];
    }

    /**
     * @return list<array{host:string,role:string,label:string,username:string,password:string,ssl:bool,port:int,timeout:int}>
     */
    public function devices(): array {
        $list = $this->config['devices'] ?? [];
        return is_array($list) ? array_values($list) : [];
    }

    /**
     * @return list<array{host:string,role:string,label:string,username:string,password:string,ssl:bool,port:int,timeout:int}>
     */
    public function readerDevices(): array {
        return array_values(array_filter(
            $this->devices(),
            static fn (array $d): bool => ($d['role'] ?? '') === 'reader'
        ));
    }

    public function hikvisionFor(array $device): HikvisionIntegration {
        return HikvisionIntegration::fromStudentDevice($device);
    }

    /**
     * Probe ONLINE / AUTH ERROR / OFFLINE via new HikvisionService (LAN only).
     *
     * @param string|null $onlyHost If set, probe only this host IP
     * @return list<array<string,mixed>>
     */
    public function probeDeviceStatuses(?string $onlyHost = null): array {
        require_once BASE_PATH . '/services/HikvisionService.php';
        require_once BASE_PATH . '/config/hikvision.php';

        $svc = new HikvisionService();
        $onlyHost = $onlyHost !== null ? trim($onlyHost) : null;
        $out = [];
        $stopAuth = false;

        foreach ($svc->devices() as $device) {
            $ip = (string) ($device['ip'] ?? '');
            if ($onlyHost !== null && $onlyHost !== '' && $ip !== $onlyHost) {
                continue;
            }

            if ($stopAuth && ($onlyHost === null || $onlyHost === '')) {
                $row = $svc->testReachabilityOnly($device);
                $row['status'] = 'AUTH ERROR';
                $row['last_error'] = 'Authentication skipped — previous device failed login (avoid lockout)';
            } else {
                $row = $svc->testDevice($device);
                if (($row['status'] ?? '') === 'AUTH ERROR') {
                    $stopAuth = true;
                }
            }

            $status = strtoupper((string) ($row['status'] ?? 'OFFLINE'));
            $legacy = 'offline';
            if ($status === 'ONLINE') {
                $legacy = 'online';
            } elseif ($status === 'AUTH ERROR') {
                $legacy = 'auth_error';
            }

            $out[] = [
                'host' => $ip,
                'role' => (string) ($row['role'] ?? ''),
                'label' => (string) ($row['label'] ?? ''),
                'online' => $status === 'ONLINE',
                'status' => $legacy,
                'message' => (string) (($row['last_error'] ?? '') !== '' ? $row['last_error'] : $status),
                'reason' => (string) ($row['last_error'] ?? $status),
                'locked' => stripos((string) ($row['last_error'] ?? ''), 'lock') !== false,
                'tcp_ok' => !empty($row['tcp_ok']),
                'http_ok' => !empty($row['http_ok']),
                'auth_ok' => !empty($row['auth_ok']),
                'model' => (string) ($row['device_name'] ?? ''),
                'sis_server' => (string) ($_SERVER['SERVER_ADDR'] ?? gethostname() ?: 'sis'),
            ];
        }

        return $out;
    }

    /**
     * Pull UserInfo directory from MAIN + all readers into student_attendance_machine_users.
     * Does not delete existing rows; upserts by (machine_id, employee_no).
     *
     * @return array{
     *   ok: bool,
     *   message: string,
     *   devices: list<array{host:string,role:string,label:string,online:bool,users:int,saved:int,message:string}>
     * }
     */
    public function refreshUserDirectoriesFromAllDevices(int $timeBudgetSec = 70): array {
        $started = time();
        $timeBudgetSec = max(20, min(110, $timeBudgetSec));
        $results = [];
        $totalSaved = 0;
        $onlineCount = 0;

        if (!HikvisionIntegration::isCurlAvailable()) {
            return [
                'ok' => false,
                'message' => 'PHP cURL is not installed.',
                'devices' => [],
            ];
        }

        require_once BASE_PATH . '/core/MachineAttendanceService.php';
        $baseCfg = $this->config;

        foreach ($this->devices() as $device) {
            if ((time() - $started) >= $timeBudgetSec) {
                $results[] = [
                    'host' => (string) ($device['host'] ?? ''),
                    'role' => (string) ($device['role'] ?? ''),
                    'label' => (string) ($device['label'] ?? ''),
                    'online' => false,
                    'users' => 0,
                    'saved' => 0,
                    'message' => 'Skipped (time budget)',
                ];
                continue;
            }

            $host = trim((string) ($device['host'] ?? ''));
            $label = (string) ($device['label'] ?? $host);
            $role = (string) ($device['role'] ?? '');
            if ($host === '') {
                continue;
            }

            $cfg = array_merge($baseCfg, [
                'host' => $host,
                'username' => (string) ($device['username'] ?? $baseCfg['username'] ?? 'admin'),
                'password' => (string) ($device['password'] ?? $baseCfg['password'] ?? ''),
                'ssl' => !empty($device['ssl']),
                'port' => (int) ($device['port'] ?? $baseCfg['port'] ?? 0),
                'timeout' => min(25, (int) ($device['timeout'] ?? $baseCfg['timeout'] ?? 60)),
            ]);

            try {
                $machine = new MachineAttendanceService($cfg);
                $test = $machine->testConnection();
                if (empty($test['ok'])) {
                    $results[] = [
                        'host' => $host,
                        'role' => $role,
                        'label' => $label,
                        'online' => false,
                        'users' => 0,
                        'saved' => 0,
                        'message' => (string) ($test['message'] ?? 'Offline'),
                    ];
                    continue;
                }
                $onlineCount++;
                $dir = $machine->fetchUserDirectory();
                if (empty($dir['ok'])) {
                    $results[] = [
                        'host' => $host,
                        'role' => $role,
                        'label' => $label,
                        'online' => true,
                        'users' => 0,
                        'saved' => 0,
                        'message' => (string) ($dir['message'] ?? 'User directory fetch failed'),
                    ];
                    continue;
                }
                $users = is_array($dir['users'] ?? null) ? $dir['users'] : [];
                $saved = $this->model->upsertMachineUsers($users, $host);
                if ($role === 'main') {
                    $this->model->linkFingerIdsFromMachineUsers($host);
                }
                $totalSaved += $saved;
                $results[] = [
                    'host' => $host,
                    'role' => $role,
                    'label' => $label,
                    'online' => true,
                    'users' => count($users),
                    'saved' => $saved,
                    'message' => 'OK — ' . count($users) . ' user(s)',
                ];
            } catch (Throwable $e) {
                $results[] = [
                    'host' => $host,
                    'role' => $role,
                    'label' => $label,
                    'online' => false,
                    'users' => 0,
                    'saved' => 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'ok' => $onlineCount > 0,
            'message' => "Refreshed user directories from {$onlineCount} online device(s); upserted {$totalSaved} row(s).",
            'devices' => $results,
        ];
    }

    /**
     * After enroll on MAIN: queue sync jobs for all readers and optionally process now.
     *
     * @param list<int> $fingerSlots
     * @return array{queued:int,processed:int,success:int,failed:int,message:string}
     */
    public function queueAndSyncEmployee(
        string $employeeNo,
        string $name = '',
        string $studentId = '',
        array $fingerSlots = [],
        bool $includeFace = false,
        bool $processNow = true,
        bool $probeMain = true
    ): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['queued' => 0, 'processed' => 0, 'success' => 0, 'failed' => 0, 'message' => 'Missing employee no'];
        }

        $readers = $this->readerDevices();
        if ($readers === []) {
            return [
                'queued' => 0,
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'message' => 'No reader IPs configured (STUDENT_HIKVISION_READER_IPS).',
            ];
        }

        // Live MAIN probes are expensive — only for single-user sync, never bulk queue.
        if ($probeMain) {
            if ($fingerSlots === []) {
                try {
                    $main = $this->hikvisionFor($this->mainDevice());
                    $detail = $main->getFingerPrintDetails($employeeNo);
                    $fingerSlots = array_values(array_map('intval', $detail['slots'] ?? []));
                } catch (Throwable $e) {
                    // keep empty
                }
            }
            if (!$includeFace) {
                try {
                    $main = $this->hikvisionFor($this->mainDevice());
                    // Lightweight UserInfo only — skip FingerPrintUpload enrich
                    $live = $main->searchUsersLite(5, $employeeNo);
                    $includeFace = ((int) ($live['users'][0]['face_count'] ?? 0)) > 0;
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }

        $queued = 0;
        foreach ($readers as $reader) {
            $host = (string) ($reader['host'] ?? '');
            if ($host === '') {
                continue;
            }
            $this->model->upsertCredentialSyncJob(
                $employeeNo,
                $host,
                $name,
                $studentId,
                $fingerSlots,
                $includeFace,
                'pending'
            );
            $queued++;
        }

        if (!$processNow) {
            return [
                'queued' => $queued,
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'message' => "Queued {$queued} reader job(s).",
            ];
        }

        $proc = $this->processPendingJobs(20, $employeeNo, 50);
        return [
            'queued' => $queued,
            'processed' => (int) ($proc['processed'] ?? 0),
            'success' => (int) ($proc['success'] ?? 0),
            'failed' => (int) ($proc['failed'] ?? 0),
            'message' => "Queued {$queued}. " . ($proc['message'] ?? ''),
        ];
    }

    /**
     * Queue every known machine user (from DB cache) to all readers — no per-user device probes.
     *
     * @return array{queued:int,processed:int,success:int,failed:int,message:string}
     */
    public function queueAndSyncAllUsers(bool $processNow = false, int $limit = 500): array {
        $mainHost = (string) ($this->mainDevice()['host'] ?? '');
        $users = $this->model->listMachineUsersForEnroll('', $limit);
        $queued = 0;
        foreach ($users as $u) {
            $eno = trim((string) ($u['employee_no'] ?? ''));
            if ($eno === '') {
                continue;
            }
            $mid = trim((string) ($u['machine_id'] ?? ''));
            if ($mid !== '' && $mainHost !== '' && $mid !== $mainHost && empty($u['on_machine'])) {
                continue;
            }
            $slotsRaw = $u['finger_slots'] ?? [];
            $slots = is_array($slotsRaw) ? $slotsRaw : [];
            if ($slots === [] && (int) ($u['finger_count'] ?? 0) > 0) {
                // Best-effort from count only (1..n) without device probe
                $n = min(2, max(1, (int) $u['finger_count']));
                $slots = range(1, $n);
            }
            $includeFace = ((int) ($u['face_count'] ?? 0) > 0) || !empty($u['has_face']);
            $r = $this->queueAndSyncEmployee(
                $eno,
                (string) ($u['student_name'] ?? $u['machine_name'] ?? ''),
                (string) ($u['student_id'] ?? ''),
                $slots,
                $includeFace,
                false,
                false
            );
            $queued += (int) ($r['queued'] ?? 0);
        }
        if (!$processNow) {
            return [
                'queued' => $queued,
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'message' => "Queued {$queued} job(s). Click “Process pending” repeatedly to sync in batches (avoids PHP timeout).",
            ];
        }
        $proc = $this->processPendingJobs(15, '', 45);
        return [
            'queued' => $queued,
            'processed' => (int) ($proc['processed'] ?? 0),
            'success' => (int) ($proc['success'] ?? 0),
            'failed' => (int) ($proc['failed'] ?? 0),
            'message' => "Queued {$queued}. " . ($proc['message'] ?? '')
                . ' Use “Process pending” for remaining jobs.',
        ];
    }

    /**
     * Process pending (and optionally failed retry) jobs with a time budget.
     *
     * @return array{processed:int,success:int,failed:int,message:string,remaining:int}
     */
    public function processPendingJobs(int $limit = 20, string $onlyEmployeeNo = '', int $timeBudgetSec = 50): array {
        if (!HikvisionIntegration::isCurlAvailable()) {
            return [
                'processed' => 0,
                'success' => 0,
                'failed' => 0,
                'remaining' => 0,
                'message' => 'PHP cURL is not installed — cannot sync to devices.',
            ];
        }

        $started = time();
        $timeBudgetSec = max(10, min(90, $timeBudgetSec));
        $limit = max(1, min(100, $limit));

        $jobs = $this->model->listCredentialSyncJobs(['pending', 'failed'], $limit * 3);
        $processed = 0;
        $success = 0;
        $failed = 0;
        $onlyEmployeeNo = trim($onlyEmployeeNo);

        foreach ($jobs as $job) {
            if ($processed >= $limit) {
                break;
            }
            if ((time() - $started) >= $timeBudgetSec) {
                break;
            }
            $eno = trim((string) ($job['employee_no'] ?? ''));
            if ($onlyEmployeeNo !== '' && $eno !== $onlyEmployeeNo) {
                continue;
            }
            $attempts = (int) ($job['attempt_count'] ?? 0);
            $status = (string) ($job['status'] ?? '');
            if ($status === 'failed' && $attempts >= $this->maxAttempts) {
                continue;
            }
            if ($status === 'failed' && !empty($job['last_attempt_at'])) {
                $wait = min(300, 30 * max(1, $attempts));
                $elapsed = time() - strtotime((string) $job['last_attempt_at']);
                if ($elapsed >= 0 && $elapsed < $wait) {
                    continue;
                }
            }

            $ok = $this->processOneJob($job);
            $processed++;
            if ($ok) {
                $success++;
            } else {
                $failed++;
            }
        }

        $remaining = count($this->model->listCredentialSyncJobs(['pending', 'failed'], 500));

        return [
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
            'remaining' => $remaining,
            'message' => "Processed {$processed}: {$success} ok, {$failed} failed"
                . ($remaining > 0 ? ", {$remaining} still pending/failed" : '') . '.',
        ];
    }

    /**
     * @param array<string,mixed> $job
     */
    private function processOneJob(array $job): bool {
        $id = (int) ($job['id'] ?? 0);
        $employeeNo = trim((string) ($job['employee_no'] ?? ''));
        $deviceHost = trim((string) ($job['device_host'] ?? ''));
        $name = trim((string) ($job['name'] ?? ''));
        if ($name === '') {
            $name = $employeeNo;
        }
        $slots = [];
        foreach (explode(',', (string) ($job['finger_slots'] ?? '')) as $p) {
            $n = (int) trim($p);
            if ($n > 0) {
                $slots[] = $n;
            }
        }
        $includeFace = !empty($job['include_face']);

        $this->model->updateCredentialSyncJob($id, 'syncing', '', false, false);

        $device = null;
        foreach ($this->readerDevices() as $d) {
            if ((string) ($d['host'] ?? '') === $deviceHost) {
                $device = $d;
                break;
            }
        }
        if ($device === null) {
            $msg = "Device {$deviceHost} is not in reader list.";
            $this->model->updateCredentialSyncJob($id, 'failed', $msg, true, false);
            $this->model->logCredentialSyncAttempt($employeeNo, $deviceHost, 'credentials', false, $msg);
            return false;
        }

        try {
            $main = $this->hikvisionFor($this->mainDevice());
            $reader = $this->hikvisionFor($device);

            // Ensure person exists on reader (same employee_no — no duplicate identity).
            $create = $reader->createUser($employeeNo, $name, 'normal');
            if (empty($create['ok'])) {
                // User may already exist — try modify as soft update
                $mod = $reader->modifyUser($employeeNo, $name, 'normal');
                if (empty($mod['ok']) && empty($create['ok'])) {
                    $msg = 'Create/modify user on reader failed: '
                        . ($create['message'] ?? '') . ' / ' . ($mod['message'] ?? '');
                    // Continue if fingerprints can still be written on some firmwares
                }
            }

            $errors = [];
            if ($slots === []) {
                $detail = $main->getFingerPrintDetails($employeeNo);
                $slots = array_values(array_map('intval', $detail['slots'] ?? []));
            }

            foreach ($slots as $fid) {
                $ext = $main->extractFingerPrintTemplate($employeeNo, $fid);
                if (empty($ext['ok']) || empty($ext['fingerData'])) {
                    $errors[] = "Finger {$fid} extract from MAIN: " . ($ext['message'] ?? 'failed');
                    continue;
                }
                $push = $reader->pushFingerPrintTemplate($employeeNo, $fid, (string) $ext['fingerData']);
                if (empty($push['ok'])) {
                    $errors[] = "Finger {$fid} push to {$deviceHost}: " . ($push['message'] ?? 'failed');
                }
            }

            if ($includeFace) {
                $photo = $main->getFacePhoto($employeeNo);
                if (!empty($photo['ok']) && !empty($photo['jpeg'])) {
                    $face = $reader->enrollFaceFromJpeg($employeeNo, (string) $photo['jpeg']);
                    if (empty($face['ok'])) {
                        $errors[] = 'Face push: ' . ($face['message'] ?? 'failed');
                    }
                } else {
                    $errors[] = 'Face extract from MAIN: ' . ($photo['message'] ?? 'not found');
                }
            }

            // Verify on reader
            $verify = $reader->getFingerPrintDetails($employeeNo);
            $readerSlots = array_values(array_map('intval', $verify['slots'] ?? []));
            foreach ($slots as $want) {
                if (!in_array($want, $readerSlots, true)) {
                    $errors[] = "Verify: finger {$want} missing on {$deviceHost}";
                }
            }

            // Cache machine_users row for this reader
            $this->model->upsertMachineUsers([[
                'employee_no' => $employeeNo,
                'name' => $name,
                'user_type' => 'normal',
                'finger_count' => count($readerSlots),
                'face_count' => $includeFace ? 1 : 0,
                'finger_slots' => $readerSlots,
            ]], $deviceHost);

            if ($errors !== [] && $slots !== []) {
                // Partial failure
                $msg = implode('; ', $errors);
                $allMissing = true;
                foreach ($slots as $want) {
                    if (in_array($want, $readerSlots, true)) {
                        $allMissing = false;
                        break;
                    }
                }
                if ($allMissing && !$includeFace) {
                    $this->model->updateCredentialSyncJob($id, 'failed', $msg, true, false);
                    $this->model->logCredentialSyncAttempt($employeeNo, $deviceHost, 'credentials', false, $msg);
                    return false;
                }
                // Some credentials synced — mark success with warning if at least one finger or face ok
                if (!$allMissing || $includeFace) {
                    $this->model->updateCredentialSyncJob($id, 'success', $msg, true, true);
                    $this->model->logCredentialSyncAttempt($employeeNo, $deviceHost, 'credentials', true, 'Partial: ' . $msg);
                    return true;
                }
            }

            if ($errors !== [] && $slots === [] && !$includeFace) {
                $msg = 'No fingerprints/face to sync. ' . implode('; ', $errors);
                $this->model->updateCredentialSyncJob($id, 'failed', $msg, true, false);
                $this->model->logCredentialSyncAttempt($employeeNo, $deviceHost, 'credentials', false, $msg);
                return false;
            }

            $msg = $errors === []
                ? 'Synced to ' . $deviceHost
                : ('Synced with warnings: ' . implode('; ', $errors));
            $this->model->updateCredentialSyncJob($id, 'success', $errors === [] ? '' : $msg, true, true);
            $this->model->logCredentialSyncAttempt($employeeNo, $deviceHost, 'credentials', true, $msg);
            return true;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->model->updateCredentialSyncJob($id, 'failed', $msg, true, false);
            $this->model->logCredentialSyncAttempt($employeeNo, $deviceHost, 'credentials', false, $msg);
            return false;
        }
    }

    /**
     * Delete a person already pushed to reader terminals (UserInfo + biometrics).
     * Does not delete attendance punch history. MAIN is skipped unless $includeMain is true.
     *
     * @param string $onlyHost If set, delete only on that host (must be a configured device)
     * @return array{
     *   ok: bool,
     *   message: string,
     *   deleted: int,
     *   failed: int,
     *   devices: list<array{host:string,role:string,label:string,ok:bool,message:string}>
     * }
     */
    public function deletePersonFromDevices(
        string $employeeNo,
        bool $includeMain = false,
        string $onlyHost = ''
    ): array {
        $employeeNo = trim($employeeNo);
        $onlyHost = trim($onlyHost);
        if ($employeeNo === '') {
            return [
                'ok' => false,
                'message' => 'Employee No is required.',
                'deleted' => 0,
                'failed' => 0,
                'devices' => [],
            ];
        }
        if (!HikvisionIntegration::isCurlAvailable()) {
            return [
                'ok' => false,
                'message' => 'PHP cURL is not installed.',
                'deleted' => 0,
                'failed' => 0,
                'devices' => [],
            ];
        }

        $targets = [];
        foreach ($this->devices() as $d) {
            $host = trim((string) ($d['host'] ?? ''));
            if ($host === '') {
                continue;
            }
            $role = (string) ($d['role'] ?? '');
            if ($onlyHost !== '') {
                if ($host !== $onlyHost) {
                    continue;
                }
            } elseif ($role === 'main' && !$includeMain) {
                continue;
            }
            $targets[] = $d;
        }

        if ($targets === []) {
            return [
                'ok' => false,
                'message' => $onlyHost !== ''
                    ? 'Device not found in config.'
                    : 'No reader devices configured.',
                'deleted' => 0,
                'failed' => 0,
                'devices' => [],
            ];
        }

        $results = [];
        $deleted = 0;
        $failed = 0;
        $hostsOk = [];

        foreach ($targets as $device) {
            $host = (string) ($device['host'] ?? '');
            $label = (string) ($device['label'] ?? $host);
            $role = (string) ($device['role'] ?? '');
            $row = [
                'host' => $host,
                'role' => $role,
                'label' => $label,
                'ok' => false,
                'message' => '',
            ];
            try {
                $hik = $this->hikvisionFor($device);
                $del = $hik->deleteUser($employeeNo);
                $row['ok'] = !empty($del['ok']);
                $row['message'] = (string) ($del['message'] ?? '');
                if ($row['ok']) {
                    $deleted++;
                    $hostsOk[] = $host;
                } else {
                    $failed++;
                }
                $this->model->logCredentialSyncAttempt(
                    $employeeNo,
                    $host,
                    'delete_user',
                    $row['ok'],
                    $row['message']
                );
            } catch (Throwable $e) {
                $failed++;
                $row['message'] = $e->getMessage();
                $this->model->logCredentialSyncAttempt(
                    $employeeNo,
                    $host,
                    'delete_user',
                    false,
                    $row['message']
                );
            }
            $results[] = $row;
        }

        // Clear queue + local cache for successfully deleted hosts
        if ($hostsOk !== []) {
            $this->model->deleteCredentialJobsForEmployee($employeeNo, $hostsOk);
            $this->model->deleteMachineUserRows($employeeNo, $hostsOk);
        }

        $scope = $includeMain ? 'MAIN + readers' : 'readers';
        if ($onlyHost !== '') {
            $scope = $onlyHost;
        }

        return [
            'ok' => $deleted > 0,
            'message' => "Deleted {$employeeNo} from {$deleted}/" . count($targets)
                . " device(s) ({$scope})"
                . ($failed > 0 ? "; {$failed} failed" : '') . '.',
            'deleted' => $deleted,
            'failed' => $failed,
            'devices' => $results,
        ];
    }
}
