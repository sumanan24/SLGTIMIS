<?php require BASE_PATH . '/views/services/partials/shell.php'; ?>
<ul class="svc-history">
    <?php if (empty($notices)): ?><li>No reminders yet. Agreement and low-stock reminders appear here when a date is due.</li><?php endif; ?>
    <?php foreach ($notices as $notice): ?>
        <li>
            <strong><?php echo $h($notice['title']); ?></strong>
            <span><?php echo $h($notice['message']); ?></span>
            <?php if (empty($notice['is_read'])): ?>
                <form method="post" action="<?php echo APP_URL; ?>/services/notifications/read">
                    <input type="hidden" name="id" value="<?php echo (int) $notice['id']; ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
</div></div></div>
