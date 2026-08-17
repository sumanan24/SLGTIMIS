<?php
/**
 * One 2-up strip (two equal stickers side-by-side).
 * Vars: $stripSlots — list of 2 sticker data arrays
 *       $preview — bool wrap in dashed preview box (screen)
 */
$stripSlots = $stripSlots ?? [];
$preview = !empty($preview);
?>
<div class="<?php echo $preview ? 'device-qr-strip-preview' : ''; ?>">
    <div class="device-qr-strip">
        <?php foreach ($stripSlots as $slot): ?>
            <?php
            $sticker = $slot;
            require BASE_PATH . '/views/devices/partials/qr_sticker_cell.php';
            ?>
        <?php endforeach; ?>
    </div>
</div>
