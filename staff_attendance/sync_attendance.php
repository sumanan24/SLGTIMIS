<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hikvision_sync_lib.php';

$pageTitle = 'Hikvision sync';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startRaw = trim((string) ($_POST['sync_start'] ?? ''));
    $endRaw = trim((string) ($_POST['sync_end'] ?? ''));

    if ($startRaw === '') {
        $startRaw = date('Y-m-d 00:00:00');
    }
    if ($endRaw === '') {
        $endRaw = date('Y-m-d 23:59:59');
    }

    $startDt = DateTime::createFromFormat('Y-m-d H:i:s', $startRaw) ?: new DateTime($startRaw);
    $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $endRaw) ?: new DateTime($endRaw);

    $result = attendance_run_hikvision_sync($startDt, $endDt);

    if ($result['ok']) {
        $_SESSION['flash_success'] = sprintf(
            'Done. Inserted: %d, skipped duplicates: %d, skipped invalid: %d.',
            $result['inserted'],
            $result['skipped_dup'],
            $result['skipped_bad']
        );
    } else {
        $_SESSION['flash_error'] = $result['error'] ?? 'Sync failed.';
    }
    header('Location: sync_attendance.php');
    exit;
}

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Sync from Hikvision</h1>
<p class="text-muted small">
    Device: <code><?php echo attendance_escape(HIKVISION_IP); ?></code> —
    POST <code>/ISAPI/AccessControl/AcsEvent?format=json</code> with Digest authentication.
    Duplicates are ignored via <code>INSERT IGNORE</code> and the unique key on (employee_no, attendance_time).
</p>

<form method="post" class="card shadow-sm mb-4" style="max-width: 480px;">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Start (server local time)</label>
            <input type="text" name="sync_start" class="form-control" placeholder="Y-m-d H:i:s"
                   value="<?php echo attendance_escape(date('Y-m-d 00:00:00')); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">End</label>
            <input type="text" name="sync_end" class="form-control" placeholder="Y-m-d H:i:s"
                   value="<?php echo attendance_escape(date('Y-m-d 23:59:59')); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Run sync</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
