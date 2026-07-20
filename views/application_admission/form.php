<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$isEdit = !empty($schedule);
$s = $schedule ?? [];
$type = $scheduleType ?? ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
$isInterview = !empty($isInterviewSchedule);
$typeLabel = $isInterview ? 'Interview schedule' : 'Entrance exam schedule';
$requireCourse = !empty($requireCourse);
$coursesByLevel = $coursesByLevel ?? ['04' => [], '05' => []];
$departmentsByLevel = $departmentsByLevel ?? ['04' => [], '05' => []];
$selectedCourseId = trim((string) ($s['course_id'] ?? ''));
$selectedLevel = (string) ($s['application_level'] ?? '');
$selectedDepartmentId = trim((string) ($selectedDepartmentId ?? ''));
$pathwayMax = (int) ($pathwayDefaultMaxApplicants ?? ApplicationAdmissionScheduleModel::INTERVIEW_ONLY_DEFAULT_MAX_APPLICANTS);
$selectedPathway = $selectedPathway ?? ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW;
$pathExam = ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW;
$pathInterviewOnly = ApplicationAdmissionScheduleModel::PATHWAY_INTERVIEW_ONLY;
?>
<div class="container-fluid px-3 px-md-4" style="max-width:720px;margin:0 auto;">
    <h1 class="h4 mb-3"><?php echo $isEdit ? 'Edit' : 'Create'; ?> <?php echo $e($typeLabel); ?></h1>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!$isInterview): ?>
    <div class="alert alert-info py-2 small mb-3">
        <strong>Exam and interview pathway.</strong> Candidates sit this entrance exam first. Mark results as <strong>Selected</strong> or <strong>Not selected</strong>. Only selected candidates may be added to a later <strong>interview</strong> schedule set to “Exam and interview” for the same course.
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo APP_URL . '/' . ltrim($formAction ?? 'application-admission/store', '/'); ?>" class="card border-0 shadow-sm" id="admission-schedule-form">
        <div class="card-body p-4">
            <input type="hidden" name="schedule_type" value="<?php echo $e($type); ?>">
            <?php if (!$isInterview): ?>
            <input type="hidden" name="admission_pathway" value="<?php echo $e($pathExam); ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">NVQ level <span class="text-danger">*</span></label>
                <select name="application_level" id="application_level" class="form-select" required>
                    <option value="">Select…</option>
                    <option value="04" <?php echo $selectedLevel === '04' ? 'selected' : ''; ?>>Level 04</option>
                    <option value="05" <?php echo $selectedLevel === '05' ? 'selected' : ''; ?>>Level 05</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="filter_department_id">Department <span class="text-danger">*</span></label>
                <select id="filter_department_id" class="form-select" required disabled>
                    <option value="">— Select NVQ level first —</option>
                </select>
                <div class="form-text">Choose department, then select the course.</div>
            </div>
            <div class="mb-3" id="course-field-wrap">
                <label class="form-label" for="course_id">Course <span class="text-danger">*</span></label>
                <select name="course_id" id="course_id" class="form-select" disabled required>
                    <option value="">— Select department first —</option>
                </select>
                <div class="form-text">Only approved applicants whose <strong>1st course preference</strong> matches appear on this schedule.</div>
            </div>

            <?php if ($isInterview): ?>
            <div class="mb-3" id="pathway-field-wrap">
                <label class="form-label d-block">Admission pathway <span class="text-danger">*</span></label>
                <div id="pathway-count-banner" class="alert alert-light border small py-2 mb-2 d-none"></div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="admission_pathway" id="pathway_exam" value="<?php echo $e($pathExam); ?>"
                        <?php echo $selectedPathway === $pathExam ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="pathway_exam">
                        <strong>Exam and interview</strong>
                        <span class="d-block small text-muted">Students face the entrance exam first; only <em>Selected</em> candidates can be scheduled for this interview.</span>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="admission_pathway" id="pathway_interview_only" value="<?php echo $e($pathInterviewOnly); ?>"
                        <?php echo $selectedPathway === $pathInterviewOnly ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="pathway_interview_only">
                        <strong>Interview only</strong>
                        <span class="d-block small text-muted">Skip the entrance exam; approved applicants for this course can be interviewed directly.</span>
                    </label>
                </div>
                <div class="form-text mt-2">Default suggestion uses approved application count: more than <?php echo (int) $pathwayMax; ?> → exam and interview; <?php echo (int) $pathwayMax; ?> or fewer → interview only. You can change this.</div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="schedule_title" class="form-control" required maxlength="200"
                       value="<?php echo $e($s['title'] ?? ''); ?>" placeholder="e.g. 2026 — Motor Mechanic entrance exam">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="schedule_date" class="form-control" required value="<?php echo $e($s['schedule_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Start time</label>
                    <input type="time" name="start_time" class="form-control" value="<?php echo $e(substr((string) ($s['start_time'] ?? ''), 0, 5)); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End time</label>
                    <input type="time" name="end_time" class="form-control" value="<?php echo $e(substr((string) ($s['end_time'] ?? ''), 0, 5)); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Venue <span class="text-danger">*</span></label>
                <input type="text" name="venue" class="form-control" required maxlength="255" value="<?php echo $e($s['venue'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Instructions (shown on PDF / public page)</label>
                <textarea name="instructions" class="form-control" rows="4"><?php echo $e($s['instructions'] ?? ''); ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo APP_URL; ?>/application-admission" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script>
