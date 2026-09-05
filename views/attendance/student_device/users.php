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
?>
<div class="container-fluid px-3 px-sm-4 py-3 student-device-page">
    <?php include __DIR__ . '/partials/styles.php'; ?>
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-md-3 col-lg-2">
            <?php include __DIR__ . '/partials/nav.php'; ?>
        </div>
        <div class="col-12 col-md-9 col-lg-10 min-w-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="mb-1 fw-bold"><?php echo $e($pageTitle); ?></h4>
                    <div class="text-muted small"><?php echo $e($pageSubtitle); ?></div>
                </div>
                <span class="badge text-bg-secondary"><?php echo count($machineUsers); ?> users</span>
            </div>

            <div class="card sd-card">
                <div class="table-responsive" style="max-height: 70vh;">
                    <table class="table table-hover sd-events-table mb-0">
                        <thead class="sticky-top bg-white">
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
        </div>
    </div>
</div>
