<?php
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$filters = is_array($filters ?? null) ? $filters : [];
$rows = is_array($rows ?? null) ? $rows : [];
$counts = is_array($counts ?? null) ? $counts : ['match' => 0, 'waiting' => 0, 'missing' => 0, 'total' => 0];
$departments = is_array($departments ?? null) ? $departments : [];
$pageNo = (int) ($pageNo ?? 1);
$pages = (int) ($pages ?? 1);
$filtered = (int) ($filtered ?? 0);
$q = static function (array $extra) use ($filters): string {
    return http_build_query(array_filter(array_merge([
        'q' => (string) ($filters['q'] ?? ''),
        'type' => (string) ($filters['type'] ?? ''),
        'department' => (string) ($filters['department'] ?? ''),
        'status' => (string) ($filters['status'] ?? ''),
    ], $extra), static function ($v) {
        return $v !== '' && $v !== null;
    }));
};
$statusLabel = [
    'match' => 'Password matches',
    'waiting' => 'Waiting for SIS password change',
    'missing' => 'Missing from LDAP',
    'unavailable' => 'LDAP unavailable',
];
?>
<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold">EDUROAM LDAP sync</h4>
            <p class="text-muted small mb-0">Active SIS logins in <code>ou=People</code>. The Wi-Fi username is the SIS username, including slashes. Sync adds missing accounts and does not change a password already stored in LDAP.</p>
        </div>
        <form method="post" action="<?php echo APP_URL; ?>/admin/ldap-sync?<?php echo $e($q(['page' => $pageNo])); ?>">
            <button type="submit" class="btn btn-primary btn-sm" name="sync" value="1">Sync now</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success py-2"><?php echo $e($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?php echo $e($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($ldapError)): ?>
        <div class="alert alert-warning py-2">LDAP could not be read. <?php echo $e($ldapError); ?> SIS password changes still succeed and the LDAP error is written to the log.</div>
    <?php endif; ?>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Active SIS</div><strong><?php echo (int) $counts['total']; ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Password matches</div><strong><?php echo (int) $counts['match']; ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Waiting for password change</div><strong><?php echo (int) $counts['waiting']; ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Missing</div><strong><?php echo (int) $counts['missing']; ?></strong></div></div>
    </div>

    <form method="get" action="<?php echo APP_URL; ?>/admin/ldap-sync" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label small fw-semibold" for="q">User</label>
            <input class="form-control form-control-sm" id="q" name="q" value="<?php echo $e($filters['q'] ?? ''); ?>" placeholder="Username or name">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold" for="type">Type</label>
            <select class="form-select form-select-sm" id="type" name="type">
                <option value="">Student or staff</option>
                <option value="student" <?php echo ($filters['type'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                <option value="staff" <?php echo ($filters['type'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold" for="department">Department</label>
            <select class="form-select form-select-sm" id="department" name="department">
                <option value="">All departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo $e($department); ?>" <?php echo ($filters['department'] ?? '') === $department ? 'selected' : ''; ?>><?php echo $e($department); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold" for="status">Sync status</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">All</option>
                <option value="match" <?php echo ($filters['status'] ?? '') === 'match' ? 'selected' : ''; ?>>Password matches</option>
                <option value="waiting" <?php echo ($filters['status'] ?? '') === 'waiting' ? 'selected' : ''; ?>>Waiting</option>
                <option value="missing" <?php echo ($filters['status'] ?? '') === 'missing' ? 'selected' : ''; ?>>Missing</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary btn-sm" type="submit">Filter</button>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/admin/ldap-sync">Clear</a>
        </div>
    </form>

    <div class="table-responsive border rounded">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>LDAP status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No accounts match these filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><code><?php echo $e($row['username'] ?? ''); ?></code></td>
                            <td><?php echo $e($row['name'] ?? ''); ?></td>
                            <td><?php echo $e(($row['department'] ?? '') !== '' ? $row['department'] : '—'); ?></td>
                            <td><?php echo $e(ucfirst((string) ($row['type'] ?? ''))); ?></td>
                            <td><?php echo $e($statusLabel[$row['status'] ?? ''] ?? (string) ($row['status'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
        <span><?php echo $filtered; ?> shown · page <?php echo $pageNo; ?> of <?php echo $pages; ?> · 40 per page</span>
        <span>
            <?php if ($pageNo > 1): ?>
                <a href="<?php echo APP_URL; ?>/admin/ldap-sync?<?php echo $e($q(['page' => $pageNo - 1])); ?>">Previous</a>
            <?php endif; ?>
            <?php if ($pageNo < $pages): ?>
                <a class="ms-2" href="<?php echo APP_URL; ?>/admin/ldap-sync?<?php echo $e($q(['page' => $pageNo + 1])); ?>">Next</a>
            <?php endif; ?>
        </span>
    </div>
</div>
