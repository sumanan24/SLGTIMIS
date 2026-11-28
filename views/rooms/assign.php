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
.assign-page { max-width: 860px; }
.assign-list { max-height: 28rem; overflow: auto; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; }
.assign-row { display: flex; gap: .75rem; align-items: flex-start; padding: .55rem .75rem; border-bottom: 1px solid #f1f5f9; }
.assign-row:last-child { border-bottom: 0; }
.assign-row.is-hide { display: none; }
.assign-row .name { font-weight: 650; }
.assign-tools { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
</style>
<div class="assign-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h5 mb-1">Assign students</h1>
            <div class="text-muted small"><?php echo $e($where); ?> · <?php echo (int) $slots; ?> free <?php echo $slots === 1 ? 'bed' : 'beds'; ?></div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($back); ?>"><i class="fas fa-arrow-left me-1"></i>Rooms</a>
    </div>

    <?php if ($slots < 1): ?>
        <div class="alert alert-warning">This room already has 8 students.</div>
    <?php else: ?>
        <p class="small text-muted">Level 04 and Level 05 stay in separate rooms. One room takes <?php echo (int) $slots; ?> students. Students who already have a room are not listed. Northern Province students are not listed. Pick <?php echo (int) $slots; ?> at random chooses different departments.</p>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
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
            <form method="GET" action="<?php echo APP_URL; ?>/rooms/assign" class="d-flex align-items-center gap-2">
                <input type="hidden" name="room_id" value="<?php echo $e($roomId); ?>">
                <input type="hidden" name="nvq" value="<?php echo $e($nvq); ?>">
                <label class="small text-muted mb-0" for="academic_year">Academic year</label>
                <select name="academic_year" id="academic_year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
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
            <div class="assign-tools mb-2">
                <input type="search" id="assignFind" class="form-control form-control-sm" style="max-width: 16rem;" placeholder="Search name or ID">
                <button type="button" class="btn btn-outline-dark btn-sm" id="assignRandom">Pick <?php echo (int) $slots; ?> from different departments</button>
                <span class="small text-muted" id="assignCount">0 selected</span>
            </div>
            <?php if (!$students): ?>
                <div class="alert alert-info">No unassigned <?php echo $e($levelLabel); ?> students<?php echo $academicYear !== '' ? ' in ' . $e($academicYear) : ''; ?> for this hostel.</div>
            <?php else: ?>
                <div class="assign-list mb-3">
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
                            <input class="form-check-input mt-1 assign-box" type="checkbox" name="student_ids[]" value="<?php echo $e($sid); ?>">
                            <span>
                                <span class="name d-block"><?php echo $e($name !== '' ? $name : $sid); ?></span>
                                <span class="small text-muted"><?php echo $e($sid); ?><?php if ($dept !== ''): ?> · <?php echo $e($dept); ?><?php endif; ?><?php if (!empty($stu['academic_year'])): ?> · <?php echo $e($stu['academic_year']); ?><?php endif; ?><?php if (!empty($stu['course_name'])): ?> · <?php echo $e($stu['course_name']); ?><?php endif; ?></span>
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
