<?php
$monthOptions = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
];
$currentYear = (int) date('Y');
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-week me-2"></i>Month Range Attendance Summary
                        </h5>
                        <?php if (!empty($reportData) && !empty($selectedDepartment)): ?>
                            <a href="<?php echo APP_URL; ?>/attendance/export-range-summary?<?php echo http_build_query([
                                'generate' => '1',
                                'department_id' => $selectedDepartment,
                                'course_id' => $selectedCourse ?? '',
                                'academic_year' => $selectedAcademicYear ?? '',
                                'year' => $selectedYear,
                                'month_from' => $selectedMonthFrom,
                                'month_to' => $selectedMonthTo,
                                'eligible_only' => !empty($eligibleOnly) ? '1' : '0',
                            ]); ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-file-csv me-1"></i>Export CSV
                            </a>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-0 mt-2">
                        Combined attendance percentage across selected calendar months (weekdays only; holidays excluded from the denominator).
                    </p>
                </div>

                <div class="card-body">
                    <form method="GET" action="<?php echo APP_URL; ?>/attendance/range-summary" class="mb-4">
                        <input type="hidden" name="generate" value="1">
                        <div class="row g-3">
                            <?php if (empty($isHOD)): ?>
                            <div class="col-md-3">
                                <label for="department_id" class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">Select department</option>
                                    <?php foreach ($departments ?? [] as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>"
                                            <?php echo ($selectedDepartment ?? '') === $dept['department_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                                <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($selectedDepartment ?? ''); ?>">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Department</label>
                                    <input type="text" class="form-control" readonly
                                           value="<?php echo htmlspecialchars($departments[0]['department_name'] ?? $selectedDepartment ?? ''); ?>">
                                </div>
                            <?php endif; ?>

                            <div class="col-md-3">
                                <label for="course_id" class="form-label fw-semibold">Course</label>
                                <select class="form-select" id="course_id" name="course_id">
                                    <option value="">All courses</option>
                                    <?php foreach ($courses ?? [] as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                            <?php echo ($selectedCourse ?? '') === $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="academic_year" class="form-label fw-semibold">Academic year</label>
                                <select class="form-select" id="academic_year" name="academic_year">
                                    <option value="">All years</option>
                                    <?php foreach ($academicYears ?? [] as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>"
                                            <?php echo ($selectedAcademicYear ?? '') === $year ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="year" class="form-label fw-semibold">Calendar year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <?php for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo (int)($selectedYear ?? $currentYear) === $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="month_from" class="form-label fw-semibold">From month</label>
                                <select class="form-select" id="month_from" name="month_from" required>
                                    <?php foreach ($monthOptions as $num => $label): ?>
                                        <option value="<?php echo $num; ?>" <?php echo (int)($selectedMonthFrom ?? 1) === $num ? 'selected' : ''; ?>>
                                            <?php echo $num; ?> – <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="month_to" class="form-label fw-semibold">To month</label>
                                <select class="form-select" id="month_to" name="month_to" required>
                                    <?php foreach ($monthOptions as $num => $label): ?>
                                        <option value="<?php echo $num; ?>" <?php echo (int)($selectedMonthTo ?? 1) === $num ? 'selected' : ''; ?>>
                                            <?php echo $num; ?> – <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="eligible_only" name="eligible_only" value="1"
                                        <?php echo !empty($eligibleOnly) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="eligible_only">Allowance eligible only</label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Generate summary
                                </button>
                                <a href="<?php echo APP_URL; ?>/attendance/range-summary" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($rangeError)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i><?php echo htmlspecialchars($rangeError); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reportData) && empty($rangeError)): ?>
                        <div class="alert alert-info py-2 mb-3">
                            <strong>Period:</strong> <?php echo htmlspecialchars($rangeLabel ?? ''); ?>
                            (<?php echo htmlspecialchars($startDate ?? ''); ?> to <?php echo htmlspecialchars($endDate ?? ''); ?>)
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 text-center bg-light">
                                    <div class="text-muted small">Students</div>
                                    <div class="fs-4 fw-bold"><?php echo (int)($summary['total_students'] ?? 0); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 text-center">
                                    <div class="text-muted small">≥ 90%</div>
                                    <div class="fs-4 fw-bold text-success"><?php echo (int)($summary['above_90'] ?? 0); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 text-center">
                                    <div class="text-muted small">75% – 89%</div>
                                    <div class="fs-4 fw-bold text-warning"><?php echo (int)($summary['above_75'] ?? 0); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 text-center">
                                    <div class="text-muted small">&lt; 75%</div>
                                    <div class="fs-4 fw-bold text-danger"><?php echo (int)($summary['below_75'] ?? 0); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Full name</th>
                                        <th>NIC</th>
                                        <th>Course</th>
                                        <th class="text-center">Working days</th>
                                        <th class="text-center">Present</th>
                                        <th class="text-center">Absent</th>
                                        <th class="text-center">Holidays</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $student): ?>
                                        <?php
                                        $absent = max(0, (int)($student['effective_working_days'] ?? 0) - (int)($student['present_days'] ?? 0));
                                        $pct = (float)($student['attendance_percentage'] ?? 0);
                                        $badge = $pct >= 90 ? 'bg-success' : ($pct >= 75 ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_nic']); ?></td>
                                            <td><?php echo htmlspecialchars($student['course_name'] ?? ''); ?></td>
                                            <td class="text-center"><?php echo (int)($student['effective_working_days'] ?? 0); ?></td>
                                            <td class="text-center"><?php echo (int)($student['present_days'] ?? 0); ?></td>
                                            <td class="text-center"><?php echo $absent; ?></td>
                                            <td class="text-center"><?php echo (int)($student['holiday_days'] ?? 0); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $badge; ?>">
                                                    <?php echo number_format($pct, 1); ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!empty($generate) && empty($rangeError) && empty($reportData)): ?>
                        <div class="alert alert-secondary">No students found for the selected filters.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
