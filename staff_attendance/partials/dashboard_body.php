<?php
declare(strict_types=1);
/** @var string $dash_form_action */
/** @var array{device: string, list: string, daily: string, month: string, sync: string} $urls */
/** @var bool $embed_main_layout */
/** @var string $employeeNo */
/** @var array $employees */
/** @var array $grouped */
/** @var string|null $dbError */
/** @var string $todayYmd */
/** @var string $dateFrom */
/** @var string $dateTo */

if (!isset($embed_main_layout)) {
    $embed_main_layout = false;
}
$embed_main_layout = (bool) $embed_main_layout;

$todayYmd = isset($todayYmd) ? (string) $todayYmd : '';
$dateFrom = isset($dateFrom) ? (string) $dateFrom : '';
$dateTo = isset($dateTo) ? (string) $dateTo : '';
$employeeNo = isset($employeeNo) ? (string) $employeeNo : '';

$multiDayRange = ($dateFrom !== '' && $dateTo !== '' && $dateFrom !== $dateTo);

$todayTs = $todayYmd !== '' ? strtotime($todayYmd . ' 12:00:00') : false;
$todayPretty = ($todayTs !== false) ? date('F j, Y', $todayTs) : '';
$todayWeekday = ($todayTs !== false) ? date('l', $todayTs) : '';

$todayRows = [];
foreach ($grouped ?? [] as $r) {
    if ((string) ($r['d'] ?? '') === $todayYmd) {
        $todayRows[] = $r;
    }
}

$reportMonth = $todayYmd !== '' ? substr($todayYmd, 0, 7) : date('Y-m');
$monthQuery = ['report_month' => $reportMonth];
if ($employeeNo !== '') {
    $monthQuery['employee_no'] = $employeeNo;
}
$monthReportHref = $urls['month'] . '?' . http_build_query($monthQuery, '', '&', PHP_QUERY_RFC3986);
?>
<?php if (!$embed_main_layout): ?>
<h1 class="h3 mb-3">Dashboard</h1>
<?php endif; ?>

<?php if ($dbError !== null): ?>
    <div class="alert alert-danger">
        <strong>Database error.</strong> Import <code>staff_attendance/database.sql</code> if the table is missing.
        <br><small class="text-break"><?php echo attendance_escape($dbError); ?></small>
    </div>
<?php else: ?>

<div class="border rounded-3 bg-light p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-sm-row flex-sm-wrap align-items-sm-center justify-content-between gap-2">
        <div>
            <div class="text-uppercase small text-body-secondary fw-semibold">Today</div>
            <div class="h4 mb-0 fw-bold text-primary"><?php echo attendance_escape($todayWeekday); ?></div>
            <div class="text-body-secondary"><?php echo attendance_escape($todayPretty); ?></div>
        </div>
        <?php if ($multiDayRange): ?>
            <div class="small text-muted">
                Range: <span class="text-dark"><?php echo attendance_escape($dateFrom); ?></span>
                → <span class="text-dark"><?php echo attendance_escape($dateTo); ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<form class="card shadow-sm mb-4 staff-device-filter-form" method="get" action="<?php echo attendance_escape($dash_form_action); ?>" id="dashFilter">
    <div class="card-body py-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg min-w-0">
                <label class="form-label small mb-0 text-body-secondary" for="dashFilterEmployee">Employee</label>
                <div class="staff-device-ts-wrap">
                <select id="dashFilterEmployee" name="employee_no" class="form-select form-select-sm js-employee-select-search" aria-label="Employee filter">
                    <option value="">All employees</option>
                    <?php foreach ($employees as $em): ?>
                        <?php
                        $eno = (string) $em['employee_no'];
                        $sn = trim((string) ($em['staff_name'] ?? ''));
                        $label = $sn !== '' ? $sn . ' (' . $eno . ')' : $eno;
                        ?>
                        <option value="<?php echo attendance_escape($eno); ?>" <?php echo $employeeNo === $eno ? 'selected' : ''; ?>>
                            <?php echo attendance_escape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>
            </div>
            <div class="col-12 col-sm-auto d-grid d-sm-flex flex-sm-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="<?php echo attendance_escape($urls['device']); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>

<h2 class="h5 mb-3"><i class="fas fa-user-clock me-2 text-primary"></i>Today’s attendance</h2>
<?php if (!$todayRows): ?>
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>No punches recorded for <?php echo attendance_escape($todayPretty !== '' ? $todayPretty : 'today'); ?><?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
        <?php foreach ($todayRows as $r): ?>
            <?php
            $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
            $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';
            $eno = (string) ($r['employee_no'] ?? '');
            $name = trim((string) ($r['staff_name'] ?? ''));
            ?>
            <div class="col">
                <div class="card h-100 border shadow-sm staff-device-today-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <h3 class="h6 card-title mb-1 text-truncate" title="<?php echo attendance_escape($name !== '' ? $name : $eno); ?>">
                                    <?php echo attendance_escape($name !== '' ? $name : '—'); ?>
                                </h3>
                                <p class="text-muted small mb-0">No. <?php echo attendance_escape($eno); ?></p>
                            </div>
                            <span class="badge rounded-pill bg-light text-primary border small"><?php echo attendance_escape($todayWeekday); ?></span>
                        </div>
                        <dl class="row small mb-0 g-1">
                            <dt class="col-5 text-body-secondary">Date</dt>
                            <dd class="col-7 mb-0"><span class="text-nowrap"><?php echo attendance_escape($todayYmd); ?></span></dd>
                            <dt class="col-5 text-body-secondary">Check-in</dt>
                            <dd class="col-7 mb-0"><code class="small"><?php echo attendance_escape($split['in']); ?></code></dd>
                            <dt class="col-5 text-body-secondary">Check-out</dt>
                            <dd class="col-7 mb-0"><code class="small"><?php echo attendance_escape($split['out']); ?></code></dd>
                            <?php if ($otherStr !== '—'): ?>
                            <dt class="col-5 text-body-secondary">Other</dt>
                            <dd class="col-7 mb-0 small text-break"><?php echo attendance_escape($otherStr); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($multiDayRange && !empty($grouped)): ?>
<h2 class="h5 mb-3"><i class="fas fa-table me-2 text-primary"></i>All days in range</h2>
<div class="table-responsive shadow-sm bg-white rounded mb-4">
    <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
        <tr>
            <th>Employee no.</th>
            <th>Name</th>
            <th>Date</th>
            <th>Day</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Other times</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($grouped as $r): ?>
            <?php
            $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
            $d = (string) $r['d'];
            $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
            $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';
            ?>
            <tr>
                <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                <td><span class="text-nowrap"><?php echo attendance_escape($d); ?></span></td>
                <td><?php echo attendance_escape($dayLabel); ?></td>
                <td><code><?php echo attendance_escape($split['in']); ?></code></td>
                <td><code><?php echo attendance_escape($split['out']); ?></code></td>
                <td class="small"><?php echo $otherStr === '—' ? '—' : attendance_escape($otherStr); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="d-grid d-sm-flex flex-sm-wrap gap-2 justify-content-sm-between align-items-sm-center pt-2 border-top">
    <p class="small text-muted mb-0 order-2 order-sm-1">Open the full calendar month view, PDF export, and filters.</p>
    <a href="<?php echo attendance_escape($monthReportHref); ?>" class="btn btn-primary order-1 order-sm-2">
        <i class="fas fa-calendar-alt me-2"></i>Show month report
    </a>
</div>

<?php endif; ?>
