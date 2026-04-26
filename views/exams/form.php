<?php
/** @var list<array<string,mixed>> $courses */
/** @var list<string>|null $errors */
/** @var array|null $old */
$old = $old ?? [];
$errors = $errors ?? [];
$formAction = $formAction ?? 'exams/store';
$heading = $heading ?? 'Create exam';
$headingIcon = $headingIcon ?? 'fa-plus-circle';
$lead = $lead ?? 'Choose course and semester to load modules. Set date, time, and venue for each module you include. Then pick a batch and tick the students to register.';
$hiddenExamId = $hiddenExamId ?? null;
$submitLabel = $submitLabel ?? 'Save exam & assign students';
$o = static function (string $k, string $d = '') use ($old): string {
    return htmlspecialchars((string) ($old[$k] ?? $d), ENT_QUOTES, 'UTF-8');
};
$oldMod = $old['mod'] ?? [];
if (!is_array($oldMod)) {
    $oldMod = [];
}
$oldStudentIds = $old['student_ids'] ?? [];
if (!is_array($oldStudentIds)) {
    $oldStudentIds = [];
}
$base = rtrim(APP_URL, '/');
?>
<div class="container-fluid py-3">
    <div class="mb-4">
        <h1 class="h3 mb-0"><i class="fas <?php echo htmlspecialchars($headingIcon, ENT_QUOTES, 'UTF-8'); ?> text-primary me-2"></i><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-muted small mb-0"><?php echo htmlspecialchars($lead, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($base . '/' . ltrim((string) $formAction, '/'), ENT_QUOTES, 'UTF-8'); ?>" id="examForm" class="needs-validation" novalidate>
                <?php if ($hiddenExamId !== null && $hiddenExamId !== ''): ?>
                    <input type="hidden" name="exam_id" value="<?php echo (int) $hiddenExamId; ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Course <span class="text-danger">*</span></label>
                        <select name="course_id" id="course_id" class="form-select" required>
                            <option value="">Choose course…</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo htmlspecialchars((string)($c['course_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['course_id'] ?? '') === ($c['course_id'] ?? '')) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)($c['course_name'] ?? '') . ' (' . ($c['course_id'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" id="semester" class="form-select" required>
                            <option value="">Choose semester…</option>
                            <?php for ($s = 1; $s <= 12; $s++): ?>
                                <option value="<?php echo $s; ?>" <?php echo (string)($old['semester'] ?? '') === (string) $s ? 'selected' : ''; ?>>
                                    Semester <?php echo $s; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <div class="form-text">Only modules tagged with this semester in <em>Modules</em> are listed.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Batch (group) <span class="text-danger">*</span></label>
                        <select name="group_id" id="group_id" class="form-select" required disabled>
                            <option value="">Choose course first…</option>
                        </select>
                        <div class="form-text">Only active groups for the selected course are listed.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Modules <span class="text-danger">*</span></label>
                        <div id="modulesBox" class="border rounded p-3 bg-light text-muted small">Select a course and semester to load modules.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Students (from batch)</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnStudentsAll">Select all</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnStudentsNone">Clear all</button>
                        </div>
                        <div id="studentsBox" class="border rounded p-3" style="max-height:280px;overflow:auto;">
                            <span class="text-muted small">Select a batch to load students.</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                    <a href="<?php echo htmlspecialchars($base . '/exams', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
  var base = <?php echo json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  var courseSel = document.getElementById('course_id');
  var semesterSel = document.getElementById('semester');
  var groupSel = document.getElementById('group_id');
  var modulesBox = document.getElementById('modulesBox');
  var studentsBox = document.getElementById('studentsBox');
  var oldMod = <?php echo json_encode($oldMod, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  var oldStudentIds = <?php echo json_encode(array_values($oldStudentIds), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function escAttr(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;');
  }

  function renderModuleRows(modules, mergeMod) {
    mergeMod = mergeMod || {};
    if (!modules || !modules.length) {
      modulesBox.innerHTML = '<span class="text-warning">No modules for this course and semester. Add modules with this semester in <strong>Modules</strong>.</span>';
      return;
    }
    var html = '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0 bg-white">';
    html += '<thead class="table-light"><tr><th style="width:2.5rem" title="Include">✓</th><th>Module</th><th style="min-width:9rem">Date</th><th style="min-width:8rem">Time</th><th>Venue</th></tr></thead><tbody>';
    modules.forEach(function (m, idx) {
      var mid = String(m.module_id || '');
      var name = String(m.module_name || mid);
      var om = mergeMod[mid] || {};
      var inc = om.include;
      var checked = (inc === undefined || inc === '1' || inc === 1 || inc === true) ? ' checked' : '';
      var dv = om.date != null ? escAttr(om.date) : '';
      var tv = om.time != null ? escAttr(om.time) : '';
      var lv = om.location != null ? escAttr(om.location) : '';
      var nameField = 'mod[' + escAttr(mid) + ']';
      html += '<tr>'
        + '<td class="text-center"><input class="form-check-input" type="checkbox" name="' + nameField + '[include]" value="1" id="inc_' + idx + '"' + checked + '></td>'
        + '<td><label class="mb-0" for="inc_' + idx + '">' + esc(name) + ' <span class="text-muted">(' + esc(mid) + ')</span></label></td>'
        + '<td><input type="date" class="form-control form-control-sm" name="' + nameField + '[date]" value="' + dv + '"></td>'
        + '<td><input type="text" class="form-control form-control-sm" name="' + nameField + '[time]" placeholder="e.g. 9:00 AM" value="' + tv + '"></td>'
        + '<td><input type="text" class="form-control form-control-sm" name="' + nameField + '[location]" placeholder="Venue" value="' + lv + '"></td>'
        + '</tr>';
    });
    html += '</tbody></table></div>';
    modulesBox.innerHTML = html;
  }

  function loadModules(courseId, semester, mergeMod) {
    if (!courseId || !semester) {
      modulesBox.innerHTML = '<span class="text-muted">Select a course and semester to load modules.</span>';
      return;
    }
    modulesBox.innerHTML = '<span class="text-muted">Loading…</span>';
    fetch(base + '/exams/ajax/modules?course_id=' + encodeURIComponent(courseId) + '&semester=' + encodeURIComponent(semester))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.success) {
          modulesBox.innerHTML = '<span class="text-danger">' + esc(j.message || 'Could not load modules.') + '</span>';
          return;
        }
        renderModuleRows(j.modules, mergeMod);
      })
      .catch(function () { modulesBox.innerHTML = '<span class="text-danger">Could not load modules.</span>'; });
  }

  function loadGroups(courseId, thenSelectId, done) {
    groupSel.innerHTML = '<option value="">Loading…</option>';
    groupSel.disabled = true;
    fetch(base + '/exams/ajax/groups?course_id=' + encodeURIComponent(courseId))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        groupSel.innerHTML = '<option value="">Choose batch…</option>';
        if (!j.success || !j.groups || !j.groups.length) {
          groupSel.innerHTML += '<option value="" disabled>No groups for this course</option>';
          groupSel.disabled = false;
          if (done) done();
          return;
        }
        j.groups.forEach(function (g) {
          var opt = document.createElement('option');
          opt.value = g.id;
          opt.textContent = (g.name || 'Group') + ' — ' + (g.academic_year || '') + ' (id ' + g.id + ')';
          groupSel.appendChild(opt);
        });
        groupSel.disabled = false;
        if (thenSelectId) {
          groupSel.value = String(thenSelectId);
        }
        if (done) done();
      })
      .catch(function () {
        groupSel.innerHTML = '<option value="">Error loading groups</option>';
        groupSel.disabled = true;
        if (done) done();
      });
  }

  function loadStudents(groupId, precheckIds) {
    studentsBox.innerHTML = '<span class="text-muted">Loading…</span>';
    fetch(base + '/exams/ajax/students?group_id=' + encodeURIComponent(groupId))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.success || !j.students || !j.students.length) {
          studentsBox.innerHTML = '<span class="text-warning">No active students in this batch.</span>';
          return;
        }
        var preset = {};
        if (precheckIds && precheckIds.length) {
          precheckIds.forEach(function (id) { preset[String(id)] = true; });
        }
        var html = '<table class="table table-sm mb-0"><thead><tr><th style="width:2.5rem"></th><th>ID</th><th>Name</th></tr></thead><tbody>';
        j.students.forEach(function (s, idx) {
          var sid = String(s.student_id || '');
          var chk = true;
          if (precheckIds && precheckIds.length) {
            chk = !!preset[sid];
          }
          var cid = 'st_' + idx;
          html += '<tr><td class="text-center">'
            + '<input class="form-check-input student-cb" type="checkbox" name="student_ids[]" value="' + escAttr(sid) + '" id="' + cid + '"' + (chk ? ' checked' : '') + '>'
            + '</td><td><label class="mb-0" for="' + cid + '">' + esc(sid) + '</label></td><td>' + esc(String(s.student_fullname || '')) + '</td></tr>';
        });
        html += '</tbody></table>';
        studentsBox.innerHTML = html;
      })
      .catch(function () { studentsBox.innerHTML = '<span class="text-danger">Could not load students.</span>'; });
  }

  courseSel.addEventListener('change', function () {
    var cid = courseSel.value;
    var sem = semesterSel.value;
    studentsBox.innerHTML = '<span class="text-muted">Select a batch to load students.</span>';
    if (!cid) {
      modulesBox.innerHTML = '<span class="text-muted">Select a course and semester to load modules.</span>';
      groupSel.innerHTML = '<option value="">Choose course first…</option>';
      groupSel.disabled = true;
      return;
    }
    loadGroups(cid, null, function () {
      if (sem) {
        loadModules(cid, sem, {});
      } else {
        modulesBox.innerHTML = '<span class="text-muted">Select a semester to load modules.</span>';
      }
    });
  });

  semesterSel.addEventListener('change', function () {
    var cid = courseSel.value;
    var sem = semesterSel.value;
    if (!cid) {
      modulesBox.innerHTML = '<span class="text-muted">Select a course and semester to load modules.</span>';
      return;
    }
    if (!sem) {
      modulesBox.innerHTML = '<span class="text-muted">Select a semester to load modules.</span>';
      return;
    }
    loadModules(cid, sem, {});
  });

  groupSel.addEventListener('change', function () {
    var gid = groupSel.value;
    if (!gid) {
      studentsBox.innerHTML = '<span class="text-muted">Select a batch to load students.</span>';
      return;
    }
    loadStudents(gid, null);
  });

  document.getElementById('btnStudentsAll').addEventListener('click', function () {
    studentsBox.querySelectorAll('.student-cb').forEach(function (cb) { cb.checked = true; });
  });
  document.getElementById('btnStudentsNone').addEventListener('click', function () {
    studentsBox.querySelectorAll('.student-cb').forEach(function (cb) { cb.checked = false; });
  });

  document.addEventListener('DOMContentLoaded', function () {
    var cid = courseSel.value;
    var sem = semesterSel.value;
    var gid = <?php echo json_encode((string)($old['group_id'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (cid && sem) {
      loadGroups(cid, gid, function () {
        loadModules(cid, sem, oldMod);
        if (gid) {
          loadStudents(gid, oldStudentIds.length ? oldStudentIds : null);
        }
      });
    } else if (cid) {
      loadGroups(cid, gid, function () {
        if (sem) {
          loadModules(cid, sem, oldMod);
        }
        if (gid) {
          loadStudents(gid, oldStudentIds.length ? oldStudentIds : null);
        }
      });
    }
  });
})();
</script>
