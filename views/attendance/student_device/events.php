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

$total = (int) ($total ?? 0);
$pageNum = max(1, (int) ($pageNum ?? 1));
$perPage = max(1, (int) ($perPage ?? 50));
$totalPages = max(1, (int) ceil($total / $perPage));
$rows = $rows ?? [];
$filters = $filters ?? [];

$filterParams = array_filter([
    'person_id' => $filters['person_id'] ?? null,
    'student_name' => $filters['student_name'] ?? null,
    'date' => $filters['date'] ?? null,
    'date_from' => $filters['date_from'] ?? null,
    'date_to' => $filters['date_to'] ?? null,
], static function ($v) {
    return $v !== null && $v !== '';
});
$hasFilters = $filterParams !== [];
$queryBase = $urls['events'];
if ($filterParams !== []) {
    $queryBase .= '?' . http_build_query($filterParams);
}
$pageHref = static function (int $p) use ($queryBase, $e): string {
    $sep = strpos($queryBase, '?') !== false ? '&' : '?';
    return $e($queryBase . $sep . 'page=' . $p);
};

$fromRow = $total === 0 ? 0 : (($pageNum - 1) * $perPage) + 1;
$toRow = min($total, $pageNum * $perPage);

$window = 2;
$startPage = max(1, $pageNum - $window);
$endPage = min($totalPages, $pageNum + $window);
if (($endPage - $startPage) < ($window * 2)) {
    $startPage = max(1, $endPage - ($window * 2));
    $endPage = min($totalPages, $startPage + ($window * 2));
}

