<?php
$sec = $deviceSection ?? 'dashboard';
$base = APP_URL . '/devices';
$canManage = !empty($canManage);
?>
<ul class="nav nav-pills flex-wrap gap-1 mb-4 p-2 bg-light rounded border device-subnav">
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $base; ?>">Dashboard</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'devices' ? 'active' : ''; ?>" href="<?php echo $base; ?>/list">All Devices</a></li>
    <?php if ($canManage): ?>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'create' ? 'active' : ''; ?>" href="<?php echo $base; ?>/create">Add Device</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'assignments' ? 'active' : ''; ?>" href="<?php echo $base; ?>/assignments">Assignments</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'maintenance' ? 'active' : ''; ?>" href="<?php echo $base; ?>/maintenance">Maintenance</a></li>
    <?php endif; ?>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'warranty' ? 'active' : ''; ?>" href="<?php echo $base; ?>/warranty">Warranty</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'scan' ? 'active' : ''; ?>" href="<?php echo $base; ?>/scan">QR Scanner</a></li>
    <?php if (!empty($canExport)): ?>
    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/export">Export</a></li>
    <?php endif; ?>
    <?php if (!empty($canViewHistory)): ?>
    <li class="nav-item"><a class="nav-link <?php echo $sec === 'audit' ? 'active' : ''; ?>" href="<?php echo $base; ?>/audit">Audit History</a></li>
    <?php endif; ?>
</ul>
