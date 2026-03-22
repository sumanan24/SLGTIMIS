<?php
declare(strict_types=1);
/** @var array $urls */
/** @var string $reportDate */
/** @var array $rows */
$dailyBase = $urls['daily'];
?>
<div class="container-fluid px-4 py-3">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">
            <?php include BASE_PATH . '/staff_attendance/partials/staff_device_nav.php'; ?>
        </div>
        <div class="col-lg-10 col-md-9">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-day me-2"></i>Daily report</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-2 align-items-end mb-4" action="<?php echo htmlspecialchars($dailyBase, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="col-auto">
                            <label class="form-label small mb-0">Date</label>
                            <input type="date" name="report_date" class="form-control" value="<?php echo htmlspecialchars($reportDate, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Show</button>
                        </div>
                    </form>

                    <p class="text-muted small">First punch as check-in, last punch as check-out (same calendar day). Rows with a resolved staff name only.</p>

                    <div class="table-responsive shadow-sm bg-white rounded border">
                        <table class="table table-striped table-sm mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Employee no.</th>
                                <th>Staff name</th>
                                <th>Check-in (MIN)</th>
                                <th>Check-out (MAX)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$rows): ?>
                                <tr><td colspan="4" class="text-center py-4">No attendance for this date.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $r['employee_no'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['staff_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['check_in'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['check_out'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
