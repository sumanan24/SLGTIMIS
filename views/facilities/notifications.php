<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader('Notices', 'Updates about tickets you raised, handle or assigned.'); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <?php if (empty($items)): ?>
                <div class="fac-empty">No notices for your tickets yet.</div>
            <?php else: foreach ($items as $item): ?>
                <div class="fac-notice">
                    <div class="fac-notice-head">
                        <strong><?php echo $h($item['title']); ?></strong>
                        <span class="fac-note"><?php echo $h($item['created_at']); ?></span>
                    </div>
                    <p class="mb-1"><?php echo $h($item['message']); ?></p>
                    <?php if (!empty($item['ticket_id'])): ?>
                        <div class="fac-notice-foot">
                            <a href="<?php echo $base; ?>/view?id=<?php echo (int) $item['ticket_id']; ?>"><?php echo $h($item['ticket_number'] ?? 'Open ticket'); ?></a>
                            <?php if (!empty($item['ticket_title'])): ?><span class="fac-note"><?php echo $h($item['ticket_title']); ?></span><?php endif; ?>
                            <?php if (!empty($item['ticket_status'])): ?><?php echo $badgeStatus($item['ticket_status']); ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
