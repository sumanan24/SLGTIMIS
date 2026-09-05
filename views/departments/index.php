<?php
$h = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$search = trim((string) ($search ?? ''));
$isADM = !empty($isADM);
$total = (int) ($total ?? count($departments ?? []));
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$perPage = (int) ($perPage ?? 12);
$pageQuery = $search !== '' ? '&search=' . urlencode($search) : '';
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
    margin-bottom: 1rem;
    font-size: 0.8125rem;
    color: var(--dept-muted);
}
.dept-page .dept-summary strong { color: var(--dept-text); }
.dept-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}
.dept-card {
    background: #fff;
    border: 1px solid var(--dept-border);
    border-radius: 0.75rem;
    padding: 1.15rem 1.2rem 1rem;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.dept-card:hover {
    border-color: #94a3b8;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}
.dept-card-top {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    margin-bottom: 1rem;
}
.dept-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 0.7rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    flex-shrink: 0;
}
.dept-card-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dept-text);
    margin: 0 0 0.2rem;
    line-height: 1.35;
}
.dept-id-chip {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--dept-navy);
    background: #eef3f8;
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
}
.dept-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.dept-stat {
    background: var(--dept-soft);
    border: 1px solid #eef2f6;
    border-radius: 0.5rem;
    padding: 0.55rem 0.7rem;
    text-decoration: none;
    color: inherit;
    min-width: 0;
}
.dept-stat:hover { background: #eef4fb; }
.dept-stat-value {
    display: block;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dept-text);
    line-height: 1.2;
}
.dept-stat-label {
    display: block;
    font-size: 0.72rem;
    color: var(--dept-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.dept-card-actions {
    margin-top: auto;
    padding-top: 0.75rem;
    border-top: 1px solid var(--dept-border);
    display: flex;
    justify-content: flex-end;
    gap: 0.45rem;
}
.dept-card-actions .btn {
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
.dept-page .pagination .page-link {
    color: var(--dept-navy);
}
.dept-page .pagination .page-item.active .page-link {
    background-color: var(--dept-navy);
    border-color: var(--dept-navy);
}
@media (max-width: 991.98px) {
    .dept-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .dept-page.container-fluid {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    .dept-page .dept-head .btn { width: 100%; }
    .dept-page .dept-toolbar { flex-direction: column; }
    .dept-page .dept-toolbar .btn { width: 100%; }
    .dept-grid { grid-template-columns: 1fr; }
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

    <form method="GET" action="<?php echo APP_URL; ?>/departments" class="dept-toolbar">
        <div class="dept-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="text" name="search" class="form-control" value="<?php echo $h($search); ?>"
                   placeholder="Search by department ID or name" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter me-1"></i>Search
        </button>
        <?php if ($search !== ''): ?>
        <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">Clear</a>
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
        <div class="dept-grid">
            <?php foreach ($departments as $dept): ?>
                <?php
                $deptId = (string) ($dept['department_id'] ?? '');
                $deptName = (string) ($dept['department_name'] ?? '');
                $courseCount = (int) ($dept['course_count'] ?? 0);
                $staffCount = (int) ($dept['staff_count'] ?? 0);
                $palette = $avatarPalette[abs(crc32($deptId)) % count($avatarPalette)];
                $initials = strtoupper(substr($deptId !== '' ? $deptId : $deptName, 0, 3));
                ?>
                <article class="dept-card">
                    <div class="dept-card-top">
                        <span class="dept-avatar" style="background: <?php echo $palette[1]; ?>; color: <?php echo $palette[0]; ?>;">
                            <?php echo $h($initials); ?>
                        </span>
                        <div class="min-w-0">
                            <h2 class="dept-card-name"><?php echo $h($deptName); ?></h2>
                            <span class="dept-id-chip"><?php echo $h($deptId); ?></span>
                        </div>
                    </div>
                    <div class="dept-stats">
                        <a class="dept-stat" href="<?php echo APP_URL; ?>/courses?department_id=<?php echo urlencode($deptId); ?>">
                            <span class="dept-stat-value"><?php echo number_format($courseCount); ?></span>
                            <span class="dept-stat-label"><?php echo $courseCount === 1 ? 'Course' : 'Courses'; ?></span>
                        </a>
                        <div class="dept-stat">
                            <span class="dept-stat-value"><?php echo number_format($staffCount); ?></span>
                            <span class="dept-stat-label"><?php echo $staffCount === 1 ? 'Staff member' : 'Staff'; ?></span>
                        </div>
                    </div>
                    <div class="dept-card-actions">
                        <?php if ($isADM): ?>
                            <a href="<?php echo APP_URL; ?>/departments/edit?id=<?php echo urlencode($deptId); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <a href="<?php echo APP_URL; ?>/departments/delete?id=<?php echo urlencode($deptId); ?>" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash me-1"></i>Delete
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">View only</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
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
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $pageQuery; ?>"><?php echo $i; ?></a>
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
                <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">Clear search</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
