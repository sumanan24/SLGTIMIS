<?php require __DIR__ . '/partials/shell.php'; ?>
<div class="lc-block">
    <h3>Import students from 2016 onwards</h3>
    <p>Upload a CSV or Excel file. The preview does not change any student. Saving an existing registration number fills only blank lifecycle fields. Name, NIC and values already stored are kept. New rows are marked historical.</p>
    <p><a href="<?php echo APP_URL; ?>/lifecycle/import/sample">Download the column template</a></p>
    <form method="post" action="<?php echo APP_URL; ?>/lifecycle/import/preview" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
        <input class="form-control" style="max-width: 360px;" type="file" name="import_file" accept=".csv,.xlsx,.xls" required>
        <button class="btn btn-outline-primary" type="submit">Preview</button>
    </form>
</div>
<?php if (!empty($preview['rows'])): ?>
<div class="lc-block">
    <h3>Preview <?php echo $h($preview['file']); ?></h3>
    <form method="post" action="<?php echo APP_URL; ?>/lifecycle/import/commit" onsubmit="return confirm('Save these rows into the student records?');">
        <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
        <button class="btn btn-primary mb-3" type="submit">Save import</button>
    </form>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Row</th><th>Registration</th><th>Name</th><th>Result</th><th>Detail</th></tr></thead><tbody>
        <?php foreach ($preview['rows'] as $row): ?>
            <tr><td><?php echo (int) $row['row_no']; ?></td><td><?php echo $h($row['student_id']); ?></td><td><?php echo $h($row['full_name']); ?></td><td><span class="lc-badge"><?php echo $h($row['outcome']); ?></span></td><td><?php echo $h($row['message']); ?></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>
<div class="lc-block">
    <h3>Import log</h3>
    <p>Batches saved: <?php echo (int) ($summary['batches'] ?? 0); ?></p>
    <ul><?php foreach ($summary['rows'] as $row): ?><li><?php echo $h($row['outcome']); ?>: <?php echo (int) $row['c']; ?></li><?php endforeach; ?></ul>
</div>
<?php require __DIR__ . '/partials/close.php'; ?>
