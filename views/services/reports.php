<?php require BASE_PATH . '/views/services/partials/shell.php';
$print = APP_URL . '/services/reports?report=' . urlencode($report) . '&date_from=' . urlencode($filters['date_from']) . '&date_to=' . urlencode($filters['date_to']) . '&print=1';
$excel = APP_URL . '/services/reports?report=' . urlencode($report) . '&date_from=' . urlencode($filters['date_from']) . '&date_to=' . urlencode($filters['date_to']) . '&export=csv';
?>
<form class="svc-filters" method="get" action="<?php echo APP_URL; ?>/services/reports">
    <select class="form-select" name="report">
        <?php foreach ($reports as $key => $label): ?>
            <option value="<?php echo $h($key); ?>" <?php echo $key === $report ? 'selected' : ''; ?>><?php echo $h($label); ?></option>
        <?php endforeach; ?>
    </select>
    <input class="form-control" type="date" name="date_from" value="<?php echo $h($filters['date_from']); ?>">
    <input class="form-control" type="date" name="date_to" value="<?php echo $h($filters['date_to']); ?>">
    <button class="btn btn-primary" type="submit">Show</button>
    <a class="btn btn-outline-primary" href="<?php echo $h($excel); ?>">Excel</a>
    <a class="btn btn-outline-secondary" href="<?php echo $h($print); ?>" target="_blank">Print / PDF</a>
</form>
<div class="table-responsive">
    <table class="table svc-table">
        <?php if (empty($rows)): ?>
            <tr><td>No records for this period.</td></tr>
        <?php else: ?>
            <thead><tr><?php foreach (array_keys($rows[0]) as $col): if ($col === 'deleted_at' || $col === 'created_by') { continue; } ?><th><?php echo $h(ucwords(str_replace('_', ' ', $col))); ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr><?php foreach ($row as $col => $value): if ($col === 'deleted_at' || $col === 'created_by') { continue; }
                    if (isset($labels[$col][$value])) { $value = $labels[$col][$value]; } ?>
                    <td><?php echo $h($value); ?></td>
                <?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        <?php endif; ?>
    </table>
</div>
</div></div></div>
