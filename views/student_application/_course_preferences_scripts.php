<?php
/**
 * Expects: window.APP_BASE, window.NVQ_COURSE_LEVEL, window.APP_FORM_OLD (set by host page).
 * Exposes: window.initAppCoursePreferenceSelects(), window.appCoursePrefsRestore(data)
 */
?>
<script>
(function () {
  const prefRows = [1, 2, 3];

  function baseUrl() {
    return (typeof window.APP_BASE === 'string' ? window.APP_BASE : '').replace(/\/$/, '');
  }

  function nvqLevel() {
    return typeof window.NVQ_COURSE_LEVEL === 'string' ? window.NVQ_COURSE_LEVEL : '4';
  }

  function oldData() {
    return (typeof window.APP_FORM_OLD === 'object' && window.APP_FORM_OLD) ? window.APP_FORM_OLD : {};
  }

  async function loadDepartments() {
    const qs = new URLSearchParams({ nvq_level: nvqLevel() });
    const r = await fetch(baseUrl() + '/student-application/api/departments?' + qs.toString(), { credentials: 'same-origin' });
    const j = await r.json();
    return (j && j.success && Array.isArray(j.departments)) ? j.departments : [];
  }

  async function loadCourses(deptId) {
    if (!deptId) return [];
    const qs = new URLSearchParams({ department_id: deptId, nvq_level: nvqLevel() });
    const r = await fetch(baseUrl() + '/student-application/api/courses?' + qs.toString(), { credentials: 'same-origin' });
    const j = await r.json();
    return (j && j.success && Array.isArray(j.courses)) ? j.courses : [];
  }

  /** Older saves used "course_id — course_name"; new saves use course name only. */
  function courseNameFromLegacyStored(stored) {
    if (!stored) return '';
    var s = String(stored).trim();
    var em = '\u2014';
    var sep = ' ' + em + ' ';
    var i = s.indexOf(sep);
    if (i !== -1) return s.substring(i + sep.length).trim();
    i = s.indexOf(' — ');
    if (i !== -1) return s.substring(i + 3).trim();
    return s;
  }

  function fillCourseSelect(sel, courses, selectedValue) {
    sel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Choose course…';
    sel.appendChild(opt0);
    var wantValue = selectedValue ? String(selectedValue).trim() : '';
    if (wantValue) {
      var fromLegacy = courseNameFromLegacyStored(wantValue);
      if (fromLegacy && fromLegacy !== wantValue) wantValue = fromLegacy.substring(0, 150);
    }
    courses.forEach(function (c) {
      const opt = document.createElement('option');
      const name = (c.course_name || '').trim();
      opt.value = name.substring(0, 150);
      opt.textContent = name;
      if (wantValue && wantValue === opt.value) opt.selected = true;
      sel.appendChild(opt);
    });
  }

  function wireDeptChange(deptSel, courseSel) {
    deptSel.addEventListener('change', function () {
      loadCourses(deptSel.value).then(function (courses) {
        fillCourseSelect(courseSel, courses, '');
      });
    });
  }

  window.initAppCoursePreferenceSelects = function () {
    const od = oldData();
    loadDepartments().then(function (depts) {
      prefRows.forEach(function (n) {
        var deptSel = document.getElementById('dept_pref_' + n);
        var courseSel = document.getElementById('course_priority_' + n);
        if (!deptSel || !courseSel) return;

        var cloneD = deptSel.cloneNode(true);
        var cloneC = courseSel.cloneNode(true);
        deptSel.parentNode.replaceChild(cloneD, deptSel);
        courseSel.parentNode.replaceChild(cloneC, courseSel);
        deptSel = document.getElementById('dept_pref_' + n);
        courseSel = document.getElementById('course_priority_' + n);
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
            opt.textContent = (d.department_name || '').trim();
            deptSel.appendChild(opt);
          });
        }

        var oldDept = od['dept_pref_' + n] || '';
        var oldCourse = od['course_priority_' + n] || '';

        wireDeptChange(deptSel, courseSel);

        if (oldDept) {
          deptSel.value = oldDept;
          loadCourses(oldDept).then(function (courses) {
            fillCourseSelect(courseSel, courses, oldCourse);
          });
        } else {
          courseSel.innerHTML = '';
          var o0 = document.createElement('option');
          o0.value = '';
          o0.textContent = 'Choose department first…';
          courseSel.appendChild(o0);
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
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.initAppCoursePreferenceSelects === 'function') {
      window.initAppCoursePreferenceSelects();
    }
  });

  window.appCoursePrefsRestore = function (data) {
    if (!data || typeof data !== 'object') return;
    window.APP_FORM_OLD = data;
    if (typeof window.initAppCoursePreferenceSelects === 'function') {
      window.initAppCoursePreferenceSelects();
    }
  };
})();
</script>
