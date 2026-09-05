<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array $filters */
/** @var array $rows */
/** @var int $total */
/** @var int $pageNum */
/** @var int $perPage */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$totalPages = max(1, (int) ceil(max(0, $total) / max(1, $perPage)));
$queryBase = $urls['events'] . '?' . http_build_query(array_filter([
    'person_id' => $filters['person_id'] ?? null,
    'student_name' => $filters['student_name'] ?? null,
    'date' => $filters['date'] ?? null,
    'date_from' => $filters['date_from'] ?? null,
    'date_to' => $filters['date_to'] ?? null,
], static function ($v) {
    return $v !== null && $v !== '';
}));
// Trim trailing ? if no filters
if (substr($queryBase, -1) === '?') {
    $queryBase = substr($queryBase, 0, -1);
}

$studentDeviceSection = 'events';
$pageTitle = 'Attendance events';
$pageSubtitle = 'One row per student per day — In (first), Out (last), Others (middle punches)';

ob_start();
?>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-sm btn-outline-success" href="<?php echo $e($urls['export_excel'] . '?' . http_build_query($filters)); ?>">Export Excel</a>
    <a class="btn btn-sm btn-outline-success" href="<?php echo $e($urls['export_csv'] . '?' . http_build_query($filters)); ?>">Export CSV</a>
</div>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<div class="card sd-card mb-3">
    <div class="card-header fw-semibold">Filter</div>
    <div class="card-body">
        <form method="get" action="<?php echo $e($urls['events']); ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Student ID / Emp No</label>
                <input type="text" name="person_id" class="form-control" value="<?php echo $e($filters['person_id'] ?? ''); ?>" placeholder="2025/ICT/… or 254TE001">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Student name</label>
                <input type="text" name="student_name" class="form-control" value="<?php echo $e($filters['student_name'] ?? ''); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $e($filters['date'] ?? ''); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $e($filters['date_from'] ?? ''); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $e($filters['date_to'] ?? ''); ?>">
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
            </div>
            <div class="col-6 col-md-2">
                <a href="<?php echo $e($urls['events']); ?>" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card sd-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="fw-semibold">Daily attendance</span>
            <div class="sd-legend mt-1">
                <span><i class="dot in"></i>In — first punch</span>
                <span><i class="dot out"></i>Out — last punch</span>
                <span><i class="dot other"></i>Others — middle</span>
            </div>
        </div>
        <span class="small text-muted"><?php echo (int) $total; ?> day-rows</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover sd-events-table mb-0">
            <thead>
            <tr>
                <th class="col-id">Student ID</th>
                <th class="col-emp">Employee No</th>
                <th class="col-name">Student Name</th>
                <th class="col-date">Date</th>
                <th class="col-time text-center">In</th>
                <th class="col-time text-center">Out</th>
                <th class="col-others">Others</th>
                <th class="col-machine">Machine</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No attendance records for this filter.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="col-id"><?php echo $e($row['student_id'] ?? ''); ?></td>
                        <td class="col-emp"><?php echo $e($row['employee_no'] ?? ''); ?></td>
                        <td class="col-name"><?php echo $e($row['student_name'] ?? ''); ?></td>
                        <td class="col-date"><?php echo $e($row['attendance_date'] ?? ''); ?></td>
                        <td class="col-time text-center">
                            <?php if (!empty($row['time_in'])): ?>
                                <span class="sd-time-in"><?php echo $e($row['time_in']); ?></span>
                            <?php else: ?>
                                <span class="sd-time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-time text-center">
                            <?php if (!empty($row['time_out'])): ?>
                                <span class="sd-time-out"><?php echo $e($row['time_out']); ?></span>
                            <?php else: ?>
                                <span class="sd-time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-others"><?php echo ($row['time_others'] ?? '') !== '' ? $e($row['time_others']) : '—'; ?></td>
                        <td class="col-machine"><?php echo $e($row['machine_id'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white">
            <span class="small text-muted">Page <?php echo (int) $pageNum; ?> of <?php echo (int) $totalPages; ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= min($totalPages, 20); $p++): ?>
                        <li class="page-item <?php echo $p === $pageNum ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $e($queryBase . (strpos($queryBase, '?') !== false ? '&' : '?') . 'page=' . $p); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
?>
<div class="container-fluid px-3 px-sm-4 py-3 student-device-page">
    <?php include __DIR__ . '/partials/styles.php'; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-md-3 col-lg-2">
            <?php include __DIR__ . '/partials/nav.php'; ?>
        </div>
        <div class="col-12 col-md-9 col-lg-10 min-w-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="mb-1 fw-bold"><?php echo $e($pageTitle); ?></h4>
                    <div class="text-muted small"><?php echo $e($pageSubtitle); ?></div>
                </div>
                <?php echo $headerActions; ?>
            </div>
            <?php echo $contentHtml; ?>
        </div>
    </div>
</div>
