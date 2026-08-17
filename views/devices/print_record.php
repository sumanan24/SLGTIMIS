<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$aa = $activeAssignment ?? null;
?>
<style>
@media print { .no-print { display: none !important; } }
.asset-print { font-size: 12px; max-width: 900px; margin: 0 auto; }
.asset-print h1 { font-size: 18px; text-align: center; }
.asset-print h2 { font-size: 13px; margin-top: 1rem; border-bottom: 1px solid #000; }
.asset-print table { width: 100%; border-collapse: collapse; margin-bottom: .5rem; }
.asset-print td, .asset-print th { border: 1px solid #333; padding: 4px 6px; vertical-align: top; }
.sig-row td { height: 48px; }
</style>
<div class="asset-print p-3">
    <div class="no-print mb-3 text-end"><button onclick="window.print()" class="btn btn-sm btn-primary">Print</button></div>
    <h1>SRI LANKA-GERMAN TRAINING INSTITUTE<br>SLGTI Laptop Asset Management Record</h1>

    <h2>Device Information</h2>
    <table>
        <tr><th>Asset ID</th><td><?php echo $e($d['asset_id'] ?? ''); ?></td><th>Asset Tag</th><td><?php echo $e($d['asset_tag_no'] ?? ''); ?></td></tr>
        <tr><th>Brand / Model</th><td colspan="3"><?php echo $e(trim(($d['brand'] ?? '') . ' ' . ($d['model'] ?? ''))); ?></td></tr>
        <tr><th>Serial No.</th><td><?php echo $e($d['serial_number'] ?? ''); ?></td><th>Service Tag</th><td><?php echo $e($d['service_tag'] ?? ''); ?></td></tr>
        <tr><th>Processor</th><td><?php echo $e($d['processor'] ?? ''); ?></td><th>RAM</th><td><?php echo $e($d['ram'] ?? ''); ?></td></tr>
        <tr><th>Storage</th><td><?php echo $e(trim(($d['storage_type'] ?? '') . ' ' . ($d['storage_capacity'] ?? ''))); ?></td><th>OS</th><td><?php echo $e($d['operating_system'] ?? ''); ?></td></tr>
        <tr><th>Purchase Date</th><td><?php echo $e($d['purchase_date'] ?? ''); ?></td><th>Warranty Expiry</th><td><?php echo $e($d['warranty_expiry'] ?? ''); ?></td></tr>
    </table>

    <h2>Issued To (Employee)</h2>
    <table>
        <tr><th>Name</th><td><?php echo $e($aa['staff_name'] ?? $d['assigned_staff_name'] ?? ''); ?></td><th>Employee ID</th><td><?php echo $e($aa['employee_id'] ?? $d['assigned_employee_id'] ?? ''); ?></td></tr>
        <tr><th>Department</th><td><?php echo $e($aa['department_name'] ?? $d['assigned_department_name'] ?? ''); ?></td><th>Issue Date</th><td><?php echo $e($aa['issue_date'] ?? ''); ?></td></tr>
    </table>

    <?php if (!empty($accessories)): ?>
    <h2>Accessories</h2>
    <table><tr><th>Type</th><th>Serial</th><th>Status</th></tr>
    <?php foreach ($accessories as $a): ?><tr><td><?php echo $e($a['accessory_type'] ?? ''); ?></td><td><?php echo $e($a['serial_number'] ?? ''); ?></td><td><?php echo $e($a['status'] ?? ''); ?></td></tr><?php endforeach; ?>
    </table>
    <?php endif; ?>

    <?php if (!empty($fullDetail)): ?>
    <h2>Configuration</h2>
    <p>Windows: <?php echo !empty($d['windows_activated']) ? 'Yes' : 'No'; ?> · MS Office: <?php echo !empty($d['ms_office_activated']) ? 'Yes' : 'No'; ?> · BitLocker: <?php echo !empty($d['bitlocker_enabled']) ? 'Yes' : 'No'; ?> · Antivirus: <?php echo !empty($d['antivirus_installed']) ? 'Yes' : 'No'; ?></p>
    <h2>Physical Condition</h2>
    <p>LCD: <?php echo $e($d['cond_lcd_screen'] ?? '—'); ?> · Keyboard: <?php echo $e($d['cond_keyboard'] ?? '—'); ?> · Body: <?php echo $e($d['cond_outer_body'] ?? '—'); ?></p>
    <?php endif; ?>

    <h2>Declaration</h2>
    <p>I confirm that I have received the above device and accessories in the stated condition and agree to use them responsibly for official SLGTI purposes.</p>

    <table class="sig-row">
        <tr><th>Issued To (Signature / Date)</th><th>Issued By — ICT Officer (Signature / Date)</th><th>Approved By — HOD / Authorized Officer</th></tr>
        <tr><td></td><td></td><td></td></tr>
    </table>

    <div class="text-end mt-3"><?php if (!empty($qrDataUri)): ?><img src="<?php echo $qrDataUri; ?>" alt="QR" style="width:100px;"><?php endif; ?></div>
</div>
