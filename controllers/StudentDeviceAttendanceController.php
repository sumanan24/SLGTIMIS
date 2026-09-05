<?php
/**
 * Student fingerprint attendance (device) — SAO / ADM only.
 */
declare(strict_types=1);

class StudentDeviceAttendanceController extends Controller {
    private function requireAccess(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if (!$userModel->canManageStudentFingerprintAttendance((int) $_SESSION['user_id'])) {
            http_response_code(403);
            echo 'Access denied. You do not have permission to access Student Attendance.';
            exit;
        }
        return true;
    }

    private function syncService(): StudentDeviceAttendanceSyncService {
        require_once BASE_PATH . '/core/StudentDeviceAttendanceSyncService.php';
        return new StudentDeviceAttendanceSyncService();
    }

    private function urls(): array {
        $root = rtrim(APP_URL, '/') . '/attendance/student-device';
        return [
            'index' => $root,
            'events' => $root . '/events',
            'users' => $root . '/users',
            'sync' => $root . '/sync',
            'search' => $root . '/events',
            'export_excel' => $root . '/export/excel',
            'export_csv' => $root . '/export/csv',
            'test' => $root . '/machine/test',
            'logs' => $root . '/logs',
        ];
    }

    private function filtersFromRequest(): array {
        $personId = trim((string) $this->get('person_id', ''));
        $name = trim((string) $this->get('student_name', ''));
        $date = trim((string) $this->get('date', ''));
        $dateFrom = trim((string) $this->get('date_from', ''));
        $dateTo = trim((string) $this->get('date_to', ''));
        $filters = [];
        if ($personId !== '') {
            $filters['person_id'] = $personId;
        }
        if ($name !== '') {
            $filters['student_name'] = $name;
        }
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $filters['date'] = $date;
        }
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $filters['date_to'] = $dateTo;
        }
        return $filters;
    }

    private function machineHost(): string {
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        return (string) ($cfg['host'] ?? '');
    }

    /** Dashboard */
    public function index() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $att = $svc->attendanceModel();
        $recent = $att->searchDailyGrouped([], 1, 8);

        return $this->view('attendance/student_device/index', [
            'title' => 'Student Fingerprint Attendance',
            'page' => 'student-device-attendance',
            'urls' => $this->urls(),
            'rows' => $recent['rows'],
            'todayCount' => $att->countToday(),
            'uniqueToday' => $att->countUniqueStudentsToday(),
            'totalRecords' => $att->countAll(),
            'lastSync' => $svc->logModel()->getLastSuccessful(),
            'machineHost' => $this->machineHost(),
            'syncSummary' => $_SESSION['student_att_sync_summary'] ?? null,
            'connectionStatus' => $_SESSION['student_att_connection'] ?? null,
        ]);
    }

    /** Attendance events (In / Out / Others) */
    public function events() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $att = $svc->attendanceModel();
        $filters = $this->filtersFromRequest();
        $page = max(1, (int) $this->get('page', 1));
        $result = $att->searchDailyGrouped($filters, $page, 50);

        return $this->view('attendance/student_device/events', [
            'title' => 'Attendance events',
            'page' => 'student-device-attendance-events',
            'urls' => $this->urls(),
            'filters' => $filters,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'pageNum' => $page,
            'perPage' => 50,
        ]);
    }

    public function users() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        return $this->view('attendance/student_device/users', [
            'title' => 'Machine users',
            'page' => 'student-device-attendance-users',
            'urls' => $this->urls(),
            'machineUsers' => $svc->attendanceModel()->listMachineUsers(500),
        ]);
    }

    public function search() {
        return $this->events();
    }

    public function filter() {
        return $this->events();
    }

    public function sync() {
        if (!$this->requireAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance/student-device');
            return;
        }

        $mode = trim((string) ($_POST['sync_mode'] ?? 'today'));
        $tz = new DateTimeZone('Asia/Colombo');
        $today = new DateTimeImmutable('now', $tz);

        if ($mode === 'range') {
            $from = trim((string) ($_POST['date_from'] ?? ''));
            $to = trim((string) ($_POST['date_to'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $_SESSION['flash_error'] = 'Invalid date range.';
                $this->redirect('attendance/student-device');
                return;
            }
            $start = new DateTimeImmutable($from . ' 00:00:00', $tz);
            $end = new DateTimeImmutable($to . ' 23:59:59', $tz);
        } elseif ($mode === 'full') {
            $start = new DateTimeImmutable('2026-01-01 00:00:00', $tz);
            $end = $today->setTime(23, 59, 59);
        } else {
            $start = $today->setTime(0, 0, 0);
            $end = $today->setTime(23, 59, 59);
        }

        $svc = $this->syncService();
        $summary = $svc->syncRange(
            $start,
            $end,
            (int) $_SESSION['user_id'],
            (string) ($_SESSION['user_name'] ?? '')
        );
        $_SESSION['student_att_sync_summary'] = $summary;
        if ($summary['ok']) {
            $_SESSION['flash_success'] = 'Synchronization Completed';
        } else {
            $_SESSION['flash_error'] = $summary['message'] ?: 'Synchronization failed.';
        }
        $this->redirect('attendance/student-device');
    }

    public function connectionTest() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $result = $svc->machine()->testConnection();
        $_SESSION['student_att_connection'] = [
            'host' => $svc->machine()->getHost(),
            'ok' => !empty($result['ok']),
            'message' => (string) ($result['message'] ?? ''),
            'tested_at' => date('Y-m-d H:i:s'),
            'device_info' => $result['device_info'] ?? null,
        ];
        if (!empty($result['ok'])) {
            $_SESSION['flash_success'] = 'Machine ' . $svc->machine()->getHost() . ': CONNECTED';
        } else {
            $_SESSION['flash_error'] = $result['message'] ?? 'DISCONNECTED';
        }
        $this->redirect('attendance/student-device');
    }

    public function logs() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        return $this->view('attendance/student_device/logs', [
            'title' => 'Student Attendance Sync Logs',
            'page' => 'student-device-attendance-logs',
            'urls' => $this->urls(),
            'logs' => $svc->logModel()->recent(100),
        ]);
    }

    public function exportExcel() {
        if (!$this->requireAccess()) {
            return;
        }
        require_once BASE_PATH . '/vendor/autoload.php';
        $svc = $this->syncService();
        $filters = $this->filtersFromRequest();
        $rows = $svc->attendanceModel()->exportDailyGrouped($filters);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            ['Student ID', 'Employee No', 'Student Name', 'Date', 'In', 'Out', 'Others', 'Machine ID'],
            null,
            'A1'
        );
        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['student_id'] ?? '',
                $row['employee_no'] ?? '',
                $row['student_name'] ?? '',
                $row['attendance_date'] ?? '',
                $row['time_in'] ?? '',
                $row['time_out'] ?? '',
                $row['time_others'] ?? '',
                $row['machine_id'] ?? '',
            ], null, 'A' . $r);
            $r++;
        }
        $filename = 'student_attendance_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportCsv() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $filters = $this->filtersFromRequest();
        $rows = $svc->attendanceModel()->exportDailyGrouped($filters);
        $filename = 'student_attendance_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Student ID', 'Employee No', 'Student Name', 'Date', 'In', 'Out', 'Others', 'Machine ID']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['student_id'] ?? '',
                $row['employee_no'] ?? '',
                $row['student_name'] ?? '',
                $row['attendance_date'] ?? '',
                $row['time_in'] ?? '',
                $row['time_out'] ?? '',
                $row['time_others'] ?? '',
                $row['machine_id'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
