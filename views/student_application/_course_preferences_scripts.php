<?php
/**
 * Expects: window.APP_BASE, window.NVQ_COURSE_LEVEL, window.APP_FORM_OLD (set by form.php).
 */
?>
<script>
(function () {
  const base = (typeof window.APP_BASE === 'string' ? window.APP_BASE : '').replace(/\/$/, '');
  const nvqLevel = (typeof window.NVQ_COURSE_LEVEL === 'string' ? window.NVQ_COURSE_LEVEL : '4');
  const oldData = (typeof window.APP_FORM_OLD === 'object' && window.APP_FORM_OLD) ? window.APP_FORM_OLD : {};
  const prefRows = [1, 2, 3];

  async function loadDepartments() {
    const qs = new URLSearchParams({ nvq_level: nvqLevel });
    const r = await fetch(base + '/student-application/api/departments?' + qs.toString(), { credentials: 'same-origin' });
    const j = await r.json();
    return (j && j.success && Array.isArray(j.departments)) ? j.departments : [];
  }

  async function loadCourses(deptId) {
    if (!deptId) return [];
    const qs = new URLSearchParams({ department_id: deptId, nvq_level: nvqLevel });
    const r = await fetch(base + '/student-application/api/courses?' + qs.toString(), { credentials: 'same-origin' });
    const j = await r.json();
    return (j && j.success && Array.isArray(j.courses)) ? j.courses : [];
  }

  function fillCourseSelect(sel, courses, selectedValue) {
    sel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Choose course…';
    sel.appendChild(opt0);
    courses.forEach(function (c) {
      const opt = document.createElement('option');
      const label = c.course_id + ' — ' + (c.course_name || '');
      opt.value = label.substring(0, 150);
      opt.textContent = (c.course_name || '') + ' (' + (c.course_id || '') + ')';
      if (selectedValue && selectedValue === opt.value) opt.selected = true;
      sel.appendChild(opt);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    loadDepartments().then(function (depts) {
      prefRows.forEach(function (n) {
        var deptSel = document.getElementById('dept_pref_' + n);
        var courseSel = document.getElementById('course_priority_' + n);
        if (!deptSel || !courseSel) return;

        deptSel.innerHTML = '';
        var optEmpty = document.createElement('option');
        optEmpty.value = '';
        optEmpty.textContent = 'Choose department…';
        deptSel.appendChild(optEmpty);
        if (depts.length === 0) {
          var optNone = document.createElement('option');
          optNone.value = '';
          optNone.textContent = 'No departments for this level';
          deptSel.appendChild(optNone);
        } else {
          depts.forEach(function (d) {
            var opt = document.createElement('option');
            opt.value = d.department_id || '';
            opt.textContent = (d.department_name || '') + ' (' + (d.department_id || '') + ')';
            deptSel.appendChild(opt);
          });
        }

        var oldDept = oldData['dept_pref_' + n] || '';
        var oldCourse = oldData['course_priority_' + n] || '';

        deptSel.addEventListener('change', function () {
          loadCourses(deptSel.value).then(function (courses) {
            fillCourseSelect(courseSel, courses, '');
          });
        });

        if (oldDept) {
          deptSel.value = oldDept;
          loadCourses(oldDept).then(function (courses) {
            fillCourseSelect(courseSel, courses, oldCourse);
          });
        }
      });
    }).catch(function () {
      prefRows.forEach(function (n) {
        var deptSel = document.getElementById('dept_pref_' + n);
        if (deptSel) {
          deptSel.innerHTML = '<option value="">Could not load. Please refresh the page.</option>';
        }
      });
    });
  });
})();
</script>
