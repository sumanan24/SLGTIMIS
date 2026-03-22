<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hikvision_sync_lib.php';

$pageTitle = 'Attendance dashboard';

$lookbackSpec = defined('STAFF_SYNC_LOOKBACK_INTERVAL') ? (string) STAFF_SYNC_LOOKBACK_INTERVAL : 'P1W';
try {
    $syncInterval = new DateInterval($lookbackSpec);
} catch (Exception $e) {
    $syncInterval = new DateInterval('P1W');
}

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
    $start = $end->sub($syncInterval);
    $result = attendance_run_hikvision_sync($start, $end);
    $_SESSION['staff_attendance_last_auto_sync'] = $nowTs;

    if (!$result['ok']) {
        $_SESSION['flash_error'] = $result['error'] ?? 'Automatic sync failed.';
    }
}

$summaryDays = defined('STAFF_DASHBOARD_SUMMARY_DAYS') ? max(1, min(31, (int) STAFF_DASHBOARD_SUMMARY_DAYS)) : 7;

$dateToIn = trim((string) ($_GET['date_to'] ?? ''));
$dateFromIn = trim((string) ($_GET['date_from'] ?? ''));
$employeeNo = trim((string) ($_GET['employee_no'] ?? ''));

$todayYmd = date('Y-m-d');
if ($dateToIn === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateToIn)) {
    $dateTo = $todayYmd;
} else {
    $dateTo = $dateToIn;
}

if ($dateFromIn === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFromIn)) {
    $dateFrom = date('Y-m-d', strtotime($dateTo . ' -' . ($summaryDays - 1) . ' days'));
} else {
    $dateFrom = $dateFromIn;
}

if ($dateFrom > $dateTo) {
    $tmp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $tmp;
}

