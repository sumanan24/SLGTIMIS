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
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-sms me-2"></i>Bulk SMS</h4>
            <div class="text-muted small">Choose an academic year (and department), review the students and phone numbers, then send one common message.</div>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/students">
            <i class="fas fa-arrow-left me-1"></i> Students
        </a>
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

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body bg-light">
            <form method="GET" action="<?php echo APP_URL; ?>/students/bulk-sms" class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label for="academic_year" class="form-label fw-bold small">Academic Year <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="academic_year" name="academic_year" required>
                        <option value="">Select academic year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $e($year); ?>" <?php echo ($filters['academic_year'] ?? '') === $year ? 'selected' : ''; ?>>
                                <?php echo $e($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($isHOD)): ?>
                <div class="col-md-4 col-lg-3">
                    <label for="department_id" class="form-label fw-bold small">Department</label>
                    <select class="form-select form-select-sm" id="department_id" name="department_id">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $e($dept['department_id']); ?>" <?php echo $deptValue === (string) $dept['department_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-4 col-lg-3">
                    <label for="course_id" class="form-label fw-bold small">Course</label>
                    <select class="form-select form-select-sm" id="course_id" name="course_id">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $e($course['course_id']); ?>"
                                    data-department="<?php echo $e($course['department_id'] ?? ''); ?>"
                                    <?php echo ($filters['course_id'] ?? '') === (string) $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-users me-1"></i>Show Students</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="<?php echo APP_URL; ?>/students/bulk-sms" id="bulkSmsForm">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
        <input type="hidden" name="academic_year" value="<?php echo $e($filters['academic_year'] ?? ''); ?>">
        <input type="hidden" name="department_id" value="<?php echo $e($deptForForm); ?>">
        <input type="hidden" name="course_id" value="<?php echo $e($filters['course_id'] ?? ''); ?>">

        <div class="row g-3">
            <div class="col-lg-7">
                <?php if (!$yearChosen): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                    <p class="mb-1">Select an academic year to list the students.</p>
                    <p class="mb-0 small">Or just type a mobile number in the box on the right to send a single SMS.</p>
                </div>
                <?php elseif (empty($rows)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-user-slash fa-3x mb-3"></i>
                    <p class="mb-0">No current students found for this selection.</p>
                </div>
                <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="fw-bold">
                            <i class="fas fa-users me-1"></i>Students
                            <span class="badge bg-primary ms-1"><?php echo count($rows); ?></span>
                            <span class="badge bg-success ms-1" title="Students with a valid mobile number"><?php echo $withNumber; ?> with mobile</span>
                            <?php if ($withNumber < count($rows)): ?>
                                <span class="badge bg-danger ms-1" title="No valid Sri Lankan mobile number, cannot be messaged"><?php echo count($rows) - $withNumber; ?> without</span>
                            <?php endif; ?>
                        </div>
                        <input type="search" id="rowFilter" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Search name / ID / phone">
                    </div>
                    <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="smsSelectAll" checked title="Select all with a mobile number"></th>
                                    <th style="width: 50px;">No</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $i => $r): ?>
                                    <tr class="sms-row<?php echo $r['valid'] ? '' : ' table-warning'; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input sms-pick" name="student_ids[]"
                                                   value="<?php echo $e($r['student_id']); ?>"
                                                   <?php echo $r['valid'] ? 'checked' : 'disabled'; ?>>
                                        </td>
                                        <td class="text-muted"><?php echo $i + 1; ?></td>
                                        <td class="fw-semibold text-primary"><?php echo $e($r['student_id']); ?></td>
                                        <td><?php echo $e($r['name']); ?></td>
                                        <td>
                                            <?php if ($r['valid']): ?>
                                                <?php echo $e($r['phone']); ?>
                                            <?php elseif ($r['phone'] !== ''): ?>
                                                <?php echo $e($r['phone']); ?> <span class="badge bg-danger">invalid</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">no number</span>
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

            <div class="col-lg-5">
                <div class="card shadow-sm border-0" style="position: sticky; top: 80px;">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-comment-dots me-1"></i>Common message</div>
                    <div class="card-body">
                        <label for="extraNumbers" class="form-label small fw-bold mb-1">Other mobile numbers <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="extra_numbers" id="extraNumbers" class="form-control form-control-sm mb-1" rows="2"
                                  placeholder="e.g. 0771234567, 0712345678"><?php echo $e($draftNumbers ?? ''); ?></textarea>
                        <div class="small text-muted mb-3">
                            Type one or more numbers, separated by comma, space or new line. Use this alone to send a single SMS,
                            or together with the ticked students.
                            <span id="typedInfo" class="fw-bold"></span>
                        </div>
                        <label for="smsMessage" class="form-label small fw-bold mb-1">Message</label>
                        <textarea name="message" id="smsMessage" class="form-control" rows="7" maxlength="600"
                                  placeholder="Type the SMS to send..." required><?php echo $e($draft ?? ''); ?></textarea>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span id="smsCount">0 characters</span>
                            <span id="smsParts">0 SMS</span>
                        </div>
                        <div class="small text-muted mt-2">
                            Tamil or Sinhala text is sent as Unicode (70 characters per SMS, 67 when joined).
                            English text allows 160 (153 when joined).
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div><span id="pickedCount" class="fw-bold">0</span> number(s) will receive this SMS</div>
                            <button type="submit" class="btn btn-success" id="smsSendBtn" <?php echo !empty($smsMissing) ? 'disabled' : ''; ?>>
                                <i class="fas fa-paper-plane me-1"></i>Send SMS
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
