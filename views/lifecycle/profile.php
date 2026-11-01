<?php require __DIR__ . '/partials/shell.php';
require_once BASE_PATH . '/models/StudentModel.php';
require_once BASE_PATH . '/models/LifecycleModel.php';
$student = $bundle['student'];
$photo = (new StudentModel())->getProfileImagePath($student);
$base = APP_URL . '/lifecycle/profile?id=' . urlencode($student['student_id']);
$options = ['certificates' => [], 'industries' => [], 'placements' => [], 'employment' => [], 'events' => ['' => 'None']];
foreach ($bundle['certificates'] as $row) { $options['certificates'][$row['id']] = $row['certificate_no']; }
foreach ($bundle['industries'] as $row) { $options['industries'][$row['id']] = $row['name']; }
foreach ($bundle['placements'] as $row) { $options['placements'][$row['id']] = ($row['industry_name'] ?: 'Placement') . ' ' . $row['status']; }
foreach ($bundle['employment'] as $row) { $options['employment'][$row['id']] = $row['organisation'] ?: ('Record ' . $row['id']); }
foreach ($bundle['events'] as $row) { $options['events'][$row['id']] = $row['title']; }
$labels = ['personal' => 'Personal', 'admission' => 'Admission', 'academic' => 'Academic', 'attendance' => 'Attendance', 'exams' => 'Exams', 'progress' => 'Competency', 'ojt' => 'OJT', 'certificates' => 'Certificates', 'employment' => 'Employment', 'education' => 'Higher education', 'alumni' => 'Alumni', 'discipline' => 'Discipline', 'clearance' => 'Clearance', 'documents' => 'Documents', 'activity' => 'Activity'];
$enroll = $bundle['enrollments'][0] ?? null;
$present = 0; $sessions = 0;
foreach ($bundle['attendance'] as $row) { $present += (int) $row['present_count']; $sessions += (int) $row['total_count']; }
$percent = $sessions > 0 ? round($present / $sessions * 100, 1) : 0;
?>
<div class="lc-block">
    <div class="lc-person">
        <?php if ($photo): ?><img src="<?php echo $h($photo); ?>" alt=""><?php else: ?><div class="lc-photo"></div><?php endif; ?>
        <div>
            <h2 class="h5 mb-1"><?php echo $h($student['student_fullname']); ?></h2>
            <div class="text-muted"><?php echo $h($student['student_id']); ?> · <span class="lc-badge"><?php echo $h($bundle['status_label']); ?></span>
                <?php if (($student['record_origin'] ?? '') === 'historical'): ?><span class="lc-badge warn">Historical</span><?php endif; ?>
            </div>
            <div class="mt-2"><a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>">Existing student profile</a></div>
        </div>
    </div>
</div>
<nav class="lc-tabs" aria-label="Student record sections">
    <?php foreach ($tabs as $item): ?>
        <a class="<?php echo $tab === $item ? 'is-on' : ''; ?>" href="<?php echo $h($base . '&tab=' . $item); ?>"><?php echo $h($labels[$item]); ?></a>
    <?php endforeach; ?>
