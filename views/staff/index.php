<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff.css?v=<?php echo time(); ?>">
<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$canManageStaff = !empty($canManageStaff);
$canOpenPersonalFile = !empty($canOpenPersonalFile);
$search = $search ?? '';
$staff = $staff ?? [];
$total = (int) ($total ?? 0);
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
?>

<div class="container-fluid px-0 st-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold"><i class="fas fa-chalkboard-teacher me-2"></i>Staff</h5>
            <div class="st-head-actions">
                <span class="st-count"><?php echo number_format($total); ?> member<?php echo $total === 1 ? '' : 's'; ?></span>
                <?php if ($canManageStaff): ?>
                    <a href="<?php echo APP_URL; ?>/staff/create" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Staff
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <?php echo $h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <?php echo $h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="GET" action="<?php echo APP_URL; ?>/staff" class="st-search">
                <input type="text" name="search" class="form-control"
                       placeholder="Search name, username, finger no, email, or NIC"
                       value="<?php echo $h($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Search
                </button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($staff)): ?>
                <div class="st-table-wrap">
                    <div class="table-responsive mb-0">
                        <table class="table table-hover align-middle st-table">
                            <colgroup>
                                <col class="col-id">
                                <col class="col-finger">
                                <col class="col-name">
                                <col class="col-dept">
                                <col class="col-pos">
                                <col class="col-status">
                                <?php if ($canManageStaff || $canOpenPersonalFile): ?>
                                    <col class="col-actions">
                                <?php endif; ?>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="col-id">Username</th>
                                    <th class="col-finger">Finger No</th>
                                    <th class="col-name">Name</th>
                                    <th class="col-dept">Department</th>
                                    <th class="col-pos">Position</th>
                                    <th class="col-status">Status</th>
                                    <?php if ($canManageStaff || $canOpenPersonalFile): ?>
                                        <th class="col-actions">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                    <?php $isWorking = ($member['staff_status'] ?? '') === 'Working'; ?>
                                    <tr>
                                        <td class="col-id">
                                            <span class="st-id"><?php echo $h($member['staff_id']); ?></span>
                                        </td>
                                        <td class="col-finger">
                                            <?php echo $h($member['finger_machine_no'] ?? '') !== '' ? $h($member['finger_machine_no']) : '—'; ?>
                                        </td>
                                        <td class="col-name">
                                            <span class="st-name" title="<?php echo $h($member['staff_name']); ?>">
                                                <?php echo $h($member['staff_name']); ?>
                                            </span>
                                        </td>
                                        <td class="col-dept">
                                            <span class="st-chip" title="<?php echo $h($member['department_name'] ?? 'N/A'); ?>">
                                                <?php echo $h($member['department_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="col-pos">
                                            <span class="st-ellipsis" title="<?php echo $h($member['staff_position'] ?? ''); ?>">
                                                <?php echo $h($member['staff_position'] ?? '—'); ?>
                                            </span>
                                        </td>
                                        <td class="col-status">
                                            <span class="st-status <?php echo $isWorking ? 'is-working' : 'is-other'; ?>">
                                                <?php echo $h($member['staff_status'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <?php if ($canManageStaff || $canOpenPersonalFile): ?>
                                            <td class="col-actions">
                                                <div class="st-actions">
                                                    <?php if ($canOpenPersonalFile): ?>
                                                    <a href="<?php echo APP_URL; ?>/staff/edit?id=<?php echo urlencode($member['staff_id']); ?>"
                                                       class="btn btn-outline-primary" title="Personal File">
                                                        <i class="fas fa-folder-open"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if ($canManageStaff): ?>
                                                    <a href="<?php echo APP_URL; ?>/staff/delete?id=<?php echo urlencode($member['staff_id']); ?>"
                                                       class="btn btn-outline-danger" title="Delete"
                                                       onclick="return confirm('Delete this staff member?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="st-pager">
                    <div class="text-muted">
                        Showing <?php echo count($staff); ?> of <?php echo number_format($total); ?>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Staff pages">
                            <ul class="pagination pagination-sm">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>">Prev</a>
                                    </li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="st-empty">
                    <i class="fas fa-user-tie fa-2x"></i>
                    <p class="text-muted mb-3">
                        <?php echo $search !== '' ? 'No staff match that search.' : 'No staff members found.'; ?>
                    </p>
                    <?php if ($search === '' && $canManageStaff): ?>
                        <a href="<?php echo APP_URL; ?>/staff/create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Staff
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