$spanDays = (int) floor((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1;
if ($spanDays > 31) {
    $dateFrom = date('Y-m-d', strtotime($dateTo . ' -30 days'));
}

$employees = [];
$grouped = [];
$rangePunches = 0;
$total = 0;
$todayCount = 0;
$dbError = null;

try {
    $db = attendance_db();
    $db->query('SET SESSION group_concat_max_len = 16384');

    $total = (int) $db->query('SELECT COUNT(*) AS c FROM staff_attendance')->fetch_assoc()['c'];

    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM staff_attendance WHERE DATE(attendance_time) = ?');
    $stmt->bind_param('s', $todayYmd);
    $stmt->execute();
    $todayCount = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    $er = $db->query(
        'SELECT DISTINCT employee_no, staff_name FROM staff_attendance ORDER BY staff_name ASC, employee_no ASC'
    );
    if ($er) {
        while ($row = $er->fetch_assoc()) {
            $employees[] = $row;
        }
    }

    $rangeStmt = $db->prepare(
        'SELECT COUNT(*) AS c FROM staff_attendance WHERE DATE(attendance_time) BETWEEN ? AND ?'
    );
    $rangeStmt->bind_param('ss', $dateFrom, $dateTo);
    $rangeStmt->execute();
    $rangePunches = (int) ($rangeStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $rangeStmt->close();

    if ($employeeNo === '') {
        $sql = 'SELECT employee_no,
                       MAX(staff_name) AS staff_name,
                       MAX(department) AS department,
                       DATE(attendance_time) AS d,
                       GROUP_CONCAT(DATE_FORMAT(attendance_time, \'%H:%i:%s\') ORDER BY attendance_time SEPARATOR \',\') AS times_csv
                FROM staff_attendance
                WHERE DATE(attendance_time) BETWEEN ? AND ?
                GROUP BY employee_no, DATE(attendance_time)
                ORDER BY d DESC, staff_name ASC, employee_no ASC';
        $gq = $db->prepare($sql);
        $gq->bind_param('ss', $dateFrom, $dateTo);
    } else {
        $sql = 'SELECT employee_no,
                       MAX(staff_name) AS staff_name,
                       MAX(department) AS department,
                       DATE(attendance_time) AS d,
                       GROUP_CONCAT(DATE_FORMAT(attendance_time, \'%H:%i:%s\') ORDER BY attendance_time SEPARATOR \',\') AS times_csv
                FROM staff_attendance
                WHERE DATE(attendance_time) BETWEEN ? AND ?
                  AND employee_no = ?
                GROUP BY employee_no, DATE(attendance_time)
                ORDER BY d DESC, staff_name ASC, employee_no ASC';
        $gq = $db->prepare($sql);
        $gq->bind_param('sss', $dateFrom, $dateTo, $employeeNo);
    }
    $gq->execute();
    $grouped = $gq->get_result()->fetch_all(MYSQLI_ASSOC);
    $gq->close();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Dashboard</h1>
<p class="text-muted small mb-3">
    Timezone: Asia/Colombo — Today: <?php echo attendance_escape($todayYmd); ?>.
    Opening this page syncs the last <strong><?php echo attendance_escape($lookbackSpec); ?></strong> of events from the device.
</p>

<?php if ($dbError !== null): ?>
    <div class="alert alert-danger">
        <strong>Database error.</strong> Import <code>staff_attendance/database.sql</code> if the table is missing.
        <br><small class="text-break"><?php echo attendance_escape($dbError); ?></small>
    </div>
<?php else: ?>

<form class="card shadow-sm mb-4" method="get" action="dashboard.php" id="dashFilter">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo attendance_escape($dateFrom); ?>">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo attendance_escape($dateTo); ?>">
            </div>
            <div class="col-md-6 col-lg-4">
                <label class="form-label small mb-0">Employee</label>
                <select name="employee_no" class="form-select form-select-sm">
                    <option value="">All employees</option>
                    <?php foreach ($employees as $em): ?>
                        <?php
                        $eno = (string) $em['employee_no'];
                        $sn = trim((string) ($em['staff_name'] ?? ''));
                        $label = $sn !== '' ? $sn . ' (' . $eno . ')' : $eno;
                        ?>
                        <option value="<?php echo attendance_escape($eno); ?>" <?php echo $employeeNo === $eno ? 'selected' : ''; ?>>
                            <?php echo attendance_escape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </div>
            <div class="col-auto">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Reset week</a>
            </div>
        </div>
        <p class="text-muted small mb-0 mt-2">
            Summary is grouped by <strong>employee</strong> and <strong>calendar day</strong>.
            Check-in = first punch, check-out = last punch; other device times that day are listed in between.
        </p>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">Punches in selected range</div>
                <div class="display-6"><?php echo $rangePunches; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">Today's punches</div>
                <div class="display-6"><?php echo $todayCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body">
                <div class="text-muted small">All-time rows</div>
                <div class="display-6"><?php echo $total; ?></div>
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mb-3">Daily summary (check-in / check-out / other times)</h2>
<div class="table-responsive shadow-sm bg-white rounded">
    <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
        <tr>
            <th>Employee no.</th>
            <th>Name</th>
            <th>Department</th>
            <th>Date</th>
            <th>Day</th>
            <th>Check-in <span class="text-muted fw-normal">(min)</span></th>
            <th>Check-out <span class="text-muted fw-normal">(max)</span></th>
            <th>Other times</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$grouped): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">No attendance in this range<?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.</td></tr>
        <?php else: ?>
            <?php foreach ($grouped as $r): ?>
                <?php
                $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
                $d = (string) $r['d'];
                $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
                $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';
                ?>
                <tr>
                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                    <td><?php echo attendance_escape((string) ($r['department'] ?? '')); ?></td>
                    <td><span class="text-nowrap"><?php echo attendance_escape($d); ?></span></td>
                    <td><?php echo attendance_escape($dayLabel); ?></td>
                    <td><code><?php echo attendance_escape($split['in']); ?></code></td>
                    <td><code><?php echo attendance_escape($split['out']); ?></code></td>
                    <td class="small"><?php echo $otherStr === '—' ? '—' : attendance_escape($otherStr); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
