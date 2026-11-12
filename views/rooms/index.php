<?php
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$isAdminOrADM = !empty($isAdminOrADM);
$totalStudents = 0;
$totalCap = 0;
$totalOcc = 0;
foreach ($hostelGroups as $g) {
    $totalStudents += (int) ($g['students'] ?? 0);
    $totalCap += (int) ($g['capacity'] ?? 0);
    $totalOcc += (int) ($g['occupied'] ?? 0);
}
$totalAvail = max(0, $totalCap - $totalOcc);
?>
<style>
.rooms-page {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: .75rem 1rem 1.25rem;
}
.rooms-page .rooms-shell {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: .65rem;
    background: #fff;
    overflow: hidden;
}
.rooms-page .rooms-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    padding: .85rem 1rem;
    background: #0d6efd;
    color: #fff;
}
.rooms-page .rooms-head h1 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}
.rooms-page .rooms-body { padding: 1rem; }
.rooms-page .filter-row {
    display: grid;
    grid-template-columns: minmax(180px, 1.2fr) minmax(180px, 1.4fr) auto;
    gap: .75rem;
    align-items: end;
    margin-bottom: 1rem;
    padding: .85rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    background: #f8fafc;
}
.rooms-page .filter-row label {
    display: block;
    margin: 0 0 .3rem;
    font-size: .75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.rooms-page .filter-actions {
    display: flex;
    gap: .5rem;
    align-items: center;
}
.rooms-page .stats-row {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .65rem;
    margin-bottom: 1rem;
}
.rooms-page .stat-card {
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    background: #fff;
    padding: .7rem .85rem;
    min-width: 0;
}
.rooms-page .stat-card .lbl {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: .2rem;
}
.rooms-page .stat-card .val {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.rooms-page .hostel-block {
    border: 1px solid #e2e8f0;
    border-radius: .55rem;
    overflow: hidden;
    margin-bottom: 1rem;
}
.rooms-page .hostel-head,
.rooms-page .flat-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    padding: .7rem 1rem;
}
.rooms-page .hostel-head {
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
}
.rooms-page .flat-head {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}
.rooms-page .meta-inline {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .85rem;
    font-size: .8rem;
    color: #64748b;
}
.rooms-page .meta-inline strong { color: #0f172a; }
.rooms-page .table-wrap { width: 100%; overflow-x: auto; }
.rooms-page table.room-table {
    width: 100%;
    table-layout: fixed;
    margin: 0;
    border-collapse: collapse;
}
.rooms-page table.room-table > thead > tr > th {
    padding: .55rem .65rem;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    vertical-align: middle;
}
.rooms-page table.room-table > tbody > tr.room-row > td {
    padding: .6rem .65rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: .875rem;
}
.rooms-page table.room-table > tbody > tr.room-row:hover { background: #f8fafc; }
.rooms-page .col-expand { width: 44px; text-align: center; }
.rooms-page .col-num { width: 48px; text-align: center; color: #94a3b8; }
.rooms-page .col-room { width: 12%; }
.rooms-page .col-stu { width: 10%; text-align: center; }
.rooms-page .col-metric { width: 9%; text-align: center; }
.rooms-page .col-occ { width: 16%; }
.rooms-page .col-act { width: 96px; text-align: right; }
.rooms-page .room-no { font-weight: 700; color: #0f172a; }
.rooms-page .occ-wrap {
    display: flex;
    align-items: center;
    gap: .5rem;
    width: 100%;
}
.rooms-page .occ-bar {
    flex: 1;
    min-width: 0;
    height: 8px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
}
.rooms-page .occ-bar > span {
    display: block;
    height: 100%;
    border-radius: 99px;
}
.rooms-page .occ-pct {
    width: 2.5rem;
    text-align: right;
    font-size: .75rem;
    color: #64748b;
    flex-shrink: 0;
}
.rooms-page .btn-expand {
    width: 28px;
    height: 28px;
    padding: 0;
    border-radius: .35rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.rooms-page .btn-expand:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.rooms-page .btn-expand .fa-chevron-down { transition: transform .15s ease; font-size: .7rem; }
.rooms-page .btn-expand[aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }
.rooms-page .students-panel { background: #f8fafc; border-top: 1px dashed #cbd5e1; }
.rooms-page .students-panel .inner { padding: .85rem 1rem 1rem; }
.rooms-page table.stu-table {
    width: 100%;
    table-layout: fixed;
    margin: 0;
    background: #fff;
}
.rooms-page table.stu-table th {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #64748b;
    font-weight: 600;
    white-space: nowrap;
    background: #f8fafc;
}
.rooms-page table.stu-table td {
    font-size: .8125rem;
    vertical-align: middle;
    word-wrap: break-word;
}
.rooms-page .empty-room { color: #94a3b8; font-size: .8125rem; }
.rooms-page .actions .btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
@media (max-width: 992px) {
    .rooms-page .stats-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .rooms-page .filter-row { grid-template-columns: 1fr 1fr; }
    .rooms-page .filter-actions { grid-column: 1 / -1; }
}
@media (max-width: 576px) {
    .rooms-page { padding: .5rem .65rem 1rem; }
    .rooms-page .stats-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rooms-page .filter-row { grid-template-columns: 1fr; }
}
</style>

<div class="rooms-page">
    <div class="rooms-shell">
        <div class="rooms-head">
            <h1><i class="fas fa-door-open me-2"></i>Room Management</h1>
            <?php if ($isAdminOrADM): ?>
                <a href="<?php echo APP_URL; ?>/rooms/create" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>Add Room
                </a>
            <?php endif; ?>
        </div>

        <div class="rooms-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo $e($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo $e($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="GET" action="<?php echo APP_URL; ?>/rooms" class="filter-row">
                <div>
                    <label for="hostel_id">Hostel</label>
                    <select name="hostel_id" id="hostel_id" class="form-select form-select-sm">
                        <option value="">All Hostels</option>
                        <?php foreach ($hostels as $hostel): ?>
                            <option value="<?php echo $e($hostel['id']); ?>"
                                <?php echo ((string) ($hostel_id ?? '') === (string) $hostel['id']) ? 'selected' : ''; ?>>
                                <?php echo $e($hostel['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="search">Room number</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm"
                           placeholder="Search room number…"
                           value="<?php echo $e($search ?? ''); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <?php if (!empty($search) || !empty($hostel_id)): ?>
                        <a href="<?php echo APP_URL; ?>/rooms" class="btn btn-outline-secondary btn-sm" title="Clear">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="stats-row">
                <div class="stat-card"><span class="lbl">Rooms</span><span class="val"><?php echo number_format((int) $total); ?></span></div>
                <div class="stat-card"><span class="lbl">Hostels</span><span class="val"><?php echo number_format(count($hostelGroups)); ?></span></div>
                <div class="stat-card"><span class="lbl">Capacity</span><span class="val"><?php echo number_format($totalCap); ?></span></div>
                <div class="stat-card"><span class="lbl">Available</span><span class="val <?php echo $totalAvail > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($totalAvail); ?></span></div>
                <div class="stat-card"><span class="lbl">Students</span><span class="val"><?php echo number_format($totalStudents); ?></span></div>
            </div>

            <?php if (!empty($hostelGroups)): ?>
                <?php foreach ($hostelGroups as $group):
                    $gCap = (int) $group['capacity'];
                    $gOcc = (int) $group['occupied'];
                    $gAvail = max(0, $gCap - $gOcc);
                    $gStu = (int) ($group['students'] ?? 0);
                    $flats = is_array($group['flats'] ?? null) ? $group['flats'] : [];
                ?>
                    <section class="hostel-block">
                        <div class="hostel-head">
                            <div>
                                <div class="fw-bold text-primary">
                                    <i class="fas fa-building me-2"></i><?php echo $e($group['name']); ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?php echo number_format(count($flats)); ?> flat(s)
                                    · <?php echo number_format(count($group['rooms'])); ?> room(s)
                                </div>
                            </div>
                            <div class="meta-inline">
                                <span>Capacity <strong><?php echo number_format($gCap); ?></strong></span>
                                <span>Occupied <strong><?php echo number_format($gOcc); ?></strong></span>
                                <span>Available <strong class="<?php echo $gAvail > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($gAvail); ?></strong></span>
                                <span>Students <strong><?php echo number_format($gStu); ?></strong></span>
                            </div>
                        </div>

                        <?php foreach ($flats as $flat):
                            $fCap = (int) ($flat['capacity'] ?? 0);
                            $fOcc = (int) ($flat['occupied'] ?? 0);
                            $fAvail = max(0, $fCap - $fOcc);
                            $fStu = (int) ($flat['students'] ?? 0);
                            $flatRooms = is_array($flat['rooms'] ?? null) ? $flat['rooms'] : [];
                        ?>
                            <div class="flat-head">
                                <div class="fw-semibold">
                                    <i class="fas fa-layer-group me-2 text-secondary"></i><?php echo $e($flat['name'] ?? 'Flat'); ?>
                                    <span class="text-muted fw-normal ms-1">(<?php echo number_format(count($flatRooms)); ?> rooms)</span>
                                </div>
                                <div class="meta-inline">
                                    <span>Cap <strong><?php echo number_format($fCap); ?></strong></span>
                                    <span>Occ <strong><?php echo number_format($fOcc); ?></strong></span>
                                    <span>Avail <strong class="<?php echo $fAvail > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($fAvail); ?></strong></span>
                                    <span>Students <strong><?php echo number_format($fStu); ?></strong></span>
                                </div>
                            </div>

                            <div class="table-wrap">
                                <table class="room-table">
                                    <colgroup>
                                        <col class="col-expand">
                                        <col class="col-num">
                                        <col class="col-room">
                                        <col class="col-stu">
                                        <col class="col-metric">
                                        <col class="col-metric">
                                        <col class="col-metric">
                                        <col class="col-occ">
                                        <?php if ($isAdminOrADM): ?><col class="col-act"><?php endif; ?>
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="col-expand"></th>
                                            <th class="col-num">#</th>
                                            <th class="col-room">Room</th>
                                            <th class="col-stu">Students</th>
                                            <th class="col-metric">Cap</th>
                                            <th class="col-metric">Occ</th>
                                            <th class="col-metric">Avail</th>
                                            <th class="col-occ">Occupancy</th>
                                            <?php if ($isAdminOrADM): ?><th class="col-act">Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $colspan = $isAdminOrADM ? 9 : 8;
                                        foreach ($flatRooms as $i => $room):
                                            $capacity = (int) ($room['capacity'] ?? 0);
                                            $occupied = (int) ($room['occupied_beds'] ?? 0);
                                            $available = max(0, $capacity - $occupied);
                                            $pct = $capacity > 0 ? min(100, ($occupied / $capacity) * 100) : 0;
                                            $barColor = $pct >= 100 ? '#dc2626' : ($pct >= 80 ? '#d97706' : '#16a34a');
                                            $students = is_array($room['students'] ?? null) ? $room['students'] : [];
                                            $studentCount = count($students);
                                            $rowId = 'room-stu-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($room['id'] ?? $i . '-' . ($flat['name'] ?? '')));
                                        ?>
                                            <tr class="room-row">
                                                <td class="col-expand">
                                                    <?php if ($studentCount > 0): ?>
                                                        <button type="button" class="btn-expand"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#<?php echo $e($rowId); ?>"
                                                                aria-expanded="false"
                                                                aria-controls="<?php echo $e($rowId); ?>"
                                                                title="Show students">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">·</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="col-num"><?php echo $i + 1; ?></td>
                                                <td class="col-room"><span class="room-no"><?php echo $e($room['room_no'] ?? '—'); ?></span></td>
                                                <td class="col-stu">
                                                    <?php if ($studentCount === 0): ?>
                                                        <span class="empty-room">Vacant</span>
                                                    <?php else: ?>
                                                        <span class="badge rounded-pill text-bg-primary"><?php echo number_format($studentCount); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="col-metric"><?php echo number_format($capacity); ?></td>
                                                <td class="col-metric fw-semibold"><?php echo number_format($occupied); ?></td>
                                                <td class="col-metric">
                                                    <span class="<?php echo $available > 0 ? 'text-success' : 'text-danger'; ?> fw-semibold">
                                                        <?php echo number_format($available); ?>
                                                    </span>
                                                </td>
                                                <td class="col-occ">
                                                    <div class="occ-wrap">
                                                        <div class="occ-bar" title="<?php echo round($pct); ?>%">
                                                            <span style="width:<?php echo round($pct); ?>%;background:<?php echo $barColor; ?>;"></span>
                                                        </div>
                                                        <span class="occ-pct"><?php echo round($pct); ?>%</span>
                                                    </div>
                                                </td>
                                                <?php if ($isAdminOrADM): ?>
                                                    <td class="col-act actions text-nowrap">
                                                        <a href="<?php echo APP_URL; ?>/rooms/edit?id=<?php echo urlencode((string) ($room['id'] ?? '')); ?>"
                                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="<?php echo APP_URL; ?>/rooms/delete?id=<?php echo urlencode((string) ($room['id'] ?? '')); ?>"
                                                           class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php if ($studentCount > 0): ?>
                                                <tr>
                                                    <td colspan="<?php echo $colspan; ?>" class="p-0 border-0">
                                                        <div class="collapse students-panel" id="<?php echo $e($rowId); ?>">
                                                            <div class="inner">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <div class="small fw-semibold text-primary mb-0">
                                                                        <i class="fas fa-user-graduate me-1"></i>
                                                                        Room <?php echo $e($room['room_no'] ?? ''); ?> — students
                                                                    </div>
                                                                    <span class="badge text-bg-light border"><?php echo number_format($studentCount); ?> allocated</span>
                                                                </div>
                                                                <div class="table-wrap border rounded">
                                                                    <table class="table table-sm stu-table mb-0">
                                                                        <colgroup>
                                                                            <col style="width:4%">
                                                                            <col style="width:14%">
                                                                            <col style="width:22%">
                                                                            <col style="width:8%">
                                                                            <col style="width:16%">
                                                                            <col style="width:16%">
                                                                            <col style="width:12%">
                                                                            <col style="width:8%">
                                                                        </colgroup>
                                                                        <thead>
                                                                            <tr>
                                                                                <th>#</th>
                                                                                <th>Student ID</th>
                                                                                <th>Name</th>
                                                                                <th>Gender</th>
                                                                                <th>Department</th>
                                                                                <th>Course</th>
                                                                                <th>Phone</th>
                                                                                <th>Allocated</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($students as $si => $st): ?>
                                                                                <tr>
                                                                                    <td class="text-muted"><?php echo $si + 1; ?></td>
                                                                                    <td class="fw-semibold"><?php echo $e($st['student_id'] ?? ''); ?></td>
                                                                                    <td><?php echo $e($st['student_fullname'] ?? ''); ?></td>
                                                                                    <td><?php echo $e($st['student_gender'] ?? '—'); ?></td>
                                                                                    <td><?php echo $e($st['department_name'] ?? '—'); ?></td>
                                                                                    <td><?php echo $e($st['course_name'] ?? '—'); ?></td>
                                                                                    <td><?php echo $e($st['student_phone'] ?? '—'); ?></td>
                                                                                    <td class="text-muted">
                                                                                        <?php
                                                                                        $at = (string) ($st['allocated_at'] ?? '');
                                                                                        echo $at !== '' ? $e(date('Y-m-d', strtotime($at))) : '—';
                                                                                        ?>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No rooms found for the selected filters.</p>
                    <?php if ($isAdminOrADM): ?>
                        <a href="<?php echo APP_URL; ?>/rooms/create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Add Room
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
