<?php
/** @var array $staff */
/** @var array $navItems */
/** @var array $assignments */
/** @var array $assignedNavIds */
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$staffId = $staff['staff_id'] ?? '';
$staffName = $staff['staff_name'] ?? $staffId;

$byParent = [];
foreach ($navItems as $n) {
    $pid = (int) ($n['parent_id'] ?? 0);
    if (!isset($byParent[$pid])) {
        $byParent[$pid] = [];
    }
    $byParent[$pid][] = $n;
}
$roots = $byParent[0] ?? [];
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-user-shield me-2"></i>Navbar privileges — <?php echo $h($staffName); ?>
            </h5>
            <a href="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>All staff
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo $h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo $h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <p class="text-muted small mb-3">
                Staff ID: <code><?php echo $h($staffId); ?></code>
                <?php if (!empty($staff['department_name'])): ?>
                    · <?php echo $h($staff['department_name']); ?>
                <?php endif; ?>
                — Check menu options this person may see when dynamic navbar is enabled.
            </p>

            <form method="post" action="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges/edit?staff_id=<?php echo urlencode($staffId); ?>">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="auto_approve_staff" value="1" id="auto_approve_staff" checked>
                    <label class="form-check-label" for="auto_approve_staff">Approve new assignments immediately</label>
                </div>

                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" id="navSearch" placeholder="Search menu label or route…" autocomplete="off">
                </div>

                <div class="border rounded p-3 mb-3" style="max-height: 420px; overflow-y: auto;">
                    <?php if (empty($navItems)): ?>
                        <p class="text-muted mb-0">No navbar options defined yet. <a href="<?php echo APP_URL; ?>/admin/nav-menu/create">Add one</a>.</p>
                    <?php else: ?>
                        <?php
                        $renderNav = static function (array $items, int $depth) use (&$renderNav, $byParent, $h, $assignedNavIds) {
                            foreach ($items as $n) {
                                $nid = (int) $n['nav_id'];
                                $label = $n['label'] ?? '';
                                $route = $n['route_path'] ?? '';
                                $searchHay = strtolower($label . ' ' . $route);
                                $pad = $depth * 1.25;
                                ?>
                                <div class="form-check nav-priv-row mb-1" style="padding-left: <?php echo $pad; ?>rem;" data-search="<?php echo $h($searchHay); ?>">
                                    <input class="form-check-input" type="checkbox" name="assigned_nav[]" value="<?php echo $nid; ?>" id="nav_<?php echo $nid; ?>"
                                        <?php echo in_array($nid, $assignedNavIds, true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="nav_<?php echo $nid; ?>">
                                        <i class="<?php echo $h($n['icon_class'] ?? 'fas fa-circle'); ?> me-1 text-primary"></i>
                                        <strong><?php echo $h($label); ?></strong>
                                        <?php if ($route !== ''): ?>
                                            <span class="text-muted small">— <?php echo $h($route); ?></span>
                                        <?php endif; ?>
                                        <?php if (empty($n['is_active'])): ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <?php
                                $children = $byParent[$nid] ?? [];
                                if ($children !== []) {
                                    $renderNav($children, $depth + 1);
                                }
                            }
                        };
                        $renderNav($roots, 0);
                        ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($assignments)): ?>
                <h6 class="fw-bold">Assignment status</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Menu item</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><?php echo $h($a['nav_label'] ?? ''); ?></td>
                                    <td>
                                        <?php $st = $a['status']; $badge = $st === 'approved' ? 'success' : ($st === 'pending' ? 'warning' : 'secondary'); ?>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $h(ucfirst($st)); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save privileges</button>
                    <a href="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('navSearch')?.addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.nav-priv-row').forEach(function (row) {
        var hay = row.getAttribute('data-search') || '';
        row.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
