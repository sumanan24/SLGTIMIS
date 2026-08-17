<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$id = (int)($d['id'] ?? 0);
$full = !empty($fullDetail);
$aa = $activeAssignment ?? null;
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><?php echo $e($d['asset_id'] ?? 'Device'); ?></h1>
            <p class="text-muted small mb-0"><?php echo $e(trim(($d['brand'] ?? '') . ' ' . ($d['model'] ?? ''))); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <?php if (!empty($canPrintQr)): ?>
            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#deviceQrPrintModal">
                <i class="fas fa-print me-1"></i> Print QR Labels
            </button>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>/devices/print?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-print me-1"></i> Asset Record</a>
            <?php if (!empty($canManage)): ?>
            <a href="<?php echo APP_URL; ?>/devices/edit?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">Edit</a>
            <a href="<?php echo APP_URL; ?>/devices/assign?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">Assign</a>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>/devices/history?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-info">History</a>
        </div>
    </div>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($canPrintQr)): ?>
    <div id="device-page-zebra-status" class="zebra-bp-status-card mb-3" aria-live="polite">
        <div class="status-head"><span class="zebra-bp-spinner"></span><span class="status-dot checking"></span><span>Connecting to Zebra Browser Print…</span></div>
        <p class="status-meta mb-2">Checking your existing Windows ZD230 printer…</p>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="device-page-zebra-retry"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
    </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="device-detail-section">
                <h3>Device Information</h3>
                <div class="row g-2 small">
                    <?php
                    $fields = [
                        'Asset Tag' => $d['asset_tag_no'] ?? '—', 'Type' => $d['device_type'] ?? '—',
                        'Serial' => $d['serial_number'] ?? '—', 'Product No.' => $d['product_number'] ?? '—',
                        'Service Tag' => $d['service_tag'] ?? '—', 'Computer Name' => $d['computer_name'] ?? '—',
                        'Processor' => $d['processor'] ?? '—', 'RAM' => $d['ram'] ?? '—',
                        'Storage' => trim(($d['storage_type'] ?? '') . ' ' . ($d['storage_capacity'] ?? '')) ?: '—',
                        'Display' => $d['display_size'] ?? '—', 'OS' => $d['operating_system'] ?? '—',
                        'Purchase Date' => $d['purchase_date'] ?? '—', 'Supplier' => $d['supplier'] ?? '—',
                        'Warranty Expiry' => $d['warranty_expiry'] ?? '—', 'LAN MAC' => $d['lan_mac_address'] ?? '—',
                        'Wi-Fi MAC' => $d['wifi_mac_address'] ?? '—',
                    ];
                    if ($full) {
                        $fields['Purchase Cost'] = $d['purchase_cost'] ?? '—';
                        $fields['Charger S/N'] = $d['charger_serial_no'] ?? '—';
                        $fields['Battery S/N'] = $d['battery_serial_no'] ?? '—';
                    }
                    foreach ($fields as $lbl => $val):
                    ?>
                    <div class="col-md-4"><span class="text-muted"><?php echo $e($lbl); ?>:</span> <?php echo $e((string)$val); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="device-detail-section">
                <h3>Assigned User</h3>
                <?php if ($aa): ?>
                <p class="mb-1"><strong><?php echo $e($aa['staff_name'] ?? ''); ?></strong> (<?php echo $e($aa['employee_id'] ?? ''); ?>)</p>
                <p class="small text-muted mb-1"><?php echo $e($aa['department_name'] ?? ''); ?> · Issue: <?php echo $e($aa['issue_date'] ?? '—'); ?><?php if (!empty($aa['return_date'])): ?> · Return: <?php echo $e($aa['return_date']); ?><?php endif; ?></p>
                <?php elseif (!empty($d['assigned_staff_name'])): ?>
                <p class="mb-0"><?php echo $e($d['assigned_staff_name']); ?> · <?php echo $e($d['assigned_department_name'] ?? ''); ?></p>
                <?php else: ?><p class="text-muted mb-0">Not assigned</p><?php endif; ?>

                <?php if (!empty($canManage) && ($d['status'] ?? '') === DeviceModel::STATUS_ASSIGNED): ?>
                <form method="post" action="<?php echo APP_URL; ?>/devices/return" class="mt-3 border-top pt-3" onsubmit="return confirm('Return this device?');">
                    <input type="hidden" name="device_id" value="<?php echo $id; ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label small">Return date</label><input type="date" name="return_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-md-3"><label class="form-label small">New status</label><select name="new_status" class="form-select form-select-sm"><option value="available">Available</option><option value="returned">Returned</option><option value="under_maintenance">Under Maintenance</option></select></div>
                        <div class="col-md-4"><label class="form-label small">Remarks</label><input name="remarks" class="form-control form-control-sm"></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-warning w-100">Return</button></div>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($full): ?>
            <div class="device-detail-section">
                <h3>Configuration</h3>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach (['windows_activated' => 'Windows', 'ms_office_activated' => 'MS Office', 'bitlocker_enabled' => 'BitLocker', 'antivirus_installed' => 'Antivirus'] as $k => $lbl): ?>
                    <span class="badge <?php echo !empty($d[$k]) ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $e($lbl); ?>: <?php echo !empty($d[$k]) ? 'Yes' : 'No'; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="device-detail-section">
                <h3>Physical Condition</h3>
                <div class="row g-2 small">
                    <?php foreach (['cond_lcd_screen' => 'LCD', 'cond_keyboard' => 'Keyboard', 'cond_touchpad' => 'Touchpad', 'cond_battery' => 'Battery', 'cond_ports' => 'Ports', 'cond_charger' => 'Charger', 'cond_outer_body' => 'Body'] as $k => $lbl): ?>
                    <div class="col-md-3"><?php echo $e($lbl); ?>: <strong><?php echo $e($d[$k] ?? '—'); ?></strong></div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($d['condition_remarks'])): ?><p class="small mt-2 mb-0"><?php echo $e($d['condition_remarks']); ?></p><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($accessories)): ?>
            <div class="device-detail-section">
                <h3>Accessories</h3>
                <table class="table table-sm mb-0"><thead><tr><th>Type</th><th>Serial</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($accessories as $a): ?><tr><td><?php echo $e($a['accessory_type'] ?? ''); ?></td><td><?php echo $e($a['serial_number'] ?? '—'); ?></td><td><?php echo $e($a['status'] ?? ''); ?></td></tr><?php endforeach; ?>
                </tbody></table>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="device-detail-section">
                <h3>Status</h3>
                <p><span class="badge bg-<?php echo DeviceAssetHelper::statusBadgeClass((string)($d['status'] ?? '')); ?>"><?php echo $e(DeviceModel::statusLabel((string)($d['status'] ?? ''))); ?></span></p>
            </div>
            <div class="device-detail-section device-qr-box">
                <h3>QR Code</h3>
                <?php if (!empty($qrDataUri)): ?><img src="<?php echo $qrDataUri; ?>" alt="QR Code"><?php endif; ?>
                <p class="small text-muted mt-2 mb-0">Scan opens authorized device page (login required).</p>
                <?php if (!empty($canManage)): ?>
                <form method="post" action="<?php echo APP_URL; ?>/devices/regenerate-qr" class="mt-2" onsubmit="return confirm('Regenerate QR? Old labels will stop working.');">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button class="btn btn-sm btn-outline-danger">Regenerate QR</button>
                </form>
                <?php endif; ?>
            </div>
            <?php if (!empty($canManage)): ?>
            <form method="post" action="<?php echo APP_URL; ?>/devices/delete" onsubmit="return confirm('Delete this device?');">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button class="btn btn-sm btn-outline-danger w-100">Delete Device</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if (!empty($canPrintQr)): ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/device-qr-printer.css', ENT_QUOTES, 'UTF-8'); ?>">
<?php require BASE_PATH . '/views/devices/partials/qr_print_modal.php'; ?>
<script src="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/js/BrowserPrint-3.0.216.min.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/js/zebra-browser-print-client.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/js/device-qr-sticker.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
