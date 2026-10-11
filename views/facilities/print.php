<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket; ?>
<div class="container py-3 fac-page">
    <h4><?php echo $h($t['ticket_number']); ?></h4>
    <p><?php echo $badgeStatus($t['status']); ?> <?php echo $badgePriority($t['current_priority']); ?></p>
    <p><strong><?php echo $h($t['title']); ?></strong></p>
    <p><?php echo nl2br($h($t['description'])); ?></p>
    <p>Requester: <?php echo $h($t['requester_name'] ?? ''); ?> · Location: <?php echo $h($t['location']); ?></p>
    <p>Assigned: <?php echo $h($t['responsible_name'] ?? ''); ?> · Deadline: <?php echo $h($t['deadline'] ?? ''); ?></p>
    <h6>Timeline</h6>
    <ul>
        <?php foreach ($timeline as $ev): ?>
            <li><?php echo $h($ev['created_at']); ?> — <?php echo $h($ev['action']); ?> (<?php echo $h($ev['actor_staff_id']); ?>)</li>
        <?php endforeach; ?>
    </ul>
    <script>window.print();</script>
</div>
