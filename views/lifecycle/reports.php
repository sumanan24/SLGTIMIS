<?php require __DIR__ . '/partials/shell.php';
$export = APP_URL . '/lifecycle/export?' . http_build_query(array_merge($filters, ['report' => $report_key]));
$print = APP_URL . '/lifecycle/print?' . http_build_query(array_merge($filters, ['report' => $report_key]));
?>
<form class="lc-filters" method="get" action="<?php echo APP_URL; ?>/lifecycle/reports">
    <label class="lc-field-wide">
        <span>Report</span>
        <select class="form-select" name="report">
            <?php foreach (LifecycleForms::reports() as $key => $label): ?>
                <option value="<?php echo $h($key); ?>" <?php echo $report_key === $key ? 'selected' : ''; ?>><?php echo $h($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php $showSearch = true; $showOrigin = true; require __DIR__ . '/partials/filter_fields.php'; ?>
    <div class="lc-filter-actions">
        <button class="btn btn-primary" type="submit">Run</button>
        <a class="btn btn-outline-secondary" href="<?php echo $h($export); ?>">Excel</a>
        <a class="btn btn-outline-secondary" href="<?php echo $h($print); ?>" target="_blank">Print</a>
    </div>
</form>
<div class="lc-table-wrap table-responsive">
    <table class="table lc-table">
        <thead><tr><?php foreach ($report['headers'] as $header): ?><th><?php echo $h($header); ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php if (!$report['rows']): ?><tr><td colspan="<?php echo count($report['headers']); ?>">No rows for this report.</td></tr><?php endif; ?>
        <?php foreach ($report['rows'] as $row): ?>
            <tr><?php foreach (array_values($row) as $cell): ?><td><?php echo $h($cell); ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="text-muted small px-3">The on-screen report shows up to 50 rows. Excel and print include up to 2,000.</p>
<?php require __DIR__ . '/partials/close.php'; ?>
