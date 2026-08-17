<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$isEdit = !empty($isEdit);
$accMap = [];
foreach ($accessories ?? [] as $a) {
    $accMap[$a['accessory_type'] ?? ''] = $a;
}
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="h4 mb-3"><?php echo $isEdit ? 'Edit Device' : 'Register Device'; ?></h1>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="post" action="<?php echo APP_URL . ($isEdit ? '/devices/update?id=' . (int)($d['id'] ?? 0) : '/devices/store'); ?>" class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Asset ID <span class="text-danger">*</span></label><input name="asset_id" class="form-control" required value="<?php echo $e($d['asset_id'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Asset Tag No.</label><input name="asset_tag_no" class="form-control" value="<?php echo $e($d['asset_tag_no'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Device Type</label><select name="device_type" class="form-select"><?php foreach ($deviceTypes ?? [] as $t): ?><option <?php echo ($d['device_type'] ?? 'Laptop') === $t ? 'selected' : ''; ?>><?php echo $e($t); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach ($statuses ?? [] as $s): ?><option value="<?php echo $e($s); ?>" <?php echo ($d['status'] ?? 'available') === $s ? 'selected' : ''; ?>><?php echo $e(DeviceModel::statusLabel($s)); ?></option><?php endforeach; ?></select></div>
            </div>

            <h6 class="text-muted text-uppercase small">Identification</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Brand</label><input name="brand" class="form-control" value="<?php echo $e($d['brand'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Model</label><input name="model" class="form-control" value="<?php echo $e($d['model'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control" value="<?php echo $e($d['serial_number'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Computer Name</label><input name="computer_name" class="form-control" value="<?php echo $e($d['computer_name'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Product Number</label><input name="product_number" class="form-control" value="<?php echo $e($d['product_number'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Service Tag</label><input name="service_tag" class="form-control" value="<?php echo $e($d['service_tag'] ?? ''); ?>"></div>
            </div>

            <h6 class="text-muted text-uppercase small">Hardware</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><label class="form-label">Processor</label><input name="processor" class="form-control" value="<?php echo $e($d['processor'] ?? ''); ?>"></div>
                <div class="col-md-2"><label class="form-label">RAM</label><input name="ram" class="form-control" value="<?php echo $e($d['ram'] ?? ''); ?>"></div>
                <div class="col-md-2"><label class="form-label">Storage Type</label><input name="storage_type" class="form-control" value="<?php echo $e($d['storage_type'] ?? ''); ?>"></div>
                <div class="col-md-2"><label class="form-label">Storage Capacity</label><input name="storage_capacity" class="form-control" value="<?php echo $e($d['storage_capacity'] ?? ''); ?>"></div>
                <div class="col-md-2"><label class="form-label">Display Size</label><input name="display_size" class="form-control" value="<?php echo $e($d['display_size'] ?? ''); ?>"></div>
                <div class="col-md-4"><label class="form-label">Operating System</label><input name="operating_system" class="form-control" value="<?php echo $e($d['operating_system'] ?? ''); ?>"></div>
            </div>

            <h6 class="text-muted text-uppercase small">Purchase</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-control" value="<?php echo $e($d['purchase_date'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Supplier</label><input name="supplier" class="form-control" value="<?php echo $e($d['supplier'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Purchase Cost</label><input type="number" step="0.01" name="purchase_cost" class="form-control" value="<?php echo $e($d['purchase_cost'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" class="form-control" value="<?php echo $e($d['warranty_expiry'] ?? ''); ?>"></div>
            </div>

            <h6 class="text-muted text-uppercase small">Network & Components</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">LAN MAC</label><input name="lan_mac_address" class="form-control" value="<?php echo $e($d['lan_mac_address'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Wi-Fi MAC</label><input name="wifi_mac_address" class="form-control" value="<?php echo $e($d['wifi_mac_address'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Charger Serial No.</label><input name="charger_serial_no" class="form-control" value="<?php echo $e($d['charger_serial_no'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">Battery Serial No.</label><input name="battery_serial_no" class="form-control" value="<?php echo $e($d['battery_serial_no'] ?? ''); ?>"></div>
            </div>

            <h6 class="text-muted text-uppercase small">Configuration</h6>
            <div class="row g-2 mb-4">
                <?php foreach (['windows_activated' => 'Windows Activated', 'ms_office_activated' => 'Microsoft Office Activated', 'bitlocker_enabled' => 'BitLocker Enabled', 'antivirus_installed' => 'Antivirus Installed'] as $k => $lbl): ?>
                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="<?php echo $k; ?>" value="1" id="<?php echo $k; ?>" <?php echo !empty($d[$k]) ? 'checked' : ''; ?>><label class="form-check-label" for="<?php echo $k; ?>"><?php echo $e($lbl); ?></label></div></div>
                <?php endforeach; ?>
            </div>

            <?php if ($isEdit): ?>
            <h6 class="text-muted text-uppercase small">Physical Condition</h6>
            <div class="row g-3 mb-4">
                <?php foreach (['cond_lcd_screen' => 'LCD Screen', 'cond_keyboard' => 'Keyboard', 'cond_touchpad' => 'Touchpad', 'cond_battery' => 'Battery', 'cond_ports' => 'Ports', 'cond_charger' => 'Charger', 'cond_outer_body' => 'Outer Body'] as $field => $lbl): ?>
                <div class="col-md-3"><label class="form-label"><?php echo $e($lbl); ?></label><select name="<?php echo $field; ?>" class="form-select"><option value="">—</option><?php foreach ($conditionValues ?? [] as $cv): ?><option value="<?php echo $e($cv); ?>" <?php echo ($d[$field] ?? '') === $cv ? 'selected' : ''; ?>><?php echo $e($cv); ?></option><?php endforeach; ?></select></div>
                <?php endforeach; ?>
                <div class="col-12"><label class="form-label">Condition Remarks</label><textarea name="condition_remarks" class="form-control" rows="2"><?php echo $e($d['condition_remarks'] ?? ''); ?></textarea></div>
            </div>
            <?php endif; ?>

            <h6 class="text-muted text-uppercase small">Accessories</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Accessory</th><th>Serial No.</th><th>Status</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($accessoryTypes ?? [] as $i => $atype):
                        $row = $accMap[$atype] ?? [];
                    ?>
                    <tr>
                        <td><?php echo $e($atype); ?><input type="hidden" name="accessories[<?php echo $i; ?>][accessory_type]" value="<?php echo $e($atype); ?>"></td>
                        <td><input name="accessories[<?php echo $i; ?>][serial_number]" class="form-control form-control-sm" value="<?php echo $e($row['serial_number'] ?? ''); ?>"></td>
                        <td><select name="accessories[<?php echo $i; ?>][status]" class="form-select form-select-sm"><option>Good</option><option>Fair</option><option>Damaged</option><option>Missing</option></select></td>
                        <td><input name="accessories[<?php echo $i; ?>][remarks]" class="form-control form-control-sm" value="<?php echo $e($row['remarks'] ?? ''); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"><?php echo $e($d['remarks'] ?? ''); ?></textarea></div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Device</button>
                <a href="<?php echo APP_URL; ?>/devices/list" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