(function () {
    var coursesByLevel = <?php echo json_encode($coursesByLevel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var departmentsByLevel = <?php echo json_encode($departmentsByLevel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var selectedCourseId = <?php echo json_encode($selectedCourseId); ?>;
    var selectedDepartmentId = <?php echo json_encode($selectedDepartmentId); ?>;
    var isInterview = <?php echo $isInterview ? 'true' : 'false'; ?>;
    var pathwayMax = <?php echo (int) $pathwayMax; ?>;
    var pathExam = <?php echo json_encode($pathExam); ?>;
    var pathInterviewOnly = <?php echo json_encode($pathInterviewOnly); ?>;
    var userPickedPathway = <?php echo $isEdit ? 'true' : 'false'; ?>;
    var levelEl = document.getElementById('application_level');
    var deptEl = document.getElementById('filter_department_id');
    var courseEl = document.getElementById('course_id');
    var titleEl = document.getElementById('schedule_title');
    var pathwayBanner = document.getElementById('pathway-count-banner');
    var pathwayExamEl = document.getElementById('pathway_exam');
    var pathwayInterviewEl = document.getElementById('pathway_interview_only');

    function defaultPathwayForCount(count) {
        return count > pathwayMax ? pathExam : pathInterviewOnly;
    }

    function updatePathwayFromCourse() {
        if (!isInterview || !courseEl.value) {
            if (pathwayBanner) {
                pathwayBanner.classList.add('d-none');
            }
            return;
        }
        var level = levelEl.value;
        var list = coursesByLevel[level] || [];
        var course = list.find(function (c) { return String(c.course_id) === String(courseEl.value); });
        var count = course && course.approved_application_count != null ? parseInt(course.approved_application_count, 10) : 0;
        if (isNaN(count)) {
            count = 0;
        }
        if (pathwayBanner) {
            pathwayBanner.classList.remove('d-none');
            var suggested = defaultPathwayForCount(count);
            var suggestedLabel = suggested === pathExam ? 'Exam and interview' : 'Interview only';
            pathwayBanner.innerHTML = '<strong>' + count + '</strong> approved application(s) for this course at this level. Suggested pathway: <strong>' + suggestedLabel + '</strong>.';
        }
        if (!userPickedPathway && pathwayExamEl && pathwayInterviewEl) {
            var pick = defaultPathwayForCount(count);
            pathwayExamEl.checked = pick === pathExam;
            pathwayInterviewEl.checked = pick === pathInterviewOnly;
        }
    }

    if (pathwayExamEl) {
        pathwayExamEl.addEventListener('change', function () { if (pathwayExamEl.checked) { userPickedPathway = true; } });
    }
    if (pathwayInterviewEl) {
        pathwayInterviewEl.addEventListener('change', function () { if (pathwayInterviewEl.checked) { userPickedPathway = true; } });
    }

    function rebuildDepartments() {
        var level = levelEl.value;
        deptEl.innerHTML = '';
        deptEl.disabled = true;
        courseEl.innerHTML = '';
        courseEl.disabled = true;
        var coursePh = document.createElement('option');
        coursePh.value = '';
        coursePh.textContent = '— Select department first —';
        courseEl.appendChild(coursePh);

        if (!level) {
            var o0 = document.createElement('option');
            o0.value = '';
            o0.textContent = '— Select NVQ level first —';
            deptEl.appendChild(o0);
            return;
        }

        var ph = document.createElement('option');
        ph.value = '';
        ph.textContent = 'Select department…';
        deptEl.appendChild(ph);

        var depts = departmentsByLevel[level] || [];
        depts.forEach(function (d) {
            var opt = document.createElement('option');
            opt.value = d.department_id;
            opt.textContent = d.department_name || d.department_id;
            deptEl.appendChild(opt);
        });
        deptEl.disabled = depts.length === 0;
        if (depts.length === 0) {
            ph.textContent = 'No departments with active courses at this level';
        }
        if (selectedDepartmentId) {
            deptEl.value = selectedDepartmentId;
        }
        rebuildCourses();
    }

    function rebuildCourses() {
        var level = levelEl.value;
        var deptId = deptEl.value;
        var prev = courseEl.value;
        courseEl.innerHTML = '';

        if (!level) {
            var o0 = document.createElement('option');
            o0.value = '';
            o0.textContent = '— Select department first —';
            courseEl.appendChild(o0);
            courseEl.disabled = true;
            return;
        }

        if (!deptId) {
            var o1 = document.createElement('option');
            o1.value = '';
            o1.textContent = 'Select department first…';
            courseEl.appendChild(o1);
            courseEl.disabled = true;
            return;
        }

        courseEl.disabled = false;
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select course…';
        courseEl.appendChild(placeholder);

        var list = coursesByLevel[level] || [];
        list = list.filter(function (c) {
            return String(c.department_id) === String(deptId);
        });
        list.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.course_id;
            var cnt = c.approved_application_count != null ? c.approved_application_count : 0;
            opt.textContent = (c.course_name || c.course_id) + ' (' + cnt + ' approved)';
            courseEl.appendChild(opt);
        });
        if (list.length === 0) {
            placeholder.textContent = 'No active courses in this department';
            courseEl.disabled = true;
        }
        var pick = selectedCourseId || prev;
        if (pick) {
            courseEl.value = pick;
        }
        updatePathwayFromCourse();
    }

    levelEl.addEventListener('change', function () {
        selectedCourseId = '';
        selectedDepartmentId = '';
        userPickedPathway = false;
        rebuildDepartments();
    });
    deptEl.addEventListener('change', function () {
        selectedCourseId = '';
        userPickedPathway = false;
        rebuildCourses();
    });
    courseEl.addEventListener('change', function () {
        userPickedPathway = false;
        updatePathwayFromCourse();
        if (!titleEl.value.trim() && courseEl.selectedIndex > 0) {
            var label = courseEl.options[courseEl.selectedIndex].textContent.replace(/\s*\(\d+ approved\)\s*$/, '');
            titleEl.placeholder = label + (isInterview ? ' — interview' : ' — entrance examination');
        }
    });

    rebuildDepartments();
})();
</script>
