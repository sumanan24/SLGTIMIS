<?php
$h = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$search = trim((string) ($search ?? ''));
$isADM = !empty($isADM);
$total = (int) ($total ?? count($departments ?? []));
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$perPage = (int) ($perPage ?? 12);
$category = (string) ($category ?? '');
$categoryCounts = is_array($categoryCounts ?? null) ? $categoryCounts : [];
$staffOptions = is_array($staffOptions ?? null) ? $staffOptions : [];
$csrf = (string) ($csrf ?? '');
$categories = DepartmentModel::categories();
$headTypes = DepartmentModel::headTypes();
$pageQuery = ($search !== '' ? '&search=' . urlencode($search) : '')
    . ($category !== '' ? '&category=' . urlencode($category) : '');
$rowOffset = ($currentPage - 1) * $perPage;
$showTabs = !empty($categoryCounts);
$tabAllCount = array_sum($categoryCounts);
$tabUrl = static function (string $cat) use ($search): string {
    $q = [];
    if ($search !== '') { $q['search'] = $search; }
    if ($cat !== '') { $q['category'] = $cat; }
    return APP_URL . '/departments' . ($q ? '?' . http_build_query($q) : '');
};
$staffJson = [];
foreach ($staffOptions as $s) {
    $staffJson[] = [
        'id' => (string) ($s['staff_id'] ?? ''),
        'name' => (string) ($s['staff_name'] ?? ''),
        'pos' => (string) ($s['staff_position'] ?? ''),
        'dept' => (string) ($s['department_name'] ?? ''),
        'type' => (string) ($s['staff_type'] ?? ''),
    ];
}
$avatarPalette = [
    ['#001f3f', '#dbe7f3'],
    ['#003d7a', '#d6e6f7'],
    ['#1e3a5f', '#e2e8f0'],
    ['#0f4c5c', '#d7eef0'],
    ['#1b4d3e', '#dceee4'],
    ['#3d2b1f', '#efe6dc'],
];
?>
<style>
.dept-page {
    --dept-navy: #001f3f;
    --dept-text: #0f172a;
    --dept-muted: #64748b;
    --dept-border: #e2e8f0;
    --dept-soft: #f8fafc;
}
.dept-page .card:hover {
    transform: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}
.dept-page .dept-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.dept-page .dept-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--dept-text);
    margin: 0 0 0.25rem;
    line-height: 1.3;
}
.dept-page .dept-lead {
    margin: 0;
    font-size: 0.875rem;
    color: var(--dept-muted);
    max-width: 38rem;
}
.dept-page .dept-head .btn {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    white-space: nowrap;
}
.dept-page .dept-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    align-items: stretch;
    background: #fff;
    border: 1px solid var(--dept-border);
    border-radius: 0.65rem;
    padding: 0.75rem;
    margin-bottom: 1rem;
}
.dept-page .dept-search {
    position: relative;
    flex: 1 1 16rem;
    min-width: 0;
}
.dept-page .dept-search i {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}
.dept-page .dept-search .form-control {
    padding-left: 2.35rem;
    min-height: 42px;
    border-color: #cbd5e1;
}
.dept-page .dept-toolbar .btn {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.dept-page .dept-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
    font-size: 0.8125rem;
    color: var(--dept-muted);
}
.dept-page .dept-summary strong { color: var(--dept-text); }

