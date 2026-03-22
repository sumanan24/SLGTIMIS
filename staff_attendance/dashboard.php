<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hikvision_sync_lib.php';

$pageTitle = 'Attendance dashboard';

$nowTs = time();
$lastAuto = (int) ($_SESSION['staff_attendance_last_auto_sync'] ?? 0);

$shouldSync = false;
if (STAFF_ATT_DASHBOARD_AUTO_SYNC) {
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
}

$staffNameFilter = trim((string) ($_GET['staff_name'] ?? ''));

$total = 0;
$monthCount = 0;
$todayCount = 0;
$latest = [];
$dbError = null;

$rowLimit = defined('STAFF_ATT_DASHBOARD_ROW_LIMIT') ? max(50, min(2000, (int) STAFF_ATT_DASHBOARD_ROW_LIMIT)) : 500;

try {
    $db = attendance_db();

    $total = (int) $db->query('SELECT COUNT(*) AS c FROM staff_attendance')->fetch_assoc()['c'];

    $monthCount = (int) $db->query(
        'SELECT COUNT(*) AS c FROM staff_attendance WHERE attendance_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)'
    )->fetch_assoc()['c'];

    $today = date('Y-m-d');
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM staff_attendance WHERE DATE(attendance_time) = ?');
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $todayCount = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    if ($staffNameFilter !== '') {
        $like = '%' . $staffNameFilter . '%';
        $q = 'SELECT attendance_id, employee_no, staff_name, department, attendance_time, device_ip, event_type
              FROM staff_attendance
              WHERE attendance_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
              AND staff_name LIKE ?
              ORDER BY attendance_time DESC
              LIMIT ?';
        $st = $db->prepare($q);
        $st->bind_param('si', $like, $rowLimit);
        $st->execute();
        $latest = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    } else {
        $q = 'SELECT attendance_id, employee_no, staff_name, department, attendance_time, device_ip, event_type
              FROM staff_attendance
              WHERE attendance_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
              ORDER BY attendance_time DESC
              LIMIT ?';
        $st = $db->prepare($q);
        $st->bind_param('i', $rowLimit);
        $st->execute();
        $latest = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$today = date('Y-m-d');

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Dashboard</h1>
<p class="text-muted small mb-4">
    Timezone: Asia/Colombo — Today: <?php echo attendance_escape($today); ?>.
    Opening this page syncs the <strong>last month</strong> of events from the device for all employees.
</p>

<?php if ($dbError !== null): ?>
    <div class="alert alert-danger">
        <strong>Database error.</strong> Import <code>staff_attendance/database.sql</code> if the table is missing.
        <br><small class="text-break"><?php echo attendance_escape($dbError); ?></small>
    </div>
<?php else: ?>

<form class="card shadow-sm mb-4" method="get" action="dashboard.php">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label class="form-label small mb-0">Filter by staff name</label>
                <input type="text" name="staff_name" class="form-control form-control-sm"
                       placeholder="Type part of name…"
                       value="<?php echo attendance_escape($staffNameFilter); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </div>
            <?php if ($staffNameFilter !== ''): ?>
                <div class="col-auto">
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0 mt-2">Table shows up to <?php echo (int) $rowLimit; ?> rows from the <strong>last month</strong> (rolling).</p>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">Last month (stored rows)</div>
                <div class="display-6"><?php echo $monthCount; ?></div>
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
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">All-time rows (database)</div>
                <div class="display-6"><?php echo $total; ?></div>
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mb-3"><?php echo $staffNameFilter !== '' ? 'Last month — filtered by name' : 'Last month — all employees'; ?></h2>
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
            <tr><td colspan="7" class="text-center py-4 text-muted">No rows in the last month<?php echo $staffNameFilter !== '' ? ' for this name filter' : ''; ?>.</td></tr>
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
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
