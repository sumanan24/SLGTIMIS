<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$columns = [];
if (!empty($spec['needs_student'])) {
    $columns[] = ['key' => 'student_id', 'label' => 'Registration'];
    $columns[] = ['key' => 'student_fullname', 'label' => 'Name'];
}
foreach (array_slice($spec['fields'], 0, 6) as $field) {
    $columns[] = ['key' => $field['name'], 'label' => $field['label']];
}
?>
<h1><?php echo $h($title); ?></h1>
<p><?php echo count($rows); ?> records. Use the browser print dialog and choose Save as PDF.</p>
<table>
    <thead><tr><?php foreach ($columns as $column): ?><th><?php echo $h($column['label']); ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="<?php echo count($columns); ?>">No records.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
        <tr><?php foreach ($columns as $column): ?><td><?php echo $h($row[$column['key']] ?? ''); ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody>
</table>
