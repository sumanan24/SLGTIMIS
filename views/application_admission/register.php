<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$app = $application ?? [];
$entry = $entry ?? [];
$student = $student ?? null;
$enrollment = $enrollment ?? null;
$account = $account ?? null;
$group = $group ?? null;
$documents = is_array($documents ?? null) ? $documents : [];
$options = $options ?? [];
$groups = is_array($groups ?? null) ? $groups : [];
$summary = $summary ?? null;
$formErrors = is_array($formErrors ?? null) ? $formErrors : [];
$old = is_array($old ?? null) ? $old : [];
$scheduleId = (int) ($sch['schedule_id'] ?? 0);
$entryId = (int) ($entry['entry_id'] ?? 0);
$suggested = $options['suggested_course'] ?? null;
$selectedYear = (string) ($old['academic_year'] ?? $options['default_academic_year'] ?? '');
$selectedCourse = (string) ($old['course_id'] ?? ($suggested['course_id'] ?? ''));
$selectedMode = (string) ($old['course_mode'] ?? 'Full');
$selectedGroup = (int) ($old['group_id'] ?? ($group['id'] ?? 0));
$alreadyEnrolled = !empty($enrollment);
unset($_SESSION['admission_registration_summary'], $_SESSION['admission_registration_errors'], $_SESSION['admission_registration_old']);
?>
<style>
.aa-reg-page { max-width: 1100px; }
.aa-reg-card { border: 1px solid #dee2e6; border-radius: 0.5rem; background: #fff; }
.aa-reg-card h2 { font-size: 1.05rem; font-weight: 600; margin: 0; }
.aa-reg-meta dt { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; }
.aa-reg-meta dd { margin-bottom: 0.65rem; }
.aa-doc-ok { color: #0f5132; }
.aa-doc-miss { color: #6c757d; }
</style>

<div class="container-fluid px-3 px-md-4 py-3 aa-reg-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Confirm admission registration</h1>
            <p class="text-muted small mb-0"><?php echo $e($sch['title'] ?? ''); ?></p>
        </div>
        <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo $scheduleId; ?>" class="btn btn-sm btn-outline-secondary">Back to selection</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if ($formErrors !== []): ?>
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                <?php foreach ($formErrors as $err): ?>
                    <li><?php echo $e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (is_array($summary)): ?>
        <div class="aa-reg-card p-3 p-md-4 mb-3 border-success">
            <h2 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Registration summary</h2>
            <dl class="row aa-reg-meta mb-0">
                <dt class="col-sm-4">Student ID</dt>
                <dd class="col-sm-8"><strong><?php echo $e($summary['student_id'] ?? ''); ?></strong>
                    <?php if (!empty($summary['student_created'])): ?><span class="badge bg-info text-dark ms-1">New record</span><?php else: ?><span class="badge bg-secondary ms-1">Existing record</span><?php endif; ?>
                </dd>
                <dt class="col-sm-4">Name</dt>
                <dd class="col-sm-8"><?php echo $e($summary['student_name'] ?? ''); ?></dd>
                <dt class="col-sm-4">Course</dt>
                <dd class="col-sm-8"><?php echo $e($summary['course_name'] ?? ''); ?> (<?php echo $e($summary['course_id'] ?? ''); ?>)</dd>
                <dt class="col-sm-4">Academic year</dt>
                <dd class="col-sm-8"><?php echo $e($summary['academic_year'] ?? ''); ?></dd>
                <dt class="col-sm-4">Enrollment / shift</dt>
                <dd class="col-sm-8"><?php echo $e($summary['course_mode_label'] ?? ''); ?></dd>
                <dt class="col-sm-4">Group / batch</dt>
                <dd class="col-sm-8"><?php echo $e($summary['group_name'] ?? ''); ?></dd>
                <dt class="col-sm-4">Login username</dt>
                <dd class="col-sm-8"><?php echo $e($summary['username'] ?? ''); ?>
                    <?php if (!empty($summary['account_created'])): ?>
                        <span class="badge bg-success ms-1">Account created</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-1">Existing account linked</span>
                    <?php endif; ?>
                </dd>
                <dt class="col-sm-4">Password</dt>
                <dd class="col-sm-8"><?php echo $e($summary['default_password_note'] ?? ''); ?></dd>
                <dt class="col-sm-4">Documents</dt>
                <dd class="col-sm-8">
                    <?php echo (int) ($summary['document_count'] ?? 0); ?> file(s) linked.
                    <?php if (!empty($summary['documents_pdf'])): ?>Combined PDF stored.<?php endif; ?>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-dark" href="<?php echo APP_URL; ?>/application-admission/documents-pdf?schedule_id=<?php echo $scheduleId; ?>&amp;entry_id=<?php echo $entryId; ?>">
                            <i class="fas fa-file-pdf me-1"></i> View student documents PDF
                        </a>
                    </div>
                </dd>
            </dl>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="aa-reg-card p-3 p-md-4 h-100">
                <h2 class="mb-3">Applicant</h2>
                <dl class="row aa-reg-meta mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?php echo $e($app['student_full_name'] ?? ''); ?></dd>
                    <dt class="col-sm-4">NIC</dt>
                    <dd class="col-sm-8"><?php echo $e($app['student_nic'] ?? ''); ?></dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?php echo $e($app['student_email'] ?? '—'); ?></dd>
                    <dt class="col-sm-4">Preferred course</dt>
                    <dd class="col-sm-8"><?php echo $e($app['course_priority_1'] ?? '—'); ?></dd>
                    <dt class="col-sm-4">Student record</dt>
                    <dd class="col-sm-8">
                        <?php if ($student): ?>
                            Existing ID <strong><?php echo $e($student['student_id']); ?></strong> — data will be preserved.
                        <?php else: ?>
                            No student record yet. One will be created with the next registration number.
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Current enrollment</dt>
                    <dd class="col-sm-8">
                        <?php if ($enrollment): ?>
                            <?php echo $e($enrollment['course_id'] ?? ''); ?> / <?php echo $e($enrollment['academic_year'] ?? ''); ?> / <?php echo $e($enrollment['course_mode'] ?? ''); ?>
                        <?php else: ?>
                            None
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Login</dt>
                    <dd class="col-sm-8"><?php echo $account ? $e($account['user_name'] ?? '') : 'Will be created (username = NIC)'; ?></dd>
                </dl>

                <h2 class="mt-4 mb-2">Admission documents</h2>
                <ul class="list-unstyled small mb-3">
                    <?php foreach ($documents as $doc): ?>
                        <li class="<?php echo !empty($doc['available']) ? 'aa-doc-ok' : 'aa-doc-miss'; ?>">
                            <i class="fas <?php echo !empty($doc['available']) ? 'fa-check' : 'fa-minus'; ?> me-1"></i>
                            <?php echo $e($doc['label'] ?? ''); ?>
                            <?php if (empty($doc['available'])): ?><span class="text-muted">(not uploaded)</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-sm btn-outline-dark" href="<?php echo APP_URL; ?>/application-admission/documents-pdf?schedule_id=<?php echo $scheduleId; ?>&amp;entry_id=<?php echo $entryId; ?>">
                    <i class="fas fa-file-pdf me-1"></i> View student documents PDF
                </a>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="aa-reg-card p-3 p-md-4">
                <h2 class="mb-3">Academic year, enrollment and shift</h2>
                <?php if ($alreadyEnrolled && !is_array($summary)): ?>
                    <div class="alert alert-warning py-2 small">This student already has an enrollment for the suggested course and year. Choose a different year or course, or open the existing student record.</div>
                <?php endif; ?>
                <form method="post" action="<?php echo APP_URL; ?>/application-admission/register-save" id="aa-register-form">
                    <input type="hidden" name="schedule_id" value="<?php echo $scheduleId; ?>">
                    <input type="hidden" name="entry_id" value="<?php echo $entryId; ?>">

                    <div class="mb-3">
                        <label class="form-label" for="academic_year">Academic year <span class="text-danger">*</span></label>
                        <select class="form-select" name="academic_year" id="academic_year" required>
                            <option value="">Select academic year</option>
                            <?php foreach (($options['academic_years'] ?? []) as $year): ?>
                                <?php $y = (string) ($year['academic_year'] ?? ''); ?>
                                <option value="<?php echo $e($y); ?>" <?php echo $y === $selectedYear ? 'selected' : ''; ?>>
                                    <?php echo $e($y); ?><?php echo (($year['academic_year_status'] ?? '') === 'Active') ? ' (Active)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="course_id">Course / programme <span class="text-danger">*</span></label>
                        <select class="form-select" name="course_id" id="course_id" required>
                            <option value="">Select course</option>
                            <?php foreach (($options['courses'] ?? []) as $c): ?>
                                <?php $cid = (string) ($c['course_id'] ?? ''); ?>
                                <option value="<?php echo $e($cid); ?>" <?php echo $cid === $selectedCourse ? 'selected' : ''; ?>>
                                    <?php echo $e(($c['course_name'] ?? $cid) . ' (' . $cid . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="course_mode">Shift / course mode <span class="text-danger">*</span></label>
                        <select class="form-select" name="course_mode" id="course_mode" required>
                            <?php foreach (($options['course_modes'] ?? []) as $modeVal => $modeLabel): ?>
                                <option value="<?php echo $e((string) $modeVal); ?>" <?php echo (string) $modeVal === $selectedMode ? 'selected' : ''; ?>>
                                    <?php echo $e($modeLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">The MIS stores shift as Full Time or Part Time on the enrollment record. There is no separate morning/afternoon/evening table.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="group_id">Student group / batch</label>
                        <select class="form-select" name="group_id" id="group_id">
                            <option value="0"><?php echo $groups === [] ? 'No matching groups for this course and year' : 'Select group'; ?></option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?php echo (int) $g['id']; ?>" <?php echo (int) $g['id'] === $selectedGroup ? 'selected' : ''; ?>>
                                    <?php echo $e($g['name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="border rounded p-3 bg-light mb-3" id="aa-confirm-box">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="confirm_registration" name="confirm_registration" required>
                            <label class="form-check-label" for="confirm_registration">
                                I confirm the academic year, course, shift and group above. The existing student record will be used when one already exists. The login username will be the NIC. At first login the student must change the default password and complete personal and parent details. The account will not be created if these details are invalid.
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" <?php echo is_array($summary) ? 'disabled' : ''; ?>>
                        <i class="fas fa-user-check me-1"></i> Confirm and register
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var yearEl = document.getElementById('academic_year');
    var courseEl = document.getElementById('course_id');
    var groupEl = document.getElementById('group_id');
    if (!yearEl || !courseEl || !groupEl) return;
    var url = <?php echo json_encode(APP_URL . '/application-admission/register-groups'); ?>;
    function loadGroups() {
        var year = yearEl.value;
        var course = courseEl.value;
        groupEl.innerHTML = '<option value="0">Loading…</option>';
        if (!year || !course) {
            groupEl.innerHTML = '<option value="0">Select course and academic year</option>';
            return;
        }
        fetch(url + '?course_id=' + encodeURIComponent(course) + '&academic_year=' + encodeURIComponent(year), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var groups = (data && data.groups) ? data.groups : [];
                groupEl.innerHTML = '';
                var first = document.createElement('option');
                first.value = '0';
                first.textContent = groups.length ? 'Select group' : 'No matching groups for this course and year';
                groupEl.appendChild(first);
                groups.forEach(function (g) {
                    var opt = document.createElement('option');
                    opt.value = g.id;
                    opt.textContent = g.name;
                    groupEl.appendChild(opt);
                });
            })
            .catch(function () {
                groupEl.innerHTML = '<option value="0">Could not load groups</option>';
            });
    }
    yearEl.addEventListener('change', loadGroups);
    courseEl.addEventListener('change', loadGroups);
})();
</script>