$studentDeviceSection = 'events';
$pageTitle = 'Attendance';
$pageSubtitle = 'One row per student per day — In (first), Out (last), Others (middle punches)';
$exportQs = $filterParams ? '?' . http_build_query($filterParams) : '';
?>
<div class="student-device-page sd-fullpage">
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

    <?php include __DIR__ . '/partials/nav.php'; ?>

    <div class="sd-fullpage-body">
        <div class="sd-page-head">
            <div class="sd-page-head-text">
                <h1 class="sd-page-title"><?php echo $e($pageTitle); ?></h1>
                <p class="sd-page-lead"><?php echo $e($pageSubtitle); ?></p>
            </div>
            <div class="sd-header-actions">
                <a class="btn btn-outline-success" href="<?php echo $e($urls['month']); ?>">
                    <i class="fas fa-calendar-alt me-1"></i>Month report
                </a>
                <a class="btn btn-outline-success" href="<?php echo $e($urls['export_excel'] . $exportQs); ?>">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </a>
                <a class="btn btn-outline-success" href="<?php echo $e($urls['export_csv'] . $exportQs); ?>">
                    <i class="fas fa-file-csv me-1"></i>CSV
                </a>
            </div>
        </div>

        <form method="get" action="<?php echo $e($urls['events']); ?>" class="sd-toolbar card sd-card">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between gap-2">
                <span><i class="fas fa-filter me-2 text-primary"></i>Filter</span>
                <?php if ($hasFilters): ?>
                    <a href="<?php echo $e($urls['events']); ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="sd-filter-grid">
                    <div class="sd-field">
                        <label class="form-label" for="sdPersonId">Student ID / Emp No</label>
                        <input type="text" id="sdPersonId" name="person_id" class="form-control"
                               value="<?php echo $e($filters['person_id'] ?? ''); ?>"
                               placeholder="2025/ICT/… or 254TE001" autocomplete="off">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdStudentName">Student name</label>
                        <input type="text" id="sdStudentName" name="student_name" class="form-control"
                               value="<?php echo $e($filters['student_name'] ?? ''); ?>" autocomplete="off">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdDate">Date</label>
                        <input type="date" id="sdDate" name="date" class="form-control"
                               value="<?php echo $e($filters['date'] ?? ''); ?>">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdDateFrom">From</label>
                        <input type="date" id="sdDateFrom" name="date_from" class="form-control"
                               value="<?php echo $e($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdDateTo">To</label>
                        <input type="date" id="sdDateTo" name="date_to" class="form-control"
                               value="<?php echo $e($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="sd-field sd-field-actions">
                        <label class="form-label d-none d-lg-block invisible">Apply</label>
                        <div class="sd-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Apply
                            </button>
                            <a href="<?php echo $e($urls['events']); ?>" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card sd-card sd-events-panel">
            <div class="card-header">
                <div class="sd-panel-head">
                    <div>
                        <div class="fw-semibold">Daily attendance</div>
                        <div class="sd-legend mt-1">
                            <span><i class="dot in"></i>In — first</span>
                            <span><i class="dot out"></i>Out — last</span>
                            <span><i class="dot other"></i>Others — middle</span>
                        </div>
                    </div>
                    <div class="sd-summary-chip">
                        <?php if ($total > 0): ?>
                            Showing <strong><?php echo number_format($fromRow); ?>–<?php echo number_format($toRow); ?></strong>
                            of <strong><?php echo number_format($total); ?></strong>
                        <?php else: ?>
                            <strong>0</strong> day-rows
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($rows === []): ?>
                <div class="sd-empty">
                    <i class="fas fa-clock"></i>
                    <p class="mb-0">No attendance records for this filter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive sd-table-wrap d-none d-lg-block">
                    <table class="table table-hover sd-events-table mb-0">
                        <colgroup>
                            <col class="col-id">
                            <col class="col-emp">
                            <col class="col-name">
                            <col class="col-date">
                            <col class="col-time">
                            <col class="col-time">
                            <col class="col-others">
                            <col class="col-machine">
                        </colgroup>
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
                        </tbody>
                    </table>
                </div>

                <div class="sd-card-list d-lg-none">
                    <?php foreach ($rows as $row): ?>
                        <article class="sd-day-card">
                            <div class="sd-day-card-top">
                                <div class="min-w-0">
                                    <div class="sd-day-name"><?php echo $e($row['student_name'] ?? '—'); ?></div>
                                    <div class="sd-day-id"><?php echo $e($row['student_id'] ?? ''); ?></div>
                                </div>
                                <div class="sd-day-date"><?php echo $e($row['attendance_date'] ?? ''); ?></div>
                            </div>
                            <div class="sd-day-times">
                                <div>
                                    <span class="sd-mini-label">In</span>
                                    <?php if (!empty($row['time_in'])): ?>
                                        <span class="sd-time-in"><?php echo $e($row['time_in']); ?></span>
                                    <?php else: ?>
                                        <span class="sd-time-empty">—</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="sd-mini-label">Out</span>
                                    <?php if (!empty($row['time_out'])): ?>
                                        <span class="sd-time-out"><?php echo $e($row['time_out']); ?></span>
                                    <?php else: ?>
                                        <span class="sd-time-empty">—</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (($row['time_others'] ?? '') !== '' || ($row['employee_no'] ?? '') !== '' || ($row['machine_id'] ?? '') !== ''): ?>
                                <div class="sd-day-meta">
                                    <?php if (($row['employee_no'] ?? '') !== ''): ?>
                                        <span>Emp <?php echo $e($row['employee_no']); ?></span>
                                    <?php endif; ?>
                                    <?php if (($row['time_others'] ?? '') !== ''): ?>
                                        <span>Others <?php echo $e($row['time_others']); ?></span>
                                    <?php endif; ?>
                                    <?php if (($row['machine_id'] ?? '') !== ''): ?>
                                        <span><?php echo $e($row['machine_id']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="card-footer sd-pager">
                    <div class="small text-muted">Page <?php echo (int) $pageNum; ?> of <?php echo (int) $totalPages; ?></div>
                    <nav aria-label="Attendance pagination">
                        <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-end">
                            <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $pageNum <= 1 ? '#' : $pageHref($pageNum - 1); ?>">Prev</a>
                            </li>
                            <?php if ($startPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $pageHref(1); ?>">1</a></li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <li class="page-item <?php echo $p === $pageNum ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $pageHref($p); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $pageHref($totalPages); ?>"><?php echo $totalPages; ?></a></li>
                            <?php endif; ?>
                            <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $pageNum >= $totalPages ? '#' : $pageHref($pageNum + 1); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
