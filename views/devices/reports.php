<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$report = (string) ($report ?? 'department');
$buttons = [
    'department' => 'My Department Assets',
    'staff_assigned' => 'Staff Assigned Assets',
    'student_assigned' => 'Student Assigned Assets',
    'available' => 'Available Assets',
    'damaged' => 'Damaged Assets',
    'transfers' => 'Transfer History',
    'disposals' => 'Disposal History',
];
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">Reports</h1>
    <p class="asset-help">Choose a report. Then download Excel or PDF.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($buttons as $key => $label): ?>
        <a class="btn btn-lg <?php echo $report === $key ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo APP_URL; ?>/devices/reports?report=<?php echo rawurlencode($key); ?>"><?php echo $e($label); ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (!in_array($report, ['transfers', 'disposals'], true)): ?>
    <div class="d-flex gap-2 mb-3">
        <a class="btn btn-success btn-lg" href="<?php echo APP_URL; ?>/devices/report-file?report=<?php echo rawurlencode($report); ?>&format=excel"><i class="fas fa-file-excel"></i> Excel</a>
        <a class="btn btn-danger btn-lg" href="<?php echo APP_URL; ?>/devices/report-file?report=<?php echo rawurlencode($report); ?>&format=pdf"><i class="fas fa-file-pdf"></i> PDF</a>
    </div>
    <?php if (empty($rows)): ?><div class="asset-empty"><p>No assets in this report.</p></div><?php else: ?>
    <div class="table-responsive card border-0 shadow-sm">
        <table class="table mb-0">
            <thead><tr><th>Asset code</th><th>Asset name</th><th>Location</th><th>Condition</th><th>Assigned person</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo $e($row['asset_id'] ?? ''); ?></td>
                <td><?php echo $e($row['asset_name'] ?: ($row['brand'] ?? '')); ?></td>
                <td><?php echo $e($row['room_location'] ?? ''); ?></td>
                <td><?php echo $e($row['asset_condition'] ?: DeviceModel::statusLabel((string) ($row['status'] ?? ''))); ?></td>
                <td><?php echo $e(($row['assigned_student_name'] ?? '') ?: ($row['assigned_staff_name'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php elseif ($report === 'transfers'): ?>
    <?php if (empty($transfers)): ?><div class="asset-empty"><p>No transfers yet.</p></div><?php else: ?>
    <ul class="list-group"><?php foreach ($transfers as $t): ?><li class="list-group-item"><?php echo $e(($t['asset_id'] ?? '') . ' · ' . ($t['from_department_name'] ?? '') . ' → ' . ($t['to_department_name'] ?? '') . ' · ' . ($t['status'] ?? '')); ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php else: ?>
    <?php if (empty($disposals)): ?><div class="asset-empty"><p>No disposals yet.</p></div><?php else: ?>
    <ul class="list-group"><?php foreach ($disposals as $t): ?><li class="list-group-item"><?php echo $e(($t['asset_id'] ?? '') . ' · ' . ($t['reason'] ?? '') . ' · ' . ($t['status'] ?? '')); ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php endif; ?>
    <a class="btn btn-link btn-lg mt-3" href="<?php echo APP_URL; ?>/devices">Back</a>
</div>
