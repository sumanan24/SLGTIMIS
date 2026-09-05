<?php
declare(strict_types=1);
/** Dashboard shell for student fingerprint attendance */
$studentDeviceSection = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Machine status, sync controls, and recent In / Out / Others';
ob_start();
?>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($urls['export_excel'], ENT_QUOTES, 'UTF-8'); ?>">Excel</a>
    <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($urls['export_csv'], ENT_QUOTES, 'UTF-8'); ?>">CSV</a>
</div>
<?php
$headerActions = ob_get_clean();
$recentRows = $rows ?? [];
$contentPartial = __DIR__ . '/partials/dashboard_body.php';
include __DIR__ . '/partials/shell.php';
