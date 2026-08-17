<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$full = !empty($fullDetail);
$aa = $activeAssignment ?? null;
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <div class="text-center mb-4">
        <h1 class="h4 mb-1">SLGTI DEVICE INFORMATION</h1>
        <p class="text-muted small mb-0">Sri Lanka-German Training Institute</p>
    </div>

    <div class="row g-3 justify-content-center">
        <div class="col-lg-8">
            <div class="device-detail-section">
                <h3>Device</h3>
                <div class="row g-2 small">
                    <?php foreach (['asset_id' => 'Asset ID', 'asset_tag_no' => 'Asset Tag', 'device_type' => 'Device Type', 'brand' => 'Brand', 'model' => 'Model', 'serial_number' => 'Serial Number', 'product_number' => 'Product Number', 'service_tag' => 'Service Tag', 'computer_name' => 'Computer Name'] as $k => $lbl): ?>
                    <div class="col-md-4"><span class="text-muted"><?php echo $e($lbl); ?>:</span> <?php echo $e($d[$k] ?? '—'); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="device-detail-section">
                <h3>Hardware & Purchase</h3>
                <div class="row g-2 small">
                    <div class="col-md-4">Processor: <?php echo $e($d['processor'] ?? '—'); ?></div>
                    <div class="col-md-4">RAM: <?php echo $e($d['ram'] ?? '—'); ?></div>
                    <div class="col-md-4">Storage: <?php echo $e(trim(($d['storage_type'] ?? '') . ' ' . ($d['storage_capacity'] ?? '')) ?: '—'); ?></div>
                    <div class="col-md-4">Display: <?php echo $e($d['display_size'] ?? '—'); ?></div>
                    <div class="col-md-4">OS: <?php echo $e($d['operating_system'] ?? '—'); ?></div>
                    <div class="col-md-4">Purchase: <?php echo $e($d['purchase_date'] ?? '—'); ?></div>
                    <div class="col-md-4">Supplier: <?php echo $e($d['supplier'] ?? '—'); ?></div>
                    <div class="col-md-4">Warranty: <?php echo $e($d['warranty_expiry'] ?? '—'); ?></div>
                    <?php if ($full): ?><div class="col-md-4">Cost: <?php echo $e($d['purchase_cost'] ?? '—'); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="device-detail-section">
                <h3>Assigned User</h3>
                <?php if ($aa || !empty($d['assigned_staff_name'])): ?>
                <p class="mb-1"><strong><?php echo $e($aa['staff_name'] ?? $d['assigned_staff_name'] ?? ''); ?></strong></p>
                <p class="small mb-0">ID: <?php echo $e($aa['employee_id'] ?? $d['assigned_employee_id'] ?? '—'); ?> · <?php echo $e($aa['department_name'] ?? $d['assigned_department_name'] ?? '—'); ?></p>
                <p class="small mb-0">Issue: <?php echo $e($aa['issue_date'] ?? '—'); ?><?php if (!empty($aa['return_date'])): ?> · Return: <?php echo $e($aa['return_date']); ?><?php endif; ?></p>
                <?php else: ?><p class="text-muted mb-0">Not assigned</p><?php endif; ?>
            </div>
            <div class="device-detail-section">
                <h3>Status</h3>
                <span class="badge bg-<?php echo DeviceAssetHelper::statusBadgeClass((string)($d['status'] ?? '')); ?>"><?php echo $e(DeviceModel::statusLabel((string)($d['status'] ?? ''))); ?></span>
                <?php if ($full && !empty($d['cond_lcd_screen'])): ?>
                <p class="small mt-2 mb-0">Condition (LCD): <?php echo $e($d['cond_lcd_screen']); ?> · Keyboard: <?php echo $e($d['cond_keyboard'] ?? '—'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="device-qr-box">
                <?php if (!empty($qrDataUri)): ?><img src="<?php echo $qrDataUri; ?>" alt="QR"><?php endif; ?>
            </div>
            <div class="mt-3 text-center">
                <a href="<?php echo APP_URL; ?>/devices/view?id=<?php echo (int)($d['id'] ?? 0); ?>" class="btn btn-sm btn-outline-primary">Full record</a>
                <a href="<?php echo APP_URL; ?>/devices/list" class="btn btn-sm btn-outline-secondary">All devices</a>
            </div>
        </div>
    </div>
</div>
