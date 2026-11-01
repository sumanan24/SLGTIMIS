<?php
$groups = $groups ?? [];
$academicYears = $academic_years ?? [];
$filterLinks = $filter_links ?? ['years' => [], 'trades' => []];
$showSearch = !empty($showSearch);
$showOrigin = !empty($showOrigin);
$selectedDepartment = (string) ($filters['department_id'] ?? '');
$selectedCourse = (string) ($filters['course_id'] ?? '');
$selectedYear = (string) ($filters['year'] ?? '');
$selectedNvq = (string) ($filters['nvq'] ?? '');
$selectedBatch = (string) ($filters['batch'] ?? '');
$selectedTrade = (string) ($filters['trade'] ?? '');
$courseMatches = static function ($course) use ($selectedDepartment, $selectedNvq) {
    if ($selectedDepartment !== '' && (string) $course['department_id'] !== $selectedDepartment) {
        return false;
    }
    if ($selectedNvq !== '' && (string) $course['course_nvq_level'] !== $selectedNvq) {
        return false;
    }
    return true;
};
$visibleCourses = array_values(array_filter($courses, $courseMatches));
$nvqLevels = [];
foreach ($courses as $course) {
    if ($selectedDepartment !== '' && (string) $course['department_id'] !== $selectedDepartment) {
        continue;
    }
    if ($selectedCourse !== '' && (string) $course['course_id'] !== $selectedCourse) {
        continue;
    }
    $level = (string) $course['course_nvq_level'];
    if ($level !== '') {
        $nvqLevels[$level] = $level;
    }
}
if (!$nvqLevels) {
    foreach (['3', '4', '5', '6', 'BRI'] as $level) {
        $nvqLevels[$level] = $level;
    }
}
$yearChoices = [];
foreach ($filterLinks['years'] as $link) {
    if ($selectedDepartment !== '' && (string) $link['department_id'] !== $selectedDepartment) {
        continue;
    }
    if ($selectedCourse !== '' && (string) $link['course_id'] !== $selectedCourse) {
        continue;
    }
    if ($selectedNvq !== '' && (string) $link['nvq'] !== $selectedNvq) {
        continue;
    }
    $year = (string) $link['year'];
    if ($year !== '') {
        $yearChoices[$year] = $year;
    }
}
if (!$yearChoices) {
    foreach ($academicYears as $academicYear) {
        $yearChoices[$academicYear] = $academicYear;
    }
}
krsort($yearChoices);
$visibleGroups = [];
foreach ($groups as $group) {
    if ($selectedDepartment !== '' && (string) ($group['department_id'] ?? '') !== $selectedDepartment) {
        continue;
    }
    if ($selectedCourse !== '' && (string) ($group['course_id'] ?? '') !== $selectedCourse) {
        continue;
    }
    if ($selectedYear !== '' && (string) ($group['academic_year'] ?? '') !== $selectedYear) {
        continue;
    }
    if ($selectedNvq !== '' && (string) ($group['course_nvq_level'] ?? '') !== $selectedNvq) {
        continue;
    }
    $visibleGroups[] = $group;
}
$tradeChoices = [];
foreach ($filterLinks['trades'] as $link) {
    if ($selectedDepartment !== '' && (string) $link['department_id'] !== $selectedDepartment) {
        continue;
    }
    if ($selectedCourse !== '' && (string) $link['course_id'] !== $selectedCourse) {
        continue;
    }
    if ($selectedNvq !== '' && (string) $link['nvq'] !== $selectedNvq) {
        continue;
    }
    $trade = (string) $link['trade'];
    if ($trade !== '') {
        $tradeChoices[$trade] = $trade;
    }
}
ksort($tradeChoices);
$cascade = [
    'courses' => [],
    'batches' => [],
    'years' => [],
    'trades' => [],
];
foreach ($courses as $course) {
    $cascade['courses'][] = [
        'value' => (string) $course['course_id'],
        'label' => (string) $course['course_name'],
        'department' => (string) $course['department_id'],
        'nvq' => (string) $course['course_nvq_level'],
    ];
}
foreach ($groups as $group) {
    $cascade['batches'][] = [
        'value' => (string) $group['id'],
        'label' => trim((string) $group['name'] . ' · ' . (string) $group['academic_year']),
        'department' => (string) ($group['department_id'] ?? ''),
        'course' => (string) ($group['course_id'] ?? ''),
        'year' => (string) ($group['academic_year'] ?? ''),
        'nvq' => (string) ($group['course_nvq_level'] ?? ''),
    ];
}
foreach ($filterLinks['years'] as $link) {
    $cascade['years'][] = [
        'value' => (string) $link['year'],
        'department' => (string) $link['department_id'],
        'course' => (string) $link['course_id'],
        'nvq' => (string) $link['nvq'],
    ];
}
foreach ($filterLinks['trades'] as $link) {
    $cascade['trades'][] = [
        'value' => (string) $link['trade'],
        'department' => (string) $link['department_id'],
        'course' => (string) $link['course_id'],
        'nvq' => (string) $link['nvq'],
    ];
}
?>
<script type="application/json" class="lc-filter-data"><?php echo json_encode($cascade, JSON_UNESCAPED_UNICODE); ?></script>
<?php if ($showSearch): ?>
<label class="lc-field-wide">
    <span>Search</span>
    <input class="form-control" type="search" name="q" value="<?php echo $h($filters['q'] ?? ''); ?>" placeholder="Name, registration, NIC, admission or certificate">
