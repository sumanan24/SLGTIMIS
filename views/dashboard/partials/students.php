<?php
/**
 * Dashboard section: students by enrolment status and by academic year.
 * Uses $h, $n, $jsonAttr, $statusColors, $statusNotes, $nvqLabel (defined below) from views/dashboard/index.php.
 */
$yearRows = $yearWiseStudents ?? [];
$nvqLabel = function ($level) {
    return $level === 'BRI' ? 'Bridging' : ($level !== '' ? 'NVQ ' . $level : '—');
};

$maxYearTotal = 0;
foreach ($yearRows as $yr) {
    $maxYearTotal = max($maxYearTotal, (int) $yr['total']);
}

// Status donut (all five statuses)
$statusLabels = array_keys($statusColors);
$statusValues = [];
foreach ($statusLabels as $s) {
    $statusValues[] = (int) ($enrollStatusCounts[$s] ?? 0);
}

// Stacked bars: oldest -> newest, only years that have students
$chartYears = [];
foreach (array_reverse($yearRows) as $yr) {
    if (((int) $yr['total'] + (int) $yr['completed']) > 0) {
        $chartYears[] = $yr;
    }
}
$yearChart = [
    'type' => 'bar',
    'horizontal' => false,
    'stacked' => true,
    'labels' => array_map(function ($y) { return (string) $y['academic_year']; }, $chartYears),
    'datasets' => [
        ['label' => 'Following', 'data' => array_map(function ($y) { return (int) $y['following']; }, $chartYears), 'color' => $statusColors['Following']],
        ['label' => 'Internship', 'data' => array_map(function ($y) { return (int) $y['internship']; }, $chartYears), 'color' => $statusColors['Internship']],
        ['label' => 'Completed', 'data' => array_map(function ($y) { return (int) $y['completed']; }, $chartYears), 'color' => $statusColors['Completed']],
    ],
];
$dash = '<span class="dh-muted">–</span>';
?>
<section class="dh-section tone-blue" id="sec-students">
    <div class="dh-sec-head">
        <span class="dh-sec-icon"><i class="fas fa-user-graduate"></i></span>
        <div>
            <h2>Students</h2>
            <p>Enrolment status and year-wise numbers</p>
        </div>
        <a class="dh-link" href="<?php echo APP_URL; ?>/students">Open students list <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="dh-grid-2 wide-left">
        <div class="dh-card">
            <div class="dh-card-head"><h3>Enrolment status</h3><span>All academic years</span></div>
            <div class="dh-card-body">
                <div class="dh-donut-wrap">
                    <div class="dh-chart" style="height:220px"><canvas role="img" aria-label="Students by enrolment status" data-chart="<?php echo $jsonAttr(['type' => 'doughnut', 'labels' => $statusLabels, 'values' => $statusValues, 'colors' => array_values($statusColors)]); ?>"></canvas></div>
                    <ul class="dh-legend">
                        <?php foreach ($statusLabels as $i => $s): ?>
                            <li>
                                <span class="dot" style="background:<?php echo $h($statusColors[$s]); ?>"></span>
                                <span><span class="name"><?php echo $h($s); ?></span><br><span class="note"><?php echo $h($statusNotes[$s]); ?></span></span>
                                <span class="val"><?php echo $n($statusValues[$i]); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="dh-card">
            <div class="dh-card-head"><h3>Students per academic year</h3><span>Following · Internship · Completed</span></div>
            <div class="dh-card-body">
                <?php if (empty($chartYears)): ?>
                    <p class="dh-empty"><i class="fas fa-chart-column"></i>No students recorded yet.</p>
                <?php else: ?>
                    <div class="dh-chart"><canvas role="img" aria-label="Students per academic year" data-chart="<?php echo $jsonAttr($yearChart); ?>"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dh-card">
        <div class="dh-card-head">
            <h3>Year-wise details</h3>
            <span>Newest first · <strong>Total</strong> = Following + Internship · click a year for departments and courses</span>
        </div>
        <?php if (empty($yearRows)): ?>
            <p class="dh-empty"><i class="fas fa-calendar"></i>No academic years found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="dh-table">
                    <thead>
                        <tr>
                            <th>Academic year</th>
                            <th class="num"><span class="th-dot" style="background:<?php echo $statusColors['Following']; ?>"></span>Following</th>
                            <th class="num"><span class="th-dot" style="background:<?php echo $statusColors['Internship']; ?>"></span>Internship</th>
                            <th class="num">Total</th>
                            <th class="num"><span class="th-dot" style="background:#4f46e5"></span>Full time</th>
                            <th class="num"><span class="th-dot" style="background:#0891b2"></span>Part time</th>
                            <th class="num"><span class="th-dot" style="background:<?php echo $statusColors['Completed']; ?>"></span>Completed</th>
                            <th class="num">Female</th>
                            <th class="num">Male</th>
                            <th style="min-width:110px">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($yearRows as $i => $yr): ?>
                        <?php
                            $hasData = ((int) $yr['total'] + (int) $yr['completed']) > 0;
                            $hasCurrent = (int) $yr['total'] > 0;
                            $collapseId = 'dhYear' . $i;
                            $pct = $maxYearTotal > 0 ? round(((int) $yr['total']) / $maxYearTotal * 100) : 0;
                        ?>
                        <tr class="dh-year-row <?php echo $hasData ? 'is-clickable' : ''; ?>">
                            <td>
                                <button type="button" class="dh-toggle"
                                    <?php if ($hasData): ?>data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>"<?php else: ?>disabled<?php endif; ?>>
                                    <?php echo $h($yr['academic_year']); ?>
                                </button>
                            </td>
                            <td class="num"><?php echo $yr['following'] > 0 ? $n($yr['following']) : $dash; ?></td>
                            <td class="num"><?php echo $yr['internship'] > 0 ? $n($yr['internship']) : $dash; ?></td>
                            <td class="num"><span class="dh-pill tone-blue"><?php echo $n($yr['total']); ?></span></td>
                            <td class="num"><?php echo $hasCurrent && $yr['full_time'] > 0 ? '<span class="dh-pill tone-indigo">' . $n($yr['full_time']) . '</span>' : $dash; ?></td>
                            <td class="num"><?php echo $hasCurrent && $yr['part_time'] > 0 ? '<span class="dh-pill tone-cyan">' . $n($yr['part_time']) . '</span>' : $dash; ?></td>
                            <td class="num"><?php echo $yr['completed'] > 0 ? $n($yr['completed']) : $dash; ?></td>
                            <td class="num"><?php echo $hasCurrent ? $n($yr['female']) : $dash; ?></td>
                            <td class="num"><?php echo $hasCurrent ? $n($yr['male']) : $dash; ?></td>
                            <td><div class="dh-bar" title="<?php echo (int) $pct; ?>% of the largest year"><span style="width: <?php echo (int) $pct; ?>%"></span></div></td>
                        </tr>
                        <?php if ($hasData): ?>
                        <tr>
                            <td colspan="10" class="p-0 border-0">
                                <div class="collapse" id="<?php echo $collapseId; ?>">
                                    <div class="dh-detail">
                                        <div class="dh-table-wrap table-responsive">
                                            <table class="dh-table">
                                                <thead>
                                                    <tr>
                                                        <th>Department / Course</th>
                                                        <th>Level</th>
                                                        <th class="num">Following</th>
                                                        <th class="num">Internship</th>
                                                        <th class="num">Total</th>
                                                        <th class="num">Full time</th>
                                                        <th class="num">Part time</th>
                                                        <th class="num">Completed</th>
                                                        <th class="num">Female</th>
                                                        <th class="num">Male</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($yr['departments'] as $dept): ?>
                                                    <tr class="dh-dept">
                                                        <td colspan="2"><i class="fas fa-building me-2"></i><?php echo $h($dept['department_name']); ?></td>
                                                        <td class="num"><?php echo $n($dept['following']); ?></td>
                                                        <td class="num"><?php echo $n($dept['internship']); ?></td>
                                                        <td class="num"><?php echo $n($dept['total']); ?></td>
                                                        <td class="num"><?php echo $n($dept['full_time']); ?></td>
                                                        <td class="num"><?php echo $n($dept['part_time']); ?></td>
                                                        <td class="num"><?php echo $n($dept['completed']); ?></td>
                                                        <td class="num"><?php echo $n($dept['female']); ?></td>
                                                        <td class="num"><?php echo $n($dept['male']); ?></td>
                                                    </tr>
                                                    <?php foreach ($dept['courses'] as $course): ?>
                                                        <tr class="dh-course">
                                                            <td>
                                                                <?php echo $h($course['course_name']); ?>
                                                            </td>
                                                            <td><?php echo $h($nvqLabel($course['nvq_level'])); ?></td>
                                                            <td class="num"><?php echo $n($course['following']); ?></td>
                                                            <td class="num"><?php echo $n($course['internship']); ?></td>
                                                            <td class="num"><strong><?php echo $n($course['total']); ?></strong></td>
                                                            <td class="num"><?php echo $course['full_time'] > 0 ? $n($course['full_time']) : $dash; ?></td>
                                                            <td class="num"><?php echo $course['part_time'] > 0 ? $n($course['part_time']) : $dash; ?></td>
                                                            <td class="num"><?php echo $n($course['completed']); ?></td>
                                                            <td class="num"><?php echo $n($course['female']); ?></td>
                                                            <td class="num"><?php echo $n($course['male']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="2">Year total (distinct students)</td>
                                                        <td class="num"><?php echo $n($yr['following']); ?></td>
                                                        <td class="num"><?php echo $n($yr['internship']); ?></td>
                                                        <td class="num"><?php echo $n($yr['total']); ?></td>
                                                        <td class="num"><?php echo $n($yr['full_time']); ?></td>
                                                        <td class="num"><?php echo $n($yr['part_time']); ?></td>
                                                        <td class="num"><?php echo $n($yr['completed']); ?></td>
                                                        <td class="num"><?php echo $n($yr['female']); ?></td>
                                                        <td class="num"><?php echo $n($yr['male']); ?></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Clicking anywhere on a year row opens its department / course breakdown.
    document.querySelectorAll('.dh-year-row.is-clickable').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('button')) { return; }
            var btn = row.querySelector('.dh-toggle');
            if (btn) { btn.click(); }
        });
    });
});
</script>
