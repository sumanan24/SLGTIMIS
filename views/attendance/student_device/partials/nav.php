<?php
declare(strict_types=1);
/** @var string $studentDeviceSection */
/** @var array $urls */
/** @var bool $canManageDevice */
$sec = $studentDeviceSection ?? 'dashboard';
$canManageDevice = array_key_exists('canManageDevice', get_defined_vars())
    ? !empty($canManageDevice)
    : true;

$items = [
    ['key' => 'sao', 'href' => $urls['sao'] ?? '#', 'icon' => 'fa-chart-pie', 'label' => 'SAO Dashboard', 'manage_only' => false],
    ['key' => 'dashboard', 'href' => $urls['index'] ?? '#', 'icon' => 'fa-th-large', 'label' => 'Device', 'manage_only' => true],
    ['key' => 'events', 'href' => $urls['events'] ?? '#', 'icon' => 'fa-clock', 'label' => 'Events', 'manage_only' => true],
    ['key' => 'month', 'href' => $urls['month'] ?? '#', 'icon' => 'fa-calendar-alt', 'label' => 'Month report', 'manage_only' => false],
    ['key' => 'holidays', 'href' => $urls['holidays'] ?? '#', 'icon' => 'fa-umbrella-beach', 'label' => 'Holidays / leave', 'manage_only' => true],
    ['key' => 'users', 'href' => $urls['users'] ?? '#', 'icon' => 'fa-users', 'label' => 'Users', 'manage_only' => true],
    ['key' => 'fingerprint-import', 'href' => $urls['fingerprint_import'] ?? '#', 'icon' => 'fa-file-excel', 'label' => 'Excel Export', 'manage_only' => false],
    ['key' => 'logs', 'href' => $urls['logs'] ?? '#', 'icon' => 'fa-history', 'label' => 'Sync logs', 'manage_only' => true],
];
?>
<nav class="sd-top-nav" aria-label="Student fingerprint attendance">
    <?php foreach ($items as $item): ?>
        <?php if (!empty($item['manage_only']) && !$canManageDevice) {
            continue;
        } ?>
        <a class="sd-top-nav-link <?php echo $sec === $item['key'] ? 'is-active' : ''; ?>"
           href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
            <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>
</nav>
