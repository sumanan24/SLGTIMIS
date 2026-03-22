<?php
$sec = $inventorySection ?? 'dashboard';
$base = APP_URL . '/inventory';
?>
<ul class="nav nav-pills flex-wrap gap-1 mb-4 p-2 bg-light rounded border">
    <li class="nav-item">
        <a class="nav-link <?php echo $sec === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $base; ?>">Dashboard</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $sec === 'items' ? 'active' : ''; ?>" href="<?php echo $base; ?>/items">Items</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $sec === 'stock-in' ? 'active' : ''; ?>" href="<?php echo $base; ?>/stock-in">Stock In</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $sec === 'stock-out' ? 'active' : ''; ?>" href="<?php echo $base; ?>/stock-out">Stock Out</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $sec === 'transfer' ? 'active' : ''; ?>" href="<?php echo $base; ?>/transfer">Transfer</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $sec === 'log' ? 'active' : ''; ?>" href="<?php echo $base; ?>/log">Log</a>
    </li>
</ul>
