<?php
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$filters = $filters ?? [];
$rows = $rows ?? [];
$withNumber = (int) ($withNumber ?? 0);
$yearChosen = ($filters['academic_year'] ?? '') !== '';
$deptValue = (string) ($filters['department_id'] ?? '');
$deptForForm = ($deptValue === '__none__') ? '' : $deptValue;
$withoutNumber = max(0, count($rows) - $withNumber);
?>
<style>
.stu-page { --stu-navy:#0b2545; --stu-ink:#1e293b; --stu-muted:#64748b; --stu-line:#dbe4f0; --stu-soft:#f5f8fd; --stu-accent:#1d4ed8; color:var(--stu-ink); }
.stu-page .stu-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
.stu-page .stu-title { margin:0; font-size:1.35rem; font-weight:700; color:var(--stu-navy); letter-spacing:-.02em; }
.stu-page .stu-sub { margin:.2rem 0 0; color:var(--stu-muted); font-size:.86rem; max-width:40rem; }
.stu-page .stu-actions { display:flex; flex-wrap:wrap; gap:.45rem; align-items:center; }
.stu-page .stu-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:.45rem; font-size:.82rem; font-weight:600; padding:.42rem .8rem; border:1px solid transparent; text-decoration:none; line-height:1.2; cursor:pointer; background:transparent; }
.stu-page .stu-btn-primary { background:var(--stu-navy); color:#fff; }
.stu-page .stu-btn-primary:hover { background:#163a66; color:#fff; }
.stu-page .stu-btn-ghost { background:#fff; color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-ghost:hover { background:var(--stu-soft); color:var(--stu-navy); }
.stu-page .stu-btn-success { background:#15803d; color:#fff; border-color:#15803d; }
.stu-page .stu-btn-success:hover { background:#166534; color:#fff; }
.stu-page .stu-btn-success:disabled { opacity:.65; cursor:not-allowed; }
.stu-page .stu-panel { background:#fff; border:1px solid var(--stu-line); border-radius:.7rem; margin-bottom:1rem; overflow:hidden; }
.stu-page .stu-panel-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem; padding:.75rem 1rem; background:var(--stu-soft); border-bottom:1px solid var(--stu-line); }
.stu-page .stu-panel-head h2 { margin:0; font-size:.92rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-panel-note { margin:0; font-size:.76rem; color:var(--stu-muted); }
.stu-page .stu-panel-body { padding:1rem 1.05rem; }
.stu-page .stu-label { display:block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--stu-muted); margin-bottom:.28rem; }
.stu-page .stu-label .req { color:#dc2626; text-transform:none; letter-spacing:0; margin-left:.15rem; }
.stu-page .stu-field .form-control,
.stu-page .stu-field .form-select { font-size:.86rem; border-color:#cfd9e6; border-radius:.45rem; min-height:2.2rem; }
.stu-page .stu-field .form-control:focus,
.stu-page .stu-field .form-select:focus { border-color:#93b4e0; box-shadow:0 0 0 .2rem rgba(29,78,216,.12); }
.stu-page .stu-filter-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:.85rem 1rem; align-items:end; }
.stu-page .stu-span-3 { grid-column:span 3; }
.stu-page .stu-span-4 { grid-column:span 4; }
.stu-page .stu-span-auto { grid-column:span 2; }
.stu-page .stu-layout { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(280px,.9fr); gap:1rem; align-items:start; }
.stu-page .stu-stats { display:flex; flex-wrap:wrap; gap:.4rem; }
.stu-page .stu-chip { display:inline-flex; align-items:center; gap:.3rem; padding:.22rem .55rem; border-radius:.35rem; font-size:.72rem; font-weight:700; background:#e2e8f0; color:#334155; }
.stu-page .stu-chip.ok { background:#dcfce7; color:#166534; }
.stu-page .stu-chip.bad { background:#fee2e2; color:#991b1b; }
.stu-page .stu-chip.info { background:#dbeafe; color:#1e40af; }
.stu-page .stu-search { position:relative; min-width:180px; max-width:240px; flex:1; }
.stu-page .stu-search i { position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.8rem; pointer-events:none; }
.stu-page .stu-search .form-control { padding-left:2rem; font-size:.82rem; min-height:2rem; }
.stu-page .stu-table-wrap { max-height:560px; overflow:auto; }
.stu-page table.stu-table { width:100%; border-collapse:collapse; font-size:.84rem; margin:0; }
.stu-page table.stu-table th { background:var(--stu-navy); color:#fff; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.55rem .7rem; white-space:nowrap; position:sticky; top:0; z-index:1; border:0; }
.stu-page table.stu-table td { padding:.5rem .7rem; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.stu-page table.stu-table tbody tr:hover td { background:#f7faff; }
.stu-page table.stu-table tbody tr.sms-warn td { background:#fffbeb; }
.stu-page table.stu-table tbody tr.sms-warn:hover td { background:#fef3c7; }
.stu-page .stu-id { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.78rem; font-weight:700; color:var(--stu-accent); }
.stu-page .stu-tag { display:inline-flex; align-items:center; padding:.15rem .45rem; border-radius:.3rem; font-size:.7rem; font-weight:700; }
.stu-page .stu-tag.bad { background:#fee2e2; color:#991b1b; }
.stu-page .stu-tag.muted { background:#e2e8f0; color:#475569; }
.stu-page .stu-empty { text-align:center; padding:2.6rem 1rem; color:var(--stu-muted); }
.stu-page .stu-empty i { font-size:2rem; margin-bottom:.65rem; opacity:.5; display:block; }
.stu-page .stu-composer { position:sticky; top:80px; }
.stu-page .stu-meta-row { display:flex; justify-content:space-between; gap:.75rem; font-size:.76rem; color:var(--stu-muted); margin-top:.35rem; }
.stu-page .stu-send-bar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; margin-top:1rem; padding-top:.9rem; border-top:1px solid #eef2f7; }
.stu-page .stu-send-count { font-size:.86rem; color:var(--stu-ink); }
.stu-page .stu-send-count strong { color:var(--stu-navy); }
.stu-page .stu-hint { margin:0; color:var(--stu-muted); font-size:.76rem; }
@media (max-width:991.98px) {
    .stu-page .stu-layout { grid-template-columns:1fr; }
    .stu-page .stu-composer { position:static; }
    .stu-page .stu-span-3,
    .stu-page .stu-span-4,
    .stu-page .stu-span-auto { grid-column:span 12; }
}
@media (max-width:575.98px) {
    .stu-page .stu-title { font-size:1.15rem; }
}
</style>

<div class="container-fluid px-4 py-3 stu-page">
    <div class="stu-head">
        <div>
            <h1 class="stu-title">Bulk SMS</h1>
            <p class="stu-sub">Filter students by year and course, tick recipients, then send one shared message. You can also type numbers directly.</p>
        </div>
        <div class="stu-actions">
            <a class="stu-btn stu-btn-ghost" href="<?php echo APP_URL; ?>/students">
                <i class="fas fa-arrow-left"></i>Back to students
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($smsMissing)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>The SMS gateway is not configured on the server
            (missing <?php echo $e(implode(', ', $smsMissing)); ?>). Messages cannot be sent until it is set up.
        </div>
    <?php endif; ?>

    <?php if (!empty($result)): ?>
        <?php
        $accepted = (int) ($result['accepted'] ?? 0);
        $failed = (int) ($result['failed'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);
        $cls = ($accepted > 0 && $failed === 0) ? 'success' : ($accepted > 0 ? 'warning' : 'danger');
        ?>
        <div class="alert alert-<?php echo $cls; ?> alert-dismissible fade show">
            <div class="fw-bold mb-1"><i class="fas fa-paper-plane me-2"></i>SMS send result</div>
            <ul class="mb-0 small">
                <li><strong><?php echo $accepted; ?></strong> number(s) accepted by the SMS gateway.</li>
                <?php if ($failed > 0): ?>
                    <li><strong><?php echo $failed; ?></strong> number(s) failed
                        <?php if (!empty($result['codes'])): ?>
                            (gateway code
                            <?php echo $e(implode(', ', array_map(static function ($c, $n) { return $c . ' x' . $n; }, array_keys($result['codes']), $result['codes']))); ?>)
                        <?php elseif (!empty($result['error'])): ?>
                            (<?php echo $e($result['error']); ?>)
                        <?php endif; ?>.
                    </li>
                <?php endif; ?>
                <?php if ($skipped > 0): ?>
                    <li><strong><?php echo $skipped; ?></strong> ticked student(s) skipped: no valid mobile number.</li>
                <?php endif; ?>
                <li class="text-muted">"Accepted" means the gateway took the message. It does not confirm handset delivery.</li>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="stu-panel">
        <div class="stu-panel-head">
            <h2><i class="fas fa-sliders-h me-2"></i>Select recipients</h2>
            <p class="stu-panel-note">Academic year is required to load the student list</p>
        </div>
        <div class="stu-panel-body">
            <form method="GET" action="<?php echo APP_URL; ?>/students/bulk-sms" class="stu-filter-grid">
                <div class="stu-field stu-span-3">
                    <label for="academic_year" class="stu-label">Academic year <span class="req">*</span></label>
                    <select class="form-select" id="academic_year" name="academic_year" required>
                        <option value="">Select year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $e($year); ?>" <?php echo ($filters['academic_year'] ?? '') === $year ? 'selected' : ''; ?>>
                                <?php echo $e($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($isHOD)): ?>
                <div class="stu-field stu-span-3">
                    <label for="department_id" class="stu-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">All departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $e($dept['department_id']); ?>" <?php echo $deptValue === (string) $dept['department_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="stu-field stu-span-4">
                    <label for="course_id" class="stu-label">Course</label>
                    <select class="form-select" id="course_id" name="course_id">
                        <option value="">All courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $e($course['course_id']); ?>"
                                    data-department="<?php echo $e($course['department_id'] ?? ''); ?>"
                                    <?php echo ($filters['course_id'] ?? '') === (string) $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="stu-field stu-span-auto">
                    <button type="submit" class="stu-btn stu-btn-primary w-100 justify-content-center">
                        <i class="fas fa-users"></i>Show students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="<?php echo APP_URL; ?>/students/bulk-sms" id="bulkSmsForm">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
        <input type="hidden" name="academic_year" value="<?php echo $e($filters['academic_year'] ?? ''); ?>">
        <input type="hidden" name="department_id" value="<?php echo $e($deptForForm); ?>">
        <input type="hidden" name="course_id" value="<?php echo $e($filters['course_id'] ?? ''); ?>">

        <div class="stu-layout">
            <div>
                <?php if (!$yearChosen): ?>
                <div class="stu-panel">
                    <div class="stu-empty">
                        <i class="fas fa-calendar-alt"></i>
                        <p class="mb-1 fw-semibold" style="color:var(--stu-navy);">Select an academic year</p>
                        <p class="stu-hint mb-0">Load students above, or type a mobile number in the message panel to send a single SMS.</p>
                    </div>
                </div>
                <?php elseif (empty($rows)): ?>
                <div class="stu-panel">
                    <div class="stu-empty">
                        <i class="fas fa-user-slash"></i>
                        <p class="mb-0">No current students found for this selection.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="stu-panel" style="margin-bottom:0;">
                    <div class="stu-panel-head">
                        <div>
                            <h2 class="mb-1"><i class="fas fa-users me-2"></i>Students</h2>
                            <div class="stu-stats">
                                <span class="stu-chip info"><?php echo count($rows); ?> total</span>
                                <span class="stu-chip ok"><?php echo $withNumber; ?> with mobile</span>
                                <?php if ($withoutNumber > 0): ?>
                                <span class="stu-chip bad"><?php echo $withoutNumber; ?> without</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="stu-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" id="rowFilter" class="form-control" placeholder="Search name / ID / phone">
                        </div>
                    </div>
                    <div class="stu-table-wrap">
                        <table class="stu-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" class="form-check-input" id="smsSelectAll" checked title="Select all with a mobile number"></th>
                                    <th style="width:48px;">No</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $i => $r): ?>
                                    <tr class="sms-row<?php echo $r['valid'] ? '' : ' sms-warn'; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input sms-pick" name="student_ids[]"
                                                   value="<?php echo $e($r['student_id']); ?>"
                                                   <?php echo $r['valid'] ? 'checked' : 'disabled'; ?>>
                                        </td>
                                        <td class="text-muted"><?php echo $i + 1; ?></td>
                                        <td><span class="stu-id"><?php echo $e($r['student_id']); ?></span></td>
                                        <td><?php echo $e($r['name']); ?></td>
                                        <td>
                                            <?php if ($r['valid']): ?>
                                                <?php echo $e($r['phone']); ?>
                                            <?php elseif ($r['phone'] !== ''): ?>
                                                <?php echo $e($r['phone']); ?> <span class="stu-tag bad">invalid</span>
                                            <?php else: ?>
                                                <span class="stu-tag muted">no number</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="stu-panel stu-composer" style="margin-bottom:0;">
                    <div class="stu-panel-head">
                        <h2><i class="fas fa-comment-dots me-2"></i>Message</h2>
                        <p class="stu-panel-note">One message to all selected numbers</p>
                    </div>
                    <div class="stu-panel-body">
                        <div class="stu-field mb-3">
                            <label for="extraNumbers" class="stu-label">Other mobile numbers</label>
                            <textarea name="extra_numbers" id="extraNumbers" class="form-control" rows="2"
                                      placeholder="e.g. 0771234567, 0712345678"><?php echo $e($draftNumbers ?? ''); ?></textarea>
                            <p class="stu-hint mt-1 mb-0">
                                Optional. Separate with comma, space, or new line.
                                <span id="typedInfo" class="fw-bold"></span>
                            </p>
                        </div>
                        <div class="stu-field">
                            <label for="smsMessage" class="stu-label">Message <span class="req">*</span></label>
                            <textarea name="message" id="smsMessage" class="form-control" rows="7" maxlength="600"
                                      placeholder="Type the SMS to send..." required><?php echo $e($draft ?? ''); ?></textarea>
                            <div class="stu-meta-row">
                                <span id="smsCount">0 characters</span>
                                <span id="smsParts">0 SMS</span>
                            </div>
                            <p class="stu-hint mt-2 mb-0">
                                Tamil or Sinhala uses Unicode (70 / 67 chars). English allows 160 / 153 when joined.
                            </p>
                        </div>
                        <div class="stu-send-bar">
                            <div class="stu-send-count"><strong id="pickedCount">0</strong> number(s) will receive this SMS</div>
                            <button type="submit" class="stu-btn stu-btn-success" id="smsSendBtn" <?php echo !empty($smsMissing) ? 'disabled' : ''; ?>>
                                <i class="fas fa-paper-plane"></i>Send SMS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dept = document.getElementById('department_id');
    var course = document.getElementById('course_id');
    if (dept && course) {
        var applyDept = function() {
            Array.prototype.forEach.call(course.options, function(opt) {
                var d = opt.getAttribute('data-department');
                opt.hidden = !!(opt.value && dept.value && d !== dept.value);
            });
            if (course.selectedOptions[0] && course.selectedOptions[0].hidden) { course.value = ''; }
        };
        dept.addEventListener('change', applyDept);
        applyDept();
    }

    var form = document.getElementById('bulkSmsForm');
    if (!form) { return; }
    var picks = Array.prototype.slice.call(document.querySelectorAll('.sms-pick'));
    var selectAll = document.getElementById('smsSelectAll');
    var msg = document.getElementById('smsMessage');
    var pickedCount = document.getElementById('pickedCount');
    var count = document.getElementById('smsCount');
    var parts = document.getElementById('smsParts');

    var extra = document.getElementById('extraNumbers');
    var typedInfo = document.getElementById('typedInfo');

    function picked() { return picks.filter(function(p) { return p.checked; }).length; }
    function typed() {
        return extra.value.split(/[\s,;]+/).filter(function(x) { return x !== ''; }).length;
    }

    function refresh() {
        var t = typed();
        pickedCount.textContent = picked() + t;
        typedInfo.textContent = t > 0 ? ' (' + t + ' typed number' + (t === 1 ? '' : 's') + ')' : '';
        var text = msg.value;
        var len = Array.from(text).length;
        var unicode = /[^\x00-\x7F]/.test(text);
        var single = unicode ? 70 : 160, multi = unicode ? 67 : 153;
        var n = len === 0 ? 0 : (len <= single ? 1 : Math.ceil(len / multi));
        count.textContent = len + ' characters' + (unicode ? ' (Unicode)' : '');
        parts.textContent = n + ' SMS per number';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            picks.forEach(function(p) {
                var row = p.closest('tr');
                if (!p.disabled && row.style.display !== 'none') { p.checked = selectAll.checked; }
            });
            refresh();
        });
    }
    picks.forEach(function(p) { p.addEventListener('change', refresh); });
    msg.addEventListener('input', refresh);
    extra.addEventListener('input', refresh);

    var filter = document.getElementById('rowFilter');
    if (filter) {
        filter.addEventListener('input', function() {
            var q = filter.value.trim().toLowerCase();
            document.querySelectorAll('.sms-row').forEach(function(tr) {
                tr.style.display = (q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    form.addEventListener('submit', function(ev) {
        var n = picked() + typed();
        if (n === 0) {
            ev.preventDefault();
            alert('Tick at least one student, or type a mobile number.');
            return;
        }
        if (msg.value.trim() === '') {
            ev.preventDefault();
            alert('Write the SMS message first.');
            return;
        }
        if (!confirm('Send this SMS to ' + n + ' number(s)?\n\nThis cannot be undone.')) {
            ev.preventDefault();
            return;
        }
        var btn = document.getElementById('smsSendBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
    });

    refresh();
});
</script>
