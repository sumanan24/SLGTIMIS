<?php require BASE_PATH . '/views/services/partials/shell.php';
$query = $_GET;
unset($query['page']);
$qs = http_build_query($query);
$pages = max(1, (int) ceil(($result['total'] ?? 0) / ($result['per_page'] ?? 20)));
?>
<div class="svc-toolbar">
    <form class="svc-filters" method="get" action="<?php echo APP_URL; ?>/services/records">
        <input type="hidden" name="area" value="<?php echo $h($area); ?>">
        <input type="hidden" name="resource" value="<?php echo $h($resource_key); ?>">
        <input class="form-control" name="q" value="<?php echo $h($filters['q'] ?? ''); ?>" placeholder="Search">
        <input class="form-control" type="date" name="date_from" value="<?php echo $h($filters['date_from'] ?? ''); ?>">
        <input class="form-control" type="date" name="date_to" value="<?php echo $h($filters['date_to'] ?? ''); ?>">
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <div class="svc-actions">
        <?php if (!empty($can_write)): ?><a class="btn btn-primary" href="<?php echo APP_URL; ?>/services/form?area=<?php echo $h($area); ?>&resource=<?php echo $h($resource_key); ?>">Add</a><?php endif; ?>
        <a class="btn btn-outline-primary" href="<?php echo APP_URL; ?>/services/export?area=<?php echo $h($area); ?>&resource=<?php echo $h($resource_key); ?>&<?php echo $h($qs); ?>">Excel</a>
    </div>
</div>
<div class="table-responsive">
    <table class="table svc-table">
        <thead><tr>
            <?php foreach ($resource['list'] as $col): ?><th><?php echo $h($resource['fields'][$col]['label'] ?? ucwords(str_replace('_', ' ', $col))); ?></th><?php endforeach; ?>
            <th></th>
        </tr></thead>
        <tbody>
        <?php if (empty($result['rows'])): ?>
            <tr><td colspan="<?php echo count($resource['list']) + 1; ?>">No records yet.</td></tr>
        <?php else: foreach ($result['rows'] as $row): ?>
            <tr>
                <?php foreach ($resource['list'] as $col):
                    $val = $row[$col] ?? '';
                    if (isset($labels[$col][$val])) {
                        $val = $labels[$col][$val];
                    } ?>
                    <td><?php echo $h($val); ?></td>
                <?php endforeach; ?>
                <td><a href="<?php echo APP_URL; ?>/services/view?area=<?php echo $h($area); ?>&resource=<?php echo $h($resource_key); ?>&id=<?php echo (int) $row['id']; ?>">Open</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
    <nav class="svc-pages">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?php echo $i === (int) $result['page'] ? 'is-on' : ''; ?>" href="?<?php echo $h($qs); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
</div></div></div>
