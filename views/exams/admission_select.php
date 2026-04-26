<?php
/**
 * @var array<string,mixed> $exam
 * @var list<array{student_id: string, student_fullname: string}> $students
 */
$h = static function (string $path): string {
    return htmlspecialchars(rtrim(APP_URL, '/') . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$examId = (int) ($exam['id'] ?? 0);
$courseLabel = (string) ($exam['course_name'] ?? $exam['course_id'] ?? '');
?>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-id-card text-primary me-2"></i>Admission download</h1>
            <p class="text-muted small mb-0">
                Exam #<?php echo $examId; ?>
                <?php if ($courseLabel !== ''): ?>
                    — <?php echo $e($courseLabel); ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?php echo $h('exams'); ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to exams</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <p class="mb-2 small text-muted">Tick the students to include, then download one PDF (two pages per student).</p>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label small mb-0" for="admSearch">Filter by number or name</label>
                    <input type="search" class="form-control form-control-sm" id="admSearch" placeholder="Type to filter…" autocomplete="off">
                </div>
                <div class="col-md-6 text-md-end">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="admSelectAll">Select all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="admSelectNone">Clear</button>
                    <a class="btn btn-sm btn-outline-success" target="_blank" href="<?php echo $h('print/admission-cards-bulk?exam_id=' . $examId); ?>"><i class="fas fa-users me-1"></i>Download all</a>
                </div>
            </div>

            <form method="post" action="<?php echo $h('print/admission-cards-selected'); ?>" target="_blank" id="admForm">
                <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                <div class="table-responsive border rounded">
                    <table class="table table-hover table-sm mb-0 align-middle" id="admTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 44px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="admMaster" title="Select / deselect visible" aria-label="Select all visible">
                                </th>
                                <th>Reg. no. (student number)</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $row): ?>
                                <tr class="adm-row" data-search="<?php echo $e(strtolower($row['student_id'] . ' ' . $row['student_fullname'])); ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input adm-cb" name="student_ids[]" value="<?php echo $e($row['student_id']); ?>">
                                    </td>
                                    <td class="font-monospace"><?php echo $e($row['student_id']); ?></td>
                                    <td><?php echo $e($row['student_fullname']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-success"><i class="fas fa-file-pdf me-1"></i>Download admission PDF (selected)</button>
                    <span class="small text-muted" id="admCount"></span>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var table = document.getElementById('admTable');
    if (!table) return;
    var search = document.getElementById('admSearch');
    var master = document.getElementById('admMaster');
    var rows = table.querySelectorAll('tbody tr.adm-row');
    var cbs = table.querySelectorAll('.adm-cb');
    var countEl = document.getElementById('admCount');
    var form = document.getElementById('admForm');

    function visibleRows() {
        var out = [];
        rows.forEach(function (tr) {
            if (tr.style.display !== 'none') out.push(tr);
        });
        return out;
    }
    function selectedCount() {
        var n = 0;
        cbs.forEach(function (cb) { if (cb.checked) n++; });
        return n;
    }
    function updateCount() {
        if (countEl) countEl.textContent = selectedCount() + ' selected';
    }
    function filter() {
        var q = (search && search.value || '').toLowerCase().trim();
        rows.forEach(function (tr) {
            var hay = tr.getAttribute('data-search') || '';
            tr.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
        });
        if (master) master.checked = false;
        updateCount();
    }
    if (search) search.addEventListener('input', filter);
    document.getElementById('admSelectAll').addEventListener('click', function () {
        visibleRows().forEach(function (tr) {
            var cb = tr.querySelector('.adm-cb');
            if (cb) cb.checked = true;
        });
        updateCount();
    });
    document.getElementById('admSelectNone').addEventListener('click', function () {
        cbs.forEach(function (cb) { cb.checked = false; });
        if (master) master.checked = false;
        updateCount();
    });
    if (master) {
        master.addEventListener('change', function () {
            visibleRows().forEach(function (tr) {
                var cb = tr.querySelector('.adm-cb');
                if (cb) cb.checked = master.checked;
            });
            updateCount();
        });
    }
    cbs.forEach(function (cb) { cb.addEventListener('change', updateCount); });
    updateCount();
    if (form) {
        form.addEventListener('submit', function (e) {
            if (selectedCount() < 1) {
                e.preventDefault();
                alert('Please select at least one student.');
            }
        });
    }
})();
</script>
