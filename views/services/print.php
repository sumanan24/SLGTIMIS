<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<div class="p-3">
    <h2><?php echo $h($title); ?></h2>
    <p><?php echo $h($filters['date_from'] ?? ''); ?> to <?php echo $h($filters['date_to'] ?? ''); ?></p>
    <table class="table table-bordered table-sm">
        <?php if (!empty($rows)): ?>
            <thead><tr><?php foreach (array_keys($rows[0]) as $col): ?><th><?php echo $h(ucwords(str_replace('_', ' ', $col))); ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?><tr><?php foreach ($row as $value): ?><td><?php echo $h($value); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
            </tbody>
        <?php else: ?><tr><td>No records.</td></tr><?php endif; ?>
    </table>
    <script>window.print();</script>
</div>