</label>
<?php endif; ?>
<label>
    <span>Department</span>
    <select class="form-select" name="department_id" data-lc="department">
        <option value="">All departments</option>
        <?php foreach ($departments as $department): ?>
            <option value="<?php echo $h($department['department_id']); ?>" <?php echo $selectedDepartment === (string) $department['department_id'] ? 'selected' : ''; ?>><?php echo $h($department['department_name']); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>Course</span>
    <select class="form-select" name="course_id" data-lc="course" data-placeholder="All courses">
        <option value="">All courses</option>
        <?php foreach ($visibleCourses as $course): ?>
            <option value="<?php echo $h($course['course_id']); ?>" <?php echo $selectedCourse === (string) $course['course_id'] ? 'selected' : ''; ?>><?php echo $h($course['course_name']); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>NVQ level</span>
    <select class="form-select" name="nvq" data-lc="nvq" data-placeholder="All levels">
        <option value="">All levels</option>
        <?php foreach ($nvqLevels as $level): ?>
            <option value="<?php echo $h($level); ?>" <?php echo $selectedNvq === (string) $level ? 'selected' : ''; ?>><?php echo $h($level); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>Academic year</span>
    <select class="form-select" name="year" data-lc="year" data-placeholder="All years">
        <option value="">All years</option>
        <?php foreach ($yearChoices as $academicYear): ?>
            <option value="<?php echo $h($academicYear); ?>" <?php echo $selectedYear === (string) $academicYear ? 'selected' : ''; ?>><?php echo $h($academicYear); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>Batch</span>
    <select class="form-select" name="batch" data-lc="batch" data-placeholder="All batches">
        <option value="">All batches</option>
        <?php foreach ($visibleGroups as $group): ?>
            <option value="<?php echo (int) $group['id']; ?>" <?php echo $selectedBatch === (string) $group['id'] ? 'selected' : ''; ?>><?php echo $h($group['name'] . ' · ' . $group['academic_year']); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>Trade</span>
    <select class="form-select" name="trade" data-lc="trade" data-placeholder="All trades">
        <option value="">All trades</option>
        <?php foreach ($tradeChoices as $trade): ?>
            <option value="<?php echo $h($trade); ?>" <?php echo $selectedTrade === (string) $trade ? 'selected' : ''; ?>><?php echo $h($trade); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>Graduation year</span>
    <input class="form-control" type="number" name="graduation_year" min="2016" max="2100" value="<?php echo $h($filters['graduation_year'] ?? ''); ?>">
</label>
<label>
    <span>Status</span>
    <select class="form-select" name="status">
        <option value="">All statuses</option>
        <?php foreach (array_merge(['Inactive'], LifecycleForms::statuses()) as $status): ?>
            <option value="<?php echo $h($status); ?>" <?php echo (($filters['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo $h($status); ?></option>
        <?php endforeach; ?>
    </select>
</label>
<?php if ($showOrigin): ?>
<label>
    <span>Record source</span>
    <select class="form-select" name="origin">
        <option value="">All records</option>
        <option value="system" <?php echo (($filters['origin'] ?? '') === 'system') ? 'selected' : ''; ?>>Entered in MIS</option>
        <option value="historical" <?php echo (($filters['origin'] ?? '') === 'historical') ? 'selected' : ''; ?>>Historical import</option>
    </select>
</label>
<?php endif; ?>
