<?php
declare(strict_types=1);
/** @var string $staffDeviceSection */
/** @var array{device: string, list: string, daily: string, month: string, sync: string} $urls */
/** @var bool $staffDeviceNavLimited DIR/REG/FIN/HOD: dashboard + month only (embedded app). */
$sec = $staffDeviceSection ?? 'dashboard';
$navLimited = !empty($staffDeviceNavLimited);
?>
<nav class="list-group shadow-sm border staff-device-side-nav mb-3" aria-label="Staff device attendance">
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'dashboard' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['device'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-th-large fa-fw text-secondary"></i><span>Dashboard</span>
    </a>
    <?php if (!$navLimited): ?>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'list' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['list'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-list fa-fw text-secondary"></i><span>All punches</span>
    </a>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'daily' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['daily'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-calendar-day fa-fw text-secondary"></i><span>Daily report</span>
    </a>
    <?php endif; ?>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'month' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['month'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-calendar-alt fa-fw text-secondary"></i><span>Month report</span>
    </a>
    <?php if (!$navLimited): ?>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'sync' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['sync'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-sync fa-fw text-secondary"></i><span>Device sync</span>
    </a>
    <?php endif; ?>
</nav>
<style>
.staff-device-side-nav .list-group-item.active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
.staff-device-side-nav .list-group-item.active i { color: rgba(255,255,255,.9) !important; }
</style>
