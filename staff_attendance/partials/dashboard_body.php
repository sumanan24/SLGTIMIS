<?php
declare(strict_types=1);
/** @var string $dash_form_action */
/** @var array{device: string, list: string, daily: string, sync: string} $urls */
/** @var bool $embed_main_layout */
/** @var string $employeeNo */
/** @var array $employees */
/** @var array $grouped */
/** @var string|null $dbError */

if (!isset($embed_main_layout)) {
    $embed_main_layout = false;
}
$embed_main_layout = (bool) $embed_main_layout;
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

<form class="card shadow-sm mb-4" method="get" action="<?php echo attendance_escape($dash_form_action); ?>" id="dashFilter">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-8 col-lg-6">
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
                <a href="<?php echo attendance_escape($urls['device']); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>

<h2 class="h5 mb-3">Daily summary</h2>
<div class="table-responsive shadow-sm bg-white rounded">
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
        <?php if (!$grouped): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">No attendance in this range<?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.</td></tr>
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
