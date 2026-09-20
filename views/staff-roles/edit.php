<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$money = static function ($value) {
    return number_format((float) $value, 2);
};
$activeGrade = isset($activeGrade) ? (int) $activeGrade : 3;
$salaryGrades = $salaryGrades ?? [];
$roleStaff = $roleStaff ?? [];
$salaryGradeLabels = $salaryGradeLabels ?? [3 => 'Grade 3', 2 => 'Grade 2', 1 => 'Grade 1'];
$salaryGradesList = $salaryGradesList ?? [3, 2, 1];
$roleId = $role['staff_position_type_id'];
$editBase = APP_URL . '/staff-roles/edit?id=' . urlencode($roleId);
$bundle = $salaryGrades[$activeGrade] ?? [];
$scale = $bundle['scale'] ?? null;
$revisions = $bundle['revisions'] ?? [];
$projection = $bundle['projection'] ?? [];
$allowanceItems = $bundle['allowance_items'] ?? StaffRoleSalaryModel::emptyAllowanceLines();
$incrementSlabs = $bundle['incrementSlabs'] ?? ($bundle['increment_slabs'] ?? StaffRoleSalaryModel::emptySlabs());
$basicVal = $scale['basic_salary'] ?? '0.00';
$allowTotal = 0;
foreach ($allowanceItems as $allowItem) {
    $allowTotal += (float) ($allowItem['allowance_amount'] ?? 0);
}
$maxVal = $scale['max_basic_salary'] ?? '0.00';
$grossVal = $scale ? $money($scale['gross_salary']) : $money((float) $basicVal + $allowTotal);
$effVal = $scale['effective_date'] ?? date('Y-m-d');
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff-role-salary.css?v=<?php echo time(); ?>">

