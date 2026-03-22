<?php
declare(strict_types=1);

/**
 * Hikvision DS-K1T320MFWX — full AcsEvent sync (last 30 days by default).
 * Uses attendance_run_hikvision_sync() in includes/hikvision_sync_lib.php
 */

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hikvision_sync_lib.php';

$pageTitle = 'Hikvision sync';

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
        $startRaw = date('Y-m-d 00:00:00', strtotime('-30 days'));
    }
    if ($endRaw === '') {
        $endRaw = date('Y-m-d 23:59:59');
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
            'Sync finished. Received %d events, inserted %d new rows (duplicates skipped: %d, invalid: %d).',
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

$defaultStart = date('Y-m-d 00:00:00', strtotime('-30 days'));
$defaultEnd = date('Y-m-d 23:59:59');

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Sync from Hikvision</h1>
<div class="alert alert-warning small mb-3" role="alert">
    <strong>Where PHP runs matters.</strong> Sync is executed by the <strong>web server process</strong>, not your browser.
    If the site is hosted on the public internet, PHP cannot reach a private IP like <code><?php echo attendance_escape(HIKVISION_IP); ?></code>.
    Install/run this app on <strong>WAMP (or similar) on the same LAN as the terminal</strong>, extend VPN to the app server, or use an on‑prem sync job.
    Opening this page in a browser on the office PC does <em>not</em> help unless PHP runs there too.
</div>
<p class="mb-2">
    <a class="btn btn-sm btn-outline-secondary" href="sync_attendance.php?test=1">Test connection to device</a>
    <span class="text-muted small ms-2">~3s; checks from this server only.</span>
</p>
<?php if ($reachTest !== null): ?>
    <div class="alert <?php echo $reachTest['ok'] ? 'alert-success' : 'alert-danger'; ?> small py-2 mb-3" role="alert">
        <?php echo attendance_escape($reachTest['message']); ?>
    </div>
<?php endif; ?>
<p class="text-muted small mb-3">
    Device: <code><?php echo attendance_escape(HIKVISION_IP); ?></code> (DS-K1T320MFWX) —
    <code>POST /ISAPI/AccessControl/AcsEvent?format=json</code> with Digest auth, JSON body,
    <strong>major = 5</strong> (access control), <strong>maxResults = 100</strong> per page,
    paginated until no more records. Inserts use <code>INSERT IGNORE</code> (unique employee_no + attendance_time).
    Times stored in <strong>Asia/Colombo</strong>.
</p>

<?php if ($showResult !== null && !empty($showResult['debug'])): ?>
<div class="card shadow-sm mb-4 border-info">
    <div class="card-header bg-info text-white py-2">Debug log (last run)</div>
    <div class="card-body p-0">
        <pre class="small mb-0 p-3 bg-light" style="max-height: 420px; overflow: auto; white-space: pre-wrap;"><?php echo attendance_escape(implode("\n", $showResult['debug'])); ?></pre>
    </div>
    <?php if (!empty($showResult['ok'])): ?>
        <div class="card-footer small text-muted">
            Total received: <strong><?php echo (int) ($showResult['total_received'] ?? 0); ?></strong> —
            Inserted: <strong><?php echo (int) ($showResult['inserted'] ?? 0); ?></strong> —
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
                   value="<?php echo attendance_escape($defaultStart); ?>"
                   placeholder="Y-m-d H:i:s">
            <div class="form-text">Default: 30 days ago at 00:00:00</div>
        </div>
        <div class="mb-3">
            <label class="form-label">End (Asia/Colombo)</label>
            <input type="text" name="sync_end" class="form-control" required
                   value="<?php echo attendance_escape($defaultEnd); ?>"
                   placeholder="Y-m-d H:i:s">
            <div class="form-text">Default: today 23:59:59</div>
        </div>
        <button type="submit" class="btn btn-primary">Run full sync</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
