<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$pageTitle = 'Attendance list';

$perPage = isset($_GET['per_page']) ? max(5, min(100, (int) $_GET['per_page'])) : ATT_PAGE_SIZE;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$q = trim((string) ($_GET['q'] ?? ''));
$employeeNo = trim((string) ($_GET['employee_no'] ?? ''));
$staffName = trim((string) ($_GET['staff_name'] ?? ''));
$department = trim((string) ($_GET['department'] ?? ''));
$startDate = trim((string) ($_GET['start_date'] ?? ''));
$endDate = trim((string) ($_GET['end_date'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = '(employee_no LIKE ? OR staff_name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($employeeNo !== '') {
    $where[] = 'employee_no LIKE ?';
    $params[] = '%' . $employeeNo . '%';
    $types .= 's';
}
if ($staffName !== '') {
    $where[] = 'staff_name LIKE ?';
    $params[] = '%' . $staffName . '%';
    $types .= 's';
}
if ($department !== '') {
    $where[] = 'department LIKE ?';
    $params[] = '%' . $department . '%';
    $types .= 's';
}
if ($startDate !== '' && $endDate !== '') {
    $where[] = 'DATE(attendance_time) BETWEEN ? AND ?';
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
} elseif ($startDate !== '') {
    $where[] = 'DATE(attendance_time) >= ?';
    $params[] = $startDate;
    $types .= 's';
} elseif ($endDate !== '') {
    $where[] = 'DATE(attendance_time) <= ?';
    $params[] = $endDate;
    $types .= 's';
}

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$db = attendance_db();

$countSql = "SELECT COUNT(*) AS c FROM staff_attendance $sqlWhere";
$countStmt = $db->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listSql = "SELECT attendance_id, employee_no, staff_name, department, attendance_time, device_ip, event_type, created_at
            FROM staff_attendance $sqlWhere
            ORDER BY attendance_time DESC
            LIMIT ? OFFSET ?";
$listStmt = $db->prepare($listSql);
$typesList = $types . 'ii';
$paramsList = array_merge($params, [$perPage, $offset]);
if ($typesList !== 'ii') {
    $listStmt->bind_param($typesList, ...$paramsList);
} else {
    $listStmt->bind_param('ii', $perPage, $offset);
}
$listStmt->execute();
$rows = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

$qs = $_GET;
unset($qs['page']);
$baseQuery = http_build_query($qs);

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">All attendance</h1>

<form class="card shadow-sm mb-4" method="get" action="">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Search (employee no. or name)</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Quick search"
                       value="<?php echo attendance_escape($q); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Employee no.</label>
                <input type="text" name="employee_no" class="form-control form-control-sm"
                       value="<?php echo attendance_escape($employeeNo); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Staff name</label>
                <input type="text" name="staff_name" class="form-control form-control-sm"
                       value="<?php echo attendance_escape($staffName); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Department</label>
                <input type="text" name="department" class="form-control form-control-sm"
                       value="<?php echo attendance_escape($department); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Start date</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                       value="<?php echo attendance_escape($startDate); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">End date</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                       value="<?php echo attendance_escape($endDate); ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-0">Per page</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $n): ?>
                        <option value="<?php echo $n; ?>" <?php echo $perPage === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="list_attendance.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </div>
    </div>
</form>

<p class="text-muted small"><?php echo $totalRows; ?> record(s) — page <?php echo $page; ?> of <?php echo $totalPages; ?></p>

<div class="table-responsive shadow-sm bg-white rounded">
    <table class="table table-striped table-hover table-sm mb-0">
        <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Employee no.</th>
            <th>Name</th>
            <th>Department</th>
            <th>Time</th>
            <th>Device IP</th>
            <th>Event</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center py-4">No records found.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo (int) $r['attendance_id']; ?></td>
                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                    <td><?php echo attendance_escape((string) $r['department']); ?></td>
                    <td><?php echo attendance_escape((string) $r['attendance_time']); ?></td>
                    <td><?php echo attendance_escape((string) $r['device_ip']); ?></td>
                    <td><?php echo attendance_escape((string) $r['event_type']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3" aria-label="Pagination">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <?php
        $buildLink = static function (int $p) use ($baseQuery): string {
            $q = $baseQuery !== '' ? $baseQuery . '&' : '';
            return 'list_attendance.php?' . $q . 'page=' . $p;
        };
        ?>
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="<?php echo attendance_escape($buildLink($page - 1)); ?>">Previous</a></li>
        <?php endif; ?>
        <?php
        $from = max(1, $page - 2);
        $to = min($totalPages, $page + 2);
        for ($p = $from; $p <= $to; $p++):
        ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo attendance_escape($buildLink($p)); ?>"><?php echo $p; ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="<?php echo attendance_escape($buildLink($page + 1)); ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
