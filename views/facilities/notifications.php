<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header"><h5 class="fw-bold mb-0">Notices</h5></div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <?php if (empty($items)): ?>
                <div class="fac-empty">No notices yet.</div>
            <?php else: foreach ($items as $item): ?>
                <div class="fac-notice">
                    <strong><?php echo $h($item['title']); ?></strong>
                    <div class="fac-note"><?php echo $h($item['created_at']); ?></div>
                    <p class="mb-1"><?php echo $h($item['message']); ?></p>
                    <?php if (!empty($item['ticket_id'])): ?>
                        <a href="<?php echo $base; ?>/view?id=<?php echo (int) $item['ticket_id']; ?>">Open ticket</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
