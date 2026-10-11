<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container py-3 fac-page">
    <h4>Facilities report</h4>
    <p><?php echo count($rows); ?> ticket(s)</p>
    <table class="table table-sm">
        <thead><tr><th>Ticket</th><th>Title</th><th>Status</th><th>Priority</th><th>Assigned</th><th>Deadline</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo $h($row['ticket_number']); ?></td>
                <td><?php echo $h($row['title']); ?></td>
                <td><?php echo $h($stLabel[$row['status']] ?? $row['status']); ?></td>
                <td><?php echo $h($prLabel[$row['current_priority']] ?? $row['current_priority']); ?></td>
                <td><?php echo $h($row['responsible_name'] ?? ''); ?></td>
                <td><?php echo $h($row['deadline'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.print();</script>
</div>
