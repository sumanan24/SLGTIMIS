<?php
$staff_whatsapp_header = isset($staff_whatsapp_header) && is_array($staff_whatsapp_header) ? $staff_whatsapp_header : [];
$selectedProvinces = is_array($filters['provinces'] ?? null) ? $filters['provinces'] : [];
$selectedProvinceKeys = [];
foreach ($selectedProvinces as $sp) {
    $key = strtolower(trim(preg_replace('/\s+province$/i', '', (string) $sp) ?? ''));
    if ($key !== '') {
        $selectedProvinceKeys[$key] = true;
    }
}
$hasActiveFilters = !empty($filters['search']) || !empty($filters['status']) || !empty($filters['district'])
    || !empty($filters['gender']) || !empty($filters['department_id']) || !empty($filters['course_id'])
    || !empty($filters['academic_year']) || !empty($filters['course_mode']) || !empty($filters['group_id'])
    || !empty($filters['provinces']);
$smsQuery = http_build_query(array_filter([
    'academic_year' => $filters['academic_year'] ?? '',
    'department_id' => $filters['department_id'] ?? '',
    'course_id' => $filters['course_id'] ?? '',
], 'strlen'));
?>
<style>
.stu-page { --stu-navy:#0b2545; --stu-ink:#1e293b; --stu-muted:#64748b; --stu-line:#dbe4f0; --stu-soft:#f5f8fd; --stu-accent:#1d4ed8; color:var(--stu-ink); }
.stu-page .stu-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
.stu-page .stu-title { margin:0; font-size:1.35rem; font-weight:700; color:var(--stu-navy); letter-spacing:-.02em; }
.stu-page .stu-sub { margin:.2rem 0 0; color:var(--stu-muted); font-size:.86rem; max-width:36rem; }
.stu-page .stu-actions { display:flex; flex-wrap:wrap; gap:.45rem; align-items:center; }
.stu-page .stu-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:.45rem; font-size:.82rem; font-weight:600; padding:.42rem .8rem; border:1px solid transparent; text-decoration:none; line-height:1.2; }
.stu-page .stu-btn-primary { background:var(--stu-navy); color:#fff; }
.stu-page .stu-btn-primary:hover { background:#163a66; color:#fff; }
.stu-page .stu-btn-ghost { background:#fff; color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-ghost:hover { background:var(--stu-soft); color:var(--stu-navy); }
.stu-page .stu-btn-soft { background:var(--stu-soft); color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-soft:hover { background:#e8eef8; color:var(--stu-navy); }
.stu-page .stu-panel { background:#fff; border:1px solid var(--stu-line); border-radius:.7rem; margin-bottom:1rem; overflow:hidden; }
.stu-page .stu-panel-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem; padding:.75rem 1rem; background:var(--stu-soft); border-bottom:1px solid var(--stu-line); }
.stu-page .stu-panel-head h2 { margin:0; font-size:.92rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-panel-body { padding:1rem; }
.stu-page .stu-label { display:block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--stu-muted); margin-bottom:.28rem; }
.stu-page .stu-field .form-control,
.stu-page .stu-field .form-select { font-size:.86rem; border-color:#cfd9e6; border-radius:.45rem; min-height:2.15rem; }
.stu-page .stu-field .form-control:focus,
.stu-page .stu-field .form-select:focus { border-color:#93b4e0; box-shadow:0 0 0 .2rem rgba(29,78,216,.12); }
.stu-page .stu-search { position:relative; }
.stu-page .stu-search i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.85rem; pointer-events:none; }
.stu-page .stu-search .form-control { padding-left:2.15rem; }
.stu-page .stu-prov-bar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.55rem; }
.stu-page .stu-prov-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(9.5rem,1fr)); gap:.4rem; }
.stu-page .stu-chip { position:relative; margin:0; }
.stu-page .stu-chip input { position:absolute; opacity:0; pointer-events:none; }
.stu-page .stu-chip span { display:block; text-align:center; padding:.42rem .55rem; border:1px solid #cfd9e6; border-radius:.4rem; background:#fff; color:#334155; font-size:.78rem; font-weight:600; cursor:pointer; transition:background .15s ease,border-color .15s ease,color .15s ease; }
.stu-page .stu-chip span:hover { border-color:#93b4e0; background:#f8fbff; }
.stu-page .stu-chip input:checked + span { background:var(--stu-navy); border-color:var(--stu-navy); color:#fff; }
.stu-page .stu-chip input:focus-visible + span { outline:2px solid #93b4e0; outline-offset:2px; }
.stu-page .stu-filter-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; margin-top:1rem; padding-top:.85rem; border-top:1px solid #eef2f7; }
.stu-page .stu-hint { color:var(--stu-muted); font-size:.78rem; margin:0; }
.stu-page .stu-meta { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.85rem; }
.stu-page .stu-count { font-size:.86rem; color:var(--stu-muted); }
.stu-page .stu-count strong { color:var(--stu-navy); }
.stu-page .stu-note { font-size:.76rem; color:#7c8aa0; }
.stu-page .stu-table-wrap { overflow-x:auto; }
.stu-page table.stu-table { width:100%; border-collapse:collapse; font-size:.86rem; margin:0; }
.stu-page table.stu-table th { background:var(--stu-navy); color:#fff; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.65rem .75rem; white-space:nowrap; border:0; }
.stu-page table.stu-table td { padding:.6rem .75rem; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.stu-page table.stu-table tbody tr:hover td { background:#f7faff; }
.stu-page .stu-id { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.8rem; font-weight:700; color:var(--stu-accent); }
.stu-page .stu-name { font-weight:600; color:var(--stu-ink); }
.stu-page .stu-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #e2e8f0; display:block; }
.stu-page .stu-avatar-fallback { width:40px; height:40px; border-radius:50%; border:2px solid #e2e8f0; background:#eef2f7; color:#94a3b8; display:inline-flex; align-items:center; justify-content:center; }
.stu-page .stu-status { display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .55rem; border-radius:.35rem; font-size:.72rem; font-weight:700; }
.stu-page .stu-status.ok { background:#dcfce7; color:#166534; }
.stu-page .stu-status.info { background:#dbeafe; color:#1e40af; }
.stu-page .stu-status.warn { background:#fef3c7; color:#92400e; }
.stu-page .students-actions .btn { min-width:34px; height:30px; padding:0 .55rem; display:inline-flex; align-items:center; justify-content:center; font-size:.8rem; border-radius:.35rem; }
.stu-page .students-actions .btn-group { gap:.25rem; }
.stu-page .students-actions .btn-group > .btn { border-radius:.35rem !important; }
.stu-page .students-actions .btn-outline-primary { color:var(--stu-navy); border-color:#c5d3e6; }
.stu-page .students-actions .btn-outline-primary:hover { background:var(--stu-navy); border-color:var(--stu-navy); color:#fff; }
.stu-page .students-actions .btn-outline-danger:hover { background:#dc3545; border-color:#dc3545; color:#fff; }
.stu-page .stu-empty { text-align:center; padding:2.8rem 1rem; color:var(--stu-muted); }
.stu-page .stu-empty i { font-size:2.2rem; margin-bottom:.7rem; opacity:.55; }
.stu-page .stu-pager { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; padding:1rem; border-top:1px solid #eef2f7; }
.stu-page .pagination .page-link { color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .pagination .page-item.active .page-link { background:var(--stu-navy); border-color:var(--stu-navy); }
.stu-page .dropdown-menu { border-color:var(--stu-line); box-shadow:0 10px 28px rgba(15,23,42,.08); }
.stu-page .dropdown-item { font-size:.84rem; }
@media (max-width:767.98px) {
    .stu-page .stu-title { font-size:1.15rem; }
}
</style>

<div class="container-fluid px-4 py-3 stu-page">
    <div class="stu-head">
        <div>
            <h1 class="stu-title">Students</h1>
            <p class="stu-sub">Browse and manage current enrolments. Following and Internship only — Dropout, Completed, and Long Absent are excluded.</p>
        </div>
        <div class="stu-actions">
            <?php if (!empty($staff_whatsapp_header['url'])): ?>
            <a href="<?php echo htmlspecialchars($staff_whatsapp_header['url'], ENT_QUOTES, 'UTF-8'); ?>" class="stu-btn stu-btn-ghost btn-wa-outline" target="_blank" rel="noopener noreferrer"
               title="WhatsApp <?php echo htmlspecialchars($staff_whatsapp_header['display'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fab fa-whatsapp" aria-hidden="true"></i><?php echo htmlspecialchars($staff_whatsapp_header['display'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endif; ?>
            <?php if (isset($canEdit) && $canEdit): ?>
            <a href="<?php echo APP_URL; ?>/students/create" class="stu-btn stu-btn-primary">
                <i class="fas fa-plus"></i>Add student
            </a>
            <?php endif; ?>
            <?php if ((isset($canExport) && $canExport) || (isset($isADM) && $isADM)): ?>
            <div class="dropdown">
                <button class="stu-btn stu-btn-soft dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-h"></i>Tools
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (isset($canExport) && $canExport): ?>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exportModal"><i class="fas fa-file-excel me-2 text-success"></i>Export to Excel</button></li>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#envelopeModal"><i class="fas fa-envelope me-2 text-warning"></i>Envelopes / Name list</button></li>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#callingLetterModal"><i class="fas fa-file-alt me-2"></i>Calling letters</button></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/students/bulk-sms<?php echo $smsQuery !== '' ? '?' . $smsQuery : ''; ?>"><i class="fas fa-sms me-2 text-info"></i>Bulk SMS</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/students/registration-checklist<?php echo $smsQuery !== '' ? '?' . $smsQuery : ''; ?>"><i class="fas fa-clipboard-list me-2 text-primary"></i>Registration checklist</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/students/intake-checklist<?php echo $smsQuery !== '' ? '?' . $smsQuery : ''; ?>"><i class="fas fa-clipboard-check me-2 text-danger"></i>Intake checklist</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/students/documents-backup-zip"><i class="fas fa-file-archive me-2"></i>Docs &amp; photos ZIP</a></li>
                    <?php endif; ?>
                    <?php if (isset($isADM) && $isADM): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/students/import-images" onclick="return confirm('This will scan the img/Studnet_profile directory and update student records with matching image files. Continue?');"><i class="fas fa-images me-2"></i>Import profile images</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

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

    <div class="stu-panel">
        <div class="stu-panel-head">
            <h2><i class="fas fa-sliders-h me-2"></i>Filters</h2>
            <?php if ($hasActiveFilters): ?>
            <a href="<?php echo APP_URL; ?>/students" class="stu-btn stu-btn-ghost" style="padding:.28rem .65rem; font-size:.75rem;">
                <i class="fas fa-times"></i>Reset all
            </a>
            <?php endif; ?>
        </div>
        <div class="stu-panel-body">
            <form method="GET" action="<?php echo APP_URL; ?>/students">
                <div class="row g-3">
                    <div class="col-12 col-lg-4 stu-field">
                        <label for="search" class="stu-label">Search</label>
                        <div class="stu-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="text" class="form-control" id="search" name="search"
                                   value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                                   placeholder="Name, ID, email, or NIC">
                        </div>
                    </div>
                    <?php if (!isset($isHOD) || !$isHOD): ?>
                    <div class="col-6 col-md-4 col-lg-2 stu-field">
                        <label for="status" class="stu-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>"
                                        <?php echo (isset($filters['status']) && $filters['status'] === $status) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-6 col-md-4 col-lg-2 stu-field">
                        <label for="gender" class="stu-label">Gender</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">All</option>
                            <?php foreach ($genders as $gender): ?>
                                <option value="<?php echo htmlspecialchars($gender); ?>"
                                        <?php echo (isset($filters['gender']) && $filters['gender'] === $gender) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($gender); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 stu-field">
                        <label for="academic_year" class="stu-label">Academic year</label>
                        <select class="form-select" id="academic_year" name="academic_year">
                            <option value="">All</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"
                                        <?php echo (isset($filters['academic_year']) && $filters['academic_year'] === $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 stu-field">
                        <label for="course_mode" class="stu-label">Mode</label>
                        <select class="form-select" id="course_mode" name="course_mode">
                            <option value="">All</option>
                            <option value="Full" <?php echo (isset($filters['course_mode']) && $filters['course_mode'] === 'Full') ? 'selected' : ''; ?>>Full Time</option>
                            <option value="Part" <?php echo (isset($filters['course_mode']) && $filters['course_mode'] === 'Part') ? 'selected' : ''; ?>>Part Time</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 stu-field">
                        <label for="department_id" class="stu-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">All departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_id']); ?>"
                                        <?php echo (isset($filters['department_id']) && $filters['department_id'] === $dept['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 stu-field">
                        <label for="course_id" class="stu-label">Course</label>
                        <select class="form-select" id="course_id" name="course_id">
                            <option value="">All courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                        data-department-id="<?php echo htmlspecialchars($course['department_id']); ?>"
                                        <?php echo (isset($filters['course_id']) && $filters['course_id'] === $course['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 stu-field">
                        <label for="group_id" class="stu-label">Group</label>
                        <select class="form-select" id="group_id" name="group_id">
                            <option value="">All groups</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 stu-field">
                        <label for="district" class="stu-label">District</label>
                        <select class="form-select" id="district" name="district">
                            <option value="">All districts</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo htmlspecialchars($district); ?>"
                                        <?php echo (isset($filters['district']) && $filters['district'] === $district) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="stu-prov-bar">
                            <label class="stu-label mb-0">Provinces</label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" id="filterProvAll">Select all</button>
                                <button type="button" class="btn btn-outline-secondary" id="filterProvNone">Clear</button>
                            </div>
                        </div>
                        <div class="stu-prov-grid" id="filterProvinceBox">
                            <?php foreach (($provinces ?? []) as $provinceName):
                                $provKey = strtolower(trim(preg_replace('/\s+province$/i', '', (string) $provinceName) ?? ''));
                                $provId = 'filter_prov_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) $provinceName);
                                $checked = isset($selectedProvinceKeys[$provKey]);
                            ?>
                            <label class="stu-chip" for="<?php echo htmlspecialchars($provId); ?>">
                                <input class="filter-province" type="checkbox"
                                       name="provinces[]"
                                       value="<?php echo htmlspecialchars((string) $provinceName); ?>"
                                       id="<?php echo htmlspecialchars($provId); ?>"
                                       <?php echo $checked ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars((string) $provinceName); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="stu-filter-foot">
                    <p class="stu-hint">Leave provinces empty to include every province.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo APP_URL; ?>/students" class="stu-btn stu-btn-ghost">Clear</a>
                        <button type="submit" class="stu-btn stu-btn-primary">
                            <i class="fas fa-filter"></i>Apply filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="stu-meta">
        <div>
            <div class="stu-count">
                Showing <strong><?php echo count($students); ?></strong> of <strong><?php echo number_format($total); ?></strong> students
            </div>
            <div class="stu-note">Select rows to use with Envelopes / Name list from Tools.</div>
        </div>
        <?php if ($hasActiveFilters): ?>
        <a href="<?php echo APP_URL; ?>/students" class="stu-btn stu-btn-ghost" style="padding:.3rem .7rem; font-size:.78rem;">
            <i class="fas fa-times-circle"></i>Clear filters
        </a>
        <?php endif; ?>
    </div>

    <div class="stu-panel" style="margin-bottom:0;">
        <?php if (!empty($students)): ?>
            <div class="stu-table-wrap">
                <table class="stu-table">
                    <thead>
                        <tr>
                            <?php if (!empty($canExport)): ?>
                            <th style="width:40px;"><input type="checkbox" class="form-check-input" id="envSelectAll" title="Select all on this page"></th>
                            <?php endif; ?>
                            <th style="width:56px;">No</th>
                            <th style="width:72px;">Photo</th>
                            <th>Student ID</th>
                            <th>Full name</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rowNumber = (($currentPage - 1) * 20) + 1;
                        require_once BASE_PATH . '/models/StudentModel.php';
                        $studentModelHelper = new StudentModel();

                        foreach ($students as $student):
                            $profileImageUrl = $studentModelHelper->getProfileImagePath($student);
                            $waDigits = StudentModel::digitsForWhatsAppMe($student);
                            $statusClass = $student['student_status'] === 'Active' ? 'ok' : ($student['student_status'] === 'Graduated' ? 'info' : 'warn');
                        ?>
                            <tr>
                                <?php if (!empty($canExport)): ?>
                                <td><input type="checkbox" class="form-check-input env-select" value="<?php echo htmlspecialchars($student['student_id']); ?>" title="Select student"></td>
                                <?php endif; ?>
                                <td class="text-muted"><?php echo $rowNumber++; ?></td>
                                <td>
                                    <?php if ($profileImageUrl): ?>
                                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>"
                                             alt="<?php echo htmlspecialchars($student['student_fullname']); ?>"
                                             class="stu-avatar">
                                    <?php else: ?>
                                        <div class="stu-avatar-fallback"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="stu-id"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                                <td><span class="stu-name"><?php echo htmlspecialchars($student['student_fullname']); ?></span></td>
                                <td>
                                    <span class="stu-status <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($student['student_status']); ?>
                                    </span>
                                </td>
                                <td class="text-end students-actions">
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($waDigits !== null): ?>
                                        <a href="<?php echo htmlspecialchars('https://wa.me/' . $waDigits, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-wa-outline" target="_blank" rel="noopener noreferrer"
                                           title="WhatsApp chat — <?php echo htmlspecialchars($waDigits, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!empty($isADM)): ?>
                                        <a href="<?php echo APP_URL; ?>/students/id-card?student_id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-primary" title="Preview ID Card">
                                            <i class="fas fa-id-card"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (isset($canEdit) && $canEdit): ?>
                                        <a href="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php
                                        $deleteUrl = APP_URL . '/students/delete?id=' . urlencode($student['student_id']);
                                        if (!empty($filters)) {
                                            $filterParams = [];
                                            foreach ($filters as $key => $value) {
                                                if (!empty($value) && $key !== 'page') {
                                                    $filterParams[$key] = $value;
                                                }
                                            }
                                            if (!empty($filterParams)) {
                                                $deleteUrl .= '&' . http_build_query($filterParams);
                                            }
                                        }
                                        ?>
                                        <a href="<?php echo $deleteUrl; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this student?');" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <?php
                if (!function_exists('buildPaginationQuery')) {
                    function buildPaginationQuery($page, $filters) {
                        $params = ['page' => $page];
                        if (!empty($filters['search'])) $params['search'] = $filters['search'];
                        if (!empty($filters['status'])) $params['status'] = $filters['status'];
                        if (!empty($filters['district'])) $params['district'] = $filters['district'];
                        if (!empty($filters['gender'])) $params['gender'] = $filters['gender'];
                        if (!empty($filters['department_id'])) $params['department_id'] = $filters['department_id'];
                        if (!empty($filters['course_id'])) $params['course_id'] = $filters['course_id'];
                        if (!empty($filters['academic_year'])) $params['academic_year'] = $filters['academic_year'];
                        if (!empty($filters['course_mode'])) $params['course_mode'] = $filters['course_mode'];
                        if (!empty($filters['group_id'])) $params['group_id'] = $filters['group_id'];
                        if (!empty($filters['provinces']) && is_array($filters['provinces'])) {
                            $params['provinces'] = array_values($filters['provinces']);
                        }
                        return '?' . http_build_query($params);
                    }
                }
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                ?>
                <div class="stu-pager">
                    <div class="stu-note">Page <strong><?php echo $currentPage; ?></strong> of <strong><?php echo $totalPages; ?></strong></div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationQuery($currentPage - 1, $filters); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationQuery(1, $filters); ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPaginationQuery($i, $filters); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationQuery($totalPages, $filters); ?>"><?php echo $totalPages; ?></a>
                                </li>
                            <?php endif; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationQuery($currentPage + 1, $filters); ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="stu-empty">
                <i class="fas fa-user-slash d-block"></i>
                <p class="mb-3">No students match these filters.<?php if (!empty($filters['search'])): ?> Try a different search term.<?php endif; ?></p>
                <?php if (isset($canEdit) && $canEdit && empty($filters['search'])): ?>
                    <a href="<?php echo APP_URL; ?>/students/create" class="stu-btn stu-btn-primary">
                        <i class="fas fa-plus"></i>Add student
                    </a>
                <?php elseif ($hasActiveFilters): ?>
                    <a href="<?php echo APP_URL; ?>/students" class="stu-btn stu-btn-ghost">Clear filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    const academicYearSelect = document.getElementById('academic_year');
    const groupSelect = document.getElementById('group_id');
    const selectedGroupId = '<?php echo htmlspecialchars($filters['group_id'] ?? ''); ?>';
    
    // Store all course options
    const allCourseOptions = courseSelect ? Array.from(courseSelect.options).slice(1) : []; // Exclude "All Courses"
    
    // Load groups by course and academic year
    function loadFilterGroups() {
        if (!groupSelect || !courseSelect || !academicYearSelect) return;
        const courseId = courseSelect.value;
        const academicYear = academicYearSelect.value;
        groupSelect.innerHTML = '<option value="">All Groups</option>';
        if (!courseId || !academicYear) return;
        fetch('<?php echo APP_URL; ?>/attendance/get-groups-by-course-and-year?course_id=' + encodeURIComponent(courseId) + '&academic_year=' + encodeURIComponent(academicYear))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.groups && data.groups.length) {
                    data.groups.forEach(function(g) {
                        const opt = document.createElement('option');
                        opt.value = g.id;
                        opt.textContent = g.name + (g.course_name ? ' - ' + g.course_name : '');
                        if (selectedGroupId && g.id == selectedGroupId) opt.selected = true;
                        groupSelect.appendChild(opt);
                    });
                }
            });
    }
    
    // Function to filter courses by department
    function filterCoursesByDepartment(departmentId) {
        if (!courseSelect) return;
        // Clear current options except "All Courses"
        courseSelect.innerHTML = '<option value="">All Courses</option>';
        
        // Get currently selected course value before filtering
        const previouslySelected = courseSelect.value;
        
        if (!departmentId) {
            // If no department selected, show all courses
            allCourseOptions.forEach(function(option) {
                const newOption = option.cloneNode(true);
                courseSelect.appendChild(newOption);
            });
        } else {
            // Filter courses by selected department
            allCourseOptions.forEach(function(option) {
                const deptId = option.getAttribute('data-department-id');
                if (deptId === departmentId) {
                    const newOption = option.cloneNode(true);
                    courseSelect.appendChild(newOption);
                }
            });
        }
        
        // Try to restore previously selected course if it's still available
        if (previouslySelected) {
            const optionExists = Array.from(courseSelect.options).some(function(opt) {
                return opt.value === previouslySelected;
            });
            if (optionExists) {
                courseSelect.value = previouslySelected;
            }
        }
    }
    
    // Handle department change - live filter
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            filterCoursesByDepartment(this.value);
            loadFilterGroups();
        });
        if (departmentSelect.value) {
            filterCoursesByDepartment(departmentSelect.value);
        }
    }

    // Handle course and academic year change - load groups
    if (courseSelect && academicYearSelect) {
        courseSelect.addEventListener('change', loadFilterGroups);
        academicYearSelect.addEventListener('change', loadFilterGroups);
    }

    loadFilterGroups();
});
</script>

<?php if (isset($canExport) && $canExport): ?>
<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-file-excel me-2"></i>Export Students to Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo APP_URL; ?>/students/export-excel" id="exportForm">
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-filter me-2"></i>Apply Filters</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="export_search" class="form-label small fw-bold">Search</label>
                                <input type="text" class="form-control form-control-sm" id="export_search" name="search" 
                                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                                       placeholder="Name, ID, Email, NIC">
                            </div>
                            <?php if (!isset($isHOD) || !$isHOD): ?>
                            <div class="col-md-6">
                                <label for="export_status" class="form-label small fw-bold">Status</label>
                                <select class="form-select form-select-sm" id="export_status" name="status">
                                    <option value="">All Status</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" 
                                                <?php echo (isset($filters['status']) && $filters['status'] === $status) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label for="export_gender" class="form-label small fw-bold">Gender</label>
                                <select class="form-select form-select-sm" id="export_gender" name="gender">
                                    <option value="">All Genders</option>
                                    <?php foreach ($genders as $gender): ?>
                                        <option value="<?php echo htmlspecialchars($gender); ?>" 
                                                <?php echo (isset($filters['gender']) && $filters['gender'] === $gender) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($gender); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="export_district" class="form-label small fw-bold">District</label>
                                <select class="form-select form-select-sm" id="export_district" name="district">
                                    <option value="">All Districts</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo htmlspecialchars($district); ?>" 
                                                <?php echo (isset($filters['district']) && $filters['district'] === $district) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($district); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="export_department_id" class="form-label small fw-bold">Department</label>
                                <select class="form-select form-select-sm" id="export_department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>" 
                                                <?php echo (isset($filters['department_id']) && $filters['department_id'] === $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="export_course_id" class="form-label small fw-bold">Course</label>
                                <select class="form-select form-select-sm" id="export_course_id" name="course_id">
                                    <option value="">All Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>" 
                                                data-department="<?php echo htmlspecialchars($course['department_id'] ?? ''); ?>"
                                                <?php echo (isset($filters['course_id']) && $filters['course_id'] === $course['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="export_academic_year" class="form-label small fw-bold">Academic Year</label>
                                <select class="form-select form-select-sm" id="export_academic_year" name="academic_year">
                                    <option value="">All Years</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>" 
                                                <?php echo (isset($filters['academic_year']) && $filters['academic_year'] === $year) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="export_group_id" class="form-label small fw-bold">Group</label>
                                <select class="form-select form-select-sm" id="export_group_id" name="group_id">
                                    <option value="">All Groups</option>
                                </select>
                                <small class="text-muted">Select course & academic year first to load groups</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold mb-3"><i class="fas fa-columns me-2"></i>Select Columns to Export</h6>
                        <div class="row">
                            <?php
                            $availableColumns = [
                                'student_id' => 'Student ID',
                                'student_title' => 'Title',
                                'student_fullname' => 'Full Name',
                                'student_ininame' => 'Name with Initials',
                                'student_gender' => 'Gender',
                                'student_civil' => 'Civil Status',
                                'student_email' => 'Email',
                                'student_nic' => 'NIC',
                                'student_dob' => 'Date of Birth',
                                'student_phone' => 'Phone',
                                'student_address' => 'Address',
                                'student_zip' => 'ZIP Code',
                                'student_district' => 'District',
                                'student_divisions' => 'Divisions',
                                'student_provice' => 'Province',
                                'student_blood' => 'Blood Group',
                                'student_em_name' => 'Emergency Contact Name',
                                'student_em_address' => 'Emergency Contact Address',
                                'student_em_phone' => 'Emergency Contact Phone',
                                'student_em_relation' => 'Emergency Contact Relation',
                                'student_status' => 'Status',
                                'allowance_eligible' => 'Allowance Eligible',
                                'course_name' => 'Course',
                                'department_name' => 'Department',
                                'academic_year' => 'Academic Year',
                                'enrollment_status' => 'Enrollment Status'
                            ];
                            
                            $defaultColumns = ['student_id', 'student_fullname', 'student_email', 'student_nic', 'student_gender', 'student_status'];
                            $chunks = array_chunk($availableColumns, ceil(count($availableColumns) / 3), true);
                            foreach ($chunks as $chunk): ?>
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <?php foreach ($chunk as $colKey => $colLabel): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="columns[]" 
                                               value="<?php echo htmlspecialchars($colKey); ?>" 
                                               id="col_<?php echo htmlspecialchars($colKey); ?>"
                                               <?php echo in_array($colKey, $defaultColumns) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small" for="col_<?php echo htmlspecialchars($colKey); ?>">
                                            <?php echo htmlspecialchars($colLabel); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllColumns()">
                                <i class="fas fa-check-square me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllColumns()">
                                <i class="fas fa-square me-1"></i>Deselect All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-excel me-1"></i>Export to Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle department change in export modal - filter courses, load groups
document.addEventListener('DOMContentLoaded', function() {
    const exportDeptSelect = document.getElementById('export_department_id');
    const exportCourseSelect = document.getElementById('export_course_id');
    const exportAcademicYearSelect = document.getElementById('export_academic_year');
    const exportGroupSelect = document.getElementById('export_group_id');
    
    if (exportDeptSelect && exportCourseSelect) {
        exportDeptSelect.addEventListener('change', function() {
            const selectedDept = this.value;
            const options = exportCourseSelect.querySelectorAll('option');
            
            options.forEach(function(option) {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                const optionDept = option.getAttribute('data-department');
                if (selectedDept === '' || optionDept === selectedDept) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset course selection if it's hidden
            if (exportCourseSelect.value && exportCourseSelect.options[exportCourseSelect.selectedIndex].style.display === 'none') {
                exportCourseSelect.value = '';
            }
            loadExportGroups();
        });
    }
    
    function loadExportGroups() {
        if (!exportCourseSelect || !exportAcademicYearSelect || !exportGroupSelect) return;
        const courseId = exportCourseSelect.value;
        const academicYear = exportAcademicYearSelect.value;
        
        exportGroupSelect.innerHTML = '<option value="">All Groups</option>';
        if (!courseId || !academicYear) return;
        
        fetch('<?php echo APP_URL; ?>/attendance/get-groups-by-course-and-year?course_id=' + encodeURIComponent(courseId) + '&academic_year=' + encodeURIComponent(academicYear))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.groups && data.groups.length) {
                    data.groups.forEach(function(g) {
                        const opt = document.createElement('option');
                        opt.value = g.id;
                        opt.textContent = g.name + (g.course_name ? ' - ' + g.course_name : '');
                        exportGroupSelect.appendChild(opt);
                    });
                }
            })
            .catch(function() {});
    }
    
    if (exportCourseSelect && exportAcademicYearSelect) {
        exportCourseSelect.addEventListener('change', loadExportGroups);
        exportAcademicYearSelect.addEventListener('change', loadExportGroups);
        loadExportGroups();
    }
});

function selectAllColumns() {
    document.querySelectorAll('#exportModal input[type="checkbox"][name="columns[]"]').forEach(function(checkbox) {
        checkbox.checked = true;
    });
}

function deselectAllColumns() {
    document.querySelectorAll('#exportModal input[type="checkbox"][name="columns[]"]').forEach(function(checkbox) {
        checkbox.checked = false;
    });
}
</script>

<!-- Envelope / Name List Modal -->
<div class="modal fade" id="envelopeModal" tabindex="-1" aria-labelledby="envelopeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="envelopeModalLabel">
                    <i class="fas fa-envelope me-2"></i>Download Student Envelopes / Name List
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo APP_URL; ?>/students/envelopes" id="envelopeForm">
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Choose filters below, then download either printable DL envelopes (220&nbsp;&times;&nbsp;110&nbsp;mm) or an A4 student name list PDF.
                        Only current students (Following and Internship) are included.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-block mb-1">Download type</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="download_type" id="envTypeEnvelope" value="envelopes" checked>
                                <label class="form-check-label" for="envTypeEnvelope">Student Envelopes (name + address)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="download_type" id="envTypeNameList" value="name_list">
                                <label class="form-check-label" for="envTypeNameList">Student Name List (PDF)</label>
                            </div>
                        </div>
                    </div>
                    <div id="envSelectedInfo" class="alert alert-info py-2 small d-none"></div>
                    <div id="envFilterFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="env_academic_year" class="form-label small fw-bold">Academic Year <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="env_academic_year" name="academic_year">
                                    <option value="">Select academic year</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>"
                                                <?php echo (isset($filters['academic_year']) && $filters['academic_year'] === $year) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!isset($isHOD) || !$isHOD): ?>
                            <div class="col-md-6">
                                <label for="env_department_id" class="form-label small fw-bold">Department</label>
                                <select class="form-select form-select-sm" id="env_department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>"
                                                <?php echo (isset($filters['department_id']) && $filters['department_id'] === $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label for="env_course_id" class="form-label small fw-bold">Course</label>
                                <select class="form-select form-select-sm" id="env_course_id" name="course_id">
                                    <option value="">All Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                                data-department="<?php echo htmlspecialchars($course['department_id'] ?? ''); ?>"
                                                <?php echo (isset($filters['course_id']) && $filters['course_id'] === $course['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold d-block">NVQ Level</label>
                                <div class="d-flex flex-wrap gap-3 pt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="nvq_levels[]" value="04" id="env_level_04">
                                        <label class="form-check-label" for="env_level_04">Level 04</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="nvq_levels[]" value="05" id="env_level_05">
                                        <label class="form-check-label" for="env_level_05">Level 05</label>
                                    </div>
                                </div>
                                <div class="form-text">Leave both unticked to include all levels.</div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                    <label class="form-label small fw-bold mb-0">Provinces</label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" id="envProvAll">Select all</button>
                                        <button type="button" class="btn btn-outline-secondary" id="envProvNone">Clear</button>
                                    </div>
                                </div>
                                <input type="search" class="form-control form-control-sm mb-2" id="envProvSearch" placeholder="Search province…" autocomplete="off">
                                <div class="border rounded p-2 bg-light" id="envProvinceBox" style="max-height:160px; overflow:auto;">
                                    <div class="row g-1">
                                        <?php foreach (($provinces ?? []) as $provinceName):
                                            $provId = 'env_prov_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) $provinceName);
                                        ?>
                                        <div class="col-md-4 col-sm-6 env-prov-item" data-name="<?php echo htmlspecialchars(strtolower((string) $provinceName)); ?>">
                                            <div class="form-check">
                                                <input class="form-check-input env-province" type="checkbox" name="provinces[]" value="<?php echo htmlspecialchars((string) $provinceName); ?>" id="<?php echo htmlspecialchars($provId); ?>">
                                                <label class="form-check-label small" for="<?php echo htmlspecialchars($provId); ?>"><?php echo htmlspecialchars((string) $provinceName); ?></label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-text">Leave all unticked to include every province.</div>
                            </div>
                        </div>
                    </div>
                    <div id="envIdFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning" id="envDownloadBtn">
                        <i class="fas fa-download me-1"></i>Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('envelopeModal');
    var form = document.getElementById('envelopeForm');
    var selectAll = document.getElementById('envSelectAll');
    var info = document.getElementById('envSelectedInfo');
    var filterFields = document.getElementById('envFilterFields');
    var idFields = document.getElementById('envIdFields');
    var yearSelect = document.getElementById('env_academic_year');
    var deptSelect = document.getElementById('env_department_id');
    var courseSelect = document.getElementById('env_course_id');
    var downloadBtn = document.getElementById('envDownloadBtn');
    var typeEnvelope = document.getElementById('envTypeEnvelope');
    var typeNameList = document.getElementById('envTypeNameList');
    var envelopesUrl = <?php echo json_encode(APP_URL . '/students/envelopes'); ?>;
    var nameListUrl = <?php echo json_encode(APP_URL . '/students/name-list'); ?>;

    function ticked() {
        return Array.prototype.slice.call(document.querySelectorAll('.env-select:checked'));
    }

    function syncDownloadTarget() {
        if (!form) return;
        var nameList = typeNameList && typeNameList.checked;
        form.action = nameList ? nameListUrl : envelopesUrl;
        if (downloadBtn) {
            downloadBtn.innerHTML = nameList
                ? '<i class="fas fa-list me-1"></i>Download Name List PDF'
                : '<i class="fas fa-envelope me-1"></i>Download Envelopes PDF';
        }
    }

    if (typeEnvelope) typeEnvelope.addEventListener('change', syncDownloadTarget);
    if (typeNameList) typeNameList.addEventListener('change', syncDownloadTarget);
    syncDownloadTarget();

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.env-select').forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }

    // Show only the courses of the chosen department
    if (deptSelect && courseSelect) {
        deptSelect.addEventListener('change', function() {
            Array.prototype.forEach.call(courseSelect.options, function(opt) {
                var d = opt.getAttribute('data-department');
                opt.hidden = !!(opt.value && deptSelect.value && d !== deptSelect.value);
            });
            if (courseSelect.selectedOptions[0] && courseSelect.selectedOptions[0].hidden) {
                courseSelect.value = '';
            }
        });
    }

    var filterProvAll = document.getElementById('filterProvAll');
    var filterProvNone = document.getElementById('filterProvNone');
    function filterProvinceBoxes() {
        return Array.prototype.slice.call(document.querySelectorAll('.filter-province'));
    }
    if (filterProvAll) {
        filterProvAll.addEventListener('click', function() {
            filterProvinceBoxes().forEach(function(cb) { cb.checked = true; });
        });
    }
    if (filterProvNone) {
        filterProvNone.addEventListener('click', function() {
            filterProvinceBoxes().forEach(function(cb) { cb.checked = false; });
        });
    }

    var provAll = document.getElementById('envProvAll');
    var provNone = document.getElementById('envProvNone');
    var provSearch = document.getElementById('envProvSearch');
    function provinceBoxes() {
        return Array.prototype.slice.call(document.querySelectorAll('.env-province'));
    }
    if (provAll) {
        provAll.addEventListener('click', function() {
            provinceBoxes().forEach(function(cb) {
                if (cb.closest('.env-prov-item') && cb.closest('.env-prov-item').style.display === 'none') return;
                cb.checked = true;
            });
        });
    }
    if (provNone) {
        provNone.addEventListener('click', function() {
            provinceBoxes().forEach(function(cb) { cb.checked = false; });
        });
    }
    if (provSearch) {
        provSearch.addEventListener('input', function() {
            var q = provSearch.value.trim().toLowerCase();
            document.querySelectorAll('.env-prov-item').forEach(function(item) {
                var name = item.getAttribute('data-name') || '';
                item.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function() {
            syncDownloadTarget();
            var n = ticked().length;
            if (n > 0) {
                info.classList.remove('d-none');
                info.innerHTML = '<i class="fas fa-check-circle me-1"></i><strong>' + n + '</strong> student' + (n === 1 ? '' : 's') +
                    ' selected on this page. Download will include these students only.';
                filterFields.classList.add('d-none');
            } else {
                info.classList.add('d-none');
                filterFields.classList.remove('d-none');
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(ev) {
            syncDownloadTarget();
            idFields.innerHTML = '';
            var rows = ticked();
            if (rows.length > 0) {
                rows.forEach(function(cb) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'student_ids[]';
                    h.value = cb.value;
                    idFields.appendChild(h);
                });
            } else if (yearSelect && !yearSelect.value) {
                ev.preventDefault();
                alert('Please select an academic year, or select students on the list first.');
            }
        });
    }
});
</script>

<!-- Calling Letter Modal (L4 / L5 official templates) -->
<div class="modal fade" id="callingLetterModal" tabindex="-1" aria-labelledby="callingLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="callingLetterModalLabel">
                    <i class="fas fa-file-alt me-2"></i>Calling Letters
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo APP_URL; ?>/students/calling-letters" id="callingLetterForm">
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Downloads the official calling letter for each eligible student (4 pages: letter + instructions + signature + blank A4; aligned fields; Branch Principal signature), merged into one PDF.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-block mb-1">Letter format <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="letter_format" id="clFmtL4North" value="l4_northern" checked>
                                <label class="form-check-label" for="clFmtL4North">Level 04 — Northern Province</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="letter_format" id="clFmtL4Other" value="l4_other">
                                <label class="form-check-label" for="clFmtL4Other">Level 04 — Other Provinces</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="letter_format" id="clFmtL5North" value="l5_northern">
                                <label class="form-check-label" for="clFmtL5North">Level 05 — Northern Province</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="letter_format" id="clFmtL5Other" value="l5_other">
                                <label class="form-check-label" for="clFmtL5Other">Level 05 — Other Provinces</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="cl_academic_year" class="form-label small fw-bold">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="cl_academic_year" name="academic_year" required>
                            <option value="">Select academic year</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"
                                        <?php echo (isset($filters['academic_year']) && $filters['academic_year'] === $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!isset($isHOD) || !$isHOD): ?>
                    <div class="mb-3">
                        <label for="cl_department_id" class="form-label small fw-bold">Department (optional)</label>
                        <select class="form-select form-select-sm" id="cl_department_id" name="department_id">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_id']); ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-0">
                        <label for="cl_course_id" class="form-label small fw-bold">Course (optional)</label>
                        <select class="form-select form-select-sm" id="cl_course_id" name="course_id">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                        data-department="<?php echo htmlspecialchars($course['department_id'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-download me-1"></i>Download Calling Letters PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var clDept = document.getElementById('cl_department_id');
    var clCourse = document.getElementById('cl_course_id');
    var clForm = document.getElementById('callingLetterForm');
    var clYear = document.getElementById('cl_academic_year');
    if (clDept && clCourse) {
        clDept.addEventListener('change', function() {
            Array.prototype.forEach.call(clCourse.options, function(opt) {
                var d = opt.getAttribute('data-department');
                opt.hidden = !!(opt.value && clDept.value && d !== clDept.value);
            });
            if (clCourse.selectedOptions[0] && clCourse.selectedOptions[0].hidden) {
                clCourse.value = '';
            }
        });
    }
    if (clForm) {
        clForm.addEventListener('submit', function(ev) {
            if (clYear && !clYear.value) {
                ev.preventDefault();
                alert('Please select an academic year.');
            }
        });
    }
});
</script>
<?php endif; ?>

