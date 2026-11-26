<?php
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$typeBadge = ['permanent' => '#0f766e', 'acting' => '#b45309', 'cover_up' => '#475569'];
$dashUrl = APP_URL . '/hod/dashboard';
$yearQs = !empty($selectedAcademicYear) ? '&academic_year=' . urlencode((string) $selectedAcademicYear) : '';
$deptId = (string) ($department['department_id'] ?? $department_id ?? '');
$deptName = (string) ($department['department_name'] ?? 'Department');
$my = $my ?? ['staff_id' => '', 'modules' => [], 'timetable' => [], 'programs' => [], 'tickets' => [], 'ticket_total' => 0];
$dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
$today = (int) date('N');
$humanize = static function ($v) {
    return ucwords(str_replace('_', ' ', (string) $v));
};
$prioClass = ['critical' => 'danger', 'urgent' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info text-dark', 'low' => 'secondary'];
$hasMultiDept = (!empty($assignments) && count($assignments) > 1) || !empty($ownDepartment);
$status = $studentStatusCounts ?? [];
$kpiStudents = (int) ($status['total']['total'] ?? $totalStudents ?? 0);
$kpiFollowing = (int) ($status['following']['total'] ?? 0);
$kpiInternship = (int) ($status['internship']['total'] ?? 0);
$kpiCompleted = (int) ($status['completed']['total'] ?? 0);
$kpiStaff = (int) ($totalStaff ?? 0);
$kpiCourses = (int) ($totalCourses ?? 0);

$attMonth = (string) ($attendanceMonth ?? date('Y-m'));
$attMonthLabel = date('M Y', strtotime($attMonth . '-01'));
$attLocked = !empty($attendanceMonthLocked);
$attBase = 'department_id=' . urlencode($deptId)
    . (!empty($selectedAcademicYear) ? '&academic_year=' . urlencode((string) $selectedAcademicYear) : '')
    . '&month=' . urlencode($attMonth);
$markUrl = APP_URL . '/attendance?' . $attBase;
$reportUrl = APP_URL . '/attendance/report?' . $attBase . '&view=summary';

$showNvq = $deptId !== '' && !empty($nvqStatsByDepartment) && isset($nvqStatsByDepartment[$deptId]);
$showGender = $deptId !== '' && !empty($genderStats);
$showEnrollment = $deptId !== '' && !empty($courseEnrollmentByDepartment) && isset($courseEnrollmentByDepartment[$deptId]);
$enrollMap = [];
if ($showEnrollment) {
    foreach (($courseEnrollmentByDepartment[$deptId]['courses'] ?? []) as $cRow) {
        $enrollMap[(string) ($cRow['course_id'] ?? '')] = (int) ($cRow['enrollment_count'] ?? 0);
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.hod {
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e2e8f0;
    --soft: #f1f5f9;
    --navy: #003366;
    --deep: #001f3f;
    --card: #ffffff;
    --ok: #0f766e;
    --warn: #b45309;
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.25rem 1.25rem 2.5rem;
    color: var(--ink);
}
.hod * { box-sizing: border-box; }

.hod-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.hod-hero h1 {
    margin: 0 0 .25rem;
    font-size: clamp(1.25rem, 2.4vw, 1.55rem);
    font-weight: 700;
    color: var(--deep);
    line-height: 1.25;
}
.hod-hero .sub {
    margin: 0;
    color: var(--muted);
    font-size: .9rem;
}
.hod-hero .year-box {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: .45rem .65rem;
}
.hod-hero .year-box label {
    margin: 0;
    font-size: .8rem;
    font-weight: 600;
    color: var(--muted);
    white-space: nowrap;
}
.hod-hero .year-box select {
    border: 0;
    background: transparent;
    font-weight: 600;
    color: var(--ink);
    min-width: 140px;
    padding: 0;
    box-shadow: none;
}

.hod-switch {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin: -.35rem 0 1rem;
}
.hod-switch a,
.hod-switch span {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .3rem .65rem;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: var(--card);
    font-size: .78rem;
    color: var(--ink);
    text-decoration: none;
}
.hod-switch .on {
    border-color: var(--navy);
    background: #e8eef6;
    font-weight: 600;
}
.hod-chip {
    display: inline-block;
    padding: .1rem .4rem;
    border-radius: 999px;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
}

.hod-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: .75rem;
    margin-bottom: 1.15rem;
}
.hod-kpi {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: .9rem 1rem;
    min-width: 0;
}
.hod-kpi .l {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: .2rem;
}
.hod-kpi .v {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--deep);
    line-height: 1.1;
}
.hod-kpi.accent-teal .v { color: var(--ok); }
.hod-kpi.accent-amber .v { color: var(--warn); }
.hod-kpi.accent-slate .v { color: #475569; }

.hod-quick {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: .6rem;
    margin-bottom: 1.15rem;
}
.hod-quick a {
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .75rem .85rem;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 10px;
    text-decoration: none;
    color: var(--ink);
    font-weight: 600;
    font-size: .86rem;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.hod-quick a:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
    color: var(--deep);
}
.hod-quick a.disabled {
    opacity: .55;
    pointer-events: none;
}
.hod-quick .qi {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--soft);
    color: var(--navy);
    flex-shrink: 0;
}
.hod-quick a.ok .qi { background: #ccfbf1; color: var(--ok); }
.hod-quick a.warn .qi { background: #ffedd5; color: var(--warn); }

.hod-sec {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;
}
.hod-sec-h {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .8rem 1rem;
    border-bottom: 1px solid var(--line);
    background: #fafbfc;
}
.hod-sec-h h2 {
    margin: 0;
    font-size: .95rem;
    font-weight: 700;
    color: var(--deep);
}
.hod-sec-h .meta {
    font-size: .78rem;
    color: var(--muted);
    font-weight: 500;
}
.hod-sec-b { padding: 0; }
.hod-sec-b.pad { padding: 1rem; }

.hod .table { margin: 0; }
.hod .table th {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--muted);
    font-weight: 700;
    background: #f8fafc;
    border-bottom-color: var(--line);
    white-space: nowrap;
}
.hod .table td {
    font-size: .875rem;
    vertical-align: middle;
    border-color: #f1f5f9;
}
.hod .table a { color: var(--navy); text-decoration: none; font-weight: 600; }
.hod .table a:hover { text-decoration: underline; }
.hod .num { text-align: right; font-variant-numeric: tabular-nums; }
.hod .num.strong { font-weight: 700; color: var(--navy); }
.hod .empty {
    margin: 0;
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--muted);
    font-size: .9rem;
}
.hod .scroll { max-height: 280px; overflow: auto; }
.hod .list-group-item {
    font-size: .875rem;
    border-color: #f1f5f9;
}
.hod-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.hod-grid-2 > .hod-sec { margin-bottom: 0; height: 100%; display: flex; flex-direction: column; }
.hod-grid-2 > .hod-sec .hod-sec-b { flex: 1; }
.hod-chart {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .75rem 1rem 1rem;
}
.hod-chart canvas { max-height: 240px; }

@media (max-width: 1199.98px) {
    .hod-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
    .hod-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .hod { padding: .85rem .7rem 1.75rem; }
    .hod-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hod-hero .year-box { width: 100%; }
    .hod-hero .year-box select { flex: 1; min-width: 0; font-size: 1rem; min-height: 40px; }
    .hod-quick { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 420px) {
    .hod-kpis { grid-template-columns: 1fr 1fr; }
    .hod-kpi .v { font-size: 1.3rem; }
}
</style>

<div class="hod">
    <div class="hod-hero">
        <div>
            <h1><?php echo $e($deptName); ?></h1>
            <p class="sub">
                Department dashboard
                <?php if (!empty($selectedAcademicYear)): ?>
                    · <?php echo $e($selectedAcademicYear); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="year-box">
            <label for="academicYearFilter">Academic year</label>
            <select id="academicYearFilter" class="form-select form-select-sm">
                <option value="">All years</option>
                <?php foreach (($academicYears ?? []) as $year): ?>
                    <option value="<?php echo $e($year); ?>" <?php echo ($selectedAcademicYear === $year) ? 'selected' : ''; ?>>
                        <?php echo $e($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 mb-3"><?php echo $e($error); ?></div>
    <?php endif; ?>

    <?php if ($hasMultiDept): ?>
    <div class="hod-switch">
        <?php foreach (($assignments ?? []) as $a): ?>
            <?php if (!empty($a['active'])): ?>
                <span class="on">
                    <?php echo $e($a['department_name']); ?>
                    <?php if (($a['head_type_label'] ?? '') !== ''): ?>
                        <span class="hod-chip" style="background:<?php echo $typeBadge[$a['head_type']] ?? '#475569'; ?>;"><?php echo $e($a['head_type_label']); ?></span>
                    <?php endif; ?>
                </span>
            <?php else: ?>
                <a href="<?php echo $dashUrl . '?department_id=' . urlencode($a['department_id']) . $yearQs; ?>">
                    <?php echo $e($a['department_name']); ?>
                    <?php if (($a['head_type_label'] ?? '') !== ''): ?>
                        <span class="hod-chip" style="background:<?php echo $typeBadge[$a['head_type']] ?? '#475569'; ?>;"><?php echo $e($a['head_type_label']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($ownDepartment)): ?>
            <?php if (!empty($ownDepartment['active'])): ?>
                <span class="on"><?php echo $e($ownDepartment['department_name']); ?> · Mine</span>
            <?php else: ?>
                <a href="<?php echo $dashUrl . '?department_id=' . urlencode($ownDepartment['department_id']) . $yearQs; ?>">
                    <?php echo $e($ownDepartment['department_name']); ?> · Mine
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($department)): ?>
    <div class="hod-kpis">
        <div class="hod-kpi">
            <div class="l">Students</div>
            <div class="v"><?php echo number_format($kpiStudents); ?></div>
        </div>
        <div class="hod-kpi accent-teal">
            <div class="l">Following</div>
            <div class="v"><?php echo number_format($kpiFollowing); ?></div>
        </div>
        <div class="hod-kpi accent-amber">
            <div class="l">Internship</div>
            <div class="v"><?php echo number_format($kpiInternship); ?></div>
        </div>
        <div class="hod-kpi accent-slate">
            <div class="l">Completed</div>
            <div class="v"><?php echo number_format($kpiCompleted); ?></div>
        </div>
        <div class="hod-kpi">
            <div class="l">Staff</div>
            <div class="v"><?php echo number_format($kpiStaff); ?></div>
        </div>
        <div class="hod-kpi">
            <div class="l">Courses</div>
            <div class="v"><?php echo number_format($kpiCourses); ?></div>
        </div>
    </div>

    <div class="hod-quick">
        <?php if (!empty($canMarkAttendance)): ?>
        <a class="<?php echo $attLocked ? 'disabled warn' : 'ok'; ?>"
           href="<?php echo $attLocked ? '#' : $e($markUrl); ?>"
           <?php echo $attLocked ? 'aria-disabled="true"' : ''; ?>>
            <span class="qi"><i class="fas fa-clipboard-check"></i></span>
            <?php echo $attLocked ? 'Attendance locked' : 'Mark attendance'; ?>
        </a>
        <?php endif; ?>
        <?php if (!empty($canViewAttendanceReport)): ?>
        <a href="<?php echo $e($reportUrl); ?>">
            <span class="qi"><i class="fas fa-chart-bar"></i></span>
            Attendance report
        </a>
        <?php endif; ?>
        <a href="<?php echo APP_URL; ?>/students?department_id=<?php echo urlencode($deptId); ?><?php echo !empty($selectedAcademicYear) ? '&academic_year=' . urlencode((string) $selectedAcademicYear) : ''; ?>">
            <span class="qi"><i class="fas fa-user-graduate"></i></span>
            Students
        </a>
        <a href="<?php echo APP_URL; ?>/courses?department_id=<?php echo urlencode($deptId); ?>">
            <span class="qi"><i class="fas fa-book"></i></span>
            Courses
        </a>
        <a href="<?php echo APP_URL; ?>/modules?department_id=<?php echo urlencode($deptId); ?>">
            <span class="qi"><i class="fas fa-cubes"></i></span>
            Modules
        </a>
        <a href="<?php echo APP_URL; ?>/groups">
            <span class="qi"><i class="fas fa-users"></i></span>
            Groups
        </a>
        <?php if (!empty($canManageCourses)): ?>
        <a href="<?php echo APP_URL; ?>/hod/staff-module-enroll">
            <span class="qi"><i class="fas fa-user-cog"></i></span>
            Staff enroll
        </a>
        <?php endif; ?>
        <a href="<?php echo APP_URL; ?>/departments/view?id=<?php echo urlencode($deptId); ?>">
            <span class="qi"><i class="fas fa-building"></i></span>
            Dept info
        </a>
    </div>
    <?php endif; ?>

    <?php if (!empty($department) && !empty($studentCountsByYear)): ?>
    <section class="hod-sec">
        <div class="hod-sec-h">
            <h2>Students by academic year</h2>
            <span class="meta">Following + Internship = Students</span>
        </div>
        <div class="hod-sec-b">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th class="num">Students</th>
                            <th class="num">Following</th>
                            <th class="num">Internship</th>
                            <th class="num">Completed</th>
                            <th class="num">Full</th>
                            <th class="num">Part</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentCountsByYear as $yr): ?>
                        <?php $sel = !empty($selectedAcademicYear) && $selectedAcademicYear === ($yr['academic_year'] ?? ''); ?>
                        <tr class="<?php echo $sel ? 'table-primary' : ''; ?>">
                            <td>
                                <a href="<?php echo $dashUrl . '?academic_year=' . urlencode($yr['academic_year']); ?>">
                                    <?php echo $e($yr['academic_year']); ?>
                                </a>
                            </td>
                            <td class="num strong"><?php echo number_format($yr['total']); ?></td>
                            <td class="num"><?php echo number_format($yr['following']); ?></td>
                            <td class="num"><?php echo number_format($yr['internship']); ?></td>
                            <td class="num"><?php echo number_format($yr['completed']); ?></td>
                            <td class="num"><?php echo number_format($yr['full']); ?></td>
                            <td class="num"><?php echo number_format($yr['part']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($department)): ?>
    <section class="hod-sec">
        <div class="hod-sec-h">
            <h2>Courses</h2>
            <span class="meta"><?php echo number_format(count($courseModuleRows ?? [])); ?> courses<?php echo !empty($selectedAcademicYear) ? ' · enrollment for ' . $e($selectedAcademicYear) : ''; ?></span>
        </div>
        <div class="hod-sec-b">
            <?php if (!empty($courseModuleRows)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>NVQ</th>
                            <th>Status</th>
                            <th class="num">Modules</th>
                            <th class="num">Groups</th>
                            <th class="num">Students</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseModuleRows as $cr): ?>
                        <?php
                            $cid = (string) ($cr['course_id'] ?? '');
                            $enrollCount = $enrollMap[$cid] ?? null;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $e($cr['course_name']); ?></strong>
                                <div class="small text-muted"><?php echo $e($cid); ?></div>
                            </td>
                            <td><?php echo ($cr['course_nvq_level'] ?? '') !== '' ? 'L' . $e($cr['course_nvq_level']) : '—'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($cr['course_status'] ?? '') === 'active' ? 'success' : (($cr['course_status'] ?? '') === 'draft' ? 'warning text-dark' : 'secondary'); ?>">
                                    <?php echo $e(ucfirst((string) ($cr['course_status'] ?? ''))); ?>
                                </span>
                            </td>
                            <td class="num"><?php echo (int) ($cr['modules'] ?? 0); ?></td>
                            <td class="num"><?php echo (int) ($cr['groups'] ?? 0); ?></td>
                            <td class="num strong"><?php echo $enrollCount === null ? '—' : number_format($enrollCount); ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo APP_URL; ?>/modules?course_id=<?php echo urlencode($cid); ?>">Modules</a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?php echo APP_URL; ?>/courses/manage?id=<?php echo urlencode($cid); ?>">Manage</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="empty">No courses in this department.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($my['staff_id'])): ?>
    <div class="hod-grid-2">
        <section class="hod-sec">
            <div class="hod-sec-h">
                <h2>My modules</h2>
                <span class="meta"><?php echo count($my['modules']); ?></span>
            </div>
            <div class="hod-sec-b">
                <?php if (empty($my['modules'])): ?>
                    <p class="empty">No modules assigned.</p>
                <?php else: ?>
                <div class="scroll table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Module</th><th>Course</th></tr></thead>
                        <tbody>
                            <?php foreach ($my['modules'] as $mm): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $e($mm['module_name'] ?? $mm['module_id']); ?></strong>
                                    <div class="small text-muted"><?php echo $e($mm['module_id']); ?></div>
                                </td>
                                <td><?php echo $e($mm['course_name'] ?? $mm['course_id']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="hod-sec">
            <div class="hod-sec-h">
                <h2>My timetable</h2>
                <span class="meta"><?php echo count($my['timetable']); ?></span>
            </div>
            <div class="hod-sec-b">
                <?php if (empty($my['timetable'])): ?>
                    <p class="empty">No classes scheduled.</p>
                <?php else: ?>
                <div class="scroll table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Day</th><th>P</th><th>Group</th><th>Subject</th><th>Room</th></tr></thead>
                        <tbody>
                            <?php foreach ($my['timetable'] as $tt): ?>
                            <tr class="<?php echo (int) $tt['weekday'] === $today ? 'table-primary' : ''; ?>">
                                <td>
                                    <?php echo $e($dayNames[(int) $tt['weekday']] ?? ''); ?>
                                    <?php if ((int) $tt['weekday'] === $today): ?><span class="badge bg-primary">Today</span><?php endif; ?>
                                </td>
                                <td><?php echo $e(str_replace('P', '', (string) $tt['period'])); ?></td>
                                <td><a href="<?php echo APP_URL; ?>/groups/timetable?id=<?php echo (int) $tt['group_id']; ?>"><?php echo $e($tt['group_name'] ?? '-'); ?></a></td>
                                <td><?php echo $e(($tt['module_name'] ?? '') !== '' ? $tt['module_name'] : ($tt['subject'] ?? '-')); ?></td>
                                <td><?php echo $e($tt['classroom'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="hod-sec">
            <div class="hod-sec-h">
                <h2>Programmes</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo APP_URL; ?>/circuit-program">All</a>
            </div>
            <div class="hod-sec-b">
                <?php if (empty($my['programs'])): ?>
                    <p class="empty">No upcoming programmes.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($my['programs'] as $pg): ?>
                    <li class="list-group-item d-flex justify-content-between gap-2">
                        <a href="<?php echo APP_URL; ?>/circuit-program/view?id=<?php echo (int) $pg['program_id']; ?>">
                            <?php echo $e($pg['destination']); ?>
                        </a>
                        <span class="text-end">
                            <span class="small d-block"><?php echo $e(date('d M Y', strtotime($pg['date']))); ?></span>
                            <span class="badge bg-<?php echo ($pg['status'] ?? '') === 'approved' ? 'success' : 'warning text-dark'; ?>">
                                <?php echo $e($humanize($pg['status'] ?? 'pending')); ?>
                            </span>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="hod-sec">
            <div class="hod-sec-h">
                <h2>Tickets<?php echo !empty($my['ticket_total']) ? ' · ' . (int) $my['ticket_total'] : ''; ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo APP_URL; ?>/facilities/tasks">All</a>
            </div>
            <div class="hod-sec-b">
                <?php if (empty($my['tickets'])): ?>
                    <p class="empty">No open tickets.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($my['tickets'] as $tk): ?>
                    <li class="list-group-item d-flex justify-content-between gap-2">
                        <div>
                            <a href="<?php echo APP_URL; ?>/facilities/view?id=<?php echo (int) $tk['id']; ?>">
                                <?php echo $e($tk['ticket_number']); ?>
                            </a>
                            <span class="ms-1"><?php echo $e($tk['title']); ?></span>
                        </div>
                        <span class="badge bg-<?php echo $prioClass[$tk['current_priority'] ?? ''] ?? 'secondary'; ?>">
                            <?php echo $e($humanize($tk['current_priority'] ?? '')); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <?php if ($showNvq || $showGender): ?>
    <div class="hod-grid-2">
        <?php if ($showNvq): ?>
        <?php
            $deptNvq = $nvqStatsByDepartment[$deptId];
            $n04 = (int) ($deptNvq['levels']['04'] ?? 0);
            $n05 = (int) ($deptNvq['levels']['05'] ?? 0);
            $n06 = (int) ($deptNvq['levels']['06'] ?? 0);
        ?>
        <section class="hod-sec">
            <div class="hod-sec-h">
                <h2>NVQ levels</h2>
                <span class="meta">Total <?php echo number_format($n04 + $n05 + $n06); ?></span>
            </div>
            <div class="hod-sec-b">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Level</th><th class="num">Students</th></tr></thead>
                    <tbody>
                        <tr><td>NVQ 04</td><td class="num strong"><?php echo number_format($n04); ?></td></tr>
                        <tr><td>NVQ 05</td><td class="num strong"><?php echo number_format($n05); ?></td></tr>
                        <tr><td>NVQ 06</td><td class="num strong"><?php echo number_format($n06); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showGender): ?>
        <section class="hod-sec">
            <div class="hod-sec-h"><h2>Gender</h2></div>
            <div class="hod-sec-b">
                <div class="hod-chart">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('academicYearFilter').addEventListener('change', function () {
    const url = new URL(window.location.href);
    if (this.value) {
        url.searchParams.set('academic_year', this.value);
    } else {
        url.searchParams.delete('academic_year');
    }
    window.location.href = url.toString();
});

<?php if ($showGender): ?>
(function () {
    const el = document.getElementById('genderChart');
    if (!el || typeof Chart === 'undefined') return;
    new Chart(el.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($genderStats)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values(array_map('intval', $genderStats))); ?>,
                backgroundColor: ['rgba(0,31,63,.88)', 'rgba(0,51,102,.88)', 'rgba(71,85,105,.88)'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 12 } } }
            }
        }
    });
})();
<?php endif; ?>
</script>
