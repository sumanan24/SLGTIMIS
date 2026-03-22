<?php
declare(strict_types=1);
/** @var array{device: string, list: string, daily: string, month: string, sync: string} $urls */
/** @var string $defaultStartStr */
/** @var string $defaultEndStr */
/** @var array{ok: bool, message: string}|null $reachTest */
/** @var array<string, mixed>|null $showResult */
$syncBase = $urls['sync'];
?>
<div class="container-fluid px-4 py-3">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars((string) $_SESSION['flash_success'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-2 col-md-3">
            <?php include BASE_PATH . '/staff_attendance/partials/staff_device_nav.php'; ?>
        </div>
        <div class="col-lg-10 col-md-9">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-sync me-2"></i>Sync from Hikvision</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning small mb-3" role="alert">
                        Sync runs on the <strong>web server</strong> (where PHP runs), not in your browser. A private address like
                        <code><?php echo htmlspecialchars(defined('HIKVISION_IP') ? HIKVISION_IP : '', ENT_QUOTES, 'UTF-8'); ?></code> is only reachable if that server is on the same LAN as the device or on VPN.
                    </div>
                    <p class="mb-2 d-flex flex-wrap align-items-center gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($syncBase . '?test=1', ENT_QUOTES, 'UTF-8'); ?>">Test connection</a>
                        <span class="text-muted small">Quick check from this server (~3s).</span>
                    </p>
                    <?php if ($reachTest !== null): ?>
                        <div class="alert <?php echo $reachTest['ok'] ? 'alert-success' : 'alert-danger'; ?> small py-2 mb-3" role="alert">
                            <?php echo htmlspecialchars($reachTest['message'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <details class="small text-muted mb-3">
                        <summary class="text-secondary" style="cursor: pointer;">Technical details</summary>
                        <p class="mb-0 mt-2">
                            Device <code><?php echo htmlspecialchars(defined('HIKVISION_IP') ? HIKVISION_IP : '', ENT_QUOTES, 'UTF-8'); ?></code>:
                            <code>POST …/ISAPI/AccessControl/AcsEvent?format=json</code>, Digest auth,
                            minors: <code><?php echo htmlspecialchars(defined('HIKVISION_ACS_MINORS') ? (string) HIKVISION_ACS_MINORS : '', ENT_QUOTES, 'UTF-8'); ?></code>.
                            Dashboard auto-sync: <?php echo (defined('STAFF_ATT_DASHBOARD_AUTO_SYNC') && STAFF_ATT_DASHBOARD_AUTO_SYNC) ? 'on' : 'off'; ?>.
                        </p>
                    </details>

                    <?php if ($showResult !== null && !empty($showResult['debug'])): ?>
                    <div class="card shadow-sm mb-4 border-info">
                        <div class="card-header bg-info text-white py-2">Debug log (last run)</div>
                        <div class="card-body p-0">
                            <pre class="small mb-0 p-3 bg-light" style="max-height: 420px; overflow: auto; white-space: pre-wrap;"><?php echo htmlspecialchars(implode("\n", (array) $showResult['debug']), ENT_QUOTES, 'UTF-8'); ?></pre>
                        </div>
                        <?php if (!empty($showResult['ok'])): ?>
                            <div class="card-footer small text-muted">
                                Total received: <strong><?php echo (int) ($showResult['total_received'] ?? 0); ?></strong> —
                                Total inserted: <strong><?php echo (int) ($showResult['inserted'] ?? 0); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <form method="post" class="card border mb-0" style="max-width: 520px;" action="<?php echo htmlspecialchars($syncBase, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Start (Asia/Colombo)</label>
                                <input type="text" name="sync_start" class="form-control" required
                                       value="<?php echo htmlspecialchars($defaultStartStr, ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="Y-m-d H:i:s">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End (Asia/Colombo)</label>
                                <input type="text" name="sync_end" class="form-control" required
                                       value="<?php echo htmlspecialchars($defaultEndStr, ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="Y-m-d H:i:s">
                            </div>
                            <button type="submit" class="btn btn-primary">Run full sync</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
