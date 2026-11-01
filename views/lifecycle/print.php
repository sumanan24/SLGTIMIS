<h1><?php echo htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8'); ?></h1>
<table>
    <thead><tr><?php foreach ($report['headers'] as $header): ?><th><?php echo htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($report['rows'] as $row): ?>
        <tr><?php foreach (array_values($row) as $cell): ?><td><?php echo htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8'); ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p>Printed <?php echo date('Y-m-d H:i'); ?></p>
