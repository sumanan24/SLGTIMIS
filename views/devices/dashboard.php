<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$st = $stats ?? [];
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Device Management Dashboard</h1>
            <p class="text-muted small mb-0">SLGTI ICT laptops and computer assets</p>
        </div>
    </div>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>

    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['Total Devices', (int)($st['total'] ?? 0), 'primary'],
            ['Available', (int)($st['available'] ?? 0), 'success'],
            ['Assigned', (int)($st['assigned'] ?? 0), 'info'],
            ['Under Maintenance', (int)($st['under_maintenance'] ?? 0), 'warning'],
            ['Damaged', (int)($st['damaged'] ?? 0), 'danger'],
            ['Retired', (int)($st['retired'] ?? 0), 'secondary'],
            ['Warranty ≤30 days', (int)($st['warranty_expiring_30'] ?? 0), 'warning'],
            ['Warranty Expired', (int)($st['warranty_expired'] ?? 0), 'danger'],
        ];
        foreach ($cards as [$label, $val, $cls]):
        ?>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="device-stat-card">
                <div class="stat-value text-<?php echo $cls; ?>"><?php echo $val; ?></div>
                <div class="stat-label"><?php echo $e($label); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="device-detail-section">
                <h3>By Status</h3>
                <?php foreach ($chartStatus ?? [] as $row): ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span><?php echo $e(DeviceModel::statusLabel((string)($row['status'] ?? ''))); ?></span>
                    <strong><?php echo (int)($row['c'] ?? 0); ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="device-detail-section">
                <h3>By Device Type</h3>
                <?php foreach ($chartType ?? [] as $row): ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span><?php echo $e($row['device_type'] ?? ''); ?></span>
                    <strong><?php echo (int)($row['c'] ?? 0); ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="device-detail-section">
                <h3>Assigned by Department</h3>
                <?php foreach ($chartDept ?? [] as $row): ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span><?php echo $e($row['department_name'] ?? 'Unassigned'); ?></span>
                    <strong><?php echo (int)($row['c'] ?? 0); ?></strong>
                </div>
                <?php endforeach; ?>
                <?php if (empty($chartDept)): ?><p class="text-muted small mb-0">No assigned devices yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
