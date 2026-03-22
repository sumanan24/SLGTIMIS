<?php
declare(strict_types=1);
/** @var array $urls */
/** @var string $listBase */
/** @var string $baseQuery */
/** @var array $rows */
$buildLink = static function (int $p) use ($listBase, $baseQuery): string {
    $q = $baseQuery !== '' ? $baseQuery . '&' : '';
    return $listBase . '?' . $q . 'page=' . $p;
};
?>
<div class="container-fluid px-4 py-3">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">
            <?php include BASE_PATH . '/staff_attendance/partials/staff_device_nav.php'; ?>
        </div>
        <div class="col-lg-10 col-md-9">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>All punches</h5>
                </div>
                <div class="card-body">
                    <form class="card bg-light border mb-4" method="get" action="<?php echo htmlspecialchars($listBase, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small mb-0">Search</label>
                                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Employee no. or name"
                                           value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Employee no.</label>
                                    <input type="text" name="employee_no" class="form-control form-control-sm"
                                           value="<?php echo htmlspecialchars($employeeNo, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Staff name</label>
                                    <input type="text" name="staff_name" class="form-control form-control-sm"
                                           value="<?php echo htmlspecialchars($staffName, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Department</label>
                                    <input type="text" name="department" class="form-control form-control-sm"
                                           value="<?php echo htmlspecialchars($department, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Start date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm"
                                           value="<?php echo htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">End date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm"
                                           value="<?php echo htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small mb-0">Per page</label>
                                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ([10, 25, 50, 100] as $n): ?>
                                            <option value="<?php echo $n; ?>" <?php echo $perPage === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                                </div>
                                <div class="col-md-2">
                                    <a href="<?php echo htmlspecialchars($listBase, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <p class="text-muted small"><?php echo (int) $totalRows; ?> record(s) — page <?php echo (int) $pageNum; ?> of <?php echo (int) $totalPages; ?></p>

                    <div class="table-responsive shadow-sm bg-white rounded border">
                        <table class="table table-striped table-hover table-sm mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Employee no.</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Time</th>
                                <th>Device IP</th>
                                <th>Event</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$rows): ?>
                                <tr><td colspan="7" class="text-center py-4">No records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo (int) $r['attendance_id']; ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['employee_no'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['staff_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['department'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['attendance_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['device_ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['event_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <nav class="mt-3" aria-label="Pagination">
                        <ul class="pagination pagination-sm justify-content-center flex-wrap">
                            <?php if ($pageNum > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?php echo htmlspecialchars($buildLink($pageNum - 1), ENT_QUOTES, 'UTF-8'); ?>">Previous</a></li>
                            <?php endif; ?>
                            <?php
                            $from = max(1, $pageNum - 2);
                            $to = min($totalPages, $pageNum + 2);
                            for ($p = $from; $p <= $to; $p++):
                            ?>
                                <li class="page-item <?php echo $p === $pageNum ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo htmlspecialchars($buildLink($p), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($pageNum < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="<?php echo htmlspecialchars($buildLink($pageNum + 1), ENT_QUOTES, 'UTF-8'); ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
