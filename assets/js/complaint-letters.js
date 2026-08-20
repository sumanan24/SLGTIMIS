(function () {
    'use strict';

    var cfgEl = document.getElementById('cl-form-config') || document.getElementById('cl-page-config');
    if (!cfgEl) {
        return;
    }
    var cfg = {};
    try {
        cfg = JSON.parse(cfgEl.textContent || '{}');
    } catch (e) {
        return;
    }

    var baseUrl = (cfg.baseUrl || '').replace(/\/$/, '');
    var isFilterMode = cfg.mode === 'filter';
    var isEdit = !!cfg.isEdit;
    var selected = {};
    var selectedMeta = {};
    (cfg.selectedStudentIds || []).forEach(function (id) {
        if (id) {
            selected[id] = true;
        }
    });
    (cfg.selectedStudents || []).forEach(function (s) {
        var id = s.student_id || '';
        if (!id) {
            return;
        }
        selected[id] = true;
        selectedMeta[id] = s.student_fullname || id;
    });

    var deptEl = document.getElementById(isFilterMode ? 'cl-filter-dept' : 'cl-dept');
    var courseEl = document.getElementById(isFilterMode ? 'cl-filter-course' : 'cl-course');
    var yearEl = document.getElementById('cl-year');
    var searchEl = document.getElementById('cl-student-search');
    var listEl = document.getElementById('cl-student-list');
    var searchTimer = null;
    var preservedCourseId = isFilterMode ? (cfg.selectedCourseId || '') : (cfg.selectedCourseId || '');

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function emptyCourseLabel() {
        return isFilterMode ? 'All Courses' : 'Select course';
    }

    function resetCourseSelect() {
        if (!courseEl) {
            return;
        }
        courseEl.innerHTML = '<option value="">' + emptyCourseLabel() + '</option>';
    }

    function mergeStudentsWithSelected(students) {
        students = students || [];
        var byId = {};
        students.forEach(function (s) {
            if (s.student_id) {
                byId[s.student_id] = s;
            }
        });
        Object.keys(selected).forEach(function (id) {
            if (!byId[id]) {
                students.push({
                    student_id: id,
                    student_fullname: selectedMeta[id] || id
                });
            }
        });
        students.sort(function (a, b) {
            var an = (a.student_fullname || a.student_id || '').toLowerCase();
            var bn = (b.student_fullname || b.student_id || '').toLowerCase();
            return an.localeCompare(bn);
        });
        return students;
    }

    function loadCourses() {
        if (!courseEl) {
            return Promise.resolve();
        }
        if (!deptEl || !deptEl.value) {
            resetCourseSelect();
            if (listEl && !isEdit) {
                listEl.innerHTML = '<p class="text-muted small mb-0">Choose department, course, and academic year to load students.</p>';
            }
            return Promise.resolve();
        }
        return fetch(baseUrl + '/complaint-letters/courses?department_id=' + encodeURIComponent(deptEl.value), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var current = courseEl.value || preservedCourseId;
                preservedCourseId = '';
                courseEl.innerHTML = '<option value="">' + emptyCourseLabel() + '</option>';
                (data.courses || []).forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.course_id || '';
                    opt.textContent = c.course_name || c.course_id || '';
                    if (current && opt.value === current) {
                        opt.selected = true;
                    }
                    courseEl.appendChild(opt);
                });
            })
            .catch(function () {
                resetCourseSelect();
            });
    }

    function bindStudentCheckboxes() {
        if (!listEl) {
            return;
        }
        listEl.querySelectorAll('.cl-student-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.checked) {
                    selected[cb.value] = true;
                } else {
                    delete selected[cb.value];
                }
            });
        });
    }

    function renderStudents(students) {
        if (!listEl) {
            return;
        }
        students = mergeStudentsWithSelected(students);
        if (!students.length) {
            listEl.innerHTML = '<p class="text-muted small mb-0">No students found for this selection.</p>';
            return;
        }
        var html = '';
        students.forEach(function (s) {
            var id = s.student_id || '';
            var checked = selected[id] ? ' checked' : '';
            html += '<label class="d-flex align-items-start gap-2 mb-2 small">'
                + '<input type="checkbox" class="form-check-input mt-1 cl-student-cb" name="student_ids[]" value="' + escapeHtml(id) + '"' + checked + '>'
                + '<span><strong>' + escapeHtml(s.student_fullname || id) + '</strong><br>'
                + '<span class="text-muted">' + escapeHtml(id) + '</span></span></label>';
        });
        listEl.innerHTML = html;
        bindStudentCheckboxes();
    }

    function loadStudents() {
        if (!deptEl || !courseEl || !yearEl || !listEl) {
            return Promise.resolve();
        }
        if (!deptEl.value || !courseEl.value || !yearEl.value) {
            if (!isEdit || !Object.keys(selected).length) {
                listEl.innerHTML = '<p class="text-muted small mb-0">Choose department, course, and academic year to load students.</p>';
            }
            return Promise.resolve();
        }
        listEl.innerHTML = '<p class="text-muted small mb-0">Loading students…</p>';
        var q = searchEl && searchEl.value ? '&q=' + encodeURIComponent(searchEl.value) : '';
        var url = baseUrl + '/complaint-letters/students?department_id=' + encodeURIComponent(deptEl.value)
            + '&course_id=' + encodeURIComponent(courseEl.value)
            + '&academic_year=' + encodeURIComponent(yearEl.value) + q;
        return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderStudents(data.students || []); })
            .catch(function () {
                if (isEdit && Object.keys(selected).length) {
                    renderStudents([]);
                } else {
                    listEl.innerHTML = '<p class="text-danger small mb-0">Could not load students.</p>';
                }
            });
    }

    function initFormStudents() {
        if (isFilterMode) {
            if (deptEl && deptEl.value && courseEl && courseEl.options.length <= 1) {
                loadCourses();
            }
            return;
        }
        bindStudentCheckboxes();
        if (!deptEl || !deptEl.value || !yearEl) {
            return;
        }
        var runLoad = function () {
            loadStudents();
        };
        if (courseEl && courseEl.value) {
            runLoad();
            return;
        }
        if (preservedCourseId) {
            loadCourses().then(runLoad);
            return;
        }
        if (isEdit && Object.keys(selected).length) {
            renderStudents([]);
        }
    }

    if (deptEl) {
        deptEl.addEventListener('change', function () {
            preservedCourseId = '';
            loadCourses().then(loadStudents);
        });
    }
    if (courseEl && !isFilterMode) {
        courseEl.addEventListener('change', loadStudents);
    }
    if (yearEl) {
        yearEl.addEventListener('change', loadStudents);
    }
    if (searchEl) {
        searchEl.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadStudents, 350);
        });
    }

    if (isFilterMode) {
        if (deptEl && deptEl.value && courseEl && courseEl.options.length <= 1) {
            loadCourses();
        } else if (courseEl && !deptEl || !deptEl.value) {
            resetCourseSelect();
        }
    } else {
        initFormStudents();
    }
})();
