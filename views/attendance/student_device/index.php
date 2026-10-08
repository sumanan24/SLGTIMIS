<?php
declare(strict_types=1);
/** Dashboard shell for student fingerprint attendance */
$studentDeviceSection = 'dashboard';
$pageTitle = 'Home';
$pageSubtitle = 'Machine status, today’s attendance, and sync.';
ob_start();
?>
<div class="sd-header-actions">
    <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($urls['events'], ENT_QUOTES, 'UTF-8'); ?>">Attendance</a>
    <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($urls['sao'], ENT_QUOTES, 'UTF-8'); ?>">Summary</a>
</div>
<?php
$headerActions = ob_get_clean();
$recentRows = $rows ?? [];
$contentPartial = __DIR__ . '/partials/dashboard_body.php';
include __DIR__ . '/partials/shell.php';
