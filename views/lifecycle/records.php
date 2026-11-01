<?php require __DIR__ . '/partials/shell.php';
$kind = $kind ?? '';
$registers = $registers ?? [];
?>
<?php if ($kind === ''): ?>
    <div class="lc-register-links">
        <a href="<?php echo APP_URL; ?>/students/create">Add student</a>
        <a href="<?php echo APP_URL; ?>/students">Student records</a>
        <a href="<?php echo APP_URL; ?>/attendance">Attendance</a>
        <a href="<?php echo APP_URL; ?>/exams">Examinations</a>
        <a href="<?php echo APP_URL; ?>/lifecycle/import">Historical import</a>
    </div>
    <div class="lc-cards">
        <?php foreach ($registers as $key => $label): ?>
            <article>
                <span><?php echo $h($label); ?></span>
                <a href="<?php echo APP_URL; ?>/lifecycle/records?kind=<?php echo urlencode($key); ?>">Open register</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php else:
    $rows = $result['rows'] ?? [];
    $query = $filters;
    unset($query['department_limit']);
    $query['kind'] = $kind;
    $baseQuery = $query;
    $statusOptions = [];
    foreach ($spec['fields'] as $field) {
        if ($field['name'] === 'status' && !empty($field['options'])) {
            $statusOptions = $field['options'];
        }
    }
    $sortLink = function ($column) use ($baseQuery) {
        $next = $baseQuery;
        $next['sort'] = $column;
        $next['dir'] = (($baseQuery['sort'] ?? '') === $column && ($baseQuery['dir'] ?? '') !== 'asc') ? 'asc' : 'desc';
        return APP_URL . '/lifecycle/records?' . http_build_query($next);
    };
    $columns = [];
    if (!empty($spec['needs_student'])) {
        $columns[] = ['key' => 'name', 'label' => 'Student'];
    }
    foreach (array_slice($spec['fields'], 0, 4) as $field) {
        $columns[] = ['key' => $field['name'], 'label' => $field['label']];
    }
