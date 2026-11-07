<?php
$h = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$isADM = !empty($isADM);
$categories = DepartmentModel::categories();
$headTypes = DepartmentModel::headTypes();

$deptId = (string) ($department['department_id'] ?? '');
$deptName = (string) ($department['department_name'] ?? '');
$catKey = DepartmentModel::normalizeCategory($department['department_category'] ?? '');
$courseCount = (int) ($department['course_count'] ?? 0);
$staffCount = (int) ($department['staff_count'] ?? 0);

$headId = (string) ($department['head_staff_id'] ?? '');
$headType = (string) ($department['head_type'] ?? '');
$hasHead = $headId !== '' && isset($headTypes[$headType]);
$headName = (string) ($department['head_name'] ?? ($headContact['staff_name'] ?? $headId));

$stTotals = $studentOverview['totals'] ?? [];
$stCourses = $studentOverview['courses'] ?? [];
$staffMembers = $staffOverview['members'] ?? [];
$staffByStatus = $staffOverview['by_status'] ?? [];
$staffByType = $staffOverview['by_type'] ?? [];
$staffTotal = (int) ($staffOverview['total'] ?? 0);

$fmtDate = static function ($d): string {
    $d = (string) $d;
    if ($d === '' || strpos($d, '0000') === 0) {
        return '—';
    }
    $t = strtotime($d);
    return $t ? date('d M Y', $t) : '—';
};
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : '?';
};
?>
<style>
.dv-page {
    --dv-navy: #001f3f;
    --dv-text: #0f172a;
    --dv-muted: #64748b;
    --dv-border: #e2e8f0;
    --dv-soft: #f8fafc;
}
.dv-page .dv-head {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
    gap: 1rem; margin-bottom: 1.25rem;
}
.dv-page .dv-title { font-size: 1.4rem; font-weight: 700; color: var(--dv-text); margin: 0 0 0.35rem; line-height: 1.3; }
.dv-page .dv-sub { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--dv-muted); }
.dv-page .dv-head-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.dv-page .dv-head-actions .btn { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; }

