<?php
$sec = $deviceSection ?? 'dashboard';
$base = APP_URL . '/devices';
$canManage = !empty($canManage);
$scopeMode = (string) ($assetScope['mode'] ?? 'all');
$assetsLabel = $scopeMode === 'department' ? 'My Department Assets' : (($scopeMode === 'staff' || $scopeMode === 'student') ? 'My Assets' : 'My Department Assets');
$items = [
    ['dashboard', 'Dashboard', $base, 'fa-house'],
    ['devices', $assetsLabel, $base . '/list', 'fa-boxes-stacked'],
];
if ($canManage) {
    $items[] = ['create', 'Add New Asset', $base . '/create', 'fa-plus'];
    $items[] = ['assign', 'Assign Assets', $base . '/assign', 'fa-user-check'];
    $items[] = ['transfer', 'Asset Transfer', $base . '/transfer', 'fa-right-left'];
    $items[] = ['condition', 'Update Asset Condition', $base . '/condition', 'fa-screwdriver-wrench'];
    $items[] = ['dispose', 'Dispose Assets', $base . '/dispose', 'fa-box-archive'];
}
$items[] = ['stickers', 'QR Code Stickers', $base . '/stickers', 'fa-qrcode'];
$items[] = ['reports', 'Reports', $base . '/reports', 'fa-file-lines'];
?>
<nav class="asset-menu mb-4" aria-label="Asset menu">
    <?php foreach ($items as [$key, $label, $href, $icon]): ?>
    <a class="asset-menu-link <?php echo $sec === $key ? 'is-active' : ''; ?>" href="<?php echo $href; ?>">
        <i class="fas <?php echo $icon; ?>"></i>
        <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
    <?php endforeach; ?>
</nav>
