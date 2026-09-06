<?php
/**
 * Hikvision LAN dashboard + per-reader test endpoints.
 */
declare(strict_types=1);

class HikvisionController extends Controller {
    private function requireLogin(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        return true;
    }

    private function service(): HikvisionService {
        require_once BASE_PATH . '/services/HikvisionService.php';
        return new HikvisionService();
    }

    private function statusModel(): HikvisionModel {
        require_once BASE_PATH . '/models/HikvisionModel.php';
        $m = new HikvisionModel();
        $m->ensureTable();
        return $m;
    }

    /** GET /hikvision */
    public function dashboard() {
        if (!$this->requireLogin()) {
            return;
        }
        require_once BASE_PATH . '/config/hikvision.php';
        $svc = $this->service();
        $model = $this->statusModel();
        $saved = $model->allByKey();

        $cards = [];
        foreach ($svc->devices() as $d) {
            $key = $d['key'];
            $db = $saved[$key] ?? null;
            $cards[] = [
                'key' => $key,
                'label' => $d['label'],
                'ip' => $d['ip'],
                'role' => $d['role'],
                'status' => $db['status'] ?? 'UNKNOWN',
                'device_name' => $db['device_name'] ?? '',
                'last_seen' => $db['last_seen'] ?? null,
                'last_error' => $db['last_error'] ?? '',
                'checked_at' => $db['checked_at'] ?? null,
                'ping_ok' => !empty($db['ping_ok']),
                'tcp_ok' => !empty($db['tcp_ok']),
                'http_ok' => !empty($db['http_ok']),
                'auth_ok' => !empty($db['auth_ok']),
                'test_url' => rtrim(APP_URL, '/') . '/hikvision/test/' . $key,
            ];
        }

        return $this->view('hikvision/dashboard', [
            'title' => 'Hikvision LAN Devices',
            'page' => 'hikvision',
            'cards' => $cards,
            'flash_success' => $_SESSION['hikvision_flash_success'] ?? null,
            'flash_error' => $_SESSION['hikvision_flash_error'] ?? null,
            'test_all_url' => rtrim(APP_URL, '/') . '/hikvision/test/all',
            'main_ip' => MAIN_MACHINE_IP,
        ]);
    }

    /** GET|POST /hikvision/test/all */
    public function testAll() {
        if (!$this->requireLogin()) {
            return;
        }
        @set_time_limit(60);
        $svc = $this->service();
        $model = $this->statusModel();
        $rows = $svc->testAll();
        $online = 0;
        $lines = [];
        foreach ($rows as $row) {
            $model->upsertStatus($row);
            if (($row['status'] ?? '') === 'ONLINE') {
                $online++;
            }
            $lines[] = ($row['ip'] ?? '') . ': ' . ($row['status'] ?? '?')
                . (!empty($row['last_error']) ? ' (' . $row['last_error'] . ')' : '');
        }
        $msg = "Online {$online}/" . count($rows) . ' — ' . implode(' · ', $lines);
        if ($online > 0) {
            $_SESSION['hikvision_flash_success'] = $msg;
            unset($_SESSION['hikvision_flash_error']);
        } else {
            $_SESSION['hikvision_flash_error'] = $msg;
            unset($_SESSION['hikvision_flash_success']);
        }
        // Also refresh student-device session cache if present
        $_SESSION['student_att_device_status'] = [
            'devices' => $this->mapToLegacyProbe($rows),
            'tested_at' => date('Y-m-d H:i:s'),
        ];
        $this->redirect('hikvision');
    }

    /**
     * GET /hikvision/test/{key}  key = main|reader1|reader2|reader3
     */
    public function testOne($key = '') {
        if (!$this->requireLogin()) {
            return;
        }
        require_once BASE_PATH . '/config/hikvision.php';
        $key = strtolower(trim((string) $key));
        $device = hikvision_device_by_key($key);
        if ($device === null) {
            $_SESSION['hikvision_flash_error'] = 'Unknown device key: ' . $key;
            $this->redirect('hikvision');
            return;
        }

        @set_time_limit(30);
        $svc = $this->service();
        $model = $this->statusModel();
        $row = $svc->testDevice($device);
        $model->upsertStatus($row);

        $msg = ($row['label'] ?? '') . ' ' . ($row['ip'] ?? '') . ' → ' . ($row['status'] ?? '?')
            . (!empty($row['last_error']) ? ' — ' . $row['last_error'] : '');

        if (($row['status'] ?? '') === 'ONLINE') {
            $_SESSION['hikvision_flash_success'] = $msg;
            unset($_SESSION['hikvision_flash_error']);
        } else {
            $_SESSION['hikvision_flash_error'] = $msg;
            unset($_SESSION['hikvision_flash_success']);
        }

        // Merge into student-device cache
        $cached = $_SESSION['student_att_device_status']['devices'] ?? [];
        $byHost = [];
        if (is_array($cached)) {
            foreach ($cached as $c) {
                $h = (string) ($c['host'] ?? '');
                if ($h !== '') {
                    $byHost[$h] = $c;
                }
            }
        }
        foreach ($this->mapToLegacyProbe([$row]) as $p) {
            $h = (string) ($p['host'] ?? '');
            if ($h !== '') {
                $byHost[$h] = $p;
            }
        }
        $_SESSION['student_att_device_status'] = [
            'devices' => array_values($byHost),
            'tested_at' => date('Y-m-d H:i:s'),
        ];

        $this->redirect('hikvision');
    }

    /**
     * Map new service rows to legacy student-device probe shape.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function mapToLegacyProbe(array $rows): array {
        $out = [];
        foreach ($rows as $r) {
            $status = strtoupper((string) ($r['status'] ?? 'OFFLINE'));
            $legacyStatus = 'offline';
            if ($status === 'ONLINE') {
                $legacyStatus = 'online';
            } elseif ($status === 'AUTH ERROR') {
                $legacyStatus = 'auth_error';
            } elseif ($status === 'TIMEOUT') {
                $legacyStatus = 'offline';
            }
            $out[] = [
                'host' => (string) ($r['ip'] ?? ''),
                'role' => (string) ($r['role'] ?? ''),
                'label' => (string) ($r['label'] ?? ''),
                'online' => $status === 'ONLINE',
                'status' => $legacyStatus,
                'message' => (string) (($r['last_error'] ?? '') !== '' ? $r['last_error'] : $status),
                'reason' => (string) ($r['last_error'] ?? $status),
                'locked' => stripos((string) ($r['last_error'] ?? ''), 'lock') !== false,
                'tcp_ok' => !empty($r['tcp_ok']),
                'http_ok' => !empty($r['http_ok']),
                'auth_ok' => !empty($r['auth_ok']),
                'model' => (string) ($r['device_name'] ?? ''),
            ];
        }
        return $out;
    }
}
