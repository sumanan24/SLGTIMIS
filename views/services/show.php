<?php require BASE_PATH . '/views/services/partials/shell.php'; ?>
<div class="svc-record">
    <?php foreach ($resource['fields'] as $name => $field):
        $value = $row[$name] ?? '';
        if (isset($labels[$name][$value])) {
            $value = $labels[$name][$value];
        } ?>
        <div>
            <span><?php echo $h($field['label']); ?></span>
            <?php if ($field['type'] === 'file' && !empty($row[$name])): ?>
                <strong><a href="<?php echo APP_URL; ?>/services/download?file=<?php echo urlencode($row[$name]); ?>">Download</a></strong>
            <?php else: ?>
                <strong><?php echo $h($value !== '' && $value !== null ? $value : '—'); ?></strong>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (!empty($row['staff_code']) || !empty($row['contract_code']) || !empty($row['provider_code']) || !empty($row['incident_code']) || !empty($row['pass_code']) || !empty($row['unit_code']) || !empty($row['complaint_code']) || !empty($row['payment_code']) || !empty($row['eval_code']) || !empty($row['item_code'])): ?>
        <div><span>Reference</span><strong><?php echo $h($row['staff_code'] ?? $row['contract_code'] ?? $row['provider_code'] ?? $row['incident_code'] ?? $row['pass_code'] ?? $row['unit_code'] ?? $row['complaint_code'] ?? $row['payment_code'] ?? $row['eval_code'] ?? $row['item_code'] ?? $row['invoice_no'] ?? ''); ?></strong></div>
    <?php endif; ?>
</div>
<?php if (!empty($history)): ?>
    <h6 class="svc-sub">Renewal history</h6>
    <ul class="svc-history">
        <?php foreach ($history as $item): ?>
            <li><a href="<?php echo APP_URL; ?>/services/view?area=<?php echo $h($area); ?>&resource=contracts&id=<?php echo (int) $item['id']; ?>"><?php echo $h($item['contract_code']); ?></a> <?php echo $h($item['status']); ?> until <?php echo $h($item['expiry_date']); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<h6 class="svc-sub">Documents</h6>
<ul class="svc-history">
    <?php if (empty($documents)): ?><li>No extra documents.</li><?php endif; ?>
    <?php foreach ($documents as $doc): ?>
        <li><a href="<?php echo APP_URL; ?>/services/download?file=<?php echo urlencode($doc['file_path']); ?>"><?php echo $h($doc['title']); ?></a></li>
    <?php endforeach; ?>
</ul>
<?php if (!empty($can_write)): ?>
    <form class="svc-upload" method="post" action="<?php echo APP_URL; ?>/services/upload" enctype="multipart/form-data">
        <input type="hidden" name="area" value="<?php echo $h($area); ?>">
        <input type="hidden" name="resource" value="<?php echo $h($resource_key); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
        <input class="form-control" name="title" placeholder="Document title">
        <input class="form-control" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
        <button class="btn btn-outline-primary" type="submit">Upload</button>
    </form>
    <div class="svc-actions">
        <a class="btn btn-primary" href="<?php echo APP_URL; ?>/services/form?area=<?php echo $h($area); ?>&resource=<?php echo $h($resource_key); ?>&id=<?php echo (int) $row['id']; ?>">Edit</a>
        <form method="post" action="<?php echo APP_URL; ?>/services/delete" onsubmit="return confirm('Archive this record?');">
            <input type="hidden" name="area" value="<?php echo $h($area); ?>">
            <input type="hidden" name="resource" value="<?php echo $h($resource_key); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
            <button class="btn btn-outline-danger" type="submit">Archive</button>
        </form>
    </div>
<?php endif; ?>
</div></div></div>
