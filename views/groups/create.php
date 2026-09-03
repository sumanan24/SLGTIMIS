<?php
$onlyDept = (is_array($departments) && count($departments) === 1) ? $departments[0] : null;
$onlyDeptId = $onlyDept['department_id'] ?? '';
?>
<style>
.group-create-wrap .card { border: 1px solid rgba(0,0,0,.06); }
.group-create-wrap .help-mini { font-size: .825rem; color: #64748b; }
.group-create-wrap .req { color: #dc3545; }
@media (max-width: 768px) {
  .group-create-wrap.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
  .group-create-wrap .d-flex.gap-2 { flex-direction: column; }
  .group-create-wrap .d-flex.gap-2 .btn { width: 100%; justify-content: center; }
}
</style>

<div class="container-fluid px-4 py-3 group-create-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold"><i class="fas fa-users text-primary me-2"></i>Create Group</h1>
      <div class="text-muted small">Create a student group by course and academic year.</div>
    </div>
    <a href="<?php echo APP_URL; ?>/groups" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
  </div>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i>
      <div><?php echo htmlspecialchars($error); ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-xl-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="POST" action="<?php echo APP_URL; ?>/groups/create" id="groupCreateForm">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">Group Name <span class="req">*</span></label>
                <input type="text" class="form-control" id="name" name="name" maxlength="255" required placeholder="e.g., Batch 2026 - Group A">
                <div class="help-mini mt-1">Use a clear naming convention (batch + group).</div>
              </div>
              <div class="col-md-6">
                <label for="academic_year" class="form-label fw-semibold">Academic Year <span class="req">*</span></label>
                <select class="form-select" id="academic_year" name="academic_year" required>
                  <option value="">Select Academic Year</option>
                  <?php foreach ($academicYears as $year): ?>
                    <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label for="department_id" class="form-label fw-semibold">Department <span class="req">*</span></label>
                <select class="form-select" id="department_id" name="department_id" required <?php echo $onlyDeptId ? 'disabled' : ''; ?>>
                  <option value="">Select Department</option>
                  <?php foreach ($departments as $dept): ?>
                    <?php $did = (string) ($dept['department_id'] ?? ''); ?>
                    <option value="<?php echo htmlspecialchars($did); ?>" <?php echo ($onlyDeptId && $did === (string) $onlyDeptId) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) ($dept['department_name'] ?? $did)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($onlyDeptId): ?>
                  <input type="hidden" name="department_id" value="<?php echo htmlspecialchars((string) $onlyDeptId); ?>">
                  <div class="help-mini mt-1">You are restricted to your department.</div>
                <?php else: ?>
                  <div class="help-mini mt-1">Select department to load courses.</div>
                <?php endif; ?>
              </div>

              <div class="col-md-6">
                <label for="course_id" class="form-label fw-semibold">Course <span class="req">*</span></label>
                <select class="form-select" id="course_id" name="course_id" required>
                  <option value="">Select Course</option>
                </select>
                <div class="help-mini mt-1" id="courseHelp">Select a department first.</div>
              </div>

              <div class="col-md-6">
                <label for="course_version" class="form-label fw-semibold">Course version <span class="req">*</span></label>
                <select class="form-select" id="course_version" name="course_version" required disabled>
                  <option value="">Select course first</option>
                </select>
                <div class="help-mini mt-1" id="versionHelp">Loads versions for the selected course.</div>
              </div>

              <div class="col-md-4">
                <label for="status" class="form-label fw-semibold">Status <span class="req">*</span></label>
                <select class="form-select" id="status" name="status" required>
                  <option value="active" selected>Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>

              <div class="col-md-8 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                  <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-save me-1"></i>Create Group</button>
                  <a href="<?php echo APP_URL; ?>/groups" class="btn btn-outline-secondary">Cancel</a>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    const versionSelect = document.getElementById('course_version');
    const courseHelp = document.getElementById('courseHelp');
    const versionHelp = document.getElementById('versionHelp');
    const versionsByCourse = {};

    function versionLabel(v) {
        v = parseInt(v, 10) || 0;
        return v === 0 ? 'Default (0)' : 'Version ' + v;
    }

    function fillVersions(courseId, preferred) {
        if (!versionSelect) return;
        versionSelect.innerHTML = '';
        versionSelect.disabled = true;
        if (!courseId) {
            versionSelect.options.add(new Option('Select course first', '', true, true));
            if (versionHelp) versionHelp.textContent = 'Select a course first.';
            return;
        }
        const versions = versionsByCourse[courseId] && versionsByCourse[courseId].length
            ? versionsByCourse[courseId]
            : [0];
        versionSelect.options.add(new Option('Select version', '', true, true));
        versions.forEach(function(v) {
            versionSelect.options.add(new Option(versionLabel(v), String(v), false, false));
        });
        versionSelect.disabled = false;
        const latest = versions[versions.length - 1];
        if (preferred !== undefined && preferred !== null && preferred !== '' && versions.indexOf(parseInt(preferred, 10)) >= 0) {
            versionSelect.value = String(preferred);
        } else if (latest !== undefined) {
            versionSelect.value = String(latest);
        }
        if (versionHelp) versionHelp.textContent = 'Modules for this batch use this version.';
    }

    function setLoading(on) {
        if (!courseHelp) return;
        courseHelp.textContent = on ? 'Loading courses…' : '';
    }

    function loadCourses(departmentId) {
        courseSelect.innerHTML = '<option value="">Select Course</option>';
        Object.keys(versionsByCourse).forEach(function(k) { delete versionsByCourse[k]; });
        fillVersions('');
        if (!departmentId) {
            if (courseHelp) courseHelp.textContent = 'Select a department first.';
            return;
        }
        setLoading(true);
        fetch('<?php echo APP_URL; ?>/groups/get-courses-by-department?department_id=' + encodeURIComponent(departmentId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (!data || !data.success) {
                    if (courseHelp) courseHelp.textContent = (data && data.error) ? data.error : 'Could not load courses.';
                    return;
                }
                const courses = Array.isArray(data.courses) ? data.courses : [];
                courses.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.course_id;
                    option.textContent = course.course_name + ' (' + course.course_id + ')';
                    courseSelect.appendChild(option);
                    versionsByCourse[course.course_id] = Array.isArray(course.versions) && course.versions.length
                        ? course.versions
                        : [0];
                });
                if (courseHelp) courseHelp.textContent = courses.length ? 'Select the course for this group.' : 'No courses found for this department.';
            })
            .catch(error => {
                console.error('Error fetching courses:', error);
                if (courseHelp) courseHelp.textContent = 'Could not load courses.';
            });
    }

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            loadCourses(this.value);
        });
    }

    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            fillVersions(this.value);
        });
    }

    // Auto-load courses if department is preselected (e.g., HOD/IN restricted)
    const initialDept = departmentSelect ? departmentSelect.value : '';
    if (initialDept) {
        loadCourses(initialDept);
    }
});
</script>

