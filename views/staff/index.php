<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$canManageStaff = !empty($canManageStaff);
$canOpenPersonalFile = !empty($canOpenPersonalFile);
$search = $search ?? '';
$staff = $staff ?? [];
$total = (int) ($total ?? 0);
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$showActions = $canManageStaff || $canOpenPersonalFile;
$colCount = 6 + ($showActions ? 1 : 0);

$initials = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return '?';
    }
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
};
$pageUrl = static function ($page) use ($search) {
    return '?page=' . (int) $page . ($search !== '' ? '&search=' . urlencode($search) : '');
};
$perPage = 20; // keep in sync with StaffController::index()
$firstShown = ($currentPage - 1) * $perPage + 1;
?>
<style>
.sti {
    --sti-text: #0f172a;
    --sti-muted: #64748b;
    --sti-border: #e2e8f0;
    --sti-soft: #f8fafc;
    --sti-navy: #001f3f;
    --sti-accent: #0d6efd;
    max-width: 1600px;
}

/* Header */
.sti-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.1rem;
}
.sti-title { font-size: 1.3rem; font-weight: 700; color: var(--sti-text); margin: 0 0 0.2rem; line-height: 1.3; }
.sti-title i { color: var(--sti-accent); }
.sti-lead { margin: 0; font-size: 0.875rem; color: var(--sti-muted); }
.sti-head .btn {
    flex-shrink: 0;
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

/* Panel */
.sti-panel { background: #fff; border: 1px solid var(--sti-border); border-radius: 0.65rem; overflow: hidden; }

/* Toolbar */
.sti-toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sti-border);
    background: var(--sti-soft);
}
.sti-search { display: flex; flex: 1 1 auto; gap: 0.5rem; min-width: 0; margin: 0; }
.sti-search-field { position: relative; flex: 1 1 auto; min-width: 0; }
.sti-search-field i {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.85rem;
    pointer-events: none;
}
.sti-search-field .form-control {
    width: 100%;
    height: 40px;
    padding-left: 2.35rem;
    border-radius: 8px;
    border-color: #cbd5e1;
    font-size: 0.9rem;
}
.sti-search-field .form-control:focus {
    border-color: var(--sti-navy);
    box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.12);
}
.sti-search .btn {
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    border-radius: 8px;
}
.sti-count {
    flex-shrink: 0;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    background: #fff;
    border: 1px solid var(--sti-border);
    border-radius: 999px;
    padding: 0.35rem 0.8rem;
    white-space: nowrap;
}

