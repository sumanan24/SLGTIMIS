<?php require __DIR__ . '/partials/shell.php';

$publicFilters = $filters;
unset($publicFilters['department_limit']);
$publicFilters = array_filter($publicFilters, static function ($value) {
    return $value !== '' && $value !== null;
});
$studentLink = APP_URL . '/lifecycle/students' . ($publicFilters ? '?' . http_build_query($publicFilters) : '');

$scope = [];
if (!empty($filters['year'])) {
    $scope[] = $filters['year'];
}
if (!empty($filters['graduation_year'])) {
    $scope[] = 'Graduated ' . $filters['graduation_year'];
}
if (!empty($filters['department_id'])) {
    foreach ($departments as $department) {
        if ((string) $department['department_id'] === (string) $filters['department_id']) {
            $scope[] = $department['department_name'];
            break;
        }
    }
}
if (!empty($filters['course_id'])) {
    foreach ($courses as $course) {
        if ((string) $course['course_id'] === (string) $filters['course_id']) {
            $scope[] = $course['course_name'];
            break;
        }
    }
}
if (!empty($filters['nvq'])) {
    $scope[] = 'NVQ ' . $filters['nvq'];
}
if (!empty($filters['batch'])) {
    foreach ($groups as $group) {
        if ((string) $group['id'] === (string) $filters['batch']) {
            $scope[] = $group['name'];
            break;
        }
    }
}
if (!empty($filters['trade'])) {
    $scope[] = $filters['trade'];
}
if (!empty($filters['status'])) {
    $scope[] = $filters['status'];
}

$sections = [
    'Enrolment status' => [
        'Active' => $summary['active'] ?? 0,
        'Completed' => $summary['completed'] ?? 0,
        'Discontinued' => $summary['discontinued'] ?? 0,
        'Suspended' => $summary['suspended'] ?? 0,
        'Withdrawn' => $summary['withdrawn'] ?? 0,
        'Inactive' => $summary['inactive'] ?? 0,
    ],
    'Training and certificates' => [
        'OJT ongoing' => $summary['ojt_ongoing'] ?? 0,
        'OJT completed' => $summary['ojt_completed'] ?? 0,
        'OJT pending' => $summary['ojt_pending'] ?? 0,
        'Earlier OJT requests' => $summary['legacy_ojt'] ?? 0,
        'Certificates issued' => $summary['certificates_issued'] ?? 0,
        'Certificates pending' => $summary['certificates_pending'] ?? 0,
    ],
    'Employment and alumni' => [
        'Employed graduates' => $summary['employed'] ?? 0,
        'Unemployed records' => $summary['unemployed'] ?? 0,
        'Course-related jobs' => $summary['course_related'] ?? 0,
        'Local employment' => $summary['local_jobs'] ?? 0,
        'Foreign employment' => $summary['foreign_jobs'] ?? 0,
        'Self-employed' => $summary['self_employed'] ?? 0,
        'Higher education' => $summary['higher'] ?? 0,
        'Alumni profiles' => $summary['alumni'] ?? 0,
        'Verified alumni' => $summary['alumni_verified'] ?? 0,
    ],
];
?>
<form class="lc-filters" method="get" action="<?php echo APP_URL; ?>/lifecycle/dashboard">
    <?php $showSearch = false; $showOrigin = false; require __DIR__ . '/partials/filter_fields.php'; ?>
    <div class="lc-filter-actions">
        <?php if ($publicFilters): ?>
            <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/lifecycle/dashboard">Clear</a>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">Apply</button>
    </div>
</form>

<section class="lc-hero">
    <div>
        <p>Showing</p>
        <h2><?php echo $h($scope ? implode(' · ', $scope) : 'All academic years'); ?></h2>
    </div>
    <div class="lc-hero-total">
        <strong><?php echo $h($summary['total'] ?? 0); ?></strong>
        <span>Students</span>
    </div>
    <a class="btn btn-outline-primary" href="<?php echo $h($studentLink); ?>">View students</a>
</section>

<?php foreach ($sections as $heading => $cards): ?>
<section class="lc-section">
    <h3><?php echo $h($heading); ?></h3>
    <div class="lc-cards">
        <?php foreach ($cards as $label => $value): ?>
            <article class="<?php echo (int) $value === 0 ? 'is-zero' : ''; ?>">
                <span><?php echo $h($label); ?></span>
                <strong><?php echo $h($value); ?></strong>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>

<div class="lc-charts">
    <section class="lc-block">
        <h3>Enrolment by calendar year</h3>
        <?php if (empty($summary['years'])): ?>
            <p class="lc-empty">No enrolment dates for this filter.</p>
        <?php else: ?>
            <div class="lc-chart"><canvas id="lcYearChart"></canvas></div>
        <?php endif; ?>
    </section>
    <section class="lc-block">
        <h3>Students by course</h3>
        <?php if (empty($summary['courses'])): ?>
            <p class="lc-empty">No course enrolment for this filter.</p>
        <?php else: ?>
            <div class="lc-chart lc-chart-wide"><canvas id="lcCourseChart"></canvas></div>
            <ol class="lc-rank">
                <?php foreach ($summary['courses'] as $courseRow): ?>
                    <li><span><?php echo $h($courseRow['label']); ?></span><strong><?php echo (int) $courseRow['c']; ?></strong></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<p class="lc-note">Employment, certificate, and alumni figures stay at zero until those records are entered. Students without a lifecycle status still count as Active or Inactive from the existing record.</p>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const ink = '#001f3f';
    const yearChart = document.getElementById('lcYearChart');
    const courseChart = document.getElementById('lcCourseChart');
    const axis = { ticks: { precision: 0, color: '#64748b' }, grid: { color: '#e8eef6' }, border: { display: false } };
    if (yearChart) {
        new Chart(yearChart, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($summary['years'] ?? [], 'y')); ?>,
                datasets: [{ label: 'Students', data: <?php echo json_encode(array_map('intval', array_column($summary['years'] ?? [], 'c'))); ?>, backgroundColor: ink, borderRadius: 6, maxBarThickness: 42 }]
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: Object.assign({ beginAtZero: true }, axis), x: { grid: { display: false }, ticks: { color: '#334155' } } } }
        });
    }
    if (courseChart) {
        new Chart(courseChart, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($summary['courses'] ?? [], 'label')); ?>,
                datasets: [{ label: 'Students', data: <?php echo json_encode(array_map('intval', array_column($summary['courses'] ?? [], 'c'))); ?>, backgroundColor: '#003366', borderRadius: 6, maxBarThickness: 18 }]
            },
            options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: Object.assign({ beginAtZero: true }, axis), y: { grid: { display: false }, ticks: { color: '#334155', font: { size: 11 } } } } }
        });
    }
})();
</script>
<?php require __DIR__ . '/partials/close.php'; ?>
