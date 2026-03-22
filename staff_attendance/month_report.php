<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/dashboard_data.php';

$pageTitle = 'Month report';
$urls = staff_attendance_dashboard_urls_for_module('dashboard.php');
$staffDeviceSection = 'month';

$tz = new DateTimeZone(STAFF_TIMEZONE);
$defaultMonth = (new DateTimeImmutable('now', $tz))->format('Y-m');

$reportMonth = trim((string) ($_GET['report_month'] ?? ''));
if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
    $reportMonth = $defaultMonth;
}

$nameOk = 'staff_name IS NOT NULL AND TRIM(staff_name) <> \'\'';
$db = attendance_db();
$sql = "SELECT employee_no,
               MAX(staff_name) AS staff_name,
               MAX(department) AS department,
               COUNT(DISTINCT DATE(attendance_time)) AS days_present,
               COUNT(*) AS punch_count
        FROM staff_attendance
        WHERE $nameOk AND DATE_FORMAT(attendance_time, '%Y-%m') = ?
        GROUP BY employee_no
        ORDER BY staff_name ASC, employee_no ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param('s', $reportMonth);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . '/includes/header.php';
$monthBase = $urls['month'];
?>
<div class="row g-4">
    <div class="col-lg-2 col-md-3">
        <?php include __DIR__ . '/partials/staff_device_nav.php'; ?>
    </div>
    <div class="col-lg-10 col-md-9">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 fw-bold">Month report</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end mb-4" action="<?php echo attendance_escape($monthBase); ?>">
                    <div class="col-auto">
                        <label class="form-label small mb-0">Month</label>
                        <input type="month" name="report_month" class="form-control" value="<?php echo attendance_escape($reportMonth); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Show</button>
                    </div>
                </form>

                <p class="text-muted small">Days present = distinct calendar days with at least one punch. Staff with resolved names only.</p>

                <div class="table-responsive shadow-sm bg-white rounded border">
                    <table class="table table-striped table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Employee no.</th>
                            <th>Staff name</th>
                            <th>Department</th>
                            <th class="text-end">Days present</th>
                            <th class="text-end">Punch count</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="5" class="text-center py-4">No attendance for this month.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                                    <td><?php echo attendance_escape((string) ($r['department'] ?? '')); ?></td>
                                    <td class="text-end"><?php echo (int) ($r['days_present'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($r['punch_count'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