/* Table */
.sti-table { width: 100%; margin: 0; table-layout: fixed; border-collapse: collapse; }
.sti-table thead th {
    padding: 0.7rem 1rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--sti-muted);
    background: #fff;
    border-bottom: 1px solid var(--sti-border);
    white-space: nowrap;
    text-align: left;
    vertical-align: middle;
}
.sti-table tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
    color: var(--sti-text);
    overflow: hidden;
}
.sti-table tbody tr:last-child td { border-bottom: 0; }
.sti-table tbody tr:hover td { background: #f8fafc; }
.sti-table col.c-no { width: 56px; }
.sti-table col.c-person { width: auto; }
.sti-table col.c-finger { width: 100px; }
.sti-table col.c-dept { width: 21%; }
.sti-table col.c-pos { width: 19%; }
.sti-table col.c-status { width: 120px; }
.sti-table col.c-actions { width: 104px; }
.sti-table th.c-no, .sti-table td.c-no { text-align: center; color: var(--sti-muted); font-variant-numeric: tabular-nums; }
.sti-table th.c-actions, .sti-table td.c-actions { text-align: right; }

.sti-person { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
.sti-avatar {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.sti-person-text { min-width: 0; line-height: 1.3; }
.sti-name { display: block; font-weight: 600; color: var(--sti-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sti-sub { display: block; font-size: 0.78rem; color: var(--sti-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sti-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
.sti-ellipsis { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sti-muted { color: #94a3b8; }

.sti-chip {
    display: inline-block;
    max-width: 100%;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    background: #f1f5f9;
    color: #334155;
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}
.sti-pos {
    display: inline-block;
    max-width: 100%;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}
.sti-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}
.sti-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.sti-status.is-working { background: #dcfce7; color: #166534; }
.sti-status.is-other { background: #fef3c7; color: #92400e; }

.sti-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: 0.35rem; }
.sti-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

/* Footer / pager */
.sti-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.8rem 1rem;
    border-top: 1px solid var(--sti-border);
    background: var(--sti-soft);
    font-size: 0.8125rem;
    color: var(--sti-muted);
}
.sti-foot .pagination { margin: 0; flex-wrap: wrap; }
.sti-foot .page-link { min-width: 34px; text-align: center; }

/* Empty */
.sti-empty { text-align: center; padding: 3rem 1rem; color: var(--sti-muted); }
.sti-empty i { font-size: 2rem; color: #cbd5e1; margin-bottom: 0.75rem; display: block; }
.sti-empty p { margin: 0 0 1rem; }

/* Medium screens: drop low-priority columns */
@media (max-width: 1199.98px) {
    .sti-table col.c-finger, .sti-table th.c-finger, .sti-table td.c-finger { display: none; }
}
@media (max-width: 991.98px) {
    .sti-table col.c-dept, .sti-table th.c-dept, .sti-table td.c-dept { display: none; }
}

/* Phones: stacked cards */
@media (max-width: 767.98px) {
    .sti.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .sti-head { align-items: stretch; flex-direction: column; }
    .sti-head .btn { width: 100%; }
    .sti-toolbar { flex-direction: column; align-items: stretch; }
    .sti-search { flex-wrap: wrap; }
    .sti-search-field { flex: 1 1 100%; }
    .sti-search .btn { flex: 1 1 0; }
    .sti-search-field .form-control { font-size: 16px; }
    .sti-count { align-self: flex-start; }

    .sti-table, .sti-table tbody, .sti-table tr, .sti-table td { display: block; width: 100%; }
    .sti-table colgroup, .sti-table thead { display: none; }
    .sti-table tbody tr { padding: 0.85rem 1rem; border-bottom: 1px solid var(--sti-border); }
    .sti-table tbody tr:last-child { border-bottom: 0; }
    .sti-table tbody td,
    .sti-table tbody tr:hover td { padding: 0.3rem 0; border: 0; background: transparent; }
    .sti-table td.c-no { display: none; }
    .sti-table td.c-person { padding-bottom: 0.6rem; }
    .sti-table td.c-finger, .sti-table td.c-dept, .sti-table td.c-pos, .sti-table td.c-status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        text-align: right;
    }
    .sti-table td[data-label]::before {
        content: attr(data-label);
        flex: 0 0 auto;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--sti-muted);
        text-align: left;
    }
    .sti-table td.c-actions { padding-top: 0.6rem; text-align: left; }
    .sti-table td.c-actions::before { display: none; }
    .sti-actions { width: 100%; justify-content: flex-start; }
    .sti-actions .btn { flex: 1 1 0; width: auto; height: 40px; }
    .sti-foot { flex-direction: column; align-items: stretch; text-align: center; }
    .sti-foot .pagination { justify-content: center; }
}
</style>

<div class="container-fluid px-4 py-3 sti">
    <div class="sti-head">
        <div>
            <h1 class="sti-title"><i class="fas fa-chalkboard-teacher me-2"></i>Staff</h1>
            <p class="sti-lead">Browse staff members, open personal files and manage records.</p>
        </div>
        <?php if ($canManageStaff): ?>
            <a href="<?php echo APP_URL; ?>/staff/create" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Staff
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <?php echo $h($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <?php echo $h($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="sti-panel">
        <div class="sti-toolbar">
            <form method="GET" action="<?php echo APP_URL; ?>/staff" class="sti-search">
                <div class="sti-search-field">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search name, username, finger no, email or NIC"
                           value="<?php echo $h($search); ?>" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>
            <span class="sti-count"><?php echo number_format($total); ?> member<?php echo $total === 1 ? '' : 's'; ?></span>
        </div>

        <?php if (!empty($staff)): ?>
            <div class="table-responsive">
                <table class="sti-table">
                    <colgroup>
                        <col class="c-no">
                        <col class="c-person">
                        <col class="c-finger">
                        <col class="c-dept">
                        <col class="c-pos">
                        <col class="c-status">
                        <?php if ($showActions): ?><col class="c-actions"><?php endif; ?>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="c-no">#</th>
                            <th class="c-person">Staff member</th>
                            <th class="c-finger">Finger No</th>
                            <th class="c-dept">Department</th>
                            <th class="c-pos">Position</th>
                            <th class="c-status">Status</th>
                            <?php if ($showActions): ?><th class="c-actions">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $i => $member): ?>
                            <?php
                            $isWorking = ($member['staff_status'] ?? '') === 'Working';
                            $finger = trim((string) ($member['finger_machine_no'] ?? ''));
                            $dept = trim((string) ($member['department_name'] ?? ''));
                            $posCode = trim((string) ($member['staff_position'] ?? ''));
                            $pos = $posCode !== '' ? (string) (($positionNames ?? [])[$posCode] ?? $posCode) : '';
                            ?>
                            <tr>
                                <td class="c-no"><?php echo $firstShown + $i; ?></td>
                                <td class="c-person">
                                    <div class="sti-person">
                                        <span class="sti-avatar" aria-hidden="true"><?php echo $h($initials($member['staff_name'] ?? '')); ?></span>
                                        <span class="sti-person-text">
                                            <span class="sti-name" title="<?php echo $h($member['staff_name'] ?? ''); ?>"><?php echo $h($member['staff_name'] ?? ''); ?></span>
                                            <span class="sti-sub sti-mono"><?php echo $h($member['staff_id']); ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="c-finger" data-label="Finger No">
                                    <?php echo $finger !== '' ? $h($finger) : '<span class="sti-muted">—</span>'; ?>
                                </td>
                                <td class="c-dept" data-label="Department">
                                    <?php if ($dept !== ''): ?>
                                        <span class="sti-chip" title="<?php echo $h($dept); ?>"><?php echo $h($dept); ?></span>
                                    <?php else: ?>
                                        <span class="sti-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="c-pos" data-label="Position">
                                    <?php if ($pos !== ''): ?>
                                        <span class="sti-pos" title="<?php echo $h($pos); ?>"><?php echo $h($pos); ?></span>
                                    <?php else: ?>
                                        <span class="sti-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="c-status" data-label="Status">
                                    <span class="sti-status <?php echo $isWorking ? 'is-working' : 'is-other'; ?>">
                                        <?php echo $h($member['staff_status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <?php if ($showActions): ?>
                                    <td class="c-actions">
                                        <div class="sti-actions">
                                            <?php if ($canOpenPersonalFile): ?>
                                                <a href="<?php echo APP_URL; ?>/staff/edit?id=<?php echo urlencode($member['staff_id']); ?>"
                                                   class="btn btn-outline-primary" title="Personal file" aria-label="Open personal file">
                                                    <i class="fas fa-folder-open"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($canManageStaff): ?>
                                                <a href="<?php echo APP_URL; ?>/staff/delete?id=<?php echo urlencode($member['staff_id']); ?>"
                                                   class="btn btn-outline-danger" title="Delete" aria-label="Delete staff"
                                                   onclick="return confirm('Delete this staff member?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="sti-foot">
                <div>Showing <?php echo count($staff); ?> of <?php echo number_format($total); ?></div>
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Staff pages">
                        <ul class="pagination pagination-sm">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $h($pageUrl($currentPage - 1)); ?>">Prev</a></li>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?php echo $p === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $h($pageUrl($p)); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $h($pageUrl($currentPage + 1)); ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sti-empty">
                <i class="fas fa-user-tie"></i>
                <p><?php echo $search !== '' ? 'No staff match that search.' : 'No staff members found.'; ?></p>
                <?php if ($search === '' && $canManageStaff): ?>
                    <a href="<?php echo APP_URL; ?>/staff/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Staff
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
