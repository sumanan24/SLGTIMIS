<?php
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$courseId = (string) ($course['course_id'] ?? '');
$cq = urlencode($courseId);
$versionFilter = $versionFilter ?? null;
$defaultVersion = $versionFilter !== null ? $versionFilter : (int) ($versionNumbers[0] ?? 0);
$statusKey = (string) ($course['course_status'] ?? 'active');
$statusLabels = ['active' => 'Active', 'draft' => 'Draft', 'deactivated' => 'Inactive'];
$statusClass = $statusKey === 'active' ? 'cw-chip-ok' : ($statusKey === 'draft' ? 'cw-chip-warn' : 'cw-chip-off');
$verLabel = static fn($v) => (int) $v === 0 ? 'Default' : 'Version ' . (int) $v;
$base = APP_URL . '/courses/manage?id=' . $cq;
$addModuleUrl = APP_URL . '/modules/create?course_id=' . $cq . '&course_version=' . $defaultVersion . '&return_course=1';
$addGroupUrl = APP_URL . '/groups/create?course_id=' . $cq . '&course_version=' . $defaultVersion;
?>
<style>
.cw-wrap { max-width: 1180px; margin: 0 auto; }
.cw-back { display: inline-flex; align-items: center; gap: .4rem; font-size: .875rem; color: #64748b; text-decoration: none; margin-bottom: .75rem; }
.cw-back:hover { color: #0d6efd; }
.cw-head { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1.25rem 1.5rem; margin-bottom: 1rem; }
.cw-head h1 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .35rem; color: #0f172a; }
.cw-meta { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; font-size: .875rem; color: #475569; }
.cw-chip { display: inline-block; font-size: .72rem; font-weight: 600; padding: .25rem .6rem; border-radius: 999px; }
.cw-chip-id { background: #eff6ff; color: #1d4ed8; font-family: monospace; }
.cw-chip-nvq { background: rgba(13,110,253,.12); color: #0d6efd; }
.cw-chip-ok { background: rgba(25,135,84,.15); color: #198754; }
.cw-chip-warn { background: rgba(255,193,7,.22); color: #856404; }
.cw-chip-off { background: rgba(108,117,125,.18); color: #495057; }
.cw-chip-ver { background: #f1f5f9; color: #334155; }
.cw-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 1rem; }
.cw-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: .9rem 1rem; }
.cw-stat .n { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: #0f172a; }
.cw-stat .l { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-top: .2rem; }
.cw-versions { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: 1rem; }
.cw-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .85rem; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .85rem; text-decoration: none; }
.cw-pill:hover { border-color: #0d6efd; color: #0d6efd; }
.cw-pill.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
.cw-pill small { opacity: .8; }
.cw-card { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; margin-bottom: 1rem; overflow: hidden; }
.cw-card-h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .85rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.cw-card-h h2 { font-size: 1rem; font-weight: 700; margin: 0; color: #0f172a; }
.cw-card-h h2 span { font-weight: 500; color: #64748b; font-size: .85rem; margin-left: .35rem; }
.cw-table { width: 100%; margin: 0; }
.cw-table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #475569; background: #fff; padding: .6rem 1rem; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.cw-table tbody td { padding: .65rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.cw-table tbody tr:last-child td { border-bottom: 0; }
.cw-table .cw-act { text-align: right; white-space: nowrap; }
.cw-sem-row td { background: #f8fafc; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #475569; padding: .4rem 1rem; }
.cw-empty { text-align: center; padding: 2rem 1rem; color: #64748b; }
.cw-empty i { font-size: 1.75rem; display: block; margin-bottom: .5rem; color: #94a3b8; }
@media (max-width: 768px) {
    .cw-wrap.container-fluid { padding-left: .75rem !important; padding-right: .75rem !important; }
    .cw-stats { grid-template-columns: repeat(2, 1fr); }
    .cw-head { padding: 1rem; }
    .cw-card-h .btn { flex: 1 1 auto; }
    .cw-table thead { display: none; }
    .cw-table, .cw-table tbody, .cw-table tr, .cw-table td { display: block; width: 100%; }
    .cw-table tbody tr:not(.cw-sem-row) { padding: .6rem 1rem; border-bottom: 1px solid #e2e8f0; }
    .cw-table tbody td { border: 0; padding: .2rem 0; display: flex; justify-content: space-between; gap: 1rem; text-align: right; }
    .cw-table tbody td::before { content: attr(data-label); font-size: .72rem; font-weight: 600; text-transform: uppercase; color: #64748b; text-align: left; flex-shrink: 0; }
    .cw-table tbody td.cw-act { justify-content: flex-end; padding-top: .5rem; }
    .cw-table tbody td.cw-act::before { display: none; }
    .cw-sem-row td { display: block; text-align: left; }
    .cw-sem-row td::before { display: none; }
}
</style>

<div class="container-fluid px-4 py-3 cw-wrap">
    <a class="cw-back" href="<?php echo APP_URL; ?>/courses"><i class="fas fa-arrow-left"></i>Back to courses</a>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $h($message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo $h($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="cw-head">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h1><?php echo $h($course['course_name'] ?? ''); ?></h1>
                <div class="cw-meta">
                    <span class="cw-chip cw-chip-id"><?php echo $h($courseId); ?></span>
                    <span class="cw-chip cw-chip-nvq">NVQ <?php echo $h($course['course_nvq_level'] ?? ''); ?></span>
                    <span class="cw-chip <?php echo $statusClass; ?>"><?php echo $h($statusLabels[$statusKey] ?? ucfirst($statusKey)); ?></span>
                    <span><i class="fas fa-building me-1"></i><?php echo $h($course['department_name'] ?? ''); ?></span>
                    <span><i class="fas fa-clock me-1"></i><?php echo (int) ($course['course_institute_training'] ?? 0); ?> institute / <?php echo (int) ($course['course_ojt_duration'] ?? 0); ?> OJT</span>
                </div>
            </div>
            <?php if (!empty($canEditCourse)): ?>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/courses/edit?id=<?php echo $cq; ?>"><i class="fas fa-edit me-1"></i>Edit course</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="cw-stats">
        <div class="cw-stat"><div class="n"><?php echo count($modules); ?></div><div class="l">Modules</div></div>
        <div class="cw-stat"><div class="n"><?php echo $h(rtrim(rtrim(number_format((float) $creditTotal, 1), '0'), '.') ?: '0'); ?></div><div class="l">Total credits</div></div>
        <div class="cw-stat"><div class="n"><?php echo $canViewGroups ? count($groups) : '—'; ?></div><div class="l">Groups</div></div>
        <div class="cw-stat"><div class="n"><?php echo $canViewGroups ? (int) $studentTotal : '—'; ?></div><div class="l">Students in groups</div></div>
    </div>

    <div class="cw-versions">
        <span class="small text-muted me-1">Version:</span>
        <a class="cw-pill <?php echo $versionFilter === null ? 'active' : ''; ?>" href="<?php echo $base; ?>">All</a>
        <?php foreach ($versionNumbers as $vn): ?>
            <a class="cw-pill <?php echo $versionFilter === (int) $vn ? 'active' : ''; ?>" href="<?php echo $base . '&version=' . (int) $vn; ?>">
                <?php echo $h($verLabel($vn)); ?>
                <small><?php echo (int) ($moduleCountByVersion[$vn] ?? 0); ?> mod<?php echo $canViewGroups ? ' · ' . (int) ($groupCountByVersion[$vn] ?? 0) . ' grp' : ''; ?></small>
            </a>
        <?php endforeach; ?>
        <?php if (!empty($canManageVersions)): ?>
            <a class="btn btn-outline-secondary btn-sm ms-auto" href="<?php echo APP_URL; ?>/courses/new-version?id=<?php echo $cq; ?>"
               onclick="return confirm('Add a new version for this course? New enrollments will use it.');">
                <i class="fas fa-code-branch me-1"></i>Add version
            </a>
        <?php endif; ?>
    </div>

    <!-- Modules -->
    <section class="cw-card">
        <div class="cw-card-h">
            <h2><i class="fas fa-layer-group text-primary me-2"></i>Modules<span><?php echo $versionFilter === null ? 'all versions' : $h($verLabel($versionFilter)); ?></span></h2>
            <?php if (!empty($canEditModules)): ?>
                <a class="btn btn-primary btn-sm" href="<?php echo $h($addModuleUrl); ?>"><i class="fas fa-plus me-1"></i>Add / import modules</a>
            <?php endif; ?>
        </div>
        <?php if (empty($modules)): ?>
            <div class="cw-empty"><i class="fas fa-layer-group"></i>No modules yet for this course<?php echo $versionFilter === null ? '' : ' version'; ?>.</div>
        <?php else: ?>
            <table class="table cw-table">
                <thead>
                    <tr>
                        <th>Module ID</th>
                        <th>Module name</th>
                        <th>Version</th>
                        <th>Credit</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $lastSem = '__init__';
                foreach ($modules as $m):
                    $sem = $m['semester'] ?? null;
                    $semKey = ($sem === null || $sem === '') ? '' : (string) (int) $sem;
                    if ($semKey !== $lastSem):
                        $lastSem = $semKey;
                ?>
                    <tr class="cw-sem-row"><td colspan="5"><?php echo $semKey === '' ? 'No semester set' : 'Semester ' . $h($semKey); ?></td></tr>
                <?php endif;
                    $ver = (int) ($m['course_version'] ?? 0);
                    $mq = 'course_id=' . urlencode((string) $m['course_id']) . '&module_id=' . urlencode((string) $m['module_id']) . '&course_version=' . $ver;
                ?>
                    <tr>
                        <td data-label="Module ID"><code class="text-primary fw-semibold"><?php echo $h($m['module_id']); ?></code></td>
                        <td data-label="Module"><?php echo $h($m['module_name'] ?? ''); ?></td>
                        <td data-label="Version"><span class="cw-chip cw-chip-ver"><?php echo $h($verLabel($ver)); ?></span></td>
                        <td data-label="Credit"><?php echo ($m['credit'] ?? '') !== '' && $m['credit'] !== null ? $h($m['credit']) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="cw-act">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/modules/view?<?php echo $mq; ?>" title="View"><i class="fas fa-eye"></i></a>
                            <?php if (!empty($canEditModules)): ?>
                                <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/modules/edit?<?php echo $mq; ?>&return_course=1" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($canDeleteModules)): ?>
                                <a class="btn btn-outline-danger btn-sm" href="<?php echo APP_URL; ?>/modules/delete?<?php echo $mq; ?>&return_course=1" title="Delete" onclick="return confirm('Delete this module?');"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Groups -->
    <?php if (!empty($canViewGroups)): ?>
    <section class="cw-card">
        <div class="cw-card-h">
            <h2><i class="fas fa-users text-primary me-2"></i>Groups<span><?php echo $versionFilter === null ? 'all versions' : $h($verLabel($versionFilter)); ?></span></h2>
            <?php if (!empty($canManageGroups)): ?>
                <a class="btn btn-primary btn-sm" href="<?php echo $h($addGroupUrl); ?>"><i class="fas fa-plus me-1"></i>Add group</a>
            <?php endif; ?>
        </div>
        <?php if (empty($groups)): ?>
            <div class="cw-empty"><i class="fas fa-users"></i>No groups yet for this course<?php echo $versionFilter === null ? '' : ' version'; ?>.</div>
        <?php else: ?>
            <table class="table cw-table">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Version</th>
                        <th>Academic year</th>
                        <th class="text-center">Students</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($groups as $g):
                    $gq = urlencode((string) $g['id']);
                    $isActive = ($g['status'] ?? 'active') === 'active';
                ?>
                    <tr>
                        <td data-label="Group"><span class="fw-semibold"><?php echo $h($g['name'] ?? ''); ?></span></td>
                        <td data-label="Version"><span class="cw-chip cw-chip-ver"><?php echo $h($verLabel($g['course_version'] ?? 0)); ?></span></td>
                        <td data-label="Academic year"><?php echo $h($g['academic_year'] ?? '—'); ?></td>
                        <td data-label="Students" class="text-center"><span class="badge bg-info rounded-pill px-3"><?php echo (int) ($g['student_count'] ?? 0); ?></span></td>
                        <td data-label="Status"><span class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?> rounded-pill px-3"><?php echo $h(ucfirst((string) ($g['status'] ?? 'active'))); ?></span></td>
                        <td class="cw-act">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/groups/show?id=<?php echo $gq; ?>" title="Students"><i class="fas fa-eye"></i></a>
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/groups/timetable?id=<?php echo $gq; ?>" title="Timetable"><i class="fas fa-calendar-alt"></i></a>
                            <?php if (!empty($canManageGroups)): ?>
                                <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/groups/edit?id=<?php echo $gq; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>
