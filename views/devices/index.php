<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$f = $filters ?? [];
$per = max(1, (int) ($perPage ?? 25));
$totalCount = (int) ($total ?? 0);
$totalPages = max(1, (int) ceil($totalCount / $per));
$curPage = max(1, (int) ($currentPage ?? 1));
$canManage = !empty($canManage);
$listQuery = static function (array $extra = []) use ($f): string {
    $params = [
        'q' => $f['search'] ?? '',
        'serial' => $f['serial'] ?? '',
        'type' => $f['device_type'] ?? '',
        'status' => $f['status'] ?? '',
        'dept' => $f['department_id'] ?? '',
        'assigned' => $f['assigned'] ?? '',
        'warranty' => $f['warranty'] ?? '',
    ];
    $params = array_merge($params, $extra);
    return http_build_query(array_filter($params, static fn ($v) => $v !== '' && $v !== null));
};
$canAssignDevice = static function (array $row) use ($canManage): bool {
    if (!$canManage) {
        return false;
    }

    // Only Available laptops/devices can be assigned.
    $status = strtolower(trim((string) ($row['status'] ?? '')));
    if ($status !== DeviceModel::STATUS_AVAILABLE) {
        return false;
    }

    $employeeId = trim((string) ($row['assigned_employee_id'] ?? ''));
    $staffName = trim((string) ($row['assigned_staff_name'] ?? ''));
    $hasActive = (int) ($row['has_active_assignment'] ?? 0) === 1
        || $row['has_active_assignment'] === true
        || $row['has_active_assignment'] === '1';

    if ($hasActive || $employeeId !== '' || $staffName !== '') {
        return false;
    }

    return true;
};
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap devices-list-page">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">All Devices</h1>
            <p class="text-muted small mb-0">Search, filter, assign, and manage ICT assets.</p>
        </div>
        <?php if ($canManage): ?>
        <a href="<?php echo APP_URL; ?>/devices/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Device</a>
        <?php endif; ?>
    </div>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>

    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="get" action="<?php echo APP_URL; ?>/devices/list" class="device-filter-bar">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-4">
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?php echo $e($f['search'] ?? ''); ?>" placeholder="Asset ID, serial, staff…">
            </div>
            <div class="col-6 col-md-2 col-lg-1">
                <label class="form-label small mb-0">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($deviceTypes ?? [] as $t): ?>
                    <option value="<?php echo $e($t); ?>" <?php echo ($f['device_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $e($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($statuses ?? [] as $s): ?>
                    <option value="<?php echo $e($s); ?>" <?php echo ($f['status'] ?? '') === $s ? 'selected' : ''; ?>><?php echo $e(DeviceModel::statusLabel($s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">Department</label>
                <select name="dept" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($departments ?? [] as $d): ?>
                    <option value="<?php echo $e($d['department_id'] ?? ''); ?>" <?php echo ($f['department_id'] ?? '') === ($d['department_id'] ?? '') ? 'selected' : ''; ?>><?php echo $e($d['department_name'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 col-lg-1">
                <label class="form-label small mb-0">Assigned</label>
                <select name="assigned" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="yes" <?php echo ($f['assigned'] ?? '') === 'yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="no" <?php echo ($f['assigned'] ?? '') === 'no' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label small mb-0">Warranty</label>
                <select name="warranty" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="expired" <?php echo ($f['warranty'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="expiring_30" <?php echo ($f['warranty'] ?? '') === 'expiring_30' ? 'selected' : ''; ?>>≤30 days</option>
                    <option value="expiring_90" <?php echo ($f['warranty'] ?? '') === 'expiring_90' ? 'selected' : ''; ?>>≤90 days</option>
                    <option value="valid" <?php echo ($f['warranty'] ?? '') === 'valid' ? 'selected' : ''; ?>>Valid</option>
                </select>
            </div>
            <div class="col-md-auto d-flex gap-1 pb-1">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?php echo APP_URL; ?>/devices/list" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="dev-list-summary">
        <span><strong><?php echo $totalCount; ?></strong> device<?php echo $totalCount === 1 ? '' : 's'; ?></span>
        <?php if ($totalPages > 1): ?>
        <span class="text-muted">Page <?php echo $curPage; ?> of <?php echo $totalPages; ?></span>
        <?php endif; ?>
    </div>

    <div class="device-table-wrap">
        <table class="table table-hover align-middle mb-0">
            <colgroup>
                <col class="dev-col-asset">
                <col class="dev-col-type">
                <col class="dev-col-brand">
                <col class="dev-col-model">
                <col class="dev-col-serial">
                <col class="dev-col-user">
                <col class="dev-col-dept">
                <col class="dev-col-status">
                <col class="dev-col-actions">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Asset ID</th>
                    <th scope="col">Type</th>
                    <th scope="col">Brand</th>
                    <th scope="col">Model</th>
                    <th scope="col">Serial</th>
                    <th scope="col">Assigned User</th>
                    <th scope="col">Department</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No devices found.</td></tr>
                <?php else: foreach ($devices as $d):
                    $id = (int) ($d['id'] ?? 0);
                    $status = (string) ($d['status'] ?? '');
                    $assignedName = trim((string) ($d['assigned_staff_name'] ?? ''));
                    $showAssign = $canAssignDevice($d);
                    // Hard stop: Assign only for Available status with no assignee.
                    if (strtolower(trim($status)) !== 'available' || $assignedName !== '') {
                        $showAssign = false;
                    }
                ?>
                <tr>
                    <td>
                        <a href="<?php echo APP_URL; ?>/devices/view?id=<?php echo $id; ?>" class="fw-semibold text-decoration-none">
                            <span class="dev-cell-ellipsis" title="<?php echo $e($d['asset_id'] ?? ''); ?>"><?php echo $e($d['asset_id'] ?? ''); ?></span>
                        </a>
                    </td>
                    <td><?php echo $e($d['device_type'] ?? ''); ?></td>
                    <td><span class="dev-cell-ellipsis" title="<?php echo $e($d['brand'] ?? ''); ?>"><?php echo $e($d['brand'] ?? '—'); ?></span></td>
                    <td><span class="dev-cell-ellipsis" title="<?php echo $e($d['model'] ?? ''); ?>"><?php echo $e($d['model'] ?? '—'); ?></span></td>
                    <td><code class="small dev-cell-ellipsis d-block" title="<?php echo $e($d['serial_number'] ?? ''); ?>"><?php echo $e($d['serial_number'] ?? '—'); ?></code></td>
                    <td>
                        <span class="dev-cell-ellipsis" title="<?php echo $e($assignedName); ?>">
                            <?php echo $e($assignedName !== '' ? $assignedName : '—'); ?>
                        </span>
                    </td>
                    <td><span class="dev-cell-ellipsis" title="<?php echo $e($d['assigned_department_name'] ?? ''); ?>"><?php echo $e($d['assigned_department_name'] ?? '—'); ?></span></td>
                    <td>
                        <span class="badge bg-<?php echo DeviceAssetHelper::statusBadgeClass($status); ?>"><?php echo $e(DeviceModel::statusLabel($status)); ?></span>
                    </td>
                    <td>
                        <div class="dev-actions">
                            <a href="<?php echo APP_URL; ?>/devices/view?id=<?php echo $id; ?>" class="btn btn-outline-primary btn-icon" title="View"><i class="fas fa-eye"></i></a>
                            <?php if ($showAssign): ?>
                            <a href="<?php echo APP_URL; ?>/devices/assign?id=<?php echo $id; ?>" class="btn btn-outline-success btn-icon" title="Assign"><i class="fas fa-user-plus"></i></a>
                            <?php endif; ?>
                            <?php if ($canManage): ?>
                            <a href="<?php echo APP_URL; ?>/devices/edit?id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($canPrintQr)): ?>
                            <a href="<?php echo APP_URL; ?>/devices/view?id=<?php echo $id; ?>" class="btn btn-outline-dark btn-icon" title="Print QR labels"><i class="fas fa-print"></i></a>
                            <a href="<?php echo APP_URL; ?>/devices/qr-pdf?id=<?php echo $id; ?>" class="btn btn-outline-danger btn-icon" title="QR PDF"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3 d-flex flex-wrap align-items-center justify-content-between gap-2" aria-label="Device list pagination">
        <span class="text-muted small">Showing page <?php echo $curPage; ?> of <?php echo $totalPages; ?></span>
        <ul class="pagination pagination-sm mb-0">
            <?php if ($curPage > 1): ?>
            <li class="page-item"><a class="page-link" href="<?php echo APP_URL; ?>/devices/list?<?php echo $e($listQuery(['page' => $curPage - 1])); ?>">&laquo;</a></li>
            <?php endif; ?>
            <?php
            $start = max(1, $curPage - 2);
            $end = min($totalPages, $curPage + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
            <li class="page-item <?php echo $p === $curPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo APP_URL; ?>/devices/list?<?php echo $e($listQuery(['page' => $p])); ?>"><?php echo $p; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($curPage < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="<?php echo APP_URL; ?>/devices/list?<?php echo $e($listQuery(['page' => $curPage + 1])); ?>">&raquo;</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
