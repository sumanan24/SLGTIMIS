<?php
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$room = is_array($room ?? null) ? $room : [];
$hostel = is_array($hostel ?? null) ? $hostel : [];
$students = is_array($students ?? null) ? $students : [];
$locked = is_array($locked ?? null) ? $locked : [];
$nvq = (string) ($nvq ?? '4');
$slots = (int) ($slots ?? 0);
$roomId = (string) ($room['id'] ?? '');
$hostelId = (string) ($room['hostel_id'] ?? ($hostel['id'] ?? ''));
$levelLabel = 'Level ' . str_pad($nvq, 2, '0', STR_PAD_LEFT);
$academicYear = trim((string) ($academic_year ?? ''));
$academicYears = is_array($academic_years ?? null) ? $academic_years : [];
$yearQuery = $academicYear !== '' ? '&academic_year=' . urlencode($academicYear) : '';
$back = APP_URL . '/rooms?hostel_id=' . urlencode($hostelId);
$floor = trim((string) ($room['block_name'] ?? ''));
$where = trim((string) ($hostel['name'] ?? 'Hostel'));
if ($floor !== '') {
    $where .= ' · Floor ' . $floor;
}
$where .= ' · Room ' . trim((string) ($room['room_no'] ?? ''));
?>
<style>
.assign-page { width: 100%; }
.assign-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
.assign-meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .4rem; }
.assign-meta span { display: inline-flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; padding: .15rem .6rem; font-size: .78rem; color: #334155; line-height: 1.3; }
.assign-note { margin: 0 0 1rem; max-width: 70rem; }
.assign-filters { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem; margin-bottom: 1rem; }
.assign-year { display: flex; align-items: center; gap: .5rem; margin: 0; }
.assign-year .form-select { width: 9.5rem; }
.assign-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .75rem; }
.assign-tools .form-control { width: 16rem; max-width: 100%; }
.assign-stats { margin-left: auto; display: flex; align-items: center; gap: .5rem; }
.assign-list { max-height: 32rem; overflow: auto; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; }
.assign-cols { display: grid; grid-template-columns: minmax(10rem, 1.5fr) 9.5rem minmax(8rem, 1fr) 6.5rem minmax(8rem, 1.15fr); gap: .65rem; align-items: center; min-width: 0; }
.assign-headrow, .assign-row { display: grid; grid-template-columns: 2.25rem minmax(0, 1fr); gap: .5rem; align-items: center; padding: .5rem .75rem; }
.assign-headrow { position: sticky; top: 0; z-index: 1; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .75rem; font-weight: 650; color: #64748b; }
.assign-row { margin: 0; border-bottom: 1px solid #f1f5f9; cursor: pointer; }
.assign-row:last-child { border-bottom: 0; }
.assign-row:hover { background: #f8fafc; }
.assign-row.is-hide { display: none; }
.assign-row .form-check-input { margin: 0; float: none; position: static; }
.assign-row .name { font-weight: 650; }
.assign-row .cell { color: #475569; font-size: .84rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
@media (max-width: 900px) {
    .assign-stats { margin-left: 0; }
    .assign-headrow { display: none; }
    .assign-cols { grid-template-columns: 1fr; gap: .1rem; }
    .assign-row .cell { white-space: normal; }
}
</style>
<div class="assign-page">
    <div class="assign-head">
        <div>
            <h1 class="h5 mb-0">Assign students</h1>
            <div class="assign-meta">
                <span><?php echo $e($where); ?></span>
                <span><?php echo (int) $slots; ?> free <?php echo $slots === 1 ? 'bed' : 'beds'; ?></span>
                <span><?php echo count($students); ?> active <?php echo count($students) === 1 ? 'student' : 'students'; ?> with hostel fee paid</span>
            </div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($back); ?>"><i class="fas fa-arrow-left me-1"></i>Rooms</a>
    </div>

    <?php if ($slots < 1): ?>
        <div class="alert alert-warning">This room already has 8 students.</div>
    <?php else: ?>
        <p class="assign-note small text-muted">Level 04 and Level 05 stay in separate rooms. One room takes <?php echo (int) $slots; ?> students. Only active students who have paid the hostel fee at least once are listed. Students who already have a room are not listed. Northern Province students are not listed. Pick <?php echo (int) $slots; ?> at random chooses different departments.</p>
        <div class="assign-filters">
            <div class="btn-group" role="group">
                <?php foreach (['4' => 'Level 04', '5' => 'Level 05'] as $code => $label):
                    $disabled = count($locked) === 1 && $locked[0] !== $code;
                    $href = APP_URL . '/rooms/assign?room_id=' . urlencode($roomId) . '&nvq=' . $code . $yearQuery;
                ?>
                    <?php if ($disabled): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled><?php echo $e($label); ?></button>
                    <?php else: ?>
                        <a class="btn btn-sm <?php echo $nvq === $code ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo $e($href); ?>"><?php echo $e($label); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <form method="GET" action="<?php echo APP_URL; ?>/rooms/assign" class="assign-year">
                <input type="hidden" name="room_id" value="<?php echo $e($roomId); ?>">
                <input type="hidden" name="nvq" value="<?php echo $e($nvq); ?>">
                <label class="small text-muted mb-0" for="academic_year">Academic year</label>
                <select name="academic_year" id="academic_year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All years</option>
                    <?php foreach ($academicYears as $year): ?>
                        <option value="<?php echo $e($year); ?>" <?php echo $academicYear === (string) $year ? 'selected' : ''; ?>><?php echo $e($year); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <form method="POST" action="<?php echo APP_URL; ?>/rooms/assign?room_id=<?php echo urlencode($roomId); ?>&amp;nvq=<?php echo urlencode($nvq); ?><?php echo $e($yearQuery); ?>" id="assignForm" data-max="<?php echo (int) $slots; ?>">
            <input type="hidden" name="room_id" value="<?php echo $e($roomId); ?>">
            <input type="hidden" name="nvq" value="<?php echo $e($nvq); ?>">
            <input type="hidden" name="academic_year" value="<?php echo $e($academicYear); ?>">
            <div class="assign-tools">
                <input type="search" id="assignFind" class="form-control form-control-sm" placeholder="Search name or ID">
                <button type="button" class="btn btn-outline-dark btn-sm" id="assignRandom">Pick <?php echo (int) $slots; ?> from different departments</button>
                <span class="assign-stats">
                    <span class="badge text-bg-light border"><?php echo count($students); ?> in this list</span>
                    <span class="small text-muted" id="assignCount">0 selected</span>
                </span>
            </div>
            <?php if (!$students): ?>
                <div class="alert alert-info">No active <?php echo $e($levelLabel); ?> students<?php echo $academicYear !== '' ? ' in ' . $e($academicYear) : ''; ?> who have paid the hostel fee are waiting for this hostel.</div>
            <?php else: ?>
                <div class="assign-list mb-3">
                    <div class="assign-headrow">
                        <span></span>
                        <span class="assign-cols">
                            <span>Name</span>
                            <span>Reg. number</span>
                            <span>Department</span>
                            <span>Year</span>
                            <span>Course</span>
                        </span>
                    </div>
                    <?php foreach ($students as $stu):
                        $sid = (string) ($stu['student_id'] ?? '');
                        $name = (string) ($stu['student_fullname'] ?? '');
                        $dept = trim((string) ($stu['department_name'] ?? ''));
                        $deptKey = trim((string) ($stu['department_id'] ?? ''));
                        if ($deptKey === '') {
                            $deptKey = $dept !== '' ? $dept : 'none';
                        }
                        $hay = strtolower($sid . ' ' . $name . ' ' . $dept);
                    ?>
                        <label class="assign-row" data-hay="<?php echo $e($hay); ?>" data-dept="<?php echo $e($deptKey); ?>">
                            <input class="form-check-input assign-box" type="checkbox" name="student_ids[]" value="<?php echo $e($sid); ?>">
                            <span class="assign-cols">
                                <span class="name"><?php echo $e($name !== '' ? $name : $sid); ?></span>
                                <span class="cell"><?php echo $e($sid); ?></span>
                                <span class="cell"><?php echo $e($dept !== '' ? $dept : '—'); ?></span>
                                <span class="cell"><?php echo $e(!empty($stu['academic_year']) ? $stu['academic_year'] : '—'); ?></span>
                                <span class="cell"><?php echo $e(!empty($stu['course_name']) ? $stu['course_name'] : '—'); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Assign to this room</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
<script>
(function () {
    var form = document.getElementById('assignForm');
    if (!form) return;
    var max = parseInt(form.getAttribute('data-max') || '8', 10);
    var boxes = Array.prototype.slice.call(form.querySelectorAll('.assign-box'));
    var count = document.getElementById('assignCount');
    var find = document.getElementById('assignFind');
    function selected() {
        return boxes.filter(function (box) { return box.checked; }).length;
    }
    function paint() {
        if (count) count.textContent = selected() + ' selected';
    }
    function visibleBoxes() {
        return boxes.filter(function (box) {
            var row = box.closest('.assign-row');
            return row && !row.classList.contains('is-hide');
        });
    }
    boxes.forEach(function (box) {
        box.addEventListener('change', function () {
            if (box.checked && selected() > max) {
                box.checked = false;
                alert('This room takes ' + max + ' students.');
            }
            paint();
        });
    });
    if (find) {
        find.addEventListener('input', function () {
            var q = find.value.trim().toLowerCase();
            form.querySelectorAll('.assign-row').forEach(function (row) {
                var hay = row.getAttribute('data-hay') || '';
                row.classList.toggle('is-hide', q !== '' && hay.indexOf(q) === -1);
            });
        });
    }
    var randomBtn = document.getElementById('assignRandom');
    if (randomBtn) {
        randomBtn.addEventListener('click', function () {
            var groups = {};
            var keys = [];
            visibleBoxes().forEach(function (box) {
                var row = box.closest('.assign-row');
                var key = (row && row.getAttribute('data-dept')) || 'none';
                if (!groups[key]) {
                    groups[key] = [];
                    keys.push(key);
                }
                groups[key].push(box);
            });
            function shuffle(list) {
                for (var i = list.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var tmp = list[i];
                    list[i] = list[j];
                    list[j] = tmp;
                }
            }
            keys.forEach(function (key) { shuffle(groups[key]); });
            shuffle(keys);
            var chosen = [];
            var guard = 0;
            while (chosen.length < max && guard < 10000) {
                var added = false;
                keys.forEach(function (key) {
                    if (chosen.length >= max) return;
                    if (groups[key].length) {
                        chosen.push(groups[key].shift());
                        added = true;
                    }
                });
                if (!added) break;
                guard++;
            }
            boxes.forEach(function (box) { box.checked = false; });
            chosen.forEach(function (box) { box.checked = true; });
            paint();
        });
    }
    form.addEventListener('submit', function (event) {
        if (selected() < 1) {
            event.preventDefault();
            alert('Select students for this room.');
        }
    });
    paint();
})();
</script>