/* ---------- Category tabs ---------- */
.dept-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
}
.dept-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.9rem;
    border: 1px solid var(--dept-border);
    border-radius: 999px;
    background: #fff;
    color: var(--dept-text);
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease;
}
.dept-tab:hover { background: #eef4fb; border-color: #94a3b8; color: var(--dept-navy); }
.dept-tab .n {
    background: #eef2f6;
    color: var(--dept-muted);
    border-radius: 999px;
    padding: 0 0.5rem;
    font-size: 0.72rem;
    line-height: 1.5;
}
.dept-tab.active { background: var(--dept-navy); border-color: var(--dept-navy); color: #fff; }
.dept-tab.active .n { background: rgba(255, 255, 255, 0.2); color: #fff; }

/* ---------- Badges ---------- */
.dept-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    border-radius: 999px;
    padding: 0.2rem 0.6rem;
    white-space: nowrap;
}
.dept-badge.cat-academic { background: #e8f0fb; color: #0b4a8f; }
.dept-badge.cat-admin { background: #f3e8fb; color: #6b21a8; }
.dept-badge.head-permanent { background: #e6f4ec; color: #1b6b43; }
.dept-badge.head-acting { background: #fff4de; color: #92580a; }
.dept-badge.head-cover_up { background: #e0f2fe; color: #0b5d86; }

.dept-head-cell { min-width: 0; line-height: 1.35; }
.dept-head-name { font-weight: 600; display: block; word-break: break-word; }
.dept-head-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-top: 0.2rem; }
.dept-head-id { font-size: 0.72rem; color: var(--dept-muted); }
.dept-head-none { color: #94a3b8; font-style: italic; font-size: 0.8125rem; }

/* ---------- Assign head modal ---------- */
.head-type-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}
.head-type-opt input { position: absolute; opacity: 0; pointer-events: none; }
.head-type-opt label {
    display: block;
    height: 100%;
    cursor: pointer;
    border: 1.5px solid #cbd5e1;
    border-radius: 0.6rem;
    padding: 0.6rem 0.7rem;
    background: #fff;
    transition: border-color 0.15s ease, background 0.15s ease;
}
.head-type-opt label strong { display: block; font-size: 0.875rem; color: var(--dept-text); }
.head-type-opt label span { display: block; font-size: 0.72rem; color: var(--dept-muted); margin-top: 0.1rem; }
.head-type-opt input:checked + label { border-color: var(--dept-navy); background: #eef4fb; box-shadow: 0 0 0 2px rgba(0, 31, 63, 0.12); }
.head-type-opt input:focus-visible + label { outline: 2px solid #3b82f6; outline-offset: 2px; }
#deptHeadModal .form-label { font-size: 0.8125rem; font-weight: 600; color: #334155; }
#deptHeadModal .head-current {
    background: var(--dept-soft, #f8fafc);
    border: 1px solid var(--dept-border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 0.6rem 0.8rem;
    font-size: 0.8125rem;
}
#deptHeadStaff { min-height: 42px; }

/* ---------- Table ---------- */
.dept-table-wrap {
    background: #fff;
    border: 1px solid var(--dept-border);
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    overflow: hidden;
}
.dept-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.dept-table {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
    color: var(--dept-text);
}
.dept-table thead th {
    position: sticky;
    top: 0;
    background: var(--dept-navy);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.85rem 1rem;
    border: 0;
    white-space: nowrap;
    vertical-align: middle;
}
.dept-table tbody td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--dept-border);
    vertical-align: middle;
    background: #fff;
}
.dept-table tbody tr:nth-child(even) td { background: var(--dept-soft); }
.dept-table tbody tr:hover td { background: #eef4fb; }
.dept-table tbody tr:last-child td { border-bottom: 0; }
.dept-table .col-num { width: 3.5rem; color: var(--dept-muted); font-weight: 600; text-align: center; }
.dept-table { min-width: 1000px; }
.dept-table .col-id { width: 7rem; white-space: nowrap; }
.dept-table .col-cat { width: 7rem; white-space: nowrap; }
.dept-table .col-head { min-width: 13rem; }
.dept-table .col-count { width: 6rem; text-align: center; }
.dept-table .col-actions { width: 1%; text-align: right; white-space: nowrap; }
.dept-table thead .col-num,
.dept-table thead .col-count { text-align: center; }
.dept-table thead .col-actions { text-align: right; }

.dept-name-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}
.dept-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.6rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    flex-shrink: 0;
}
.dept-name {
    font-weight: 600;
    color: var(--dept-text);
    line-height: 1.35;
    overflow-wrap: break-word;
}
.dept-id-chip {
    display: inline-flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--dept-navy);
    background: #eef3f8;
    border-radius: 999px;
    padding: 0.2rem 0.6rem;
}
.dept-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.25rem;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.8125rem;
    font-weight: 700;
    text-decoration: none;
    background: #e8f0fb;
    color: #0b4a8f;
}
a.dept-count:hover { background: #d4e4f8; color: #083a72; }
.dept-count.is-staff { background: #e6f4ec; color: #1b6b43; }
.dept-count.is-zero { background: #f1f5f9; color: #94a3b8; }
.dept-actions {
    display: inline-flex;
    gap: 0.4rem;
    justify-content: flex-end;
}
.dept-actions .btn {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    font-weight: 600;
}
.dept-empty {
    text-align: center;
    background: #fff;
    border: 1px dashed #cbd5e1;
    border-radius: 0.75rem;
    padding: 3rem 1.25rem;
}
.dept-empty i { color: #94a3b8; }
.dept-page .pagination { flex-wrap: wrap; gap: 0.25rem; }
.dept-page .pagination .page-link { color: var(--dept-navy); }
.dept-page .pagination .page-item.active .page-link {
    background-color: var(--dept-navy);
    border-color: var(--dept-navy);
    color: #fff;
}

/* ---------- Phone / small screens (page chrome follows the screen) ---------- */
@media (max-width: 767.98px) {
    .dept-page.container-fluid {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    .dept-page .dept-head .btn { width: 100%; }
    .dept-page .dept-toolbar { flex-direction: column; }
    .dept-page .dept-toolbar .dept-search { flex: 0 0 auto; width: 100%; }
    .dept-page .dept-toolbar .btn { width: 100%; }
    .head-type-grid { grid-template-columns: 1fr; }
}

/* ---------- Responsive table ----------
   The table adapts to the width it actually has (the 280px sidebar takes space on desktop),
   not to the screen width:  full table > 1450px, compact table 901-1450px, cards <= 900px. */
.dept-table-host { container: dept-table / inline-size; }
.dept-name-wrap { min-width: 0; }
.dept-name-sub {
    display: none;
    margin-top: 0.1rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--dept-muted);
}
.dept-table .col-name { min-width: 14rem; }

/* Compact table: drop # and ID columns, icon-only actions */
@container dept-table (max-width: 1450px) {
    .dept-table { min-width: 0; }
    .dept-table .col-num,
    .dept-table .col-id { display: none; }
    .dept-name-sub { display: block; }
    .dept-table thead th,
    .dept-table tbody td { padding: 0.7rem 0.6rem; }
    .dept-table .col-name { min-width: 11rem; }
    .dept-table .col-cat { width: auto; }
    .dept-table .col-head { min-width: 10rem; }
    .dept-table .col-count { width: 4.5rem; }
    .dept-table .col-actions { width: auto; }
    .dept-actions { gap: 0.3rem; }
    .dept-actions .btn .lbl { display: none; }
    .dept-actions .btn i { margin-right: 0 !important; }
    .dept-actions .btn { min-width: 34px; padding-left: 0.5rem; padding-right: 0.5rem; }
}

/* Cards: each row becomes a labelled card; several cards per row when there is room */
@container dept-table (max-width: 900px) {
    .dept-table-wrap { background: transparent; border: 0; box-shadow: none; overflow: visible; }
    .dept-table-scroll { overflow: visible; }
    .dept-table thead {
        position: absolute;
        width: 1px; height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
    }
    .dept-table { min-width: 0; }
    .dept-table,
    .dept-table tbody,
    .dept-table tr,
    .dept-table td { display: block; width: 100%; }
    .dept-table tbody {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 18.5rem), 1fr));
        gap: 0.85rem;
    }
    .dept-table tbody tr {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid var(--dept-border);
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        min-width: 0;
    }
    .dept-table tbody td,
    .dept-table tbody tr:nth-child(even) td,
    .dept-table tbody tr:hover td { background: transparent; }
    .dept-table tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.6rem 1rem;
        border-bottom: 1px solid #eef2f6;
        text-align: right;
        width: 100%;
    }
    .dept-table tbody td::before {
        content: attr(data-label);
        flex: 0 0 auto;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--dept-muted);
        text-align: left;
    }
    .dept-table tbody td.col-num { display: none; }
    .dept-table tbody td.col-id { display: flex; }
    .dept-name-sub { display: none; }
    .dept-table tbody tr:hover td.col-name,
    .dept-table tbody td.col-name {
        background: var(--dept-soft);
        padding: 0.85rem 1rem;
        text-align: left;
        justify-content: flex-start;
    }
    .dept-table td.col-name::before { display: none; }
    .dept-table tbody td.col-actions {
        border-bottom: 0;
        padding: 0.75rem 1rem;
        justify-content: stretch;
        margin-top: auto;
    }
    .dept-table td.col-actions::before { display: none; }
    .dept-table .col-name,
    .dept-table .col-id, .dept-table .col-cat, .dept-table .col-head,
    .dept-table .col-count, .dept-table .col-actions { width: 100%; min-width: 0; }
    .dept-table .dept-head-cell { text-align: right; }
    .dept-table .dept-head-meta { justify-content: flex-end; }
    .dept-actions { display: flex; flex-wrap: wrap; width: 100%; gap: 0.4rem; }
    .dept-actions .btn { flex: 1 1 calc(50% - 0.4rem); }
    .dept-actions .btn .lbl { display: inline; }
    .dept-actions .btn i { margin-right: 0.25rem !important; }
}
@media (max-width: 575.98px) {
    .dept-page .dept-title { font-size: 1.2rem; }
    .dept-page .pagination .page-link { padding: 0.4rem 0.65rem; font-size: 0.85rem; }
}
</style>

<div class="container-fluid px-4 py-3 dept-page">
    <div class="dept-head">
        <div>
            <h1 class="dept-title"><i class="fas fa-building text-primary me-2"></i>Departments</h1>
            <p class="dept-lead">Academic units used across courses, staff, and student applications.</p>
        </div>
        <?php if ($isADM): ?>
        <a href="<?php echo APP_URL; ?>/departments/create" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add department
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <div><?php echo $h($message); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div><?php echo $h($error); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($showTabs): ?>
    <nav class="dept-tabs" aria-label="Department category">
        <a href="<?php echo $h($tabUrl('')); ?>" class="dept-tab<?php echo $category === '' ? ' active' : ''; ?>">
            <i class="fas fa-layer-group"></i> All <span class="n"><?php echo (int) $tabAllCount; ?></span>
        </a>
        <?php foreach ($categories as $catKey => $catLabel): ?>
            <a href="<?php echo $h($tabUrl($catKey)); ?>" class="dept-tab<?php echo $category === $catKey ? ' active' : ''; ?>">
                <i class="fas <?php echo $catKey === 'admin' ? 'fa-briefcase' : 'fa-graduation-cap'; ?>"></i>
                <?php echo $h($catLabel); ?> <span class="n"><?php echo (int) ($categoryCounts[$catKey] ?? 0); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <form method="GET" action="<?php echo APP_URL; ?>/departments" class="dept-toolbar">
        <?php if ($category !== ''): ?>
            <input type="hidden" name="category" value="<?php echo $h($category); ?>">
        <?php endif; ?>
        <div class="dept-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="text" name="search" class="form-control" value="<?php echo $h($search); ?>"
                   placeholder="Search ID or name" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter me-1"></i>Search
        </button>
        <?php if ($search !== ''): ?>
        <a href="<?php echo APP_URL; ?>/departments<?php echo $category !== '' ? '?category=' . urlencode($category) : ''; ?>" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <div class="dept-summary">
        <div>
            Showing <strong><?php echo count($departments ?? []); ?></strong>
            of <strong><?php echo number_format($total); ?></strong> department<?php echo $total === 1 ? '' : 's'; ?>
            <?php if ($search !== ''): ?>
                for “<?php echo $h($search); ?>”
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($departments)): ?>
        <div class="dept-table-host">
        <div class="dept-table-wrap">
            <div class="dept-table-scroll">
                <table class="dept-table">
                    <thead>
                        <tr>
                            <th scope="col" class="col-num">#</th>
                            <th scope="col" class="col-name">Department</th>
                            <th scope="col" class="col-id">ID</th>
                            <th scope="col" class="col-cat">Category</th>
                            <th scope="col" class="col-head">Head of Department</th>
                            <th scope="col" class="col-count">Courses</th>
                            <th scope="col" class="col-count">Staff</th>
                            <th scope="col" class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $i => $dept): ?>
                            <?php
                            $deptId = (string) ($dept['department_id'] ?? '');
                            $deptName = (string) ($dept['department_name'] ?? '');
                            $courseCount = (int) ($dept['course_count'] ?? 0);
                            $staffCount = (int) ($dept['staff_count'] ?? 0);
                            $palette = $avatarPalette[abs(crc32($deptId)) % count($avatarPalette)];
                            $initials = strtoupper(substr($deptId !== '' ? $deptId : $deptName, 0, 3));
                            $catKey = DepartmentModel::normalizeCategory($dept['department_category'] ?? '');
                            $headId = (string) ($dept['head_staff_id'] ?? '');
                            $headName = (string) ($dept['head_name'] ?? '');
                            $headType = (string) ($dept['head_type'] ?? '');
                            $hasHead = $headId !== '' && isset($headTypes[$headType]);
                            $headDate = (string) ($dept['head_start_date'] ?? '');
                            ?>
                            <tr>
                                <td class="col-num" data-label="#"><?php echo $rowOffset + $i + 1; ?></td>
                                <td class="col-name" data-label="Department">
                                    <div class="dept-name-cell">
                                        <span class="dept-avatar" style="background: <?php echo $palette[1]; ?>; color: <?php echo $palette[0]; ?>;">
                                            <?php echo $h($initials); ?>
                                        </span>
                                        <div class="dept-name-wrap">
                                            <a class="dept-name text-decoration-none" href="<?php echo APP_URL; ?>/departments/view?id=<?php echo urlencode($deptId); ?>" title="View department details"><?php echo $h($deptName); ?></a>
                                            <span class="dept-name-sub"><?php echo $h($deptId); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-id" data-label="ID">
                                    <span class="dept-id-chip"><?php echo $h($deptId); ?></span>
                                </td>
                                <td class="col-cat" data-label="Category">
                                    <span class="dept-badge cat-<?php echo $h($catKey); ?>"><?php echo $h($categories[$catKey]); ?></span>
                                </td>
                                <td class="col-head" data-label="Head of Dept.">
                                    <?php if ($hasHead): ?>
                                        <div class="dept-head-cell">
                                            <span class="dept-head-name"><?php echo $h($headName !== '' ? $headName : $headId); ?></span>
                                            <div class="dept-head-meta">
                                                <span class="dept-badge head-<?php echo $h($headType); ?>"><?php echo $h($headTypes[$headType]); ?></span>
                                                <span class="dept-head-id"><?php echo $h($headId); ?><?php echo $headDate !== '' ? ' · since ' . $h($headDate) : ''; ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="dept-head-none">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-count" data-label="Courses">
                                    <a class="dept-count<?php echo $courseCount === 0 ? ' is-zero' : ''; ?>"
                                       href="<?php echo APP_URL; ?>/courses?department_id=<?php echo urlencode($deptId); ?>"
                                       title="View courses">
                                        <?php echo number_format($courseCount); ?>
                                    </a>
                                </td>
                                <td class="col-count" data-label="Staff">
                                    <span class="dept-count is-staff<?php echo $staffCount === 0 ? ' is-zero' : ''; ?>">
                                        <?php echo number_format($staffCount); ?>
                                    </span>
                                </td>
                                <td class="col-actions" data-label="Actions">
                                    <?php if ($isADM): ?>
                                        <div class="dept-actions">
                                            <a href="<?php echo APP_URL; ?>/departments/view?id=<?php echo urlencode($deptId); ?>" class="btn btn-sm btn-outline-secondary" title="View details">
                                                <i class="fas fa-eye me-1"></i><span class="lbl">View</span>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                    data-bs-toggle="modal" data-bs-target="#deptHeadModal"
                                                    data-dept-id="<?php echo $h($deptId); ?>"
                                                    data-dept-name="<?php echo $h($deptName); ?>"
                                                    data-head-id="<?php echo $hasHead ? $h($headId) : ''; ?>"
                                                    data-head-name="<?php echo $hasHead ? $h($headName) : ''; ?>"
                                                    data-head-type="<?php echo $hasHead ? $h($headType) : ''; ?>"
                                                    data-head-date="<?php echo $hasHead ? $h($headDate) : ''; ?>"
                                                    data-head-remarks="<?php echo $hasHead ? $h($dept['head_remarks'] ?? '') : ''; ?>"
                                                    title="<?php echo $hasHead ? 'Change head of department' : 'Assign head of department'; ?>">
                                                <i class="fas fa-user-tie me-1"></i><span class="lbl"><?php echo $hasHead ? 'Change head' : 'Assign head'; ?></span>
                                            </button>
                                            <a href="<?php echo APP_URL; ?>/departments/edit?id=<?php echo urlencode($deptId); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit me-1"></i><span class="lbl">Edit</span>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/departments/delete?id=<?php echo urlencode($deptId); ?>" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash me-1"></i><span class="lbl">Delete</span>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="dept-actions">
                                            <a href="<?php echo APP_URL; ?>/departments/view?id=<?php echo urlencode($deptId); ?>" class="btn btn-sm btn-outline-primary" title="View details">
                                                <i class="fas fa-eye me-1"></i><span class="lbl">View</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Departments pagination" class="mt-4">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $pageQuery; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo $p === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $p; ?><?php echo $pageQuery; ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $pageQuery; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="dept-empty">
            <i class="fas fa-building fa-3x mb-3"></i>
            <p class="text-muted mb-3">
                <?php echo $search !== '' ? 'No departments match your search.' : 'No departments found.'; ?>
            </p>
            <?php if ($isADM && $search === ''): ?>
                <a href="<?php echo APP_URL; ?>/departments/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Create one now
                </a>
            <?php elseif ($search !== ''): ?>
                <a href="<?php echo APP_URL; ?>/departments<?php echo $category !== '' ? '?category=' . urlencode($category) : ''; ?>" class="btn btn-outline-secondary">Clear search</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($isADM): ?>
<div class="modal fade" id="deptHeadModal" tabindex="-1" aria-labelledby="deptHeadModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="<?php echo APP_URL; ?>/departments/assign-head" id="deptHeadForm">
            <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
            <input type="hidden" name="department_id" id="deptHeadDeptId" value="">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="deptHeadModalTitle"><i class="fas fa-user-tie text-primary me-2"></i>Head of Department</h5>
                    <div class="small text-muted" id="deptHeadDeptName"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="head-current mb-3 d-none" id="deptHeadCurrent"></div>

                <div class="mb-3">
                    <label class="form-label d-block">Appointment type <span class="text-danger">*</span></label>
                    <div class="head-type-grid">
                        <?php
                        $typeHints = [
                            'permanent' => 'Substantive head',
                            'acting' => 'Temporarily in the post',
                            'cover_up' => 'Covering extra duties',
                        ];
                        foreach ($headTypes as $tKey => $tLabel): ?>
                            <div class="head-type-opt">
                                <input type="radio" name="head_type" id="headType_<?php echo $h($tKey); ?>" value="<?php echo $h($tKey); ?>" required>
                                <label for="headType_<?php echo $h($tKey); ?>">
                                    <strong><?php echo $h($tLabel); ?></strong>
                                    <span><?php echo $h($typeHints[$tKey] ?? ''); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="deptHeadSearch">Staff member <span class="text-danger">*</span></label>
                    <input type="search" class="form-control mb-2" id="deptHeadSearch" placeholder="Search by name, staff ID or position" autocomplete="off">
                    <select class="form-select" id="deptHeadStaff" name="staff_id" size="6" required></select>
                    <div class="form-text" id="deptHeadStaffInfo">Only one person can be responsible for a department. Assigning replaces the current head.</div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-sm-5">
                        <label class="form-label" for="deptHeadDate">Effective from</label>
                        <input type="date" class="form-control" id="deptHeadDate" name="head_start_date">
                    </div>
                    <div class="col-12 col-sm-7">
                        <label class="form-label" for="deptHeadRemarks">Remarks</label>
                        <input type="text" class="form-control" id="deptHeadRemarks" name="head_remarks" maxlength="255" placeholder="e.g. Cover for study leave">
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="submit" form="deptHeadRemoveForm" class="btn btn-outline-danger me-sm-auto d-none" id="deptHeadRemoveBtn"
                        onclick="return confirm('Remove the current head of department?');">
                    <i class="fas fa-user-slash me-1"></i>Remove head
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save assignment</button>
            </div>
        </form>
    </div>
</div>
<form method="POST" action="<?php echo APP_URL; ?>/departments/remove-head" id="deptHeadRemoveForm" class="d-none">
    <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
    <input type="hidden" name="department_id" id="deptHeadRemoveDeptId" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('deptHeadModal');
    if (!modalEl) return;
    // Keep the modal outside any transformed/positioned page wrapper.
    document.body.appendChild(modalEl);
    var removeForm = document.getElementById('deptHeadRemoveForm');
    if (removeForm) document.body.appendChild(removeForm);

    var staff = <?php echo json_encode($staffJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
    var typeLabels = <?php echo json_encode($headTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var select = document.getElementById('deptHeadStaff');
    var search = document.getElementById('deptHeadSearch');
    var info = document.getElementById('deptHeadStaffInfo');
    var defaultInfo = info.textContent;
    var selectedId = '';

    function label(s) {
        return s.name + ' (' + s.id + ')' + (s.pos ? ' – ' + s.pos : '');
    }

    function render() {
        var q = search.value.trim().toLowerCase();
        select.innerHTML = '';
        var shown = 0;
        staff.forEach(function (s) {
            var hay = (s.name + ' ' + s.id + ' ' + s.pos + ' ' + s.dept).toLowerCase();
            if (q && hay.indexOf(q) === -1 && s.id !== selectedId) return;
            var o = document.createElement('option');
            o.value = s.id;
            o.textContent = label(s);
            if (s.id === selectedId) o.selected = true;
            select.appendChild(o);
            shown++;
        });
        if (!shown) {
            var none = document.createElement('option');
            none.disabled = true;
            none.textContent = 'No matching staff';
            select.appendChild(none);
        }
    }

    function showInfo() {
        var s = staff.filter(function (x) { return x.id === select.value; })[0];
        if (!s) { info.textContent = defaultInfo; return; }
        info.textContent = s.name + ' · ' + (s.pos || 'No role') + ' · ' + (s.dept || 'No department') + (s.type ? ' · ' + s.type : '');
    }

    search.addEventListener('input', render);
    select.addEventListener('change', function () { selectedId = select.value; showInfo(); });

    modalEl.addEventListener('show.bs.modal', function (ev) {
        var b = ev.relatedTarget;
        if (!b) return;
        var d = b.dataset;
        document.getElementById('deptHeadDeptId').value = d.deptId || '';
        document.getElementById('deptHeadRemoveDeptId').value = d.deptId || '';
        document.getElementById('deptHeadDeptName').textContent = (d.deptName || '') + ' (' + (d.deptId || '') + ')';
        document.getElementById('deptHeadDate').value = d.headDate || '';
        document.getElementById('deptHeadRemarks').value = d.headRemarks || '';
        document.querySelectorAll('#deptHeadForm input[name="head_type"]').forEach(function (r) {
            r.checked = (r.value === d.headType);
        });

        selectedId = d.headId || '';
        search.value = '';
        render();
        showInfo();

        var cur = document.getElementById('deptHeadCurrent');
        var rm = document.getElementById('deptHeadRemoveBtn');
        if (d.headId) {
            cur.textContent = '';
            var strong = document.createElement('strong');
            strong.textContent = d.headName || d.headId;
            cur.appendChild(document.createTextNode('Current: '));
            cur.appendChild(strong);
            cur.appendChild(document.createTextNode(' — ' + (typeLabels[d.headType] || '') + ' head'));
            cur.classList.remove('d-none');
            rm.classList.remove('d-none');
        } else {
            cur.classList.add('d-none');
            rm.classList.add('d-none');
        }
    });

    modalEl.addEventListener('shown.bs.modal', function () { search.focus(); });
});
</script>
<?php endif; ?>
