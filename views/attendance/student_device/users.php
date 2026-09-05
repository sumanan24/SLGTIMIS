<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array $machineUsers */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$studentDeviceSection = 'users';
$pageTitle = 'Machine users';
$pageSubtitle = 'Employee No and name from the fingerprint terminal (UserInfo)';

ob_start();
?>
<span class="sd-summary-chip"><?php echo count($machineUsers ?? []); ?> users</span>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<div class="card sd-card sd-events-panel">
    <div class="table-responsive sd-table-wrap" style="max-height: 70vh;">
        <table class="table table-hover sd-events-table mb-0">
            <thead class="sticky-top">
            <tr>
                <th class="col-emp">Employee No</th>
                <th class="col-name">Name</th>
                <th>Type</th>
                <th class="col-machine">Machine</th>
                <th>Last synced</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($machineUsers)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No machine users yet. Run a sync from Dashboard.</td></tr>
            <?php else: ?>
                <?php foreach ($machineUsers as $mu): ?>
                    <tr>
                        <td class="col-emp"><?php echo $e($mu['employee_no'] ?? ''); ?></td>
                        <td class="col-name"><?php echo $e($mu['name'] ?? ''); ?></td>
                        <td><span class="badge text-bg-light border"><?php echo $e($mu['user_type'] ?? ''); ?></span></td>
                        <td class="col-machine"><?php echo $e($mu['machine_id'] ?? ''); ?></td>
                        <td class="small text-muted"><?php echo $e($mu['synced_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
