<?php
/**
 * Expects: window.APP_BASE, window.NVQ_COURSE_LEVEL, window.APP_FORM_OLD (set by host page).
 * Exposes: window.initAppCoursePreferenceSelects(), window.appCoursePrefsRestore(data),
 *          window.appCoursePrefsValidateUnique()
 */
?>
<script>
(function () {
  const prefRows = [1, 2, 3];
  const ORD = { 1: '1st', 2: '2nd', 3: '3rd' };

  function baseUrl() {
    return (typeof window.APP_BASE === 'string' ? window.APP_BASE : '').replace(/\/$/, '');
  }

  function nvqLevel() {
    return typeof window.NVQ_COURSE_LEVEL === 'string' ? window.NVQ_COURSE_LEVEL : '4';
  }

  function oldData() {
    return (typeof window.APP_FORM_OLD === 'object' && window.APP_FORM_OLD) ? window.APP_FORM_OLD : {};
  }

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

  function normalizeCourseKey(stored) {
    var name = courseNameFromLegacyStored(stored || '');
    return String(name).replace(/\s+/g, ' ').trim().toLowerCase();
  }

  function courseSelect(n) {
    return document.getElementById('course_priority_' + n);
  }

  function deptSelect(n) {
    return document.getElementById('dept_pref_' + n);
  }

  function selectedCourseValue(n) {
    var sel = courseSelect(n);
    return sel ? String(sel.value || '').trim() : '';
  }

  function selectedCourseLabel(n) {
    var sel = courseSelect(n);
    if (!sel || !sel.value) return '';
    var opt = sel.options[sel.selectedIndex];
    return opt ? String(opt.textContent || sel.value).trim() : String(sel.value).trim();
  }

  function excludedKeysFor(prefN) {
    var set = {};
    prefRows.forEach(function (n) {
      if (n === prefN) return;
      var key = normalizeCourseKey(selectedCourseValue(n));
      if (key) set[key] = n;
    });
    return set;
  }

  function showCoursePrefMessage(msg) {
    if (!msg) return;
    if (typeof window.showAlert === 'function') {
      window.showAlert(msg);
      return;
    }
    if (typeof window.alert === 'function') {
      window.alert(msg);
    }
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

  function fillCourseSelect(sel, courses, selectedValue, excludeKeys) {
    excludeKeys = excludeKeys || {};
    sel._saCourses = Array.isArray(courses) ? courses : [];
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
    var wantKey = normalizeCourseKey(wantValue);
    var kept = false;

    sel._saCourses.forEach(function (c) {
      const name = (c.course_name || '').trim();
      if (!name) return;
      var key = normalizeCourseKey(name);
      if (key && excludeKeys[key] && key !== wantKey) {
        return;
      }
      const opt = document.createElement('option');
      opt.value = name.substring(0, 150);
      opt.textContent = name;
      if (wantValue && wantValue === opt.value) {
        opt.selected = true;
        kept = true;
      }
      sel.appendChild(opt);
    });

    if (wantValue && !kept) {
      sel.value = '';
      return { cleared: true, clearedLabel: wantValue };
    }
    return { cleared: false, clearedLabel: '' };
  }

  function refillCourseFromCache(prefN, notifyClear) {
    var sel = courseSelect(prefN);
    var dept = deptSelect(prefN);
    if (!sel || !dept || !dept.value) return;
    var courses = Array.isArray(sel._saCourses) ? sel._saCourses : [];
    if (!courses.length) return;
    var prev = selectedCourseValue(prefN);
    var result = fillCourseSelect(sel, courses, prev, excludedKeysFor(prefN));
    if (result.cleared && notifyClear && prev) {
      var takenBy = excludedKeysFor(prefN)[normalizeCourseKey(prev)];
      var msg = result.clearedLabel
        + ' is already selected as your ' + (ORD[takenBy] || 'other')
        + ' choice. Please select a different course for your ' + (ORD[prefN] || '') + ' choice.';
      showCoursePrefMessage(msg);
    }
  }

  function onCourseChanged(changedN) {
    prefRows.forEach(function (n) {
      if (n === changedN) return;
      refillCourseFromCache(n, true);
    });
  }

  function wireDeptChange(deptSel, courseSel, prefN) {
    deptSel.addEventListener('change', function () {
      loadCourses(deptSel.value).then(function (courses) {
        fillCourseSelect(courseSel, courses, '', excludedKeysFor(prefN));
        onCourseChanged(prefN);
      });
    });
  }

  function wireCourseChange(courseSel, prefN) {
    courseSel.addEventListener('change', function () {
      var val = String(courseSel.value || '').trim();
      if (val) {
        var key = normalizeCourseKey(val);
        var taken = null;
        prefRows.forEach(function (n) {
          if (n === prefN) return;
          if (normalizeCourseKey(selectedCourseValue(n)) === key) taken = n;
        });
        if (taken) {
          var label = selectedCourseLabel(prefN) || val;
          courseSel.value = '';
          showCoursePrefMessage(
            label + ' is already selected as your ' + ORD[taken]
              + ' choice. Please select a different course for your ' + ORD[prefN] + ' choice.'
          );
        }
      }
      onCourseChanged(prefN);
    });
  }

  window.appCoursePrefsValidateUnique = function () {
    var seen = {};
    for (var i = 0; i < prefRows.length; i++) {
      var n = prefRows[i];
      var val = selectedCourseValue(n);
      if (!val) continue;
      var key = normalizeCourseKey(val);
      if (!key) continue;
      if (seen[key]) {
        var first = seen[key];
        var label = selectedCourseLabel(n) || val;
        var msg = label + ' is already selected as your ' + ORD[first]
          + ' choice. Please select a different course for your ' + ORD[n] + ' choice.';
        showCoursePrefMessage(msg);
        var sel = courseSelect(n);
        if (sel) {
          sel.focus();
          if (typeof sel.classList !== 'undefined') sel.classList.add('is-invalid');
        }
        return false;
      }
      seen[key] = n;
    }
    prefRows.forEach(function (n) {
      var sel = courseSelect(n);
      if (sel && sel.classList) sel.classList.remove('is-invalid');
    });
    return true;
  };

  window.initAppCoursePreferenceSelects = function () {
    const od = oldData();
    loadDepartments().then(function (depts) {
      var loadPromises = [];
      prefRows.forEach(function (n) {
        var deptSel = deptSelect(n);
        var courseSel = courseSelect(n);
        if (!deptSel || !courseSel) return;

        var cloneD = deptSel.cloneNode(true);
        var cloneC = courseSel.cloneNode(true);
        deptSel.parentNode.replaceChild(cloneD, deptSel);
        courseSel.parentNode.replaceChild(cloneC, courseSel);
        deptSel = deptSelect(n);
        courseSel = courseSelect(n);
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

        wireDeptChange(deptSel, courseSel, n);
        wireCourseChange(courseSel, n);

        if (oldDept) {
          deptSel.value = oldDept;
          loadPromises.push(
            loadCourses(oldDept).then(function (courses) {
              // First pass: fill without exclusions so all old values can load; unique filter applied after.
              fillCourseSelect(courseSel, courses, oldCourse, {});
            })
          );
        } else {
          courseSel.innerHTML = '';
          courseSel._saCourses = [];
          var o0 = document.createElement('option');
          o0.value = '';
          o0.textContent = 'Choose department first…';
          courseSel.appendChild(o0);
        }
      });

      return Promise.all(loadPromises).then(function () {
        // Second pass: drop duplicates from 2nd/3rd (and any later) while keeping earlier choices.
        prefRows.forEach(function (n) {
          if (n === 1) return;
          var sel = courseSelect(n);
          if (!sel || !Array.isArray(sel._saCourses) || !sel._saCourses.length) return;
          var prev = selectedCourseValue(n);
          var result = fillCourseSelect(sel, sel._saCourses, prev, excludedKeysFor(n));
          if (result.cleared && prev) {
            var takenBy = excludedKeysFor(n)[normalizeCourseKey(prev)];
            showCoursePrefMessage(
              result.clearedLabel + ' is already selected as your ' + (ORD[takenBy] || 'other')
                + ' choice. Please select a different course for your ' + (ORD[n] || '') + ' choice.'
            );
          }
        });
      });
    }).catch(function () {
      prefRows.forEach(function (n) {
        var deptSel = deptSelect(n);
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
