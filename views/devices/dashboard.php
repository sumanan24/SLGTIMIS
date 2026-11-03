<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$st = $stats ?? [];
$cards = [
    ['Total Assets', (int) ($st['total'] ?? 0), 'fa-boxes-stacked', '#001f3f'],
    ['Available Assets', (int) ($st['available'] ?? 0), 'fa-circle-check', '#0f766e'],
    ['Assigned Assets', (int) ($st['assigned'] ?? 0), 'fa-user-check', '#1d4ed8'],
    ['Damaged Assets', (int) ($st['damaged'] ?? 0), 'fa-triangle-exclamation', '#b45309'],
    ['Under Repair', (int) ($st['under_repair'] ?? 0), 'fa-screwdriver-wrench', '#7c3aed'],
    ['Pending Transfers', (int) ($st['pending_transfers'] ?? 0), 'fa-right-left', '#be123c'],
];
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">Asset Dashboard</h1>
    <p class="asset-help">See how many assets you have, who is using them, and what needs attention.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <?php foreach ($cards as [$label, $val, $icon, $color]): ?>
        <div class="col-6 col-lg-4">
            <div class="asset-summary" style="border-top-color: <?php echo $color; ?>">
                <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>"></i>
                <div>
                    <div class="asset-summary-value"><?php echo number_format($val); ?></div>
                    <div class="asset-summary-label"><?php echo $e($label); ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">Recent activity</h2>
            <?php if (empty($activity)): ?>
            <p class="text-muted mb-0">No activity yet. Add an asset to get started.</p>
            <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($activity as $row): ?>
                <li class="list-group-item px-0">
                    <strong><?php echo $e(str_replace('_', ' ', (string) ($row['action'] ?? 'update'))); ?></strong>
                    <?php if (!empty($row['asset_id'])): ?> · <?php echo $e($row['asset_id']); ?><?php endif; ?>
                    <span class="text-muted d-block small"><?php echo $e($row['created_at'] ?? ''); ?><?php if (!empty($row['user_name'])): ?> · <?php echo $e($row['user_name']); ?><?php endif; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
