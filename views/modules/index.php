<style>
/* Column plan: No | Course | Ver | Module ID | Name | Sem | Credit | Actions */
.modules-table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.modules-table col.col-no { width: 4%; }
.modules-table col.col-course { width: 20%; }
.modules-table col.col-version { width: 7%; }
.modules-table col.col-id { width: 10%; }
.modules-table col.col-name { width: 24%; }
.modules-table col.col-sem { width: 8%; }
.modules-table col.col-credit { width: 8%; }
.modules-table col.col-actions { width: 11%; }

.modules-table thead th {
    padding: 0.625rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    white-space: nowrap;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.modules-table tbody td {
    padding: 0.625rem 0.75rem;
    vertical-align: middle;
    line-height: 1.4;
    border-bottom: 1px solid #eef1f4;
}

.modules-table tbody tr:last-child td {
    border-bottom: none;
}

.modules-table tbody tr:hover td {
    background-color: rgba(13, 110, 253, 0.04);
}

.modules-table th.col-no,
.modules-table td.col-no {
    text-align: center;
    color: #6c757d;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.modules-table th.col-course,
.modules-table td.col-course,
.modules-table th.col-id,
.modules-table td.col-id,
.modules-table th.col-name,
.modules-table td.col-name {
    text-align: left;
}

.modules-table td.col-course .course-text,
.modules-table td.col-name .module-name-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.modules-table td.col-course .course-code {
    color: #6c757d;
    font-size: 0.8125rem;
}

.modules-table th.col-version,
.modules-table td.col-version,
.modules-table th.col-sem,
.modules-table td.col-sem,
.modules-table th.col-credit,
.modules-table td.col-credit {
    text-align: center;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.modules-table th.col-actions,
.modules-table td.col-actions {
    text-align: right;
    padding-right: 0.75rem;
    white-space: nowrap;
}

.modules-table .modules-chip {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1.2;
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
    white-space: nowrap;
    vertical-align: middle;
}

.modules-table .modules-chip-version {
    background-color: rgba(108, 117, 125, 0.18);
    color: #495057;
}

.modules-table .modules-chip-sem {
    background-color: rgba(13, 202, 240, 0.18);
    color: #087990;
}

.modules-actions .btn {
    min-width: 34px;
    width: 34px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
}

.modules-actions .btn-group {
    display: inline-flex;
    justify-content: flex-end;
    gap: 0.25rem;
}

.modules-actions .btn-group > .btn + .btn {
    margin-left: 0;
}

.modules-page-wrap .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    background: #fff;
}

.modules-page-wrap .modules-filter-form .form-select-sm {
    min-height: 38px;
}

.modules-filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: stretch;
    flex-wrap: wrap;
}

.modules-filter-actions .btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.modules-summary-bar {
    padding: 0.625rem 0.875rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
}

.modules-page-wrap .modules-header-btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .modules-page-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .modules-page-wrap .card {
        border-radius: 0;
    }
    .modules-page-wrap .card-header {
        padding: 0.75rem 0.5rem;
    }
    .modules-page-wrap .card-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.5rem;
    }
    .modules-page-wrap .modules-header-btn {
        width: 100%;
    }
    .modules-page-wrap .card-body {
        padding: 0.75rem 0.5rem !important;
    }
    .modules-page-wrap .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        border-radius: 0;
        border-left: none;
        border-right: none;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .modules-table {
        min-width: 960px;
    }
    .modules-filter-actions {
        width: 100%;
    }
    .modules-filter-actions .btn-primary {
        flex: 1;
    }
}
</style>

