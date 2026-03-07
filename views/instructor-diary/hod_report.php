<?php
$staffList = $staffList ?? [];
$courses = $courses ?? [];
$groups = $groups ?? [];
$academicYears = $academicYears ?? [];
$entries = $entries ?? [];
$filters = $filters ?? ['staff_id' => '', 'course_id' => '', 'academic_year' => '', 'from_date' => '', 'to_date' => ''];

// Build export URL with current filters
$exportQuery = http_build_query([
    'staff_id' => $filters['staff_id'] ?? '',
    'course_id' => $filters['course_id'] ?? '',
    'academic_year' => $filters['academic_year'] ?? '',
    'from_date' => $filters['from_date'] ?? '',
    'to_date' => $filters['to_date'] ?? '',
]);
$exportUrl = APP_URL . '/hod/instructor-diary/export' . ($exportQuery ? ('?' . $exportQuery) : '');
?>

<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-book-open me-2"></i>Instructor Diary - HOD Report
                </h5>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form class="row g-2 g-md-3 mb-3" method="get">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold">Staff</label>
                    <select name="staff_id" class="form-select">
                        <option value="">All Staff</option>
                        <?php foreach ($staffList as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['staff_id']); ?>"
                                <?php echo (($filters['staff_id'] ?? '') === $s['staff_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(($s['staff_name'] ?? '') . ' (' . $s['staff_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold">Course</label>
                    <select name="course_id" class="form-select">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['course_id']); ?>"
                                <?php echo (($filters['course_id'] ?? '') === $c['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(($c['course_name'] ?? '') . ' (' . $c['course_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold">Academic Year</label>
                    <select name="academic_year" class="form-select">
                        <option value="">All Years</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>"
                                <?php echo (($filters['academic_year'] ?? '') === (string)$year) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold">From</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($filters['from_date'] ?? ''); ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold">To</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($filters['to_date'] ?? ''); ?>">
                </div>
                <div class="col-12 col-md-6 col-lg-4 align-self-end d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="<?php echo APP_URL; ?>/hod/instructor-diary" class="btn btn-outline-secondary">
                        Clear
                    </a>
                    <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="btn btn-success ms-auto">
                        <i class="fas fa-file-excel me-1"></i>Export Excel
                    </a>
                </div>
            </form>
            
            <?php if (!empty($entries)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Module</th>
                                <th>Course</th>
                                <th>Academic Year</th>
                                <th>Time</th>
                                <th>Topic</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $row): ?>
                                <tr>
                                    <td class="small"><?php echo htmlspecialchars($row['diary_date']); ?></td>
                                    <td class="small">
                                        <?php echo htmlspecialchars(($row['staff_name'] ?? '') . ' (' . ($row['staff_id'] ?? '') . ')'); ?>
                                    </td>
                                    <td class="small text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($row['module_name'] ?? $row['module_id']); ?>">
                                        <?php echo htmlspecialchars($row['module_name'] ?? $row['module_id']); ?>
                                    </td>
                                    <td class="small">
                                        <?php
                                            $courseLabel = $row['course_name'] ?? '';
                                            if ($courseLabel === '' && !empty($row['course_id'] ?? '')) {
                                                $courseLabel = $row['course_id'];
                                            } elseif ($courseLabel !== '' && !empty($row['course_id'] ?? '')) {
                                                $courseLabel .= ' (' . $row['course_id'] . ')';
                                            }
                                            echo htmlspecialchars($courseLabel);
                                        ?>
                                    </td>
                                    <td class="small"><?php echo htmlspecialchars($row['academic_year'] ?? ''); ?></td>
                                    <td class="small">
                                        <?php echo htmlspecialchars(substr($row['start_time'], 0, 5)); ?>
                                        &ndash;
                                        <?php echo htmlspecialchars(substr($row['end_time'], 0, 5)); ?>
                                    </td>
                                    <td class="small text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($row['topic_covered']); ?>">
                                        <?php echo htmlspecialchars($row['topic_covered']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">No diary entries found for the selected filters.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

