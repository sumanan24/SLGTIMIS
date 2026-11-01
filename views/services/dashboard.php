<?php require BASE_PATH . '/views/services/partials/shell.php';
$cards = [
    'Total service categories' => $summary['categories'] ?? 0,
    'Registered service staff' => $summary['staff'] ?? 0,
    'Active agreements' => $summary['active_contracts'] ?? 0,
    'Expiring within 90 days' => $summary['expiring_90'] ?? 0,
    'Expired agreements' => $summary['expired'] ?? 0,
    'Pending invoices' => $summary['pending_invoices'] ?? 0,
    'Spend this month' => number_format((float) ($summary['month_spend'] ?? 0), 2),
    'Pending complaints' => $summary['pending_complaints'] ?? 0,
    'Ongoing maintenance' => $summary['ongoing_maintenance'] ?? 0,
    'Stock alerts' => $summary['stock_alerts'] ?? 0,
    'Maintenance due in 30 days' => $summary['upcoming_maintenance'] ?? 0,
];
?>
<div class="svc-cards">
    <?php foreach ($cards as $label => $value): ?>
        <article><span><?php echo $h($label); ?></span><strong><?php echo $h($value); ?></strong></article>
    <?php endforeach; ?>
</div>
<div class="svc-links">
    <?php foreach ($areas as $key => $area):
        if (!in_array($key, $actor['areas'] ?? [], true)) {
            continue;
        } ?>
        <a href="<?php echo APP_URL; ?>/services/area?area=<?php echo $h($key); ?>"><i class="fas <?php echo $h($area['icon']); ?>"></i><span><?php echo $h($area['title']); ?></span></a>
    <?php endforeach; ?>
</div>
</div></div></div>
