<?php
declare(strict_types=1);

/**
 * Hikvision DS-K1T320MFWX — AcsEvent sync (staff_attendance table)
 *
 * API: POST /ISAPI/AccessControl/AcsEvent?format=json — Digest auth
 * Request body (see includes/hikvision_sync_lib.php):
 *   AcsEventCond: searchID, searchResultPosition, maxResults (config, e.g. 2000), major (5), minor (config),
 *   startTime, endTime — format YYYY-MM-DDTHH:MM:SS (Asia/Colombo)
 * Pagination: searchResultPosition += count(records returned); loop until zero records.
 * No employee filter — all employeeNoString values from device.
 * DB: INSERT IGNORE chunk size 500; UNIQUE (employee_no, attendance_time).
 *
 * Config: staff_attendance/config.php — HIKVISION_IP, HIKVISION_ACS_MINOR (0 or 75 if device rejects 0).
 */

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hikvision_sync_lib.php';

$pageTitle = 'Hikvision sync';

$tz = new DateTimeZone(STAFF_TIMEZONE);
$now = new DateTimeImmutable('now', $tz);
$defaultEnd = $now->setTime(23, 59, 59);
$defaultStart = $defaultEnd->sub(new DateInterval('P30D'))->setTime(0, 0, 0);

$reachTest = null;
if (isset($_GET['test']) && (string) $_GET['test'] === '1') {
    $reachTest = attendance_hikvision_test_reachability();
}

$showResult = null;
if (!empty($_SESSION['staff_attendance_sync_result'])) {
    $showResult = $_SESSION['staff_attendance_sync_result'];
    unset($_SESSION['staff_attendance_sync_result']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startRaw = trim((string) ($_POST['sync_start'] ?? ''));
    $endRaw = trim((string) ($_POST['sync_end'] ?? ''));

    if ($startRaw === '') {
        $startRaw = $defaultStart->format('Y-m-d H:i:s');
    }
    if ($endRaw === '') {
        $endRaw = $defaultEnd->format('Y-m-d H:i:s');
    }

    $startDt = DateTime::createFromFormat('Y-m-d H:i:s', $startRaw);
    if ($startDt === false) {
        $startDt = new DateTime($startRaw);
    }
    $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $endRaw);
    if ($endDt === false) {
        $endDt = new DateTime($endRaw);
    }

    $result = attendance_run_hikvision_sync($startDt, $endDt);
    $_SESSION['staff_attendance_sync_result'] = $result;

    if ($result['ok']) {
        $tr = (int) ($result['total_received'] ?? 0);
        $ins = (int) ($result['inserted'] ?? 0);
        $_SESSION['flash_success'] = sprintf(
            'Sync finished. Total received: %d — Total inserted: %d (duplicates skipped: %d, invalid: %d).',
            $tr,
            $ins,
            (int) ($result['skipped_dup'] ?? 0),
            (int) ($result['skipped_bad'] ?? 0)
        );
    } else {
        $_SESSION['flash_error'] = $result['error'] ?? 'Sync failed.';
    }

    header('Location: sync_attendance.php');
    exit;
}

$defaultStartStr = $defaultStart->format('Y-m-d H:i:s');
$defaultEndStr = $defaultEnd->format('Y-m-d H:i:s');

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Sync from Hikvision</h1>
<div class="alert alert-warning small mb-3" role="alert">
    Sync runs on the <strong>web server</strong> (where PHP runs), not in your browser. A private address like
    <code><?php echo attendance_escape(HIKVISION_IP); ?></code> is only reachable if that server is on the same LAN as the device, on VPN to it, or you use an on‑prem sync job.
</div>
<p class="mb-2 d-flex flex-wrap align-items-center gap-2">
    <a class="btn btn-sm btn-outline-secondary" href="sync_attendance.php?test=1">Test connection</a>
    <span class="text-muted small">Quick check from this server (~3s).</span>
</p>
<?php if ($reachTest !== null): ?>
    <div class="alert <?php echo $reachTest['ok'] ? 'alert-success' : 'alert-danger'; ?> small py-2 mb-3" role="alert">
        <?php echo attendance_escape($reachTest['message']); ?>
    </div>
<?php endif; ?>
<details class="small text-muted mb-3">
    <summary class="text-secondary" style="cursor: pointer;">Technical details</summary>
    <p class="mb-0 mt-2">
        Device <code><?php echo attendance_escape(HIKVISION_IP); ?></code> (DS-K1T320MFWX):
        <code>POST …/ISAPI/AccessControl/AcsEvent?format=json</code>, Digest auth,
        <strong>major</strong>=<?php echo (int) (defined('HIKVISION_ACS_MAJOR') ? HIKVISION_ACS_MAJOR : 5); ?>,
        <strong>minor</strong>=<?php echo (int) (defined('HIKVISION_ACS_MINOR') ? HIKVISION_ACS_MINOR : 0); ?>
        (<code>HIKVISION_ACS_MINOR</code> — try <strong>75</strong> if the API rejects <strong>0</strong>),
        <strong>maxResults</strong>=<?php echo (int) (defined('HIKVISION_MAX_RESULTS_PER_CHUNK') ? HIKVISION_MAX_RESULTS_PER_CHUNK : 2000); ?>, paginate until no rows.
        <code>INSERT IGNORE</code> (employee_no, attendance_time, device_ip, event_type) in chunks of 500;
        UNIQUE (<code>employee_no</code>, <code>attendance_time</code>). Times: <strong>Asia/Colombo</strong>.
    </p>
</details>

<?php if ($showResult !== null && !empty($showResult['debug'])): ?>
<div class="card shadow-sm mb-4 border-info">
    <div class="card-header bg-info text-white py-2">Debug log (last run)</div>
    <div class="card-body p-0">
        <pre class="small mb-0 p-3 bg-light" style="max-height: 420px; overflow: auto; white-space: pre-wrap;"><?php echo attendance_escape(implode("\n", $showResult['debug'])); ?></pre>
    </div>
    <?php if (!empty($showResult['ok'])): ?>
        <div class="card-footer small text-muted">
            Total received: <strong><?php echo (int) ($showResult['total_received'] ?? 0); ?></strong> —
            Total inserted: <strong><?php echo (int) ($showResult['inserted'] ?? 0); ?></strong> —
            Skipped dup: <?php echo (int) ($showResult['skipped_dup'] ?? 0); ?> —
            Invalid: <?php echo (int) ($showResult['skipped_bad'] ?? 0); ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" class="card shadow-sm mb-4" style="max-width: 520px;">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Start (Asia/Colombo)</label>
            <input type="text" name="sync_start" class="form-control" required
                   value="<?php echo attendance_escape($defaultStartStr); ?>"
                   placeholder="Y-m-d H:i:s">
            <div class="form-text">Default: 30 days ago at 00:00:00</div>
        </div>
        <div class="mb-3">
            <label class="form-label">End (Asia/Colombo)</label>
            <input type="text" name="sync_end" class="form-control" required
                   value="<?php echo attendance_escape($defaultEndStr); ?>"
                   placeholder="Y-m-d H:i:s">
            <div class="form-text">Default: today 23:59:59</div>
        </div>
        <button type="submit" class="btn btn-primary">Run full sync</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