<div class="container-fluid px-4 py-3 modules-page-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-cubes me-2"></i>Modules</h5>
                <?php if (isset($canCreate) && $canCreate): ?>
                <a href="<?php echo APP_URL; ?>/modules/create" class="btn btn-light btn-sm modules-header-btn mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add Module
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border mb-4 shadow-sm">
                <div class="card-body py-3">
                    <h6 class="card-title mb-3 fw-bold text-primary small text-uppercase">
                        <i class="fas fa-filter me-2"></i>Filter Modules
                    </h6>
                    <form method="GET" action="<?php echo APP_URL; ?>/modules" class="modules-filter-form mb-0" id="modulesFilterForm">
                        <div class="row g-3 align-items-end">
                            <?php if (empty($isDepartmentRestricted)): ?>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="department_id" class="form-label small fw-semibold text-muted mb-1">Department</label>
                                <select name="department_id" id="department_id" class="form-select form-select-sm">
                                    <option value="">All departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>"
                                                <?php echo (isset($filter_department_id) && $filter_department_id === $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                                <input type="hidden" name="department_id" id="department_id" value="<?php echo htmlspecialchars($filter_department_id ?? ''); ?>">
                            <?php endif; ?>

                            <div class="col-12 col-md-6 col-lg-4">
                                <label for="course_id" class="form-label small fw-semibold text-muted mb-1">Course</label>
                                <select name="course_id" id="course_id" class="form-select form-select-sm">
                                    <option value="">All courses</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['course_id']); ?>"
                                                data-department-id="<?php echo htmlspecialchars($c['department_id'] ?? ''); ?>"
                                                <?php echo (isset($filter_course_id) && $filter_course_id === $c['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(($c['course_name'] ?? '') . ' (' . $c['course_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="course_version" class="form-label small fw-semibold text-muted mb-1">Version</label>
                                <select name="course_version" id="course_version" class="form-select form-select-sm" <?php echo empty($filter_course_id) ? 'disabled' : ''; ?>>
                                    <option value="">All versions</option>
                                    <?php if (!empty($filter_course_id)): ?>
                                        <?php
                                        $versionOptions = $versionsByCourse[$filter_course_id] ?? [0];
                                        foreach ($versionOptions as $ver):
                                            $verLabel = $ver === 0 ? 'Default (0)' : 'Version ' . $ver;
                                        ?>
                                            <option value="<?php echo (int) $ver; ?>"
                                                    <?php echo (isset($filter_course_version) && $filter_course_version !== '' && (int) $filter_course_version === (int) $ver) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($verLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-semibold text-muted mb-1 d-none d-md-block invisible">Apply</label>
                                <div class="modules-filter-actions">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($modules)): ?>
                <div class="modules-summary-bar mb-3">
                    <div class="text-muted small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <strong><?php echo count($modules); ?></strong> module(s)
                        <?php
                        $hasFilters = !empty($filter_department_id) || !empty($filter_course_id) || ($filter_course_version ?? '') !== '';
                        if ($hasFilters): ?>
                            <span class="text-muted">matching filters</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive mb-0">
                    <table class="table table-hover align-middle modules-table mb-0">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-course">
                            <col class="col-version">
                            <col class="col-id">
                            <col class="col-name">
                            <col class="col-sem">
                            <col class="col-credit">
                            <col class="col-actions">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="fw-semibold col-no">No</th>
                                <th scope="col" class="fw-semibold col-course">Course</th>
                                <th scope="col" class="fw-semibold col-version">Version</th>
                                <th scope="col" class="fw-semibold col-id">Module ID</th>
                                <th scope="col" class="fw-semibold col-name">Module Name</th>
                                <th scope="col" class="fw-semibold col-sem">Semester</th>
                                <th scope="col" class="fw-semibold col-credit">Credit</th>
                                <th scope="col" class="fw-semibold col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rowNumber = 1;
                            foreach ($modules as $m):
                                $ver = isset($m['course_version']) ? (int) $m['course_version'] : 0;
                                $courseLabel = $m['course_name'] ?? $m['course_id'];
                                $moduleName = $m['module_name'] ?? '—';
                                $sem = isset($m['semester']) ? $m['semester'] : null;
                            ?>
                                <tr>
                                    <td class="col-no"><?php echo $rowNumber++; ?></td>
                                    <td class="col-course">
                                        <span class="course-text" title="<?php echo htmlspecialchars($courseLabel . ' (' . $m['course_id'] . ')'); ?>">
                                            <?php echo htmlspecialchars($courseLabel); ?>
                                            <span class="course-code">(<?php echo htmlspecialchars($m['course_id']); ?>)</span>
                                        </span>
                                    </td>
                                    <td class="col-version">
                                        <span class="modules-chip modules-chip-version">
                                            <?php echo $ver === 0 ? 'Default' : $ver; ?>
                                        </span>
                                    </td>
                                    <td class="col-id">
                                        <code class="text-primary fw-semibold"><?php echo htmlspecialchars($m['module_id']); ?></code>
                                    </td>
                                    <td class="col-name">
                                        <span class="module-name-text" title="<?php echo htmlspecialchars($moduleName); ?>">
                                            <?php echo htmlspecialchars($moduleName); ?>
                                        </span>
                                    </td>
                                    <td class="col-sem">
                                        <?php if ($sem !== null && $sem !== ''): ?>
                                            <span class="modules-chip modules-chip-sem"><?php echo htmlspecialchars((string) (int) $sem); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-credit">
                                        <?php echo isset($m['credit']) && $m['credit'] !== '' && $m['credit'] !== null ? htmlspecialchars($m['credit']) : '<span class="text-muted">—</span>'; ?>
                                    </td>
                                    <td class="col-actions modules-actions">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/modules/view?course_id=<?php echo urlencode($m['course_id']); ?>&module_id=<?php echo urlencode($m['module_id']); ?>&course_version=<?php echo $ver; ?>" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (isset($canEdit) && $canEdit): ?>
                                            <a href="<?php echo APP_URL; ?>/modules/edit?course_id=<?php echo urlencode($m['course_id']); ?>&module_id=<?php echo urlencode($m['module_id']); ?>&course_version=<?php echo $ver; ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/delete?course_id=<?php echo urlencode($m['course_id']); ?>&module_id=<?php echo urlencode($m['module_id']); ?>&course_version=<?php echo $ver; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this module?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-cubes fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No modules found.</p>
                    <?php if (isset($canCreate) && $canCreate): ?>
                    <a href="<?php echo APP_URL; ?>/modules/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Module
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var versionsByCourse = <?php echo json_encode($versionsByCourse ?? []); ?>;
    var isDeptRestricted = <?php echo !empty($isDepartmentRestricted) ? 'true' : 'false'; ?>;
    var deptSelect = document.getElementById('department_id');
    var courseSelect = document.getElementById('course_id');
    var versionSelect = document.getElementById('course_version');
    if (!courseSelect || !versionSelect) {
        return;
    }

    var allCourseOptions = [];
    for (var i = 0; i < courseSelect.options.length; i++) {
        var opt = courseSelect.options[i];
        if (!opt.value) {
            continue;
        }
        allCourseOptions.push({
            value: opt.value,
            label: opt.textContent,
            departmentId: opt.getAttribute('data-department-id') || ''
        });
    }

    var selectedCourse = courseSelect.value;
    var selectedVersion = versionSelect.value;

    function getDeptId() {
        return deptSelect ? (deptSelect.value || '') : '';
    }

    function fillCourses() {
        var deptId = getDeptId();
        var current = courseSelect.value || selectedCourse;
        courseSelect.innerHTML = '';
        courseSelect.options.add(new Option('All courses', '', false, false));
        allCourseOptions.forEach(function (c) {
            if (deptId && c.departmentId !== deptId) {
                return;
            }
            courseSelect.options.add(new Option(c.label, c.value, false, false));
        });
        if (current && Array.prototype.some.call(courseSelect.options, function (o) { return o.value === current; })) {
            courseSelect.value = current;
        } else {
            courseSelect.value = '';
        }
        fillVersions();
    }

    function fillVersions() {
        var cid = courseSelect.value;
        var currentVer = versionSelect.value;
        versionSelect.innerHTML = '';
        if (!cid) {
            versionSelect.options.add(new Option('All versions', '', true, true));
            versionSelect.disabled = true;
            return;
        }
        versionSelect.disabled = false;
        versionSelect.options.add(new Option('All versions', '', false, currentVer === ''));
        var versions = versionsByCourse[cid] || [0];
        versions.forEach(function (v) {
            var label = v === 0 ? 'Default (0)' : 'Version ' + v;
            var val = String(v);
            versionSelect.options.add(new Option(label, val, false, val === currentVer));
        });
    }

    if (deptSelect && deptSelect.tagName === 'SELECT') {
        deptSelect.addEventListener('change', function () {
            courseSelect.value = '';
            fillCourses();
        });
    }

    courseSelect.addEventListener('change', function () {
        selectedCourse = courseSelect.value;
        fillVersions();
    });

    fillCourses();
    if (selectedVersion !== '') {
        versionSelect.value = selectedVersion;
    }
})();
</script>
