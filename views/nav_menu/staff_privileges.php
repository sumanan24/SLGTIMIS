<?php
/** @var array $staffList */
/** @var array $approvedCounts */
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold"><i class="fas fa-user-shield me-2"></i>Staff Navbar Privileges</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo APP_URL; ?>/admin/nav-menu" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-bars me-1"></i>Navbar options
                </a>
                <a href="<?php echo APP_URL; ?>/admin/nav-menu/create" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>Add option
                </a>
            </div>
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

            <p class="text-muted small">
                Assign dynamic sidebar menu items to each staff member by name.
                Enable <strong>dynamic navbar</strong> on the navbar settings page for these privileges to apply.
            </p>

            <div class="mb-3">
                <input type="text" class="form-control" id="staffListSearch" placeholder="Search staff name, ID, or department…" autocomplete="off">
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Staff name</th>
                            <th>Staff ID</th>
                            <th>Department</th>
                            <th>Approved menu items</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffList)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No staff records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($staffList as $st): ?>
                                <?php
                                $sid = $st['staff_id'];
                                $cnt = (int) ($approvedCounts[$sid] ?? 0);
                                $searchHay = strtolower(($st['staff_name'] ?? '') . ' ' . $sid . ' ' . ($st['department_name'] ?? ''));
                                ?>
                                <tr class="staff-priv-row" data-search="<?php echo $h($searchHay); ?>">
                                    <td><strong><?php echo $h($st['staff_name'] ?? $sid); ?></strong></td>
                                    <td><code class="small"><?php echo $h($sid); ?></code></td>
                                    <td class="small"><?php echo $h($st['department_name'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($cnt > 0): ?>
                                            <span class="badge bg-success"><?php echo $cnt; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges/edit?staff_id=<?php echo urlencode($sid); ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i>Manage privileges
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('staffListSearch')?.addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.staff-priv-row').forEach(function (row) {
        var hay = row.getAttribute('data-search') || '';
        row.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