?>
    <form class="lc-filters" method="get" action="<?php echo APP_URL; ?>/lifecycle/records">
        <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
        <label class="lc-field-wide"><span>Search</span><input class="form-control" type="search" name="q" value="<?php echo $h($filters['q'] ?? ''); ?>" placeholder="Registration, NIC, name or reference"></label>
        <label><span>Academic year</span>
            <select class="form-select" name="year">
                <option value="">All years</option>
                <?php foreach ($academic_years as $academicYear): ?>
                    <option value="<?php echo $h($academicYear); ?>" <?php echo (($filters['year'] ?? '') === $academicYear) ? 'selected' : ''; ?>><?php echo $h($academicYear); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($statusOptions): ?>
        <label><span>Status</span>
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?php echo $h($value); ?>" <?php echo (($filters['status'] ?? '') === (string) $value) ? 'selected' : ''; ?>><?php echo $h($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <label><span>Rows</span>
            <select class="form-select" name="per_page">
                <?php foreach ([10, 20, 50] as $size): ?>
                    <option value="<?php echo $size; ?>" <?php echo ((int) $per_page === $size) ? 'selected' : ''; ?>><?php echo $size; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if (!empty($actor['can_delete'])): ?>
            <label><span>Archived</span>
                <select class="form-select" name="deleted">
                    <option value="">Active records</option>
                    <option value="1" <?php echo (($filters['deleted'] ?? '') === '1') ? 'selected' : ''; ?>>Archived only</option>
                </select>
            </label>
        <?php endif; ?>
        <div class="lc-filter-actions">
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/lifecycle/records?kind=<?php echo urlencode($kind); ?>">Clear</a>
        </div>
    </form>
    <div class="lc-register-links">
        <a class="btn btn-primary btn-sm" href="<?php echo APP_URL; ?>/lifecycle/record/form?kind=<?php echo urlencode($kind); ?>">Add new</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/lifecycle/records/export?<?php echo $h(http_build_query($baseQuery)); ?>">Excel</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/lifecycle/records/print?<?php echo $h(http_build_query($baseQuery)); ?>" target="_blank">Print / PDF</a>
        <span class="text-muted"><?php echo (int) ($result['total'] ?? 0); ?> records</span>
    </div>
    <form id="lcBulk" method="post" action="<?php echo APP_URL; ?>/lifecycle/bulk-status" class="d-none">
        <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
        <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
    </form>
        <div class="lc-table-wrap table-responsive">
            <table class="table lc-table align-middle">
                <thead>
                    <tr>
                        <?php if ($statusOptions && !empty($actor['office']) && ($filters['deleted'] ?? '') !== '1'): ?><th></th><?php endif; ?>
                        <?php foreach ($columns as $column): ?>
                            <th><a href="<?php echo $h($sortLink($column['key'])); ?>"><?php echo $h($column['label']); ?></a></th>
                        <?php endforeach; ?>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="<?php echo count($columns) + 3; ?>">No records match this register.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php if ($statusOptions && !empty($actor['office']) && ($filters['deleted'] ?? '') !== '1'): ?>
                            <td><input form="lcBulk" type="checkbox" name="ids[]" value="<?php echo (int) $row['id']; ?>" aria-label="Select record"></td>
                        <?php endif; ?>
                        <?php foreach ($columns as $column): ?>
                            <td>
                                <?php if ($column['key'] === 'name'): ?>
                                    <a href="<?php echo APP_URL; ?>/lifecycle/profile?id=<?php echo urlencode($row['student_id']); ?>"><?php echo $h($row['student_fullname'] ?: $row['student_id']); ?></a>
                                    <div class="text-muted small"><?php echo $h($row['student_id']); ?><?php if (!empty($row['student_nic'])): ?> · <?php echo $h($row['student_nic']); ?><?php endif; ?></div>
                                <?php else: ?>
                                    <?php echo $h($row[$column['key']] ?? ''); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td><?php if (!empty($row['status'])): ?><span class="lc-badge"><?php echo $h($row['status']); ?></span><?php elseif (!empty($row['deleted_at'])): ?><span class="lc-badge warn">Archived</span><?php endif; ?></td>
                        <td class="text-nowrap">
                            <?php if (empty($row['deleted_at'])): ?>
                                <?php $row_kind = $kind; $row_record = $row; $row_student = $row['student_id'] ?? ''; $row_tab = ''; require __DIR__ . '/partials/row_tools.php'; ?>
                            <?php elseif (!empty($actor['can_delete'])): ?>
                                <form method="post" action="<?php echo APP_URL; ?>/lifecycle/restore" onsubmit="return confirm('Restore this record?');">
                                    <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
                                    <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
                                    <input type="hidden" name="record_id" value="<?php echo (int) $row['id']; ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Restore</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($kind === 'approval' && ($row['status'] ?? '') === 'Submitted' && !empty($actor['can_approve']) && empty($row['deleted_at'])): ?>
                                <form method="post" action="<?php echo APP_URL; ?>/lifecycle/approve" class="lc-row-tools">
                                    <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
                                    <input type="hidden" name="record_id" value="<?php echo (int) $row['id']; ?>">
                                    <input class="form-control form-control-sm" name="remarks" placeholder="Remarks">
                                    <input class="form-control form-control-sm" name="rejection_reason" placeholder="Reason if rejecting">
                                    <button class="btn btn-sm btn-primary" name="decision" value="Approved" type="submit">Approve</button>
                                    <button class="btn btn-sm btn-outline-danger" name="decision" value="Rejected" type="submit">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($statusOptions && !empty($actor['office']) && ($filters['deleted'] ?? '') !== '1'): ?>
            <div class="lc-register-links">
                <select form="lcBulk" class="form-select form-select-sm" name="status" style="width:auto">
                    <?php foreach ($statusOptions as $value => $label): ?><option value="<?php echo $h($value); ?>"><?php echo $h($label); ?></option><?php endforeach; ?>
                </select>
                <button form="lcBulk" class="btn btn-sm btn-outline-primary" type="submit" onclick="return confirm('Update the selected records?');">Update selected</button>
            </div>
        <?php endif; ?>
    <?php if (($result['pages'] ?? 1) > 1): ?>
        <nav class="lc-nav">
            <?php for ($i = 1; $i <= (int) $result['pages']; $i++):
                $pageQuery = $baseQuery;
                $pageQuery['page'] = $i;
                $pageQuery['per_page'] = $per_page;
            ?>
                <a class="<?php echo ((int) $result['page'] === $i) ? 'is-on' : ''; ?>" href="<?php echo $h(APP_URL . '/lifecycle/records?' . http_build_query($pageQuery)); ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/close.php'; ?>
