<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$d = $device ?? null;
$id = (int) ($d['id'] ?? 0);
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">Assign Assets</h1>
    <p class="asset-help">First choose who will receive the asset. Then pick the person. One asset cannot be given to two people unless the quantity allows it.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <form method="post" action="<?php echo APP_URL; ?>/devices/assign-save" class="card border-0 shadow-sm" id="assignForm">
        <div class="card-body p-4">
            <div class="mb-3">
                <label class="form-label">Which asset?</label>
                <select name="device_id" class="form-select form-select-lg" required onchange="if (this.value) location.href='<?php echo APP_URL; ?>/devices/assign?id=' + this.value;">
                    <option value="">Choose an asset</option>
                    <?php foreach ($devices ?? [] as $row): ?>
                    <option value="<?php echo (int) ($row['id'] ?? 0); ?>" <?php echo (int) ($row['id'] ?? 0) === $id ? 'selected' : ''; ?>><?php echo $e(($row['asset_name'] ?: $row['asset_id']) . ' — ' . $row['asset_id']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($d): ?>
            <p><strong>Asset code:</strong> <?php echo $e($d['asset_id'] ?? ''); ?> · <strong>Free quantity:</strong> <?php echo (int) ($slots ?? 0); ?></p>
            <fieldset class="mb-3">
                <legend class="form-label">Who will receive this asset?</legend>
                <label class="asset-choice"><input type="radio" name="who" value="staff" checked> Staff Member</label>
                <label class="asset-choice"><input type="radio" name="who" value="student"> Student</label>
            </fieldset>
            <div id="staffBox">
                <label class="form-label" for="staffSearch">Search staff by name or ID</label>
                <input id="staffSearch" class="form-control form-control-lg mb-2" placeholder="Type a name or staff ID">
                <select name="employee_id" id="employee_id" class="form-select form-select-lg">
                    <option value="">Choose a staff member</option>
                    <?php foreach ($staffList ?? [] as $s): ?>
                    <option value="<?php echo $e($s['staff_id'] ?? ''); ?>"><?php echo $e(($s['staff_name'] ?? '') . ' — ' . ($s['staff_id'] ?? '')); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-text" id="staffChosen">Staff name and staff ID appear in the list.</p>
            </div>
            <div id="studentBox" class="d-none">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Course</label>
                        <select id="course_id" class="form-select form-select-lg">
                            <option value="">All courses</option>
                            <?php foreach ($courses ?? [] as $c): ?>
                            <option value="<?php echo $e($c['course_id'] ?? ''); ?>"><?php echo $e($c['course_name'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Batch</label>
                        <select id="group_id" class="form-select form-select-lg">
                            <option value="">All batches</option>
                            <?php foreach ($groups ?? [] as $g): ?>
                            <option value="<?php echo (int) ($g['id'] ?? 0); ?>"><?php echo $e(($g['name'] ?? $g['group_name'] ?? 'Batch') . ' ' . ($g['academic_year'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label class="form-label mt-2" for="studentSearch">Search student by registration number or name</label>
                <input id="studentSearch" class="form-control form-control-lg mb-2" placeholder="Type a name or registration number">
                <select name="student_id" id="student_id" class="form-select form-select-lg">
                    <option value="">Choose a student</option>
                </select>
                <p class="form-text" id="studentChosen">Student name and registration number appear here.</p>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Assignment Date</label>
                    <input type="date" name="issue_date" class="form-control form-control-lg" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expected Return Date (optional)</label>
                    <input type="date" name="expected_return_date" class="form-control form-control-lg">
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <input type="hidden" name="change_person" id="change_person" value="0">
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="btn btn-primary btn-lg" type="submit" onclick="document.getElementById('change_person').value='0';">Assign Asset</button>
                <button class="btn btn-outline-primary btn-lg" type="submit" onclick="document.getElementById('change_person').value='1'; return confirm('Change the person who has this asset?');">Change Assigned Person</button>
                <a class="btn btn-link btn-lg" href="<?php echo APP_URL; ?>/devices/list">Back</a>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($activeAssignments)): ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h2 class="h5">Who has it now</h2>
            <?php foreach ($activeAssignments as $a): ?>
            <form method="post" action="<?php echo APP_URL; ?>/devices/return" class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2" onsubmit="return confirm('Mark this asset as returned?');">
                <div>
                    <strong><?php echo $e(($a['student_fullname'] ?? '') ?: ($a['staff_name'] ?? '')); ?></strong>
                    <span class="text-muted"><?php echo $e(($a['student_id'] ?? '') ?: ($a['employee_id'] ?? '')); ?></span>
                </div>
                <input type="hidden" name="device_id" value="<?php echo $id; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo (int) ($a['id'] ?? 0); ?>">
                <input type="hidden" name="return_date" value="<?php echo date('Y-m-d'); ?>">
                <button class="btn btn-warning btn-lg" type="submit">Return Asset</button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($history)): ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h2 class="h5">Assignment History</h2>
            <ul class="list-group list-group-flush">
                <?php foreach ($history as $a): ?>
                <li class="list-group-item px-0">
                    <?php echo $e(($a['student_fullname'] ?? '') ?: ($a['staff_name'] ?? 'Person')); ?>
                    · <?php echo $e($a['issue_date'] ?? ''); ?>
                    <?php echo !empty($a['is_active']) ? ' · With this person now' : ' · Returned ' . $e($a['return_date'] ?? ''); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php if ($d): ?>
<script>
(function () {
    var who = document.querySelectorAll('input[name="who"]');
    var staffBox = document.getElementById('staffBox');
    var studentBox = document.getElementById('studentBox');
    function toggle() {
        var student = document.querySelector('input[name="who"]:checked').value === 'student';
        staffBox.classList.toggle('d-none', student);
        studentBox.classList.toggle('d-none', !student);
    }
    who.forEach(function (el) { el.addEventListener('change', toggle); });
    function filterSelect(input, select) {
        input.addEventListener('input', function () {
            var q = input.value.toLowerCase();
            Array.prototype.forEach.call(select.options, function (opt, i) {
                if (i === 0) return;
                opt.hidden = q !== '' && opt.text.toLowerCase().indexOf(q) === -1;
            });
        });
    }
    filterSelect(document.getElementById('staffSearch'), document.getElementById('employee_id'));
    document.getElementById('employee_id').addEventListener('change', function () {
        document.getElementById('staffChosen').textContent = this.options[this.selectedIndex].text;
    });
    var studentSearch = document.getElementById('studentSearch');
    var studentSelect = document.getElementById('student_id');
    var timer = null;
    function findStudents() {
        var q = studentSearch.value.trim();
        if (q.length < 2) return;
        var url = '<?php echo APP_URL; ?>/devices/lookup-people?who=student&q=' + encodeURIComponent(q)
            + '&course_id=' + encodeURIComponent(document.getElementById('course_id').value)
            + '&group_id=' + encodeURIComponent(document.getElementById('group_id').value)
            + '&department_id=' + encodeURIComponent('<?php echo $e($selectedDepartment ?? ''); ?>');
        fetch(url).then(function (r) { return r.json(); }).then(function (data) {
            studentSelect.innerHTML = '<option value="">Choose a student</option>';
            (data.people || []).forEach(function (p) {
                var opt = document.createElement('option');
                opt.value = p.student_id;
                opt.textContent = p.student_fullname + ' — ' + p.student_id;
                studentSelect.appendChild(opt);
            });
        });
    }
    studentSearch.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(findStudents, 300);
    });
    studentSelect.addEventListener('change', function () {
        document.getElementById('studentChosen').textContent = this.value ? this.options[this.selectedIndex].text : 'Student name and registration number appear here.';
    });
})();
</script>
<?php endif; ?>
