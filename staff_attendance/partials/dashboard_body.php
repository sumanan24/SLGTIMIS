<?php
declare(strict_types=1);
/** @var string $dash_form_action */
/** @var array{device: string, list: string, daily: string, sync: string} $urls */
/** @var bool $embed_main_layout */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string $employeeNo */
/** @var string $todayYmd */
/** @var array $employees */
/** @var array $grouped */
/** @var int $rangePunches */
/** @var int $todayCount */
/** @var int $distinctEmployees */
/** @var string|null $dbError */

if (!isset($embed_main_layout)) {
    $embed_main_layout = false;
}
$embed_main_layout = (bool) $embed_main_layout;
?>
<?php if (!$embed_main_layout): ?>
<h1 class="h3 mb-3">Dashboard</h1>
<?php endif; ?>

<p class="text-muted small mb-3">
    All times use <strong>Sri Lanka (Asia/Colombo, UTC+5:30)</strong>. Today: <?php echo attendance_escape($todayYmd); ?>.
    <?php if (STAFF_ATT_DASHBOARD_AUTO_SYNC): ?>
        <?php $dashIv = defined('STAFF_DASHBOARD_AUTO_SYNC_INTERVAL') ? (string) STAFF_DASHBOARD_AUTO_SYNC_INTERVAL : 'P0D'; ?>
        The table loads from the <strong>database first</strong>; then a quick device sync runs for <code><?php echo attendance_escape($dashIv); ?></code> (today only, Asia/Colombo) so load stays fast. For a full week or more, use <a href="<?php echo attendance_escape($urls['sync']); ?>">Device sync</a> (<code><?php echo attendance_escape(defined('STAFF_ATTENDANCE_SYNC_DEFAULT_INTERVAL') ? STAFF_ATTENDANCE_SYNC_DEFAULT_INTERVAL : 'P6D'); ?></code> default).
    <?php else: ?>
        Auto-sync on open is off. Use <a href="<?php echo attendance_escape($urls['sync']); ?>">Device sync</a> from a PC on the same LAN as the Hikvision (or set <code>STAFF_ATT_DASHBOARD_AUTO_SYNC</code> in config when PHP can reach the device).
    <?php endif; ?>
    The employee list only shows people with at least one punch in the <strong>selected date range</strong>.
</p>

<?php if ($dbError !== null): ?>
    <div class="alert alert-danger">
        <strong>Database error.</strong> Import <code>staff_attendance/database.sql</code> if the table is missing.
        <br><small class="text-break"><?php echo attendance_escape($dbError); ?></small>
    </div>
<?php else: ?>

<form class="card shadow-sm mb-4" method="get" action="<?php echo attendance_escape($dash_form_action); ?>" id="dashFilter">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo attendance_escape($dateFrom); ?>">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-0">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo attendance_escape($dateTo); ?>">
            </div>
            <div class="col-md-6 col-lg-4">
                <label class="form-label small mb-0">Employee</label>
                <select name="employee_no" class="form-select form-select-sm">
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
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </div>
            <div class="col-auto">
                <a href="<?php echo attendance_escape($urls['device']); ?>" class="btn btn-outline-secondary btn-sm">Reset to today</a>
            </div>
        </div>
        <p class="text-muted small mb-0 mt-2">
            Summary is grouped by <strong>employee</strong> and <strong>calendar day</strong>.
            Check-in = first punch, check-out = last punch; other device times that day are listed in between.
        </p>
    </div>
</form>

<div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm border-0 bg-white h-100">
            <div class="card-body">
                <div class="text-muted small">Punches in selected range</div>
                <div class="display-6"><?php echo (int) $rangePunches; ?></div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm border-0 bg-white h-100">
            <div class="card-body">
                <div class="text-muted small">Today's punches (calendar day)</div>
                <div class="display-6"><?php echo (int) $todayCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm border-0 bg-white h-100">
            <div class="card-body">
                <div class="text-muted small">Distinct employees in range</div>
                <div class="display-6"><?php echo (int) $distinctEmployees; ?></div>
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mb-3">Daily summary (check-in / check-out / other times)</h2>
<div class="table-responsive shadow-sm bg-white rounded">
    <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
        <tr>
            <th>Employee no.</th>
            <th>Name</th>
            <th>Department</th>
            <th>Date</th>
            <th>Day</th>
            <th>Check-in <span class="text-muted fw-normal">(min)</span></th>
            <th>Check-out <span class="text-muted fw-normal">(max)</span></th>
            <th>Other times</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$grouped): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">No attendance in this range<?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.</td></tr>
        <?php else: ?>
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
                    <td><?php echo attendance_escape((string) ($r['department'] ?? '')); ?></td>
                    <td><span class="text-nowrap"><?php echo attendance_escape($d); ?></span></td>
                    <td><?php echo attendance_escape($dayLabel); ?></td>
                    <td><code><?php echo attendance_escape($split['in']); ?></code></td>
                    <td><code><?php echo attendance_escape($split['out']); ?></code></td>
                    <td class="small"><?php echo $otherStr === '—' ? '—' : attendance_escape($otherStr); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
