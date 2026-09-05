<?php
/**
 * CLI sync for student fingerprint attendance.
 * Usage:
 *   php scripts/attendance_sync.php
 *   php scripts/attendance_sync.php full
 *   php scripts/attendance_sync.php 2026-09-01 2026-09-05
 */
declare(strict_types=1);

$base = dirname(__DIR__);
require_once $base . '/config/database.php';
require_once $base . '/core/Database.php';
require_once $base . '/core/Model.php';
require_once $base . '/core/EnvLoader.php';
require_once $base . '/core/StudentDeviceAttendanceSyncService.php';

EnvLoader::load($base . '/.env');

$tz = new DateTimeZone('Asia/Colombo');
$today = new DateTimeImmutable('now', $tz);

if ($argc >= 2 && strtolower((string) $argv[1]) === 'full') {
    $start = new DateTimeImmutable('2026-01-01 00:00:00', $tz);
    $end = $today->setTime(23, 59, 59);
} elseif ($argc >= 3 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[1]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[2])) {
    $start = new DateTimeImmutable($argv[1] . ' 00:00:00', $tz);
    $end = new DateTimeImmutable($argv[2] . ' 23:59:59', $tz);
} else {
    $start = $today->setTime(0, 0, 0);
    $end = $today->setTime(23, 59, 59);
}

$svc = new StudentDeviceAttendanceSyncService();
$summary = $svc->syncRange($start, $end, null, 'cli');

echo (!empty($summary['ok']) ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Message: ' . ($summary['message'] ?? '') . PHP_EOL;
echo 'Range: ' . $start->format('Y-m-d') . ' → ' . $end->format('Y-m-d') . PHP_EOL;
echo 'Retrieved: ' . (int) ($summary['records_retrieved'] ?? 0) . PHP_EOL;
echo 'Machine users: ' . (int) ($summary['machine_users'] ?? 0) . PHP_EOL;
echo 'Finger IDs linked: ' . (int) ($summary['finger_ids_linked'] ?? 0) . PHP_EOL;
echo 'Valid students: ' . (int) ($summary['valid_student'] ?? 0) . PHP_EOL;
echo 'Staff ignored: ' . (int) ($summary['staff_ignored'] ?? 0) . PHP_EOL;
echo 'Unmatched: ' . (int) ($summary['unmatched'] ?? 0) . PHP_EOL;
echo 'Duplicates: ' . (int) ($summary['duplicates'] ?? 0) . PHP_EOL;
echo 'Saved: ' . (int) ($summary['saved'] ?? 0) . PHP_EOL;
echo 'Failed: ' . (int) ($summary['failed'] ?? 0) . PHP_EOL;

exit(!empty($summary['ok']) ? 0 : 1);
