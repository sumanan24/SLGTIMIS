<div class="container-fluid px-3 py-3" style="max-width:520px;">
    <?php require __DIR__ . '/../_nav.php'; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger text-white">Delete item</div>
        <div class="card-body">
            <p>Delete <strong><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></strong> (<?php echo htmlspecialchars($item['item_code'] ?? ''); ?>)?</p>
            <form method="post" action="<?php echo APP_URL; ?>/inventory/items/delete?id=<?php echo (int)($item['item_id'] ?? 0); ?>">
                <button type="submit" class="btn btn-danger">Delete</button>
                <a href="<?php echo APP_URL; ?>/inventory/items" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
