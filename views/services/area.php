<?php require BASE_PATH . '/views/services/partials/shell.php'; ?>
<div class="svc-links">
    <?php foreach ($items as $key => $item): ?>
        <a href="<?php echo APP_URL; ?>/services/records?area=<?php echo $h($area); ?>&resource=<?php echo $h($key); ?>">
            <i class="fas fa-folder-open"></i>
            <span><?php echo $h($item['title']); ?></span>
        </a>
    <?php endforeach; ?>
</div>
</div></div></div>