.dv-chip {
    display: inline-flex; align-items: center; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em;
    color: var(--dv-navy); background: #eef3f8; border-radius: 999px; padding: 0.2rem 0.6rem;
}
.dv-badge {
    display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.03em; border-radius: 999px; padding: 0.2rem 0.6rem; white-space: nowrap;
}
.dv-badge.cat-academic { background: #e8f0fb; color: #0b4a8f; }
.dv-badge.cat-admin { background: #f3e8fb; color: #6b21a8; }
.dv-badge.head-permanent { background: #e6f4ec; color: #1b6b43; }
.dv-badge.head-acting { background: #fff4de; color: #92580a; }
.dv-badge.head-cover_up { background: #e0f2fe; color: #0b5d86; }
.dv-badge.st-working, .dv-badge.st-following { background: #e6f4ec; color: #1b6b43; }
.dv-badge.st-completed { background: #e8f0fb; color: #0b4a8f; }
.dv-badge.st-left, .dv-badge.st-resigned, .dv-badge.st-terminated { background: #fde8e8; color: #9b1c1c; }
.dv-badge.st-ongoing { background: #e6f4ec; color: #1b6b43; }
.dv-badge.st-ended { background: #f1f5f9; color: #64748b; }

.dv-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; margin-bottom: 1.25rem; }
.dv-stat {
    background: #fff; border: 1px solid var(--dv-border); border-radius: 0.75rem;
    padding: 0.9rem 1rem; display: flex; align-items: center; gap: 0.8rem; min-width: 0;
    text-decoration: none; color: inherit;
}
a.dv-stat:hover { border-color: #94a3b8; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07); color: inherit; }
.dv-stat-icon {
    width: 2.6rem; height: 2.6rem; border-radius: 0.65rem; display: inline-flex;
    align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;
}
.dv-stat-value { display: block; font-size: 1.4rem; font-weight: 700; line-height: 1.1; color: var(--dv-text); }
.dv-stat-label { display: block; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--dv-muted); }
.dv-stat-sub { display: block; font-size: 0.75rem; color: var(--dv-muted); }

.dv-panel {
    background: #fff; border: 1px solid var(--dv-border); border-radius: 0.75rem;
    margin-bottom: 1.25rem; overflow: hidden; height: calc(100% - 1.25rem);
}
.dv-panel-head {
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap;
    padding: 0.8rem 1.1rem; border-bottom: 1px solid var(--dv-border); background: var(--dv-soft);
}
.dv-panel-title { margin: 0; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--dv-navy); }
.dv-panel-body { padding: 1rem 1.1rem; }
.dv-link { font-size: 0.8125rem; font-weight: 600; text-decoration: none; }

.dv-head-card { display: flex; gap: 0.9rem; align-items: flex-start; }
.dv-avatar {
    width: 3.4rem; height: 3.4rem; border-radius: 50%; background: var(--dv-navy); color: #fff;
    display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.05rem; flex-shrink: 0;
}
.dv-head-name { font-size: 1.05rem; font-weight: 700; color: var(--dv-text); margin: 0 0 0.25rem; word-break: break-word; }
.dv-kv { display: grid; grid-template-columns: 7rem 1fr; gap: 0.3rem 0.75rem; font-size: 0.8125rem; margin-top: 0.7rem; }
.dv-kv dt { color: var(--dv-muted); font-weight: 600; margin: 0; }
.dv-kv dd { margin: 0; color: var(--dv-text); word-break: break-word; }
.dv-empty { color: #94a3b8; font-style: italic; font-size: 0.875rem; margin: 0; }
.dv-note {
    margin-top: 0.8rem; padding: 0.55rem 0.75rem; border-radius: 0.5rem; background: #fff8e6;
    border: 1px solid #f6e3b0; color: #7a5a06; font-size: 0.8rem;
}

.dv-timeline { list-style: none; margin: 0; padding: 0; }
.dv-timeline li { position: relative; padding: 0 0 0.9rem 1.35rem; border-left: 2px solid var(--dv-border); margin-left: 0.35rem; }
.dv-timeline li:last-child { padding-bottom: 0; border-left-color: transparent; }
.dv-timeline li::before {
    content: ''; position: absolute; left: -0.45rem; top: 0.25rem; width: 0.7rem; height: 0.7rem;
    border-radius: 50%; background: #94a3b8; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--dv-border);
}
.dv-timeline li.is-current::before { background: #1b6b43; }
.dv-tl-title { font-weight: 600; font-size: 0.875rem; color: var(--dv-text); }
.dv-tl-meta { font-size: 0.75rem; color: var(--dv-muted); }

.dv-table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.dv-table { width: 100%; margin: 0; border-collapse: collapse; font-size: 0.8125rem; color: var(--dv-text); }
.dv-table th {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
    color: var(--dv-muted); background: var(--dv-soft); padding: 0.6rem 0.85rem; white-space: nowrap;
    border-bottom: 1px solid var(--dv-border); text-align: left;
}
.dv-table td { padding: 0.6rem 0.85rem; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
.dv-table tbody tr:last-child td { border-bottom: 0; }
.dv-table tbody tr:hover td { background: #f3f7fc; }
.dv-table .num { text-align: right; white-space: nowrap; }
.dv-table th.num { text-align: right; }
.dv-table a { text-decoration: none; font-weight: 600; }

.dv-minis { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.75rem; }
.dv-mini { font-size: 0.75rem; background: #eef2f6; color: #334155; border-radius: 999px; padding: 0.2rem 0.65rem; font-weight: 600; }
.dv-mini b { color: var(--dv-navy); }

@media (max-width: 991.98px) {
    .dv-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .dv-page.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .dv-page .dv-head-actions { width: 100%; }
    .dv-page .dv-head-actions .btn { flex: 1 1 auto; }
    .dv-kv { grid-template-columns: 6rem 1fr; }
    .dv-panel { height: auto; }
    /* Wide tables become stacked, labelled rows */
    .dv-table.stack thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
    .dv-table.stack, .dv-table.stack tbody, .dv-table.stack tr, .dv-table.stack td { display: block; width: 100%; }
    .dv-table.stack tr { padding: 0.55rem 0.9rem; border-bottom: 1px solid var(--dv-border); }
    .dv-table.stack tbody tr:last-child { border-bottom: 0; }
    .dv-table.stack td { display: flex; justify-content: space-between; gap: 1rem; border: 0; padding: 0.2rem 0; text-align: right; }
    .dv-table.stack td::before {
        content: attr(data-label); flex: 0 0 auto; font-size: 0.68rem; font-weight: 700;
        letter-spacing: 0.05em; text-transform: uppercase; color: var(--dv-muted); text-align: left;
    }
    .dv-table.stack tbody tr:hover td { background: transparent; }
}
@media (max-width: 479.98px) {
    .dv-stats { grid-template-columns: 1fr 1fr; gap: 0.6rem; }
    .dv-stat { padding: 0.7rem 0.75rem; gap: 0.6rem; }
    .dv-stat-icon { width: 2.2rem; height: 2.2rem; }
    .dv-stat-value { font-size: 1.15rem; }
}
</style>

<div class="container-fluid px-4 py-3 dv-page">
    <div class="dv-head">
        <div>
            <h1 class="dv-title"><i class="fas fa-building text-primary me-2"></i><?php echo $h($deptName); ?></h1>
            <div class="dv-sub">
                <span class="dv-chip"><?php echo $h($deptId); ?></span>
                <span class="dv-badge cat-<?php echo $h($catKey); ?>"><?php echo $h($categories[$catKey]); ?></span>
                <span>Department profile</span>
            </div>
        </div>
        <div class="dv-head-actions">
            <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <?php if ($isADM): ?>
            <a href="<?php echo APP_URL; ?>/departments/edit?id=<?php echo urlencode($deptId); ?>" class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary -->
    <div class="dv-stats">
        <a class="dv-stat" href="<?php echo APP_URL; ?>/courses?department_id=<?php echo urlencode($deptId); ?>">
            <span class="dv-stat-icon" style="background:#e8f0fb;color:#0b4a8f;"><i class="fas fa-book"></i></span>
            <span>
                <span class="dv-stat-value"><?php echo number_format($courseCount); ?></span>
                <span class="dv-stat-label">Courses</span>
            </span>
        </a>
        <div class="dv-stat">
            <span class="dv-stat-icon" style="background:#e6f4ec;color:#1b6b43;"><i class="fas fa-user-tie"></i></span>
            <span>
                <span class="dv-stat-value"><?php echo number_format((int) ($staffByStatus['Working'] ?? 0)); ?></span>
                <span class="dv-stat-label">Working staff</span>
                <span class="dv-stat-sub">of <?php echo number_format($staffCount); ?> on record</span>
            </span>
        </div>
        <a class="dv-stat" href="<?php echo APP_URL; ?>/students?department_id=<?php echo urlencode($deptId); ?>">
            <span class="dv-stat-icon" style="background:#fff4de;color:#92580a;"><i class="fas fa-user-graduate"></i></span>
            <span>
                <span class="dv-stat-value"><?php echo number_format((int) ($stTotals['following'] ?? 0)); ?></span>
                <span class="dv-stat-label">Students following</span>
            </span>
        </a>
        <div class="dv-stat">
            <span class="dv-stat-icon" style="background:#f3e8fb;color:#6b21a8;"><i class="fas fa-history"></i></span>
            <span>
                <span class="dv-stat-value"><?php echo number_format((int) ($stTotals['ever'] ?? 0)); ?></span>
                <span class="dv-stat-label">Students all-time</span>
                <span class="dv-stat-sub"><?php echo number_format((int) ($stTotals['completed'] ?? 0)); ?> completed</span>
            </span>
        </div>
    </div>

    <div class="row g-3">
        <!-- Current head -->
        <div class="col-12 col-lg-6">
            <section class="dv-panel">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-user-tie me-2"></i>Head of department</h2>
                    <?php if ($hasHead): ?>
                        <span class="dv-badge head-<?php echo $h($headType); ?>"><?php echo $h($headTypes[$headType]); ?></span>
                    <?php endif; ?>
                </div>
                <div class="dv-panel-body">
                    <?php if ($hasHead): ?>
                        <div class="dv-head-card">
                            <span class="dv-avatar"><?php echo $h($initials($headName)); ?></span>
                            <div class="min-w-0">
                                <h3 class="dv-head-name"><?php echo $h($headName); ?></h3>
                                <div class="dv-sub"><span class="dv-chip"><?php echo $h($headId); ?></span>
                                    <?php if (!empty($headContact['staff_position'])): ?>
                                        <span><?php echo $h($headContact['staff_position']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <dl class="dv-kv">
                            <dt>Appointment</dt><dd><?php echo $h($headTypes[$headType]); ?> head</dd>
                            <dt>Effective from</dt><dd><?php echo $h($fmtDate($department['head_start_date'] ?? '')); ?></dd>
                            <?php if (!empty($headContact['staff_email'])): ?>
                                <dt>Email</dt><dd><a href="mailto:<?php echo $h($headContact['staff_email']); ?>"><?php echo $h($headContact['staff_email']); ?></a></dd>
                            <?php endif; ?>
                            <?php if (!empty($headContact['staff_pno'])): ?>
                                <dt>Phone</dt><dd><?php echo $h($headContact['staff_pno']); ?></dd>
                            <?php endif; ?>
                            <?php if (!empty($department['head_remarks'])): ?>
                                <dt>Remarks</dt><dd><?php echo $h($department['head_remarks']); ?></dd>
                            <?php endif; ?>
                            <?php if (!empty($department['head_assigned_by'])): ?>
                                <dt>Assigned by</dt>
                                <dd><?php echo $h($department['head_assigned_by']); ?>
                                    <?php if (!empty($department['head_assigned_at'])): ?>· <?php echo $h($fmtDate($department['head_assigned_at'])); ?><?php endif; ?></dd>
                            <?php endif; ?>
                        </dl>
                    <?php else: ?>
                        <p class="dv-empty">No head of department assigned yet.</p>
                        <?php if ($isADM): ?>
                            <a class="btn btn-sm btn-outline-success mt-2" href="<?php echo APP_URL; ?>/departments?search=<?php echo urlencode($deptId); ?>">
                                <i class="fas fa-user-tie me-1"></i>Assign from the list
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($roleHeads) && (!$hasHead || !in_array($headId, array_column($roleHeads, 'staff_id'), true))): ?>
                        <div class="dv-note">
                            <i class="fas fa-info-circle me-1"></i>
                            Staff record role is HOD:
                            <strong><?php echo $h(implode(', ', array_map(static fn ($r) => $r['staff_name'], $roleHeads))); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Head history -->
        <div class="col-12 col-lg-6">
            <section class="dv-panel">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-clock-rotate-left me-2"></i>HOD history</h2>
                    <span class="small text-muted">Last <?php echo count($headHistory); ?></span>
                </div>
                <div class="dv-panel-body">
                    <?php if (!empty($headHistory)): ?>
                        <ul class="dv-timeline">
                            <?php foreach ($headHistory as $row):
                                $hType = (string) ($row['head_type'] ?? '');
                                $open = empty($row['end_date']); ?>
                                <li class="<?php echo $open ? 'is-current' : ''; ?>">
                                    <div class="dv-tl-title">
                                        <?php echo $h($row['staff_name'] !== '' && $row['staff_name'] !== null ? $row['staff_name'] : $row['staff_id']); ?>
                                        <span class="dv-badge head-<?php echo $h($hType); ?> ms-1"><?php echo $h($headTypes[$hType] ?? $hType); ?></span>
                                        <span class="dv-badge <?php echo $open ? 'st-ongoing' : 'st-ended'; ?> ms-1"><?php echo $open ? 'Current' : 'Ended'; ?></span>
                                    </div>
                                    <div class="dv-tl-meta">
                                        <?php echo $h($fmtDate($row['start_date'])); ?> →
                                        <?php echo $open ? 'present' : $h($fmtDate($row['end_date'])); ?>
                                        <?php if (!empty($row['remarks'])): ?> · <?php echo $h($row['remarks']); ?><?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="dv-empty">No head changes recorded yet. Assignments made from now on will appear here.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Staff -->
        <div class="col-12 col-xl-6">
            <section class="dv-panel">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-users me-2"></i>Staff</h2>
                    <span class="small text-muted"><?php echo count($staffMembers); ?> of <?php echo number_format($staffTotal); ?></span>
                </div>
                <div class="dv-panel-body pb-2">
                    <?php if ($staffTotal > 0): ?>
                        <div class="dv-minis">
                            <?php foreach ($staffByStatus as $k => $n): ?>
                                <span class="dv-mini"><?php echo $h($k); ?> <b><?php echo (int) $n; ?></b></span>
                            <?php endforeach; ?>
                            <?php foreach ($staffByType as $k => $n): ?>
                                <span class="dv-mini"><?php echo $h($k); ?> <b><?php echo (int) $n; ?></b></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($staffMembers)): ?>
                    <div class="dv-table-scroll">
                        <table class="dv-table stack">
                            <thead>
                                <tr><th>Name</th><th>Role</th><th>Type</th><th>Joined</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffMembers as $m):
                                    $st = (string) ($m['staff_status'] ?? '');
                                    $isHeadRow = $hasHead && $m['staff_id'] === $headId; ?>
                                    <tr>
                                        <td data-label="Name">
                                            <?php if ($isADM): ?>
                                                <a href="<?php echo APP_URL; ?>/staff/edit?id=<?php echo urlencode($m['staff_id']); ?>"><?php echo $h($m['staff_name']); ?></a>
                                            <?php else: ?>
                                                <strong><?php echo $h($m['staff_name']); ?></strong>
                                            <?php endif; ?>
                                            <?php if ($isHeadRow): ?><i class="fas fa-star text-warning ms-1" title="Head of department"></i><?php endif; ?>
                                        </td>
                                        <td data-label="Role"><?php echo $h($m['staff_position']); ?></td>
                                        <td data-label="Type"><?php echo $h($m['staff_type']); ?></td>
                                        <td data-label="Joined"><?php echo $h($fmtDate($m['staff_date_of_join'])); ?></td>
                                        <td data-label="Status">
                                            <span class="dv-badge st-<?php echo $h(strtolower($st)); ?>"><?php echo $h($st !== '' ? $st : '—'); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="dv-panel-body pt-0"><p class="dv-empty">No staff in this department.</p></div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Students by year (history) -->
        <div class="col-12 col-xl-6">
            <section class="dv-panel">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-user-graduate me-2"></i>Students history</h2>
                    <a class="dv-link" href="<?php echo APP_URL; ?>/students?department_id=<?php echo urlencode($deptId); ?>">View all students</a>
                </div>
                <?php if (!empty($yearHistory)): ?>
                    <div class="dv-table-scroll">
                        <table class="dv-table stack">
                            <thead>
                                <tr>
                                    <th>Academic year</th><th class="num">Enrolled</th><th class="num">Following</th>
                                    <th class="num">Completed</th><th class="num">Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearHistory as $y): ?>
                                    <tr>
                                        <td data-label="Academic year"><strong><?php echo $h($y['academic_year']); ?></strong></td>
                                        <td class="num" data-label="Enrolled"><?php echo number_format((int) $y['total']); ?></td>
                                        <td class="num" data-label="Following"><?php echo number_format((int) $y['following']); ?></td>
                                        <td class="num" data-label="Completed"><?php echo number_format((int) $y['completed']); ?></td>
                                        <td class="num" data-label="Left"><?php echo number_format((int) $y['left_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="dv-panel-body"><p class="dv-empty">No student enrolments recorded.</p></div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Courses -->
        <div class="col-12 col-xl-6">
            <section class="dv-panel">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-book me-2"></i>Courses &amp; students</h2>
                    <a class="dv-link" href="<?php echo APP_URL; ?>/courses?department_id=<?php echo urlencode($deptId); ?>">View courses</a>
                </div>
                <?php if (!empty($stCourses)): ?>
                    <div class="dv-table-scroll">
                        <table class="dv-table stack">
                            <thead>
                                <tr>
                                    <th>Course</th><th>NVQ</th><th class="num">Following</th>
                                    <th class="num">Completed</th><th class="num">Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stCourses as $c): ?>
                                    <tr>
                                        <td data-label="Course">
                                            <strong><?php echo $h(trim((string) $c['course_name'])); ?></strong>
                                            <span class="dv-chip ms-1"><?php echo $h($c['course_id']); ?></span>
                                        </td>
                                        <td data-label="NVQ"><?php echo $h($c['course_nvq_level']); ?></td>
                                        <td class="num" data-label="Following"><?php echo number_format((int) $c['following']); ?></td>
                                        <td class="num" data-label="Completed"><?php echo number_format((int) $c['completed']); ?></td>
                                        <td class="num" data-label="Left"><?php echo number_format((int) $c['left_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="dv-panel-body"><p class="dv-empty">No courses in this department.</p></div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Recent students -->
        <div class="col-12 col-xl-6">
            <section class="dv-panel">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-user-plus me-2"></i>Recent enrolments</h2>
                    <span class="small text-muted">Currently following</span>
                </div>
                <?php if (!empty($recentStudents)): ?>
                    <div class="dv-table-scroll">
                        <table class="dv-table stack">
                            <thead>
                                <tr><th>Student</th><th>Course</th><th>Year</th><th>Enrolled</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStudents as $s): ?>
                                    <tr>
                                        <td data-label="Student">
                                            <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($s['student_id']); ?>"><?php echo $h($s['student_fullname']); ?></a>
                                            <div class="small text-muted"><?php echo $h($s['student_id']); ?></div>
                                        </td>
                                        <td data-label="Course"><?php echo $h(trim((string) $s['course_name'])); ?></td>
                                        <td data-label="Year"><?php echo $h($s['academic_year']); ?></td>
                                        <td data-label="Enrolled"><?php echo $h($fmtDate($s['student_enroll_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="dv-panel-body"><p class="dv-empty">No active students.</p></div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Activity -->
        <div class="col-12">
            <section class="dv-panel" style="height:auto;">
                <div class="dv-panel-head">
                    <h2 class="dv-panel-title"><i class="fas fa-list-check me-2"></i>Recent changes</h2>
                    <span class="small text-muted">Department record activity</span>
                </div>
                <div class="dv-panel-body">
                    <?php if (!empty($activity)): ?>
                        <ul class="dv-timeline">
                            <?php foreach ($activity as $a): ?>
                                <li>
                                    <div class="dv-tl-title"><?php echo $h($a['description'] ?? ($a['activity_type'] ?? '')); ?></div>
                                    <div class="dv-tl-meta">
                                        <?php echo $h($a['activity_type'] ?? ''); ?>
                                        · <?php echo $h($a['user_name'] ?? 'System'); ?>
                                        · <?php echo $h(!empty($a['created_at']) ? date('d M Y, H:i', strtotime((string) $a['created_at'])) : ''); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="dv-empty">No recorded changes for this department.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