<div class="container-fluid px-0 srs-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-edit me-2"></i>Edit Staff Role
                <span class="badge bg-light text-dark ms-2 align-middle"><?php echo $h($roleId); ?></span>
            </h5>
            <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-sm btn-light">Back</a>
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

            <form method="POST" action="<?php echo $h($editBase); ?>" class="srs-role-grid">
                <input type="hidden" name="form_section" value="role">
                <div>
                    <label class="form-label" for="role_id_display">Role ID</label>
                    <input type="text" class="form-control" id="role_id_display" value="<?php echo $h($roleId); ?>" disabled>
                </div>
                <div>
                    <label class="form-label" for="staff_position_type_name">Role Name</label>
                    <input type="text" class="form-control" id="staff_position_type_name" name="staff_position_type_name"
                           value="<?php echo $h($role['staff_position_type_name']); ?>" maxlength="64" required>
                </div>
                <div>
                    <label class="form-label" for="staff_position">Level</label>
                    <input type="number" class="form-control" id="staff_position" name="staff_position"
                           value="<?php echo $h($role['staff_position']); ?>" min="1" required>
                </div>
                <div>
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>

            <div class="srs-divider"></div>

            <div class="srs-section-label">Salary structure — <?php echo $h($role['staff_position_type_name']); ?></div>
            <nav class="srs-tabs" aria-label="Salary grade">
                <?php foreach ($salaryGradesList as $grade): ?>
                    <a href="<?php echo $h($editBase . '&grade=' . (int) $grade); ?>"
                       class="<?php echo $activeGrade === (int) $grade ? 'active' : ''; ?>">
                        <?php echo $h($salaryGradeLabels[$grade] ?? ('Grade ' . $grade)); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/salary/save" class="srs-salary-form">
                <input type="hidden" name="staff_position_type_id" value="<?php echo $h($roleId); ?>">
                <input type="hidden" name="grade" value="<?php echo (int) $activeGrade; ?>">

                <div class="srs-metrics">
                    <div>
                        <label class="form-label" for="basic_salary">Basic</label>
                        <input type="number" step="0.01" min="0" class="form-control srs-basic"
                               id="basic_salary" name="basic_salary" value="<?php echo $h($basicVal); ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Allowances</label>
                        <div class="srs-readonly srs-allow-total">Rs. <?php echo $h($money($allowTotal)); ?></div>
                    </div>
                    <div>
                        <label class="form-label">Gross</label>
                        <div class="srs-readonly srs-gross">Rs. <?php echo $h($grossVal); ?></div>
                    </div>
                    <div>
                        <label class="form-label" for="max_basic_salary">Max Basic</label>
                        <input type="number" step="0.01" min="0" class="form-control srs-max"
                               id="max_basic_salary" name="max_basic_salary" value="<?php echo $h($maxVal); ?>">
                    </div>
                </div>

                <div class="srs-meta">
                    <div>
                        <label class="form-label" for="effective_date">Effective Date</label>
                        <input type="date" class="form-control" id="effective_date" name="effective_date"
                               value="<?php echo $h($effVal); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="remarks">Remarks</label>
                        <input type="text" class="form-control" id="remarks" name="remarks" maxlength="255"
                               placeholder="Revision note">
                    </div>
                </div>

                <div class="srs-split">
                    <div class="srs-panel">
                        <div class="srs-panel-title">Allowances</div>
                        <div class="srs-allow-grid srs-grid-head">
                            <span>Name</span>
                            <span class="text-end">Amount</span>
                        </div>
                        <?php for ($allowOrder = 1; $allowOrder <= 3; $allowOrder++): ?>
                            <?php $allowRow = $allowanceItems[$allowOrder] ?? ['allowance_name' => '', 'allowance_amount' => 0]; ?>
                            <div class="srs-allow-grid">
                                <input type="text" class="form-control form-control-sm"
                                       name="allowance_name[<?php echo $allowOrder; ?>]"
                                       placeholder="<?php echo $allowOrder === 3 ? 'Optional allowance' : 'Allowance ' . $allowOrder; ?>"
                                       maxlength="100"
                                       value="<?php echo $h($allowRow['allowance_name'] ?? ''); ?>"
                                       <?php echo $allowOrder === 3 ? '' : 'required'; ?>>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm srs-allow-amt"
                                       name="allowance_amount[<?php echo $allowOrder; ?>]"
                                       value="<?php echo $h($allowRow['allowance_amount'] ?? '0.00'); ?>">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="srs-panel">
                        <div class="srs-panel-title">Year increment</div>
                        <div class="srs-slab-grid srs-grid-head">
                            <span>Years</span>
                            <span class="text-end">Amount</span>
                            <span>Applies to</span>
                        </div>
                        <?php
                        $slabCursor = 1;
                        for ($slabOrder = 1; $slabOrder <= 3; $slabOrder++):
                            $slab = $incrementSlabs[$slabOrder] ?? ['years' => 0, 'increment_amount' => 0];
                            $slabYears = (int) ($slab['years'] ?? 0);
                            $slabFrom = $slabYears > 0 ? $slabCursor : 0;
                            $slabTo = $slabYears > 0 ? ($slabCursor + $slabYears - 1) : 0;
                            if ($slabYears > 0) {
                                $slabCursor += $slabYears;
                            }
                        ?>
                        <div class="srs-slab-grid srs-slab-row">
                            <input type="number" min="0" max="18" class="form-control form-control-sm srs-slab-years"
                                   name="slab_years[<?php echo $slabOrder; ?>]"
                                   placeholder="<?php echo $slabOrder === 1 ? '10' : ($slabOrder === 2 ? '8' : 'optional'); ?>"
                                   value="<?php echo $slabYears > 0 ? $slabYears : ''; ?>">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm srs-slab-amount"
                                   name="slab_amount[<?php echo $slabOrder; ?>]"
                                   placeholder="600"
                                   value="<?php echo $h($slab['increment_amount'] ?? '0.00'); ?>">
                            <div class="srs-range">
                                <span class="srs-slab-range-text"><?php
                                    echo $slabYears > 0 ? 'Years ' . $slabFrom . '–' . min(18, $slabTo) : '—';
                                ?></span>
                            </div>
                        </div>
                        <?php endfor; ?>
                        <div class="srs-slab-note">
                            Covered <strong class="srs-slab-covered">0</strong>/18 years
                            <span class="srs-slab-remain"></span>
                        </div>
                    </div>
                </div>

                <div class="srs-actions">
                    <span class="text-muted small">Same amount applies to the years in each row. Total up to 18 years.</span>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Grade <?php echo (int) $activeGrade; ?>
                    </button>
                </div>

                <div class="srs-details-row">
                    <details class="srs-details">
                        <summary>Projection</summary>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered srs-table srs-projection">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th class="text-end">Increment</th>
                                        <th class="text-end">Basic</th>
                                        <th class="text-end">Allowance</th>
                                        <th class="text-end">Gross</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($year = 0; $year <= 18; $year++): ?>
                                        <?php $row = $projection[$year] ?? ['increment' => 0, 'basic' => 0, 'allowance' => 0, 'gross' => 0]; ?>
                                        <tr data-year="<?php echo $year; ?>">
                                            <td><?php echo $year === 0 ? 'Entry' : $year; ?></td>
                                            <td class="text-end proj-inc"><?php echo $year === 0 ? '—' : $money($row['increment']); ?></td>
                                            <td class="text-end proj-basic"><?php echo $money($row['basic']); ?></td>
                                            <td class="text-end proj-allow"><?php echo $money($row['allowance']); ?></td>
                                            <td class="text-end proj-gross"><?php echo $money($row['gross']); ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                    <details class="srs-details">
                        <summary>Revision history<?php echo empty($revisions) ? '' : ' (' . count($revisions) . ')'; ?></summary>
                        <?php if (empty($revisions)): ?>
                            <p class="text-muted small mb-0">No revisions yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm srs-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Old Basic / Allow / Gross</th>
                                            <th class="text-end">New Basic / Allow / Gross</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revisions as $rev): ?>
                                            <tr>
                                                <td><?php echo $h($rev['effective_date']); ?></td>
                                                <td class="text-end"><?php echo $money($rev['old_basic']) . ' / ' . $money($rev['old_allowance']) . ' / ' . $money($rev['old_gross']); ?></td>
                                                <td class="text-end"><?php echo $money($rev['new_basic']) . ' / ' . $money($rev['new_allowance']) . ' / ' . $money($rev['new_gross']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </details>
                </div>
            </form>

            <div class="srs-divider"></div>

            <div class="srs-staff-head">
                <h6 class="fw-bold">Assigned staff</h6>
                <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/salary/apply-staff" class="m-0">
                    <input type="hidden" name="staff_position_type_id" value="<?php echo $h($roleId); ?>">
                    <input type="hidden" name="redirect_grade" value="<?php echo (int) $activeGrade; ?>">
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Apply calculated salaries to assigned staff?');">Apply</button>
                </form>
            </div>
            <?php if (empty($roleStaff)): ?>
                <p class="text-muted mb-0">No staff assigned to this role.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle srs-table srs-staff-table">
                        <colgroup>
                            <col class="col-staff">
                            <col class="col-join">
                            <col class="col-year">
                            <col class="col-grade">
                            <col class="col-money">
                            <col class="col-money">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Join</th>
                                <th class="text-center">Year</th>
                                <th>Grade</th>
                                <th class="text-end">Basic</th>
                                <th class="text-end">Gross</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roleStaff as $member): ?>
                                <?php $calc = $member['calculation'] ?? []; ?>
                                <tr>
                                    <td class="col-staff" title="<?php echo $h($member['staff_name']); ?>"><?php echo $h($member['staff_name']); ?></td>
                                    <td><?php echo $h($member['staff_date_of_join'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo (int) ($member['service_year'] ?? 0); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/salary/assign-grade">
                                            <input type="hidden" name="staff_position_type_id" value="<?php echo $h($roleId); ?>">
                                            <input type="hidden" name="staff_id" value="<?php echo $h($member['staff_id']); ?>">
                                            <input type="hidden" name="grade" value="<?php echo (int) $activeGrade; ?>">
                                            <select name="salary_grade" class="form-select form-select-sm srs-grade-select" onchange="this.form.submit()">
                                                <option value="">Select</option>
                                                <?php foreach ($salaryGradesList as $g): ?>
                                                    <option value="<?php echo (int) $g; ?>" <?php echo ((int) ($member['salary_grade'] ?? 0) === (int) $g) ? 'selected' : ''; ?>>
                                                        <?php echo $h($salaryGradeLabels[$g] ?? ('Grade ' . $g)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-end"><?php echo !empty($calc['has_scale']) ? $money($calc['basic']) : '—'; ?></td>
                                    <td class="text-end"><?php echo !empty($calc['has_scale']) ? $money($calc['gross']) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    function money(n) {
        return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function num(el) {
        var v = parseFloat(el && el.value ? el.value : '0');
        return isNaN(v) ? 0 : v;
    }
    function intVal(el) {
        var v = parseInt(el && el.value ? el.value : '0', 10);
        return isNaN(v) ? 0 : v;
    }
    function incrementsFromSlabs(form) {
        var incs = {};
        var year = 1;
        form.querySelectorAll('.srs-slab-row').forEach(function (row) {
            var years = intVal(row.querySelector('.srs-slab-years'));
            var amt = num(row.querySelector('.srs-slab-amount'));
            var from = year;
            for (var i = 0; i < years && year <= 18; i++, year++) incs[year] = amt;
            var range = row.querySelector('.srs-slab-range-text');
            if (range) range.textContent = years > 0 ? 'Years ' + from + '–' + Math.min(18, from + years - 1) : '—';
        });
        var covered = Math.min(18, year - 1);
        var coveredEl = form.querySelector('.srs-slab-covered');
        var remainEl = form.querySelector('.srs-slab-remain');
        if (coveredEl) coveredEl.textContent = String(covered);
        if (remainEl) remainEl.textContent = covered >= 18 ? '' : ' · ' + (18 - covered) + ' remaining';
        return incs;
    }
    document.querySelectorAll('.srs-salary-form').forEach(function (form) {
        function recalc() {
            var basic = num(form.querySelector('.srs-basic'));
            var allow = 0;
            form.querySelectorAll('.srs-allow-amt').forEach(function (el) { allow += num(el); });
            var max = num(form.querySelector('.srs-max'));
            var allowBox = form.querySelector('.srs-allow-total');
            var grossBox = form.querySelector('.srs-gross');
            if (allowBox) allowBox.textContent = 'Rs. ' + money(allow);
            if (grossBox) grossBox.textContent = 'Rs. ' + money(basic + allow);
            var incs = incrementsFromSlabs(form);
            var running = basic;
            form.querySelectorAll('.srs-projection tbody tr').forEach(function (row) {
                var year = parseInt(row.getAttribute('data-year'), 10);
                var inc = year === 0 ? 0 : (incs[year] || 0);
                running = year === 0 ? basic : running + inc;
                if (year > 0 && max > 0 && running > max) running = max;
                var incCell = row.querySelector('.proj-inc');
                var basicCell = row.querySelector('.proj-basic');
                var allowCell = row.querySelector('.proj-allow');
                var grossCell = row.querySelector('.proj-gross');
                if (incCell) incCell.textContent = year === 0 ? '—' : money(inc);
                if (basicCell) basicCell.textContent = money(running);
                if (allowCell) allowCell.textContent = money(allow);
                if (grossCell) grossCell.textContent = money(running + allow);
            });
        }
        form.addEventListener('input', recalc);
        recalc();
    });
})();
</script>
