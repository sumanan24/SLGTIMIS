<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$c = $complaint ?? [];
$id = (int) ($c['id'] ?? 0);
$f = $filters ?? [];
$canManage = !empty($canManage);
$selected = array_flip($selectedStudentIds ?? []);
$linkedStudents = $linkedStudents ?? [];
$isEdit = $id > 0;
$selectedStudentsJson = array_values(array_map(static function ($s) {
    return [
        'student_id' => (string) ($s['student_id'] ?? ''),
        'student_fullname' => (string) ($s['student_name'] ?? $s['student_id'] ?? ''),
    ];
}, $linkedStudents));
?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><?php echo $e($formTitle ?? 'Complaint Letter'); ?></h1>
            <p class="text-muted small mb-0"><?php echo $isEdit ? 'Update complaint letter details.' : 'Select students and compose the complaint letter.'; ?></p>
        </div>
        <a href="<?php echo APP_URL; ?>/complaint-letters" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="post" action="<?php echo $e($formAction ?? ''); ?>" id="complaint-letter-form">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Student Selection</strong></div>
                    <div class="card-body">
                        <?php if (empty($isHodScoped)): ?>
                        <div class="mb-2">
                            <label class="form-label small">Department</label>
                            <select class="form-select form-select-sm" id="cl-dept" name="department_id" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments ?? [] as $d): ?>
                                <option value="<?php echo $e($d['department_id'] ?? ''); ?>" <?php echo ($f['department_id'] ?? $c['department_id'] ?? '') === ($d['department_id'] ?? '') ? 'selected' : ''; ?>><?php echo $e($d['department_name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="mb-2">
                            <label class="form-label small">Department</label>
                            <input type="text" class="form-control form-control-sm" readonly value="<?php echo $e(($departments[0]['department_name'] ?? $hodDepartmentId) ?? ''); ?>">
                            <input type="hidden" id="cl-dept" name="department_id" value="<?php echo $e($hodDepartmentId ?? $c['department_id'] ?? ''); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label small">Course</label>
                            <select class="form-select form-select-sm" id="cl-course" name="course_id" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses ?? [] as $co): ?>
                                <option value="<?php echo $e($co['course_id'] ?? ''); ?>" <?php echo ($f['course_id'] ?? $c['course_id'] ?? '') === ($co['course_id'] ?? '') ? 'selected' : ''; ?>><?php echo $e($co['course_name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Academic Year</label>
                            <select class="form-select form-select-sm" id="cl-year" name="academic_year" required>
                                <option value="">Select year</option>
                                <?php foreach ($academicYears ?? [] as $y): ?>
                                <option value="<?php echo $e($y); ?>" <?php echo ($f['academic_year'] ?? $c['academic_year'] ?? '') === $y ? 'selected' : ''; ?>><?php echo $e($y); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Search students</label>
                            <input type="text" class="form-control form-control-sm" id="cl-student-search" placeholder="Name, ID, NIC…">
                        </div>
                        <div id="cl-student-list" class="border rounded p-2" style="max-height:320px;overflow:auto;">
                            <?php if ($isEdit && !empty($linkedStudents)): ?>
                                <?php foreach ($linkedStudents as $ls):
                                    $sid = (string) ($ls['student_id'] ?? '');
                                    if ($sid === '') { continue; }
                                ?>
                                <label class="d-flex align-items-start gap-2 mb-2 small">
                                    <input type="checkbox" class="form-check-input mt-1 cl-student-cb" name="student_ids[]" value="<?php echo $e($sid); ?>" checked>
                                    <span><strong><?php echo $e($ls['student_name'] ?? $sid); ?></strong><br><span class="text-muted"><?php echo $e($sid); ?></span></span>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <p class="text-muted small mb-0">Choose department, course, and academic year to load students.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header py-2"><strong>Letter Details</strong></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Letter Date</label>
                                <input type="date" name="letter_date" class="form-control form-control-sm" required value="<?php echo $e($c['letter_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">Subject</label>
                                <input type="text" name="subject" class="form-control form-control-sm" required value="<?php echo $e($c['subject'] ?? 'Complaint regarding student conduct'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="draft" <?php echo ($c['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="final" <?php echo ($c['status'] ?? '') === 'final' ? 'selected' : ''; ?>>Final</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Complaint Details</label>
                                <textarea name="complaint_body" class="form-control" rows="8" required><?php echo $e($c['complaint_body'] ?? "We wish to bring to your attention a matter concerning your ward's conduct at the institute.\n\n[Describe the incident, dates, and impact.]\n\nWe request your cooperation in addressing this matter."); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Action Required</label>
                                <textarea name="action_required" class="form-control form-control-sm" rows="3"><?php echo $e($c['action_required'] ?? 'Kindly discuss this matter with your ward and ensure improved conduct at the institute.'); ?></textarea>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Save Complaint Letter</button>
                            <a href="<?php echo APP_URL; ?>/complaint-letters" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script type="application/json" id="cl-form-config"><?php echo json_encode([
    'baseUrl' => rtrim(APP_URL, '/'),
    'selectedStudentIds' => array_keys($selected),
    'selectedStudents' => $selectedStudentsJson,
    'selectedCourseId' => (string) ($c['course_id'] ?? $f['course_id'] ?? ''),
    'selectedAcademicYear' => (string) ($c['academic_year'] ?? $f['academic_year'] ?? ''),
    'isEdit' => $isEdit,
    'hodScoped' => !empty($isHodScoped),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script src="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/js/complaint-letters.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
