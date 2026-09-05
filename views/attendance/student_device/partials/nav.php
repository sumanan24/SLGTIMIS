<?php
declare(strict_types=1);
/** @var string $studentDeviceSection */
/** @var array $urls */
$sec = $studentDeviceSection ?? 'dashboard';
?>
<nav class="list-group shadow-sm border student-device-side-nav mb-3" aria-label="Student fingerprint attendance">
    <div class="list-group-item bg-light fw-semibold small text-uppercase text-muted py-2">
        Fingerprint attendance
    </div>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'dashboard' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['index'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-th-large fa-fw"></i><span>Dashboard</span>
    </a>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'events' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['events'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-clock fa-fw"></i><span>Attendance events</span>
    </a>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'users' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['users'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-users fa-fw"></i><span>Machine users</span>
    </a>
    <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $sec === 'logs' ? 'active' : ''; ?>"
       href="<?php echo htmlspecialchars($urls['logs'], ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-history fa-fw"></i><span>Sync logs</span>
    </a>
</nav>
<style>
.student-device-side-nav .list-group-item.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: #fff;
}
.student-device-side-nav .list-group-item.active i { color: rgba(255,255,255,.95) !important; }
.student-device-side-nav .list-group-item-action i { color: #6c757d; width: 1.25rem; }
</style>
