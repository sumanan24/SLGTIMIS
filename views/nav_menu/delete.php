<?php
/** @var array $item */
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Navbar Option</h5>
        </div>
        <div class="card-body">
            <p>Delete <strong><?php echo $h($item['label']); ?></strong>? Child items under this parent will also be removed.</p>
            <table class="table table-sm table-bordered w-auto">
                <tr><th>Route</th><td><code><?php echo $item['route_path'] !== '' ? $h($item['route_path']) : '—'; ?></code></td></tr>
                <tr><th>Roles</th><td><?php $r = $item['allowed_roles'] ?? []; echo $r === [] ? 'All' : $h(implode(', ', $r)); ?></td></tr>
                <tr><th>Departments</th><td><?php $d = $item['allowed_departments'] ?? []; echo $d === [] ? 'All' : $h(implode(', ', $d)); ?></td></tr>
            </table>
            <form method="post" action="<?php echo APP_URL; ?>/admin/nav-menu/delete?id=<?php echo (int) $item['nav_id']; ?>" class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">Delete</button>
                <a href="<?php echo APP_URL; ?>/admin/nav-menu" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