</nav>
<div class="lc-block">
<?php if ($tab === 'personal' || $tab === 'admission'): ?>
    <h3><?php echo $tab === 'personal' ? 'Personal information' : 'Admission and registration'; ?></h3>
    <div class="row g-2 mb-3">
        <div class="col-md-4"><span class="text-muted">NIC</span><div><?php echo $h($student['student_nic']); ?></div></div>
        <div class="col-md-4"><span class="text-muted">Initials</span><div><?php echo $h($student['student_ininame']); ?></div></div>
        <div class="col-md-4"><span class="text-muted">Gender</span><div><?php echo $h($student['student_gender']); ?></div></div>
        <div class="col-md-4"><span class="text-muted">Date of birth</span><div><?php echo $h($student['student_dob']); ?></div></div>
        <div class="col-md-4"><span class="text-muted">Phone</span><div><?php echo $h($student['student_phone']); ?></div></div>
        <div class="col-md-4"><span class="text-muted">Email</span><div><?php echo $h($student['student_email']); ?></div></div>
        <div class="col-md-6"><span class="text-muted">Address</span><div><?php echo $h($student['student_address']); ?></div></div>
        <div class="col-md-6"><span class="text-muted">Emergency contact</span><div><?php echo $h(trim(($student['student_em_name'] ?? '') . ' ' . ($student['student_em_phone'] ?? '') . ' ' . ($student['student_em_relation'] ?? ''))); ?></div></div>
        <?php if ($enroll): ?>
            <div class="col-md-4"><span class="text-muted">Course</span><div><?php echo $h($enroll['course_name']); ?></div></div>
            <div class="col-md-4"><span class="text-muted">Department</span><div><?php echo $h($enroll['department_name']); ?></div></div>
            <div class="col-md-4"><span class="text-muted">NVQ</span><div><?php echo $h($enroll['course_nvq_level']); ?></div></div>
            <div class="col-md-4"><span class="text-muted">Registration date</span><div><?php echo $h($enroll['student_enroll_date']); ?></div></div>
            <div class="col-md-4"><span class="text-muted">Enrolment status</span><div><?php echo $h($enroll['student_enroll_status']); ?></div></div>
            <div class="col-md-4"><span class="text-muted">Batch / group</span><div><?php echo $h($bundle['group']['name'] ?? ''); ?></div></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($actor['office'])): ?>
    <form method="post" action="<?php echo APP_URL; ?>/lifecycle/save-profile" class="lc-fields">
        <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
        <input type="hidden" name="student_id" value="<?php echo $h($student['student_id']); ?>">
        <input type="hidden" name="tab" value="<?php echo $h($tab); ?>">
        <?php if ($tab === 'personal'): ?>
            <label><span>Parent / guardian</span><input class="form-control" name="parent_name" value="<?php echo $h($student['parent_name'] ?? ''); ?>"></label>
            <label><span>Parent phone</span><input class="form-control" name="parent_phone" value="<?php echo $h($student['parent_phone'] ?? ''); ?>"></label>
            <label><span>Previous employment</span><input class="form-control" name="previous_employment" value="<?php echo $h($student['previous_employment'] ?? ''); ?>"></label>
            <label><span>Lifecycle status</span>
                <select class="form-select" name="lifecycle_status">
                    <option value="">Keep current (<?php echo $h($bundle['status_label']); ?>)</option>
                    <?php foreach (LifecycleForms::statuses() as $status): ?>
                        <option value="<?php echo $h($status); ?>" <?php echo (($student['lifecycle_status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo $h($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Reason for discontinuation</span><input class="form-control" name="discontinuation_reason" value="<?php echo $h($student['discontinuation_reason'] ?? ''); ?>"></label>
        <?php else: ?>
            <label><span>Admission number</span><input class="form-control" name="admission_no" value="<?php echo $h($student['admission_no'] ?? ''); ?>"></label>
            <label><span>Intake</span><input class="form-control" name="intake" value="<?php echo $h($student['intake'] ?? ''); ?>"></label>
            <label><span>Trade</span><input class="form-control" name="trade" value="<?php echo $h($student['trade'] ?? ''); ?>"></label>
            <label><span>Course commencement</span><input class="form-control" type="date" name="course_commencement" value="<?php echo $h($student['course_commencement'] ?? ''); ?>"></label>
            <label><span>Expected completion</span><input class="form-control" type="date" name="expected_completion" value="<?php echo $h($student['expected_completion'] ?? ''); ?>"></label>
            <label><span>Actual completion</span><input class="form-control" type="date" name="actual_completion" value="<?php echo $h($student['actual_completion'] ?? ''); ?>"></label>
        <?php endif; ?>
        <div><button class="btn btn-primary" type="submit">Update</button></div>
    </form>
    <?php endif; ?>
<?php elseif ($tab === 'academic'): ?>
    <h3>Course modules</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Module</th><th>Semester</th></tr></thead><tbody>
        <?php if (!$bundle['modules']): ?><tr><td colspan="2">No modules are stored for the current course.</td></tr><?php endif; ?>
        <?php foreach ($bundle['modules'] as $row): ?><tr><td><?php echo $h($row['module_name']); ?></td><td><?php echo $h($row['semester']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <h3>Enrolment history</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Course</th><th>Year</th><th>From</th><th>Exit</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($bundle['enrollments'] as $row): ?><tr><td><?php echo $h($row['course_name']); ?></td><td><?php echo $h($row['academic_year']); ?></td><td><?php echo $h($row['student_enroll_date']); ?></td><td><?php echo $h($row['student_enroll_exit_date']); ?></td><td><?php echo $h($row['student_enroll_status']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <p>Class attendance on file: <?php echo $h($percent); ?>% (<?php echo (int) $present; ?> of <?php echo (int) $sessions; ?> sessions).</p>
    <?php
    $progressOpen = 0;
    foreach ($bundle['progress'] as $progressRow) {
        if (($progressRow['completion_status'] ?? '') !== 'Completed') {
            $progressOpen++;
        }
    }
    $ojtDone = false;
    foreach ($bundle['placements'] as $placementRow) {
        if (($placementRow['status'] ?? '') === 'Completed') {
            $ojtDone = true;
        }
    }
    if (!$ojtDone && $bundle['legacy_ojt']) {
        $ojtDone = true;
    }
    $finalCleared = false;
    foreach ($bundle['clearance'] as $clearRow) {
        if (($clearRow['section'] ?? '') === 'Final' && ($clearRow['status'] ?? '') === 'Cleared') {
            $finalCleared = true;
        }
    }
    $checks = [
        'Attendance is at least 80%' => $sessions > 0 && $percent >= 80,
        'Recorded module progress is completed' => $bundle['progress'] && $progressOpen === 0,
        'OJT is completed' => $ojtDone,
        'Final clearance is approved' => $finalCleared,
    ];
    ?>
    <h3>Course completion check</h3>
    <ul>
        <?php foreach ($checks as $label => $ok): ?>
            <li><span class="lc-badge <?php echo $ok ? 'ok' : 'warn'; ?>"><?php echo $ok ? 'Met' : 'Open'; ?></span> <?php echo $h($label); ?></li>
        <?php endforeach; ?>
    </ul>
<?php elseif ($tab === 'attendance'): ?>
    <h3>Class attendance by module</h3>
    <p>Device attendance days on file: <?php echo (int) $bundle['device_days']; ?>.</p>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Module</th><th>Present</th><th>Sessions</th><th>Percent</th></tr></thead><tbody>
        <?php if (!$bundle['attendance']): ?><tr><td colspan="4">No class attendance is stored for this student.</td></tr><?php endif; ?>
        <?php foreach ($bundle['attendance'] as $row): $t = (int) $row['total_count']; ?>
            <tr><td><?php echo $h($row['module_name']); ?></td><td><?php echo (int) $row['present_count']; ?></td><td><?php echo $t; ?></td><td><?php echo $t ? round($row['present_count'] / $t * 100, 1) : 0; ?>%</td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
<?php elseif ($tab === 'exams'): ?>
    <h3>Examination records</h3>
    <p class="text-muted">Marks already entered in the examination module are shown here. New exam papers stay in that module.</p>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Date</th><th>Semester</th><th>Location</th><th>Module</th><th>Marks</th><th>Final</th></tr></thead><tbody>
        <?php if (!$bundle['exams']): ?><tr><td colspan="6">No examination entry is linked to this student.</td></tr><?php endif; ?>
        <?php foreach ($bundle['exams'] as $row): ?><tr><td><?php echo $h($row['exam_date']); ?></td><td><?php echo $h($row['semester']); ?></td><td><?php echo $h($row['location']); ?></td><td><?php echo $h($row['module_id']); ?></td><td><?php echo $h($row['marks']); ?></td><td><?php echo $h($row['marks_final']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
<?php elseif ($tab === 'progress'): ?>
    <h3>Competency and module progress</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Module</th><th>Theory</th><th>Practical</th><th>Continuous</th><th>Final</th><th>Competency</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($bundle['progress'] as $row): ?><tr><td><?php echo $h($row['module_name']); ?></td><td><?php echo $h($row['theory_marks']); ?></td><td><?php echo $h($row['practical_marks']); ?></td><td><?php echo $h($row['continuous_marks']); ?></td><td><?php echo $h($row['final_marks']); ?></td><td><?php echo $h($row['competency_result']); ?></td><td><?php echo $h($row['completion_status']); ?></td><td><?php $row_kind = 'progress'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'progress'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <h3 class="mt-3">Qualifications recorded at admission</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Type</th><th>Year</th><th>Result</th></tr></thead><tbody>
        <?php if (!$bundle['qualifications']): ?><tr><td colspan="3">No prior qualification rows are stored.</td></tr><?php endif; ?>
        <?php foreach ($bundle['qualifications'] as $row): ?><tr><td><?php echo $h($row['qualification_type']); ?></td><td><?php echo $h($row['qualification_year']); ?></td><td><?php echo $h($row['qualification_results']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!empty($actor['office']) || !empty($actor['hod'])): $fields = $forms['progress']['fields']; $kind = 'progress'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; endif; ?>
<?php elseif ($tab === 'ojt'): ?>
    <h3>Placements</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Industry</th><th>Status</th><th>Start</th><th>End</th><th>Attendance</th></tr></thead><tbody>
        <?php foreach ($bundle['placements'] as $row): ?><tr><td><?php echo $h($row['industry_name']); ?></td><td><span class="lc-badge"><?php echo $h($row['status']); ?></span></td><td><?php echo $h($row['start_date']); ?></td><td><?php echo $h($row['end_date']); ?></td><td><?php echo $h($row['attendance_percent']); ?></td><td><?php $row_kind = 'ojt'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'ojt'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if ($bundle['legacy_ojt']): ?>
        <h3>Earlier OJT requests</h3>
        <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Requested place</th><th>Final place</th><th>Start</th><th>End</th></tr></thead><tbody>
            <?php foreach ($bundle['legacy_ojt'] as $row): ?><tr><td><?php echo $h($row['requested_place']); ?></td><td><?php echo $h($row['final_place']); ?></td><td><?php echo $h($row['starting date'] ?? ''); ?></td><td><?php echo $h($row['ending date'] ?? ''); ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
    <?php if ($bundle['monitoring'] || $bundle['evaluations']): ?>
        <h3>Visits and evaluations</h3>
        <ul><?php foreach ($bundle['monitoring'] as $row): ?><li><?php echo $h($row['visit_date'] . ' ' . $row['officer_name'] . ' — ' . $row['report']); ?></li><?php endforeach; ?>
        <?php foreach ($bundle['evaluations'] as $row): ?><li><?php echo $h($row['evaluated_on'] . ' ' . $row['evaluator'] . ' score ' . $row['score']); ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if (!empty($actor['office']) || !empty($actor['hod'])): ?>
        <h3>Add a placement</h3>
        <?php $fields = $forms['ojt']['fields']; $kind = 'ojt'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Add an industry</h3>
        <?php $fields = $forms['industry']['fields']; $kind = 'industry'; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Monitoring visit</h3>
        <?php $fields = $forms['monitoring']['fields']; $kind = 'monitoring'; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Employer evaluation</h3>
        <?php $fields = $forms['evaluation']['fields']; $kind = 'evaluation'; require __DIR__ . '/partials/form.php'; ?>
    <?php endif; ?>
<?php elseif ($tab === 'certificates'): ?>
    <h3>Certificates</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Number</th><th>Name printed</th><th>Course</th><th>Status</th><th>Issued</th><th>Pending reason</th><th>Scan</th></tr></thead><tbody>
        <?php if (!$bundle['certificates']): ?><tr><td colspan="7">No certificate is registered yet.</td></tr><?php endif; ?>
        <?php foreach ($bundle['certificates'] as $row): ?><tr><td><?php echo $h($row['certificate_no']); ?></td><td><?php echo $h($row['printed_name']); ?></td><td><?php echo $h($row['course_name']); ?></td><td><span class="lc-badge"><?php echo $h($row['status']); ?></span></td><td><?php echo $h($row['issuance_date']); ?></td><td><?php echo $h($row['pending_reason']); ?></td><td><?php if (!empty($row['attachment_path'])): ?><a href="<?php echo APP_URL; ?>/lifecycle/certificate-file?id=<?php echo (int) $row['id']; ?>" target="_blank" rel="noopener">View</a><?php endif; ?> <?php $row_kind = 'certificate'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'certificates'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if ($bundle['certificate_events']): ?>
        <h3>Collection and replacement</h3>
        <ul><?php foreach ($bundle['certificate_events'] as $row): ?><li><?php echo $h($row['event_date'] . ' ' . $row['event_type'] . ' ' . $row['certificate_no'] . ' ' . $row['status'] . ' ' . $row['note']); ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <p><a href="<?php echo APP_URL; ?>/verify/certificate">Public verification page</a> shows the certificate number, printed name, course and status only.</p>
    <?php if (!empty($actor['office'])): ?>
        <h3>Register a certificate</h3>
        <?php $fields = $forms['certificate']['fields']; $kind = 'certificate'; $studentId = $student['student_id']; $multipart = true; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Collection</h3>
        <?php $fields = $forms['collection']['fields']; $kind = 'collection'; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Replacement</h3>
        <?php $fields = $forms['replacement']['fields']; $kind = 'replacement'; require __DIR__ . '/partials/form.php'; ?>
    <?php endif; ?>
<?php elseif ($tab === 'employment'): ?>
    <h3>Employment history</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Organisation</th><th>Role</th><th>Type</th><th>Location</th><th>Course related</th><th>Current</th><th>Verified</th></tr></thead><tbody>
        <?php foreach ($bundle['employment'] as $row): ?><tr><td><?php echo $h($row['organisation']); ?></td><td><?php echo $h($row['designation']); ?></td><td><?php echo $h($row['employment_type']); ?></td><td><?php echo $h($row['location_scope']); ?></td><td><?php echo $h($row['course_related']); ?></td><td><?php echo $h($row['current_status']); ?></td><td><?php echo !empty($row['verified']) ? 'Yes' : 'No'; ?></td><td><?php $row_kind = 'employment'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'employment'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if ($bundle['feedback']): ?><h3>Employer feedback</h3><ul><?php foreach ($bundle['feedback'] as $row): ?><li><?php echo $h($row['employer_name'] . ' (' . $row['rating'] . ') ' . $row['feedback']); ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if (!empty($actor['office'])): $fields = $forms['employment']['fields']; $kind = 'employment'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Feedback</h3>
        <?php $fields = $forms['feedback']['fields']; $kind = 'feedback'; require __DIR__ . '/partials/form.php'; ?>
    <?php endif; ?>
<?php elseif ($tab === 'education'): ?>
    <h3>Higher education</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Institution</th><th>Programme</th><th>Qualification</th><th>Start</th></tr></thead><tbody>
        <?php foreach ($bundle['education'] as $row): ?><tr><td><?php echo $h($row['institution']); ?></td><td><?php echo $h($row['programme']); ?></td><td><?php echo $h($row['qualification']); ?></td><td><?php echo $h($row['start_date']); ?></td><td><?php $row_kind = 'education'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'education'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!empty($actor['office'])): $fields = $forms['education']['fields']; $kind = 'education'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; endif; ?>
<?php elseif ($tab === 'alumni'): ?>
    <?php $alumni = $bundle['alumni'][0] ?? null; ?>
    <h3>Alumni</h3>
    <?php if ($alumni): ?>
        <p>Alumni ID <strong><?php echo $h($alumni['alumni_code']); ?></strong> · <?php echo $h($alumni['membership']); ?> · <?php echo !empty($alumni['verified']) ? 'Verified' : 'Waiting for verification'; ?></p>
        <p><?php echo $h(trim(($alumni['contact_phone'] ?? '') . ' ' . ($alumni['contact_email'] ?? '') . ' ' . ($alumni['contact_address'] ?? ''))); ?></p>
        <?php if (!empty($actor['can_approve']) && empty($alumni['verified'])): ?>
            <form method="post" action="<?php echo APP_URL; ?>/lifecycle/save"><?php /* verify */ ?>
                <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
                <input type="hidden" name="kind" value="verify_alumni">
                <input type="hidden" name="student_id" value="<?php echo $h($student['student_id']); ?>">
                <input type="hidden" name="tab" value="alumni">
                <button class="btn btn-primary" type="submit">Verify alumni profile</button>
            </form>
        <?php endif; ?>
    <?php elseif (!empty($actor['office'])): ?>
        <form method="post" action="<?php echo APP_URL; ?>/lifecycle/save">
            <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
            <input type="hidden" name="kind" value="register_alumni">
            <input type="hidden" name="student_id" value="<?php echo $h($student['student_id']); ?>">
            <input type="hidden" name="tab" value="alumni">
            <button class="btn btn-primary" type="submit">Create alumni profile</button>
        </form>
    <?php else: ?>
        <p>No alumni profile yet. A profile is also created when the lifecycle status is set to Completed.</p>
    <?php endif; ?>
    <?php if ($bundle['engagements']): ?><h3 class="mt-3">Engagement</h3><ul><?php foreach ($bundle['engagements'] as $row): ?><li><?php echo $h($row['engaged_on'] . ' ' . $row['kind'] . ' — ' . $row['title']); ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if (!empty($actor['office'])): ?>
        <h3 class="mt-3">Engagement</h3>
        <?php $fields = $forms['engagement']['fields']; $kind = 'engagement'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Event</h3>
        <?php $fields = $forms['event']['fields']; $kind = 'event'; require __DIR__ . '/partials/form.php'; ?>
        <h3 class="mt-3">Tracer survey</h3>
        <?php $fields = $forms['tracer']['fields']; $kind = 'tracer'; require __DIR__ . '/partials/form.php'; ?>
    <?php endif; ?>
<?php elseif ($tab === 'discipline'): ?>
    <h3>Discipline</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Action</th><th>Status</th><th></th></tr></thead><tbody>
        <?php if (!$bundle['discipline']): ?><tr><td colspan="6">No discipline record is stored.</td></tr><?php endif; ?>
        <?php foreach ($bundle['discipline'] as $row): ?><tr><td><?php echo $h($row['incident_date']); ?></td><td><?php echo $h($row['category']); ?></td><td><?php echo $h($row['description']); ?></td><td><?php echo $h($row['action_taken']); ?></td><td><span class="lc-badge"><?php echo $h($row['status']); ?></span></td><td><?php $row_kind = 'discipline'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'discipline'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!empty($actor['office'])): $fields = $forms['discipline']['fields']; $kind = 'discipline'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; endif; ?>
    <h3 class="mt-3">Welfare</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
        <?php if (!$bundle['welfare']): ?><tr><td colspan="6">No welfare record is stored.</td></tr><?php endif; ?>
        <?php foreach ($bundle['welfare'] as $row): ?><tr><td><?php echo $h($row['aid_date']); ?></td><td><?php echo $h($row['aid_type']); ?></td><td><?php echo $h($row['description']); ?></td><td><?php echo $h($row['amount']); ?></td><td><span class="lc-badge"><?php echo $h($row['status']); ?></span></td><td><?php $row_kind = 'welfare'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'discipline'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!empty($actor['office'])): $fields = $forms['welfare']['fields']; $kind = 'welfare'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; endif; ?>
    <h3 class="mt-3">Correspondence</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Date</th><th>Direction</th><th>Subject</th><th>Reference</th><th></th></tr></thead><tbody>
        <?php if (!$bundle['correspondence']): ?><tr><td colspan="5">No letter is stored.</td></tr><?php endif; ?>
        <?php foreach ($bundle['correspondence'] as $row): ?><tr><td><?php echo $h($row['letter_date']); ?></td><td><?php echo $h($row['direction']); ?></td><td><?php echo $h($row['subject']); ?></td><td><?php echo $h($row['reference_no']); ?></td><td><?php $row_kind = 'correspondence'; $row_record = $row; $row_student = $student['student_id']; $row_tab = 'discipline'; require __DIR__ . '/partials/row_tools.php'; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!empty($actor['office'])): $fields = $forms['correspondence']['fields']; $kind = 'correspondence'; $studentId = $student['student_id']; require __DIR__ . '/partials/form.php'; endif; ?>
    <h3 class="mt-3">Complaint letters already on file</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Letter</th><th>Course</th><th>Name on letter</th></tr></thead><tbody>
        <?php if (!$bundle['complaints']): ?><tr><td colspan="3">No complaint letter is linked.</td></tr><?php endif; ?>
        <?php foreach ($bundle['complaints'] as $row): ?><tr><td><?php echo $h($row['complaint_letter_id']); ?></td><td><?php echo $h($row['course_name']); ?></td><td><?php echo $h($row['student_name']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <h3>Hostel</h3>
    <ul><?php foreach ($bundle['hostel'] as $row): ?><li><?php echo $h(($row['status'] ?? '') . ' from ' . ($row['allocated_at'] ?? '') . ' to ' . ($row['leaving_at'] ?? '')); ?></li><?php endforeach; ?></ul>
<?php elseif ($tab === 'clearance'): ?>
    <h3>Clearance</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Section</th><th>Status</th><th>Note</th><th>By</th><th></th></tr></thead><tbody>
        <?php foreach ($bundle['clearance'] as $row): ?>
            <tr>
                <td><?php echo $h($row['section']); ?></td>
                <td><span class="lc-badge <?php echo $row['status'] === 'Cleared' ? 'ok' : ($row['status'] === 'Held' ? 'warn' : ''); ?>"><?php echo $h($row['status']); ?></span></td>
                <td><?php echo $h($row['note']); ?></td>
                <td><?php echo $h($row['cleared_by']); ?></td>
                <td>
                    <?php if (LifecycleAccess::canClearSection($actor, $row['section']) && ($row['section'] !== 'Final' || !empty($actor['can_approve']))): ?>
                    <form method="post" action="<?php echo APP_URL; ?>/lifecycle/save" class="d-flex gap-1">
                        <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
                        <input type="hidden" name="kind" value="clearance">
                        <input type="hidden" name="student_id" value="<?php echo $h($student['student_id']); ?>">
                        <input type="hidden" name="tab" value="clearance">
                        <input type="hidden" name="section" value="<?php echo $h($row['section']); ?>">
                        <select class="form-select form-select-sm" name="status">
                            <?php foreach (['Pending', 'Cleared', 'Held'] as $status): ?><option <?php echo $row['status'] === $status ? 'selected' : ''; ?>><?php echo $h($status); ?></option><?php endforeach; ?>
                        </select>
                        <input class="form-control form-control-sm" name="note" value="<?php echo $h($row['note']); ?>" placeholder="Note">
                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
<?php elseif ($tab === 'documents'): ?>
    <h3>Documents</h3>
    <div class="table-responsive"><table class="table lc-table"><thead><tr><th>Category</th><th>Title</th><th>Reference</th><th>Version</th><th>Date</th><th></th></tr></thead><tbody>
        <?php foreach ($bundle['documents'] as $row): ?><tr><td><?php echo $h($row['category']); ?></td><td><?php echo $h($row['title']); ?></td><td><?php echo $h($row['reference_no']); ?></td><td><?php echo (int) $row['version_no']; ?></td><td><?php echo $h($row['issue_date']); ?></td><td><a href="<?php echo APP_URL; ?>/lifecycle/preview?id=<?php echo (int) $row['id']; ?>" target="_blank" rel="noopener">Preview</a> · <a href="<?php echo APP_URL; ?>/lifecycle/download?id=<?php echo (int) $row['id']; ?>">Download</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!empty($actor['office'])): ?>
    <form class="lc-fields" method="post" action="<?php echo APP_URL; ?>/lifecycle/upload" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
        <input type="hidden" name="student_id" value="<?php echo $h($student['student_id']); ?>">
        <label><span>Category</span><select class="form-select" name="category" required><?php foreach (LifecycleForms::documentCategories() as $category): ?><option><?php echo $h($category); ?></option><?php endforeach; ?></select></label>
        <label><span>Title</span><input class="form-control" name="title" required></label>
        <label><span>Reference</span><input class="form-control" name="reference_no"></label>
        <label><span>Issue date</span><input class="form-control" type="date" name="issue_date"></label>
        <label><span>File</span><input class="form-control" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label>
        <div><button class="btn btn-primary" type="submit">Upload</button></div>
    </form>
    <?php endif; ?>
<?php else: ?>
    <h3>Status history</h3>
    <ul><?php foreach ($bundle['history'] as $row): ?><li><?php echo $h($row['created_at'] . ' ' . $row['old_status'] . ' → ' . $row['new_status'] . ' ' . $row['reason'] . ' (' . $row['changed_by'] . ')'); ?></li><?php endforeach; ?></ul>
    <h3>Audit</h3>
    <ul><?php foreach ($bundle['audit'] as $row): ?><li><?php echo $h($row['created_at'] . ' ' . $row['action'] . ' ' . $row['field_name'] . ' ' . $row['old_value'] . ' → ' . $row['new_value']); ?></li><?php endforeach; ?></ul>
<?php endif; ?>
</div>
<?php require __DIR__ . '/partials/close.php'; ?>
