<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$f = $filters ?? [];
$canManage = !empty($canManage);
$readOnly = !empty($readOnly);
$totalCount = (int) ($total ?? 0);
$per = max(1, (int) ($perPage ?? 20));
$curPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ceil($totalCount / $per));
$listQuery = static function (array $extra = []) use ($f): string {
    $params = array_merge([
        'department_id' => $f['department_id'] ?? '',
        'course_id' => $f['course_id'] ?? '',
        'academic_year' => $f['academic_year'] ?? '',
        'q' => $f['search'] ?? '',
    ], $extra);
    return http_build_query(array_filter($params, static fn ($v) => $v !== '' && $v !== null));
};
?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Student Letters</h1>
            <p class="text-muted small mb-0">Generate formal complaint letters to parents/guardians.</p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <?php if ($canManage): ?>
            <a href="<?php echo APP_URL; ?>/complaint-letters/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Create Complaint</a>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>/complaint-letters/history" class="btn btn-outline-secondary btn-sm"><i class="fas fa-history me-1"></i> History</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="get" action="<?php echo APP_URL; ?>/complaint-letters" class="card card-body mb-3 py-3">
        <div class="row g-2 align-items-end">
            <?php if (empty($isHodScoped)): ?>
            <div class="col-md-3">
                <label class="form-label small mb-0">Department</label>
                <select name="department_id" class="form-select form-select-sm" id="cl-filter-dept">
                    <option value="">All Departments</option>
                    <?php foreach ($departments ?? [] as $d): ?>
                    <option value="<?php echo $e($d['department_id'] ?? ''); ?>" <?php echo ($f['department_id'] ?? '') === ($d['department_id'] ?? '') ? 'selected' : ''; ?>><?php echo $e($d['department_name'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="col-md-3">
                <label class="form-label small mb-0">Department</label>
                <input type="text" class="form-control form-control-sm" readonly value="<?php echo $e(($departments[0]['department_name'] ?? $hodDepartmentId) ?? ''); ?>">
                <input type="hidden" name="department_id" id="cl-filter-dept" value="<?php echo $e($hodDepartmentId ?? ''); ?>">
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small mb-0">Course</label>
                <select name="course_id" class="form-select form-select-sm" id="cl-filter-course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses ?? [] as $c): ?>
                    <option value="<?php echo $e($c['course_id'] ?? ''); ?>" <?php echo ($f['course_id'] ?? '') === ($c['course_id'] ?? '') ? 'selected' : ''; ?>><?php echo $e($c['course_name'] ?? ''); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Academic Year</label>
                <select name="academic_year" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    <?php foreach ($academicYears ?? [] as $y): ?>
                    <option value="<?php echo $e($y); ?>" <?php echo ($f['academic_year'] ?? '') === $y ? 'selected' : ''; ?>><?php echo $e($y); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?php echo $e($f['search'] ?? ''); ?>" placeholder="Ref, subject, student…">
            </div>
            <div class="col-md-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?php echo APP_URL; ?>/complaint-letters" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Students</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No complaint letters found.</td></tr>
                <?php else: foreach ($complaints as $row):
                    $id = (int) ($row['id'] ?? 0);
                ?>
                <tr>
                    <td><a href="<?php echo APP_URL; ?>/complaint-letters/view?id=<?php echo $id; ?>" class="fw-semibold text-decoration-none"><?php echo $e($row['reference_no'] ?? ''); ?></a></td>
                    <td><?php echo $e($row['letter_date'] ?? ''); ?></td>
                    <td><span class="d-inline-block text-truncate" style="max-width:14rem;" title="<?php echo $e($row['subject'] ?? ''); ?>"><?php echo $e($row['subject'] ?? ''); ?></span></td>
                    <td><?php echo $e($row['department_name'] ?? $row['department_id'] ?? '—'); ?></td>
                    <td><?php echo $e($row['course_name'] ?? '—'); ?></td>
                    <td><?php echo $e($row['academic_year'] ?? ''); ?></td>
                    <td><?php echo (int) ($row['student_count'] ?? 0); ?></td>
                    <td><span class="badge bg-<?php echo ($row['status'] ?? '') === 'final' ? 'success' : 'secondary'; ?>"><?php echo $e(ucfirst((string)($row['status'] ?? 'draft'))); ?></span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo APP_URL; ?>/complaint-letters/view?id=<?php echo $id; ?>" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                            <a href="<?php echo APP_URL; ?>/complaint-letters/preview?id=<?php echo $id; ?>" class="btn btn-outline-secondary" title="Preview" target="_blank"><i class="fas fa-file-alt"></i></a>
                            <?php if ($canManage): ?>
                            <a href="<?php echo APP_URL; ?>/complaint-letters/edit?id=<?php echo $id; ?>" class="btn btn-outline-dark" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="<?php echo APP_URL; ?>/complaint-letters/pdf?id=<?php echo $id; ?>" class="btn btn-outline-danger" title="Generate PDF"><i class="fas fa-file-pdf"></i></a>
                            <a href="<?php echo APP_URL; ?>/complaint-letters/print?id=<?php echo $id; ?>" class="btn btn-outline-info" title="Print" target="_blank"><i class="fas fa-print"></i></a>
                            <?php elseif (!$readOnly): ?>
                            <a href="<?php echo APP_URL; ?>/complaint-letters/pdf?id=<?php echo $id; ?>" class="btn btn-outline-danger" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                            <?php else: ?>
                            <a href="<?php echo APP_URL; ?>/complaint-letters/pdf?id=<?php echo $id; ?>" class="btn btn-outline-danger" title="View PDF"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?php echo $p === $curPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo APP_URL; ?>/complaint-letters?<?php echo $e($listQuery(['page' => $p])); ?>"><?php echo $p; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<script type="application/json" id="cl-page-config"><?php echo json_encode([
    'baseUrl' => rtrim(APP_URL, '/'),
    'mode' => 'filter',
    'hodScoped' => !empty($isHodScoped),
    'selectedCourseId' => (string) ($f['course_id'] ?? ''),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script src="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/js/complaint-letters.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
