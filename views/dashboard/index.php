<?php
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$n = function ($value) {
    return number_format((int) $value);
};
$yearLabel = !empty($selectedAcademicYear) ? (string) $selectedAcademicYear : 'All academic years';
$statusRows = [
    'Following' => 'Included in the total',
    'Internship' => 'Included in the total',
    'Dropout' => 'Not included',
    'Completed' => 'Not included',
    'Long Absent' => 'Not included',
];
$modeLabel = function ($mode) {
    if ($mode === 'Full') {
        return 'Full time';
    }
    if ($mode === 'Part') {
        return 'Part time';
    }
    return $mode !== '' ? $mode : '—';
};
$statusLabels = array_keys($statusRows);
$statusValues = [];
foreach ($statusLabels as $statusName) {
    $statusValues[] = (int) ($enrollStatusCounts[$statusName] ?? 0);
}
$deptLabels = [];
$deptValues = [];
foreach ($departmentStats ?? [] as $dept) {
    $deptLabels[] = (string) ($dept['name'] ?? $dept['department_name'] ?? '');
    $deptValues[] = (int) ($dept['count'] ?? 0);
}
$chartData = [
    'status' => ['labels' => $statusLabels, 'values' => $statusValues],
    'gender' => ['labels' => array_keys($genderStats ?? []), 'values' => array_map('intval', array_values($genderStats ?? []))],
    'religion' => ['labels' => array_keys($religionStats ?? []), 'values' => array_map('intval', array_values($religionStats ?? []))],
    'department' => ['labels' => $deptLabels, 'values' => $deptValues],
    'district' => ['labels' => array_keys($districtStats ?? []), 'values' => array_map('intval', array_values($districtStats ?? []))],
    'province' => ['labels' => array_keys($provinceStats ?? []), 'values' => array_map('intval', array_values($provinceStats ?? []))],
];
$chartJson = json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$deptChartHeight = max(240, count($deptLabels) * 36);
$districtChartHeight = max(240, count($districtStats ?? []) * 32);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .dash-page { color: #1e293b; }
    .dash-banner {
        background: #0b2545;
        color: #fff;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .dash-banner h1 { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.2rem; }
    .dash-banner p { margin: 0; color: #d6e2ef; font-size: 0.9rem; }
    .dash-year { display: flex; align-items: center; gap: 0.5rem; }
    .dash-year label { margin: 0; font-size: 0.85rem; color: #d6e2ef; white-space: nowrap; }
    .dash-year select {
        min-width: 160px;
        border: 0;
        border-radius: 8px;
        min-height: 40px;
        padding: 0 0.75rem;
        color: #0b2545;
        font-weight: 600;
    }
    .dash-kpis {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .dash-kpi {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1rem;
    }
    .dash-kpi span {
        display: block;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }
    .dash-kpi strong {
        display: block;
        color: #0b2545;
        font-size: 1.55rem;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }
    .dash-kpi em { display: block; margin-top: 0.3rem; color: #64748b; font-size: 0.75rem; font-style: normal; }
    .dash-note { color: #64748b; font-size: 0.85rem; margin: 0 0 1rem; }
    .dash-block {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .dash-block h2 {
        margin: 0;
        padding: 0.75rem 1rem 0.15rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0b2545;
    }
    .dash-block .dash-caption {
        margin: 0;
        padding: 0 1rem 0.65rem;
        color: #64748b;
        font-size: 0.78rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .dash-chart { position: relative; height: 260px; padding: 0.75rem 0.75rem 0.25rem; }
    .dash-chart-tall { height: auto; }
    .dash-block .table { margin: 0; }
    .dash-block .table th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .dash-block .table td { vertical-align: middle; }
    .dash-block .table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
    .dash-block .table tfoot td { background: #f8fafc; font-weight: 700; }
    .dash-dept td { background: #eef3f8; color: #0b2545; font-weight: 700; }
    .dash-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
    .dash-grid .dash-block { margin-bottom: 0; }
    .dash-empty { padding: 1rem; color: #64748b; margin: 0; }
    .dash-more { border-top: 1px solid #e2e8f0; }
    .dash-more summary {
        list-style: none;
        cursor: pointer;
        padding: 0.65rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #0b2545;
        background: #f8fafc;
    }
    .dash-more summary::-webkit-details-marker { display: none; }
    .dash-more summary::before { content: "▸ "; color: #64748b; }
    .dash-more[open] summary::before { content: "▾ "; }
    .dash-more[open] .when-closed, .dash-more:not([open]) .when-open { display: none; }
    @media (max-width: 900px) {
        .dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dash-grid { grid-template-columns: 1fr; }
        .dash-banner { align-items: flex-start; }
        .dash-year { width: 100%; }
        .dash-year select { flex: 1; }
        .dash-chart { height: 220px; }
    }
</style>

<div class="dash-page">
    <?php if (!empty($error)): ?>
        <div class="alert alert-warning" role="alert"><?php echo $h($error); ?></div>
    <?php endif; ?>

    <div class="dash-banner">
        <div>
            <h1>Dashboard</h1>
            <p><?php echo $h($yearLabel); ?> · <?php echo $n($totalStudents ?? 0); ?> students (Following + Internship)</p>
        </div>
        <?php if (!empty($academicYears)): ?>
            <div class="dash-year">
                <label for="academicYearFilter">Academic year</label>
                <select id="academicYearFilter">
                    <?php foreach ($academicYears as $year): ?>
                        <option value="<?php echo $h($year); ?>" <?php echo ($selectedAcademicYear === $year) ? 'selected' : ''; ?>><?php echo $h($year); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="dash-kpis">
        <div class="dash-kpi">
            <span>Students</span>
            <strong><?php echo $n($totalStudents ?? 0); ?></strong>
            <em>Following + Internship</em>
        </div>
        <div class="dash-kpi">
            <span>Following</span>
            <strong><?php echo $n($enrollStatusCounts['Following'] ?? 0); ?></strong>
            <em>In the total</em>
        </div>
        <div class="dash-kpi">
            <span>Internship</span>
            <strong><?php echo $n($enrollStatusCounts['Internship'] ?? 0); ?></strong>
            <em>In the total</em>
        </div>
        <div class="dash-kpi">
            <span>Staff</span>
            <strong><?php echo $n($totalStaff ?? 0); ?></strong>
            <em>All years</em>
        </div>
        <div class="dash-kpi">
            <span>Courses</span>
            <strong><?php echo $n($totalCourses ?? 0); ?></strong>
            <em>All years</em>
        </div>
    </div>
    <p class="dash-note">The student total is Following plus Internship. Dropout, Completed, and Long Absent stay separate. Open a table when you need the figures behind a chart.</p>

    <div class="dash-grid">
        <div class="dash-block">
            <h2>Enrolment status</h2>
            <p class="dash-caption"><?php echo $h($yearLabel); ?></p>
            <div class="dash-chart"><canvas id="statusChart"></canvas></div>
            <details class="dash-more">
                <summary><span class="when-closed">Show table</span><span class="when-open">Hide table</span></summary>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Status</th><th class="num">Students</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusRows as $statusName => $statusNote): ?>
                                <tr>
                                    <td><?php echo $h($statusName); ?></td>
                                    <td class="num"><?php echo $n($enrollStatusCounts[$statusName] ?? 0); ?></td>
                                    <td><?php echo $h($statusNote); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="num"><?php echo $n($totalStudents ?? 0); ?></td>
                                <td>Following + Internship</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </details>
        </div>

        <div class="dash-block">
            <h2>Students by department</h2>
            <p class="dash-caption">Active students who are Following</p>
            <?php if (empty($departmentStats)): ?>
                <p class="dash-empty">No records.</p>
            <?php else: ?>
                <div class="dash-chart dash-chart-tall" style="height: <?php echo (int) $deptChartHeight; ?>px;"><canvas id="departmentChart"></canvas></div>
                <details class="dash-more">
                    <summary><span class="when-closed">Show table</span><span class="when-open">Hide table</span></summary>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Department</th><th class="num">Students</th></tr></thead>
                        <tbody>
                            <?php foreach ($departmentStats as $dept): ?>
                                <tr>
                                    <td><?php echo $h($dept['name'] ?? $dept['department_name'] ?? ''); ?></td>
                                    <td class="num"><?php echo $n($dept['count'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
        </div>
    </div>

    <div class="dash-grid">
        <div class="dash-block">
            <h2>Students by gender</h2>
            <p class="dash-caption">Active students who are Following</p>
            <?php if (empty($genderStats)): ?>
                <p class="dash-empty">No records.</p>
            <?php else: ?>
                <div class="dash-chart"><canvas id="genderChart"></canvas></div>
                <details class="dash-more">
                    <summary><span class="when-closed">Show table</span><span class="when-open">Hide table</span></summary>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Gender</th><th class="num">Students</th></tr></thead>
                        <tbody>
                            <?php foreach ($genderStats as $label => $count): ?>
                                <tr><td><?php echo $h($label); ?></td><td class="num"><?php echo $n($count); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
        </div>
        <div class="dash-block">
            <h2>Students by religion</h2>
            <p class="dash-caption">Active students who are Following</p>
            <?php if (empty($religionStats)): ?>
                <p class="dash-empty">No records.</p>
            <?php else: ?>
                <div class="dash-chart"><canvas id="religionChart"></canvas></div>
                <details class="dash-more">
                    <summary><span class="when-closed">Show table</span><span class="when-open">Hide table</span></summary>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Religion</th><th class="num">Students</th></tr></thead>
                        <tbody>
                            <?php foreach ($religionStats as $label => $count): ?>
                                <tr><td><?php echo $h($label); ?></td><td class="num"><?php echo $n($count); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
        </div>
    </div>

    <div class="dash-grid">
        <div class="dash-block">
            <h2>Top 10 districts</h2>
            <p class="dash-caption">Active students who are Following</p>
            <?php if (empty($districtStats)): ?>
                <p class="dash-empty">No records.</p>
            <?php else: ?>
                <div class="dash-chart dash-chart-tall" style="height: <?php echo (int) $districtChartHeight; ?>px;"><canvas id="districtChart"></canvas></div>
                <details class="dash-more">
                    <summary><span class="when-closed">Show table</span><span class="when-open">Hide table</span></summary>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>District</th><th class="num">Students</th></tr></thead>
                        <tbody>
                            <?php foreach ($districtStats as $label => $count): ?>
                                <tr><td><?php echo $h($label); ?></td><td class="num"><?php echo $n($count); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
        </div>
        <div class="dash-block">
            <h2>Students by province</h2>
            <p class="dash-caption">Active students who are Following</p>
            <?php if (empty($provinceStats)): ?>
                <p class="dash-empty">No records.</p>
            <?php else: ?>
                <div class="dash-chart"><canvas id="provinceChart"></canvas></div>
                <details class="dash-more">
                    <summary><span class="when-closed">Show table</span><span class="when-open">Hide table</span></summary>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Province</th><th class="num">Students</th></tr></thead>
                        <tbody>
                            <?php foreach ($provinceStats as $label => $count): ?>
                                <tr><td><?php echo $h($label); ?></td><td class="num"><?php echo $n($count); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
        </div>
    </div>

    <div class="dash-block">
        <h2>Course enrolment by department</h2>
        <p class="dash-caption">Active students who are Following. Internship is not included.</p>
        <?php if (empty($courseEnrollmentByDepartment)): ?>
            <p class="dash-empty">No course enrolment for this year.</p>
        <?php else: ?>
            <details class="dash-more">
                <summary><span class="when-closed">Show course table</span><span class="when-open">Hide course table</span></summary>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>NVQ</th>
                                <th>Mode</th>
                                <th class="num">Female</th>
                                <th class="num">Male</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseEnrollmentByDepartment as $dept): ?>
                                <?php if (empty($dept['nvq_levels'])) { continue; } ?>
                                <tr class="dash-dept">
                                    <td colspan="5"><?php echo $h($dept['department_name'] ?? ''); ?></td>
                                    <td class="num"><?php echo $n($dept['total_enrollment'] ?? 0); ?></td>
                                </tr>
                                <?php foreach ($dept['nvq_levels'] as $nvqLevel => $courses): ?>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?php echo $h($course['course_name'] ?? ''); ?></td>
                                            <td><?php echo $h($nvqLevel); ?></td>
                                            <td><?php echo $h($modeLabel((string) ($course['course_mode'] ?? ''))); ?></td>
                                            <td class="num"><?php echo $n($course['female_count'] ?? 0); ?></td>
                                            <td class="num"><?php echo $n($course['male_count'] ?? 0); ?></td>
                                            <td class="num"><?php echo $n($course['total_count'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var year = document.getElementById('academicYearFilter');
    if (year) {
        year.addEventListener('change', function () {
            var url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('academic_year', this.value);
            } else {
                url.searchParams.delete('academic_year');
            }
            window.location.href = url.toString();
        });
    }
    if (typeof Chart === 'undefined') {
        return;
    }
    var data = <?php echo $chartJson ?: '{}'; ?>;
    var palette = ['#1e3a5f', '#0f766e', '#b45309', '#be185d', '#64748b', '#1d4ed8', '#7c3aed', '#0e7490', '#a16207'];
    var statusColors = ['#15803d', '#b45309', '#64748b', '#1e3a8a', '#9a3412'];
    Chart.defaults.font.family = 'inherit';
    Chart.defaults.color = '#475569';

    function total(values) {
        return (values || []).reduce(function (sum, value) { return sum + value; }, 0);
    }
    function draw(id, config) {
        var canvas = document.getElementById(id);
        if (!canvas) {
            return;
        }
        var values = config.data.datasets[0].data;
        if (!total(values)) {
            canvas.parentNode.innerHTML = '<p class="dash-empty">No records.</p>';
            return;
        }
        new Chart(canvas, config);
    }
    function doughnut(id, pack, colors) {
        var labels = [];
        var values = [];
        var cols = [];
        (pack.labels || []).forEach(function (label, index) {
            if ((pack.values[index] || 0) > 0) {
                labels.push(label);
                values.push(pack.values[index]);
                cols.push(colors[index] || palette[index % palette.length]);
            }
        });
        draw(id, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: cols, borderWidth: 2, borderColor: '#ffffff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } }
            }
        });
    }
    function bars(id, pack, horizontal) {
        draw(id, {
            type: 'bar',
            data: {
                labels: pack.labels,
                datasets: [{ data: pack.values, backgroundColor: '#1e4b7a', borderRadius: 4, maxBarThickness: 22 }]
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { precision: 0 } },
                    y: { beginAtZero: true, grid: { display: horizontal ? false : true, color: '#e2e8f0' } }
                }
            }
        });
    }

    doughnut('statusChart', data.status, statusColors);
    bars('departmentChart', data.department, true);
    doughnut('genderChart', data.gender, ['#1e3a5f', '#be185d', '#0f766e', '#64748b']);
    bars('religionChart', data.religion, false);
    bars('districtChart', data.district, true);
    if ((data.province.labels || []).length > 8) {
        bars('provinceChart', data.province, true);
    } else {
        doughnut('provinceChart', data.province, palette);
    }
})();
</script>
