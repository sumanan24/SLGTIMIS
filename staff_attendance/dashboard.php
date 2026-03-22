<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hikvision_sync_lib.php';

$pageTitle = 'Attendance dashboard';

$nowTs = time();
$forceSync = isset($_GET['force_sync']) && $_GET['force_sync'] === '1';
$lastAuto = (int) ($_SESSION['staff_attendance_last_auto_sync'] ?? 0);

$shouldSync = $forceSync;
if (!$shouldSync) {
    if (STAFF_ATT_DASHBOARD_SYNC_COOLDOWN === 0) {
        $shouldSync = true;
    } else {
        $shouldSync = ($nowTs - $lastAuto >= STAFF_ATT_DASHBOARD_SYNC_COOLDOWN);
    }
}

if ($shouldSync) {
    $end = new DateTimeImmutable('now');
    $start = $end->sub(new DateInterval('P1M'));
    $result = attendance_run_hikvision_sync($start, $end);
    $_SESSION['staff_attendance_last_auto_sync'] = $nowTs;

    if (!$result['ok']) {
        $_SESSION['flash_error'] = $result['error'] ?? 'Automatic sync failed.';
    }
    // Success is silent — totals and table below reflect new data. Manual sync page shows full stats.
}

$db = attendance_db();

$total = (int) $db->query('SELECT COUNT(*) AS c FROM staff_attendance')->fetch_assoc()['c'];

$today = date('Y-m-d');
$stmt = $db->prepare('SELECT COUNT(*) AS c FROM staff_attendance WHERE DATE(attendance_time) = ?');
$stmt->bind_param('s', $today);
$stmt->execute();
$todayCount = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$latest = $db->query(
    'SELECT attendance_id, employee_no, staff_name, department, attendance_time, device_ip, event_type
     FROM staff_attendance
     ORDER BY attendance_time DESC
     LIMIT 50'
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Dashboard</h1>
<p class="text-muted small mb-2">Timezone: Asia/Colombo — Today: <?php echo attendance_escape($today); ?></p>
<p class="text-muted small mb-4">
    Opening this page pulls the <strong>last 1 month</strong> of events from the Hikvision device (same as auto-sync).
    <?php if (STAFF_ATT_DASHBOARD_SYNC_COOLDOWN > 0): ?>
        Cooldown: <?php echo (int) STAFF_ATT_DASHBOARD_SYNC_COOLDOWN; ?>s between syncs —
        <a href="dashboard.php?force_sync=1">sync now</a>.
    <?php endif; ?>
</p>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">Total attendance rows</div>
                <div class="display-6"><?php echo $total; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">Today's attendance</div>
                <div class="display-6"><?php echo $todayCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center flex-wrap gap-2">
        <a href="list_attendance.php" class="btn btn-primary">Full list</a>
        <a href="sync_attendance.php" class="btn btn-outline-primary">Manual sync</a>
        <a href="dashboard.php?force_sync=1" class="btn btn-outline-secondary btn-sm">Re-sync month</a>
    </div>
</div>

<h2 class="h5 mb-3">Latest 50 records</h2>
<div class="table-responsive shadow-sm bg-white rounded">
    <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Employee no.</th>
            <th>Name</th>
            <th>Department</th>
            <th>Time</th>
            <th>Device IP</th>
            <th>Event</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$latest): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">No data yet.</td></tr>
        <?php else: ?>
            <?php foreach ($latest as $r): ?>
                <tr>
                    <td><?php echo (int) $r['attendance_id']; ?></td>
                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                    <td><?php echo attendance_escape((string) $r['department']); ?></td>
                    <td><?php echo attendance_escape((string) $r['attendance_time']); ?></td>
                    <td><?php echo attendance_escape((string) $r['device_ip']); ?></td>
                    <td><?php echo attendance_escape((string) $r['event_type']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
