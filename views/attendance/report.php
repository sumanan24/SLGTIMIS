<?php
$isFIN = !empty($isFIN);
$isHOD = !empty($isHOD);
$isAdmin = !empty($isAdmin);
$canLockMonth = !empty($canLockMonth) || $isHOD;
$isMonthLocked = !empty($isMonthLocked);
$reportData = $reportData ?? [];
$allDays = $allDays ?? [];
$summary = $summary ?? [];
$monthLabel = !empty($selectedMonth) ? date('F Y', strtotime($selectedMonth . '-01')) : '';
$exportQuery = http_build_query(array_filter([
    'department_id' => $selectedDepartment ?? '',
    'course_id' => $selectedCourse ?? '',
    'academic_year' => $selectedAcademicYear ?? '',
    'month' => $selectedMonth ?? ''
], static function ($v) {
    return $v !== '' && $v !== null;
}));
$canExport = $isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth);
?>
<style>
.att-page { width: 100%; }
.att-page .att-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .75rem 1rem;
    align-items: end;
}
.att-page .att-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
}
.att-page .att-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}
.att-page .att-stat {
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    padding: .75rem;
    text-align: center;
    background: #fff;
}
.att-page .att-stat .lbl { color: #6c757d; font-size: .75rem; margin-bottom: .2rem; }
.att-page .att-stat .val { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
.att-page .att-table-wrap {
    overflow: auto;
    max-height: 72vh;
    border: 1px solid #dee2e6;
    border-radius: .5rem;
}
.att-page .att-table {
    width: max-content;
    min-width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .8rem;
}
.att-page .att-table th,
.att-page .att-table td {
    vertical-align: middle;
    padding: .3rem .35rem;
    white-space: nowrap;
    border: 1px solid #dee2e6;
}
.att-page .att-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #212529;
    color: #fff;
    text-align: center;
    font-weight: 600;
}
.att-page .att-table .c-no { min-width: 36px; text-align: center; }
.att-page .att-table .c-id { min-width: 118px; }
.att-page .att-table .c-name { min-width: 170px; }
.att-page .att-table .c-nic { min-width: 105px; }
.att-page .att-table .c-day {
    min-width: 34px;
    width: 34px;
    text-align: center;
    font-weight: 700;
    padding: .2rem .1rem;
}
.att-page .att-table .c-day .d-n { display: block; font-size: .68rem; line-height: 1.1; }
.att-page .att-table .c-day .d-w { display: block; font-size: .6rem; opacity: .85; font-weight: 500; }
.att-page .att-table .c-tot,
.att-page .att-table .c-p,
.att-page .att-table .c-pct { min-width: 58px; text-align: center; font-weight: 700; }
.att-page .att-table .c-allow { min-width: 86px; text-align: right; font-weight: 700; }
.att-page .att-table .sticky {
    position: sticky;
    z-index: 4;
    background: #fff;
}
.att-page .att-table thead .sticky { z-index: 6; background: #212529; }
.att-page .att-table .sticky.c-no { left: 0; }
.att-page .att-table .sticky.c-id { left: 36px; }
.att-page .att-table .sticky.c-name { left: 154px; }
.att-page .att-table .sticky.c-nic { left: 324px; box-shadow: 2px 0 0 rgba(0,0,0,.06); }
.att-page .att-table .m-p { background: #198754; color: #fff; }
.att-page .att-table .m-a { background: #dc3545; color: #fff; }
.att-page .att-table .m-h { background: #ffc107; color: #212529; }
.att-page .att-table .m-l { background: #ffe5b4; color: #212529; }
.att-page .att-table tbody tr:nth-child(even) .sticky { background: #f8f9fa; }
.att-page .att-table tfoot td {
    background: #e9ecef;
    font-weight: 700;
    position: sticky;
    bottom: 0;
    z-index: 3;
}
.att-page .att-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem 1rem;
    margin-top: .75rem;
    font-size: .8rem;
    color: #6c757d;
}
.att-page .att-legend .sw {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.4rem;
    height: 1.25rem;
    padding: 0 .3rem;
    border-radius: .25rem;
    font-weight: 700;
    font-size: .72rem;
    margin-right: .3rem;
}
@media (max-width: 991.98px) {
    .att-page .att-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media print {
    .no-print, form, .btn, .alert { display: none !important; }
    .card, .card-header, .card-body { border: none !important; box-shadow: none !important; }
    .att-page .att-table-wrap { max-height: none; overflow: visible; }
    .att-page .att-table { font-size: 9px; }
}
</style>

<div class="container-fluid px-3 px-lg-4 py-3 att-page">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-calendar-check me-2"></i>
                        <?php echo $isFIN ? 'Attendance Summary' : 'Attendance Report'; ?>
                    </h5>
                    <?php if ($monthLabel !== ''): ?>
                        <div class="text-muted small mt-1"><?php echo htmlspecialchars($monthLabel); ?><?php echo $isMonthLocked ? ' · Locked' : ''; ?></div>
                    <?php endif; ?>
                </div>
                <div class="att-actions no-print">
                    <?php if ($isMonthLocked): ?>
                        <span class="badge bg-danger align-self-center px-3 py-2"><i class="fas fa-lock me-1"></i>Locked</span>
                    <?php endif; ?>
                    <?php if (!empty($reportData)): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print();">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    <?php endif; ?>
                    <?php if ($canExport): ?>
                        <a class="btn btn-success btn-sm" href="<?php echo APP_URL; ?>/attendance/export-report?<?php echo htmlspecialchars($exportQuery); ?>">
                            <i class="fas fa-file-excel me-1"></i>Download Excel
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($reportData) && !empty($canLockMonth) && !$isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                        <button type="button" class="btn btn-warning btn-sm" id="lockMonthBtn"
                                data-department-id="<?php echo htmlspecialchars($selectedDepartment); ?>"
                                data-month="<?php echo htmlspecialchars($selectedMonth); ?>">
                            <i class="fas fa-lock me-1"></i>Lock Month
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($reportData) && $isAdmin && $isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                        <button type="button" class="btn btn-outline-success btn-sm" id="unlockMonthBtn"
                                data-department-id="<?php echo htmlspecialchars($selectedDepartment); ?>"
                                data-month="<?php echo htmlspecialchars($selectedMonth); ?>">
                            <i class="fas fa-unlock me-1"></i>Unlock
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="<?php echo APP_URL; ?>/attendance/report" class="mb-3 no-print">
                <input type="hidden" name="view" value="summary">
                <div class="att-filters">
                    <?php if (!$isHOD): ?>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="department_id">Department</label>
                        <select class="form-select form-select-sm" id="department_id" name="department_id">
                            <option value="">All</option>
                            <?php foreach (($departments ?? []) as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_id']); ?>"
                                    <?php echo ($selectedDepartment ?? '') == $dept['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($selectedDepartment ?? ''); ?>">
                    <?php endif; ?>

                    <div>
                        <label class="form-label small fw-semibold mb-1" for="course_id">Course</label>
                        <select class="form-select form-select-sm" id="course_id" name="course_id">
                            <option value="">All</option>
                            <?php foreach (($courses ?? []) as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                    <?php echo ($selectedCourse ?? '') == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold mb-1" for="academic_year">Academic Year</label>
                        <select class="form-select form-select-sm" id="academic_year" name="academic_year">
                            <option value="">All</option>
                            <?php foreach (($academicYears ?? []) as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"
                                    <?php echo ($selectedAcademicYear ?? '') === $year ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold mb-1" for="month">Month</label>
                        <input type="month" class="form-control form-control-sm" id="month" name="month"
                               value="<?php echo htmlspecialchars($selectedMonth ?? date('Y-m')); ?>" required>
                    </div>

                    <?php if (!$isFIN): ?>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="source">Source</label>
                        <select class="form-select form-select-sm" id="source" name="source">
                            <option value="manual" <?php echo ($source ?? 'manual') === 'manual' ? 'selected' : ''; ?>>Manual</option>
                            <option value="finger" <?php echo ($source ?? '') === 'finger' ? 'selected' : ''; ?>>Finger</option>
                            <option value="both" <?php echo ($source ?? '') === 'both' ? 'selected' : ''; ?>>Both</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1 d-block">Eligible</label>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="eligible_only" name="eligible_only" value="1"
                                   <?php echo !empty($eligibleOnly) ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="eligible_only">Eligible only</label>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="source" value="manual">
                    <?php endif; ?>
                </div>

                <div class="att-actions mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>View Report
                    </button>
                    <a href="<?php echo APP_URL; ?>/attendance/report" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success py-2"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($isFIN && !$isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                <div class="alert alert-info py-2">
                    This month is not locked yet. Finance can view the report after HOD lock.
                </div>
            <?php endif; ?>

            <?php if (!empty($reportData)): ?>
                <div class="att-stats mb-3">
                    <div class="att-stat">
                        <div class="lbl">Students</div>
                        <div class="val"><?php echo (int)($summary['total_students'] ?? 0); ?></div>
                    </div>
                    <div class="att-stat">
                        <div class="lbl">≥ 90%</div>
                        <div class="val text-success"><?php echo (int)($summary['above_90'] ?? 0); ?></div>
                    </div>
                    <div class="att-stat">
                        <div class="lbl">80% – 89%</div>
                        <div class="val text-warning"><?php echo (int)($summary['above_80'] ?? 0); ?></div>
                    </div>
                    <div class="att-stat">
                        <div class="lbl">Total allowance</div>
                        <div class="val" style="font-size:1.05rem;">Rs. <?php echo number_format($summary['total_allowance'] ?? 0, 0); ?></div>
                    </div>
                </div>

                <div class="att-table-wrap" id="attendance-summary-document">
                    <table class="att-table">
                        <thead>
                            <tr>
                                <th class="sticky c-no">#</th>
                                <th class="sticky c-id">Student ID</th>
                                <th class="sticky c-name">Full name</th>
                                <th class="sticky c-nic">NIC</th>
                                <?php foreach ($allDays as $day): ?>
                                    <th class="c-day" title="<?php echo htmlspecialchars(($day['date'] ?? '') . ' ' . ($day['day_name'] ?? '')); ?>">
                                        <span class="d-n"><?php echo htmlspecialchars($day['day'] ?? ''); ?></span>
                                        <?php if (!empty($day['day_name'])): ?>
                                            <span class="d-w"><?php echo htmlspecialchars(substr($day['day_name'], 0, 3)); ?></span>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="c-tot">Days</th>
                                <th class="c-p">P</th>
                                <th class="c-pct">%</th>
                                <th class="c-allow">Allowance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNo = 0; foreach ($reportData as $student): $rowNo++;
                                $workingDays = (int)($student['effective_working_days'] ?? 0);
                                $presentDays = (int)($student['present_days'] ?? 0);
                                $pct = (float)($student['attendance_percentage'] ?? 0);
                                $badge = $pct >= 90 ? 'bg-success' : ($pct >= 80 ? 'bg-warning text-dark' : 'bg-danger');
                            ?>
                            <tr>
                                <td class="sticky c-no"><?php echo $rowNo; ?></td>
                                <td class="sticky c-id"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td class="sticky c-name"><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                <td class="sticky c-nic"><?php echo htmlspecialchars($student['student_nic']); ?></td>
                                <?php foreach ($allDays as $day):
                                    $status = $student['day_by_day'][$day['date']] ?? '';
                                    $cls = '';
                                    $val = '';
                                    if ($status === 'P') { $cls = 'm-p'; $val = '1'; }
                                    elseif ($status === 'A') { $cls = 'm-a'; $val = '0'; }
                                    elseif ($status === 'H') { $cls = 'm-h'; $val = '-1'; }
                                    elseif ($status === 'L') { $cls = 'm-l'; $val = 'L'; }
                                ?>
                                    <td class="c-day <?php echo $cls; ?>"><?php echo $val; ?></td>
                                <?php endforeach; ?>
                                <td class="c-tot"><?php echo $workingDays; ?></td>
                                <td class="c-p"><?php echo $presentDays; ?></td>
                                <td class="c-pct"><span class="badge <?php echo $badge; ?>"><?php echo number_format($pct, 1); ?>%</span></td>
                                <td class="c-allow">Rs. <?php echo number_format($student['allowance'] ?? 0, 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="sticky c-no"></td>
                                <td class="sticky c-id"></td>
                                <td class="sticky c-name text-end">Total</td>
                                <td class="sticky c-nic"></td>
                                <?php foreach ($allDays as $day): ?>
                                    <td class="c-day"></td>
                                <?php endforeach; ?>
                                <td class="c-tot"><?php echo (int)($summary['total_effective_working_days'] ?? 0); ?></td>
                                <td class="c-p"><?php echo (int)($summary['total_present'] ?? 0); ?></td>
                                <td class="c-pct">—</td>
                                <td class="c-allow">Rs. <?php echo number_format($summary['total_allowance'] ?? 0, 0); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="att-legend">
                    <span><span class="sw m-p">1</span>Present</span>
                    <span><span class="sw m-a">0</span>Absent</span>
                    <span><span class="sw m-h">-1</span>Holiday</span>
                    <span><span class="sw m-l">L</span>Late</span>
                    <span>% = Present ÷ Working days</span>
                </div>
            <?php else: ?>
                <div class="alert alert-light border text-center mb-0">
                    <?php if ($isFIN && $isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                        No eligible students for this selection.
                    <?php else: ?>
                        Select filters and click <strong>View Report</strong>.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var departmentSelect = document.getElementById('department_id');
    var courseSelect = document.getElementById('course_id');
    if (departmentSelect && courseSelect) {
        departmentSelect.addEventListener('change', function () {
            var departmentId = this.value;
            if (!departmentId) {
                courseSelect.innerHTML = '<option value="">All</option>';
                return;
            }
            fetch('<?php echo APP_URL; ?>/courses/get-by-department?department_id=' + encodeURIComponent(departmentId))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    courseSelect.innerHTML = '<option value="">All</option>';
                    if (data.success && data.courses) {
                        data.courses.forEach(function (course) {
                            var option = document.createElement('option');
                            option.value = course.course_id;
                            option.textContent = course.course_name;
                            courseSelect.appendChild(option);
                        });
                    }
                })
                .catch(function () {
                    courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                });
        });
    }

    function postLock(url, btn, confirmMsg) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (!confirm(confirmMsg)) return;
            var body = 'department_id=' + encodeURIComponent(btn.dataset.departmentId)
                + '&month=' + encodeURIComponent(btn.dataset.month);
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                alert(data.message || (data.success ? 'Done' : 'Failed'));
                if (data.success) location.reload();
            })
            .catch(function () { alert('Request failed.'); });
        });
    }

    postLock('<?php echo APP_URL; ?>/attendance/lock-month', document.getElementById('lockMonthBtn'),
        'Lock attendance for this month?');
    postLock('<?php echo APP_URL; ?>/attendance/unlock-month', document.getElementById('unlockMonthBtn'),
        'Unlock this month so attendance can be edited again?');
});
</script>
