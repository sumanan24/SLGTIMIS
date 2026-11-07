<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff-roles.css?v=<?php echo time(); ?>">
<?php
$h = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$roles = $roles ?? [];
$canAdd = !empty($canAdd);
$canEdit = !empty($canEdit);
$canDelete = !empty($canDelete);

$totalRoles = count($roles);
$staffAssigned = 0;
$inUse = 0;
foreach ($roles as $r) {
    $staffAssigned += (int) ($r['staff_working'] ?? 0);
    if ((int) ($r['staff_total'] ?? 0) + (int) ($r['user_total'] ?? 0) > 0) {
        $inUse++;
    }
}
$unused = $totalRoles - $inUse;

$palette = [
    ['#001f3f', '#dbe7f3'],
    ['#003d7a', '#d6e6f7'],
    ['#1e3a5f', '#e2e8f0'],
    ['#0f4c5c', '#d7eef0'],
    ['#1b4d3e', '#dceee4'],
    ['#3d2b1f', '#efe6dc'],
];
$initials = static function (string $id, string $name): string {
    $src = $id !== '' ? $id : $name;
    return strtoupper(function_exists('mb_substr') ? mb_substr($src, 0, 3) : substr($src, 0, 3));
};
?>
<div class="container-fluid px-0 sr-page sr-wide srl">
    <div class="srl-head">
        <div>
            <h1 class="srl-title"><i class="fas fa-user-tag me-2"></i>Staff Roles</h1>
            <p class="srl-lead">Positions used for staff records, login access and salary structures.</p>
        </div>
        <?php if ($canAdd): ?>
            <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-primary srl-add">
                <i class="fas fa-plus me-1"></i>Add role
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2" role="alert">
            <i class="fas fa-check-circle me-2"></i><div><?php echo $h($message); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><div><?php echo $h($error); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($totalRoles > 0): ?>
    <div class="srl-stats">
        <div class="srl-stat">
            <span class="srl-stat-icon" style="background:#e8f0fb;color:#0b4a8f;"><i class="fas fa-user-tag"></i></span>
            <span><span class="srl-stat-value"><?php echo number_format($totalRoles); ?></span><span class="srl-stat-label">Roles</span></span>
        </div>
        <div class="srl-stat">
            <span class="srl-stat-icon" style="background:#e6f4ec;color:#1b6b43;"><i class="fas fa-users"></i></span>
            <span><span class="srl-stat-value"><?php echo number_format($staffAssigned); ?></span><span class="srl-stat-label">Working staff</span></span>
        </div>
        <div class="srl-stat">
            <span class="srl-stat-icon" style="background:#fff4de;color:#92580a;"><i class="fas fa-circle-check"></i></span>
            <span><span class="srl-stat-value"><?php echo number_format($inUse); ?></span><span class="srl-stat-label">Roles in use</span></span>
        </div>
        <div class="srl-stat">
            <span class="srl-stat-icon" style="background:#f3e8fb;color:#6b21a8;"><i class="fas fa-box-archive"></i></span>
            <span><span class="srl-stat-value"><?php echo number_format($unused); ?></span><span class="srl-stat-label">Unused roles</span></span>
        </div>
    </div>

    <div class="srl-toolbar">
        <div class="srl-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="srlSearch" class="form-control" placeholder="Search by role name or ID" autocomplete="off">
        </div>
        <select id="srlUsage" class="form-select srl-select" aria-label="Filter by usage">
            <option value="">All roles</option>
            <option value="used">In use (<?php echo (int) $inUse; ?>)</option>
            <option value="unused">Unused (<?php echo (int) $unused; ?>)</option>
        </select>
        <button type="button" id="srlReset" class="btn btn-outline-secondary srl-reset d-none">Reset</button>
    </div>

    <div class="srl-summary">
        Showing <strong id="srlShown"><?php echo $totalRoles; ?></strong> of <strong><?php echo $totalRoles; ?></strong>
        role<?php echo $totalRoles === 1 ? '' : 's'; ?>
    </div>

    <div class="srl-table-wrap">
        <div class="srl-table-scroll">
            <table class="srl-table" id="srlTable">
                <thead>
                    <tr>
                        <th scope="col" class="col-no">#</th>
                        <th scope="col">Role</th>
                        <th scope="col" class="col-id">Role ID</th>
                        <th scope="col" class="col-num">Staff</th>
                        <th scope="col" class="col-num">Logins</th>
                        <?php if ($canEdit || $canDelete): ?><th scope="col" class="col-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($roles as $i => $role):
                    $rid = (string) ($role['staff_position_type_id'] ?? '');
                    $rname = (string) ($role['staff_position_type_name'] ?? '');
                    $staffTotal = (int) ($role['staff_total'] ?? 0);
                    $staffWorking = (int) ($role['staff_working'] ?? 0);
                    $userTotal = (int) ($role['user_total'] ?? 0);
                    $used = ($staffTotal + $userTotal) > 0;
                    $pal = $palette[abs(crc32($rid)) % count($palette)];
                    ?>
                    <tr data-name="<?php echo $h(strtolower($rname)); ?>"
                        data-id="<?php echo $h(strtolower($rid)); ?>"
                        data-used="<?php echo $used ? 'used' : 'unused'; ?>">
                        <td class="col-no" data-label="#"><?php echo $i + 1; ?></td>
                        <td class="col-role" data-label="Role">
                            <div class="srl-role">
                                <span class="srl-avatar" style="background:<?php echo $pal[1]; ?>;color:<?php echo $pal[0]; ?>;"><?php echo $h($initials($rid, $rname)); ?></span>
                                <span class="srl-role-name"><?php echo $h($rname); ?></span>
                            </div>
                        </td>
                        <td class="col-id" data-label="Role ID"><span class="srl-id"><?php echo $h($rid); ?></span></td>
                        <td class="col-num" data-label="Staff">
                            <span class="srl-count<?php echo $staffTotal === 0 ? ' is-zero' : ''; ?>"
                                  title="<?php echo $staffWorking; ?> working of <?php echo $staffTotal; ?> staff"><?php echo number_format($staffWorking); ?></span>
                            <?php if ($staffTotal > $staffWorking): ?>
                                <span class="srl-of">of <?php echo number_format($staffTotal); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="col-num" data-label="Logins">
                            <span class="srl-count is-login<?php echo $userTotal === 0 ? ' is-zero' : ''; ?>"><?php echo number_format($userTotal); ?></span>
                        </td>
                        <?php if ($canEdit || $canDelete): ?>
                        <td class="col-actions" data-label="Actions">
                            <div class="srl-actions">
                                <?php if ($canEdit): ?>
                                    <a href="<?php echo APP_URL; ?>/staff-roles/edit?id=<?php echo urlencode($rid); ?>"
                                       class="btn btn-sm btn-outline-primary" title="Edit role &amp; salary structure">
                                        <i class="fas fa-edit me-1"></i><span class="lbl">Edit</span>
                                    </a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <?php if ($used): ?>
                                        <span class="d-inline-block" tabindex="0"
                                              title="In use by <?php echo $staffTotal; ?> staff and <?php echo $userTotal; ?> login(s) — cannot delete">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-lock me-1"></i><span class="lbl">In use</span>
                                            </button>
                                        </span>
                                    <?php else: ?>
                                        <a href="<?php echo APP_URL; ?>/staff-roles/delete?id=<?php echo urlencode($rid); ?>"
                                           class="btn btn-sm btn-outline-danger" title="Delete role">
                                            <i class="fas fa-trash me-1"></i><span class="lbl">Delete</span>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="srl-empty d-none" id="srlNoMatch">
            <i class="fas fa-magnifying-glass fa-2x mb-2"></i>
            <p class="mb-2">No roles match your filters.</p>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="srlNoMatchReset">Clear filters</button>
        </div>
    </div>
    <?php else: ?>
        <div class="srl-empty srl-empty-box">
            <i class="fas fa-user-tag fa-3x mb-3"></i>
            <p class="text-muted mb-3">No staff roles yet.</p>
            <?php if ($canAdd): ?>
                <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add role
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalRoles > 0): ?>
<script>
(function () {
    var search = document.getElementById('srlSearch');
    var usage = document.getElementById('srlUsage');
    var reset = document.getElementById('srlReset');
    var shown = document.getElementById('srlShown');
    var noMatch = document.getElementById('srlNoMatch');
    var noMatchReset = document.getElementById('srlNoMatchReset');
    var table = document.getElementById('srlTable');
    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));

    function apply() {
        var q = search.value.trim().toLowerCase();
        var us = usage.value;
        var n = 0;
        rows.forEach(function (tr) {
            var ok = (!q || tr.dataset.name.indexOf(q) !== -1 || tr.dataset.id.indexOf(q) !== -1)
                && (!us || tr.dataset.used === us);
            tr.hidden = !ok;
            if (ok) {
                n++;
                tr.querySelector('.col-no').textContent = n;
            }
        });
        shown.textContent = n;
        table.parentNode.classList.toggle('d-none', n === 0);
        noMatch.classList.toggle('d-none', n !== 0);
        reset.classList.toggle('d-none', !(q || us));
    }
    function clearAll() {
        search.value = ''; usage.value = '';
        apply();
        search.focus();
    }
    search.addEventListener('input', apply);
    usage.addEventListener('change', apply);
    reset.addEventListener('click', clearAll);
    noMatchReset.addEventListener('click', clearAll);
})();
</script>
<?php endif; ?>
