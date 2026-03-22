<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$pageTitle = 'Daily report';

$reportDate = trim((string) ($_GET['report_date'] ?? ''));
if ($reportDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    $reportDate = date('Y-m-d');
}

$db = attendance_db();
$sql = 'SELECT employee_no, MAX(staff_name) AS staff_name,
        MIN(attendance_time) AS check_in,
        MAX(attendance_time) AS check_out
        FROM staff_attendance
        WHERE DATE(attendance_time) = ?
        GROUP BY employee_no
        ORDER BY employee_no';

$stmt = $db->prepare($sql);
$stmt->bind_param('s', $reportDate);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Daily report</h1>

<form method="get" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label small mb-0">Date</label>
        <input type="date" name="report_date" class="form-control" value="<?php echo attendance_escape($reportDate); ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Show</button>
    </div>
</form>

<p class="text-muted small">First punch as check-in, last punch as check-out (same calendar day).</p>

<div class="table-responsive shadow-sm bg-white rounded">
    <table class="table table-striped table-sm mb-0">
        <thead class="table-light">
        <tr>
            <th>Employee no.</th>
            <th>Staff name</th>
            <th>Check-in (MIN)</th>
            <th>Check-out (MAX)</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="4" class="text-center py-4">No attendance for this date.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                    <td><?php echo attendance_escape((string) $r['check_in']); ?></td>
                    <td><?php echo attendance_escape((string) $r['check_out']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
