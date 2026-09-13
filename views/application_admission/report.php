<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = static function ($n): string {
    if ($n === null || $n === '') {
        return '—';
    }
    if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
        return (string) (int) round((float) $n);
    }

    return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
};
$level = trim((string) ($level ?? ''));
$departmentId = trim((string) ($department_id ?? ''));
$courseId = trim((string) ($course_id ?? ''));
$departments = is_array($departments ?? null) ? $departments : [];
$courses = is_array($courses ?? null) ? $courses : [];
$groups = is_array($groups ?? null) ? $groups : [];
$failGroups = is_array($fail_groups ?? null) ? $fail_groups : [];
$filterQuery = (string) ($filter_query ?? '');
$total = (int) ($total_students ?? 0);
$totalFail = (int) ($total_fail ?? 0);
$choiceCounts = is_array($choice_counts ?? null) ? $choice_counts : [];
$failCounts = is_array($fail_counts ?? null) ? $fail_counts : [];
$minMarks = (int) ($min_marks ?? 30);
$overview = is_array($overview ?? null) ? $overview : [];
$ovTotals = is_array($overview['totals'] ?? null) ? $overview['totals'] : [];
$ovDepartments = is_array($overview['departments'] ?? null) ? $overview['departments'] : [];
$ovCourses = is_array($overview['courses'] ?? null) ? $overview['courses'] : [];
$listFiltered = $level !== '' || $departmentId !== '' || $courseId !== '';
$hasFilter = !empty($has_filter);
$aaNavActive = 'report';
$reportBase = rtrim(APP_URL, '/') . '/application-admission/report';
$fmtCut = static function (array $card) use ($fmt): string {
    $parts = is_array($card['cutoff_parts'] ?? null) ? $card['cutoff_parts'] : [];
    if ($parts === []) {
        return '—';
    }
    $bits = [];
    foreach ($parts as $p) {
        $pair = 'N ' . $fmt($p['cutoff_northern'] ?? null) . ' / O ' . $fmt($p['cutoff_other'] ?? null);
        $label = trim((string) ($p['label'] ?? ''));
        $bits[] = ($label !== '' && strcasecmp($label, 'All languages') !== 0) ? ($label . ': ' . $pair) : $pair;
    }

    return implode(' · ', $bits);
};
?>
<style>
.aa-page { width: 100%; max-width: none; margin: 0; padding-bottom: 1.5rem; }
.aa-page-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem 1rem; margin-bottom: 1rem; }
.aa-page-header h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.25rem; }
.aa-page-header .aa-sub { margin: 0; font-size: 0.8125rem; color: #6c757d; }
.aa-stats { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.aa-stat { background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 0.55rem 0.85rem; min-width: 7.5rem; }
.aa-stat strong { display: block; font-size: 1.15rem; line-height: 1.2; }
.aa-stat span { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
.aa-filters { display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem 1rem; margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; }
.aa-filters label { display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 0.25rem; }
.aa-card { background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; margin-bottom: 1rem; overflow: hidden; }
.aa-card-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.5rem; padding: 0.75rem 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
.aa-card-head h2 { font-size: 1rem; font-weight: 700; margin: 0; }
.aa-meta { font-size: 0.8125rem; color: #6c757d; }
.aa-table { width: 100%; margin: 0; }
.aa-table thead th { padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #495057; background: #fff; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.aa-table tbody td { padding: 0.5rem 0.75rem; font-size: 0.875rem; border-bottom: 1px solid #eef1f4; vertical-align: middle; }
.aa-table tbody tr:last-child td { border-bottom: none; }
.aa-col-no { text-align: center; color: #6c757d; width: 3rem; }
.aa-col-roll, .aa-col-marks { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.8125rem; text-align: center; white-space: nowrap; }
.aa-col-choice { text-align: center; white-space: nowrap; }
.aa-diff { background: #dcfce7; font-weight: 600; }
.aa-stat.aa-stat-fail { border-color: #fecaca; background: #fef2f2; }
.aa-stat.aa-stat-fail strong { color: #991b1b; }
.aa-section-title { font-size: 1.05rem; font-weight: 700; margin: 1.25rem 0 0.75rem; }
.aa-card-head.aa-fail-head { background: #fef2f2; }
.aa-fail-reason { color: #991b1b; font-weight: 600; white-space: nowrap; }
.aa-empty { text-align: center; color: #6c757d; padding: 2rem 1rem !important; }
.aa-sum-wrap { background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; overflow: auto; }
.aa-sum-table { width: 100%; margin: 0; border-collapse: collapse; }
.aa-sum-table thead th { padding: 0.65rem 0.75rem; font-size: 0.75rem; font-weight: 700; color: #fff; background: #1f4e79; border-bottom: 1px solid #163a5c; white-space: nowrap; text-align: left; }
.aa-sum-table tbody td { padding: 0.55rem 0.75rem; font-size: 0.875rem; border-bottom: 1px solid #e8eef4; vertical-align: middle; background: #fff; }
.aa-sum-table tbody tr:nth-child(even) td { background: #f8fafc; }
.aa-sum-table tbody tr:hover td { background: #eef6ff; }
.aa-sum-table tbody tr.is-active td { background: #dbeafe; }
.aa-sum-table tfoot th { padding: 0.6rem 0.75rem; font-size: 0.8125rem; font-weight: 700; background: #e8eef4; border-top: 2px solid #1f4e79; }
.aa-sum-table .aa-num { text-align: center; white-space: nowrap; font-variant-numeric: tabular-nums; }
.aa-sum-table .is-fail { color: #991b1b; font-weight: 600; }
.aa-dept-link, .aa-course-row { color: #0f172a; font-weight: 600; text-decoration: none; }
.aa-dept-link:hover, .aa-course-row:hover { color: #1d4ed8; text-decoration: underline; }
.aa-course-cut { font-size: 0.8125rem; color: #334155; }
.aa-dept-label { font-size: 0.8rem; font-weight: 700; color: #334155; margin: 0.75rem 0 0.5rem; text-transform: uppercase; letter-spacing: 0.04em; }
.aa-part-jump { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0 0 1rem; }
.aa-part-jump a { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.85rem; border-radius: 999px; border: 1px solid #dee2e6; background: #fff; color: #334155; font-size: 0.8125rem; font-weight: 600; text-decoration: none; }
.aa-part-jump a:hover, .aa-part-jump a:focus { border-color: #93c5fd; color: #1d4ed8; }
.aa-part { background: #fff; border: 1px solid #dee2e6; border-radius: 0.75rem; padding: 1rem 1.1rem 0.75rem; margin-bottom: 1.25rem; }
.aa-part-dashboard { background: #f8fafc; border-color: #dbe3ee; }
.aa-part-report { background: #fff; }
.aa-part-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; }
.aa-part-head h2 { font-size: 1.1rem; font-weight: 700; margin: 0 0 0.2rem; }
.aa-part-head p { margin: 0; font-size: 0.8125rem; color: #64748b; }
.aa-part-dashboard .aa-section-title:first-of-type { margin-top: 0.25rem; }
#aa-exam-dashboard, #aa-selection-report { scroll-margin-top: 1rem; }
#aa-report-ajax.is-loading { opacity: 0.55; pointer-events: none; }
.aa-ajax-status { font-size: 0.8125rem; color: #64748b; min-height: 1.2em; margin: 0 0 0.75rem; }
</style>

<div class="container-fluid px-3 px-md-4 aa-page">
    <div class="aa-page-header">
        <div>
            <h1>Admission results</h1>
            <p class="aa-sub">All NVQ 04 and 05 students are shown first. Use the filter only when you need a level, department, or course.</p>
        </div>
    </div>

    <?php require BASE_PATH . '/views/application_admission/_module_nav.php'; ?>

    <form method="get" action="<?php echo APP_URL; ?>/application-admission/report" class="aa-filters"
          id="aa-report-filter"
          data-report-url="<?php echo APP_URL; ?>/application-admission/report"
          data-level="<?php echo $e($level); ?>">
        <div>
            <label for="aa_rep_level">NVQ level</label>
            <select name="level" id="aa_rep_level" class="form-select form-select-sm">
                <option value="" <?php echo $level === '' ? 'selected' : ''; ?>>All levels</option>
                <option value="04" <?php echo $level === '04' ? 'selected' : ''; ?>>Level 04</option>
                <option value="05" <?php echo $level === '05' ? 'selected' : ''; ?>>Level 05</option>
            </select>
        </div>
        <div>
            <label for="aa_rep_dept">Department</label>
            <select name="department_id" id="aa_rep_dept" class="form-select form-select-sm">
                <option value="">All departments</option>
                <?php foreach ($departments as $d):
                    $did = trim((string) ($d['department_id'] ?? ''));
                ?>
                <option value="<?php echo $e($did); ?>" <?php echo strcasecmp($departmentId, $did) === 0 ? 'selected' : ''; ?>>
                    <?php echo $e($d['department_name'] ?? $did); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="aa_rep_course">Course</label>
            <select name="course_id" id="aa_rep_course" class="form-select form-select-sm">
                <option value="">All courses</option>
                <?php foreach ($courses as $c):
                    $cidOpt = trim((string) ($c['course_id'] ?? ''));
                    $cDept = trim((string) ($c['department_id'] ?? ''));
                    $cLv = trim((string) ($c['application_level'] ?? ''));
                    $cLabel = trim((string) ($c['course_name'] ?? $cidOpt));
                    if ($level === '' && $cLv !== '') {
                        $cLabel .= ' (NVQ ' . $cLv . ')';
                    }
                ?>
                <option value="<?php echo $e($cidOpt); ?>" data-dept="<?php echo $e($cDept); ?>" data-level="<?php echo $e($cLv); ?>"
                    <?php echo strcasecmp($courseId, $cidOpt) === 0 ? 'selected' : ''; ?>>
                    <?php echo $e($cLabel); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Apply</button>
        </div>
        <div id="aa-report-clear-wrap"<?php echo $listFiltered ? '' : ' hidden'; ?>>
            <label>&nbsp;</label>
            <a href="<?php echo APP_URL; ?>/application-admission/report"
               id="aa-report-clear" class="btn btn-sm btn-link">Clear</a>
        </div>
    </form>
    <p class="aa-ajax-status" id="aa-report-status" hidden></p>
    <div id="aa-report-ajax">
        <?php require BASE_PATH . '/views/application_admission/_report_results.php'; ?>
    </div>
</div>
<script>
(function () {
    var form = document.getElementById('aa-report-filter');
    var levelEl = document.getElementById('aa_rep_level');
    var dept = document.getElementById('aa_rep_dept');
    var course = document.getElementById('aa_rep_course');
    var ajaxBox = document.getElementById('aa-report-ajax');
    var statusEl = document.getElementById('aa-report-status');
    var clearWrap = document.getElementById('aa-report-clear-wrap');
    var clearLink = document.getElementById('aa-report-clear');
    if (!form || !levelEl || !dept || !course || !ajaxBox) return;

    var abortCtl = null;
    var pageLevel = form.getAttribute('data-level') || '';

    function reportUrl(partial) {
        var u = new URL(form.getAttribute('data-report-url'), window.location.origin);
        u.search = '';
        if (levelEl.value) u.searchParams.set('level', levelEl.value);
        if (dept.value) u.searchParams.set('department_id', dept.value);
        if (course.value) u.searchParams.set('course_id', course.value);
        if (partial) u.searchParams.set('partial', '1');
        return u;
    }

    function syncCourses() {
        var want = (dept.value || '').toLowerCase();
        Array.prototype.forEach.call(course.options, function (opt) {
            if (!opt.value) {
                opt.hidden = false;
                opt.disabled = false;
                return;
            }
            var match = want === '' || (opt.getAttribute('data-dept') || '').toLowerCase() === want;
            opt.hidden = !match;
            opt.disabled = !match;
        });
        if (course.selectedOptions.length && course.selectedOptions[0].hidden) {
            course.value = '';
        }
        course.disabled = false;
    }

    function updateClear() {
        if (!clearWrap) return;
        clearWrap.hidden = !(levelEl.value || dept.value || course.value);
        if (clearLink) {
            clearLink.href = form.getAttribute('data-report-url');
        }
    }

    function pushPrettyUrl() {
        var pretty = reportUrl(false);
        history.pushState({ aaReport: true }, '', pretty.pathname + pretty.search);
    }

    function loadReport(push) {
        if (abortCtl) abortCtl.abort();
        abortCtl = window.AbortController ? new AbortController() : null;
        ajaxBox.classList.add('is-loading');
        if (statusEl) {
            statusEl.hidden = false;
            statusEl.textContent = 'Loading…';
        }
        var opts = {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin'
        };
        if (abortCtl) opts.signal = abortCtl.signal;
        fetch(reportUrl(true).toString(), opts).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        }).then(function (html) {
            ajaxBox.innerHTML = html;
            ajaxBox.classList.remove('is-loading');
            if (statusEl) {
                statusEl.hidden = true;
                statusEl.textContent = '';
            }
            updateClear();
            if (push !== false) pushPrettyUrl();
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            ajaxBox.classList.remove('is-loading');
            if (statusEl) {
                statusEl.hidden = false;
                statusEl.textContent = 'Could not load the report. Try Apply again.';
            }
        });
    }

    function applyFromUrl(push) {
        var params = new URLSearchParams(window.location.search);
        var urlLevel = params.get('level') || '';
        if (urlLevel !== pageLevel) {
            window.location.reload();
            return;
        }
        dept.value = params.get('department_id') || '';
        syncCourses();
        course.value = params.get('course_id') || '';
        updateClear();
        loadReport(push);
    }

    levelEl.addEventListener('change', function () {
        course.value = '';
        window.location.href = reportUrl(false).toString();
    });
    dept.addEventListener('change', function () {
        course.value = '';
        syncCourses();
        updateClear();
        loadReport(true);
    });
    course.addEventListener('change', function () {
        loadReport(true);
    });
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        loadReport(true);
    });
    if (clearLink) {
        clearLink.addEventListener('click', function (ev) {
            ev.preventDefault();
            window.location.href = form.getAttribute('data-report-url');
        });
    }
    ajaxBox.addEventListener('click', function (ev) {
        var card = ev.target.closest('a.aa-ov-card, a.aa-dept-link, a.aa-course-row');
        if (!card || !ajaxBox.contains(card)) return;
        var did = card.getAttribute('data-aa-dept') || '';
        var cid = card.getAttribute('data-aa-course') || '';
        var lv = card.getAttribute('data-aa-level') || '';
        if (!did && !cid) return;
        ev.preventDefault();
        if (lv && levelEl.value !== lv) {
            levelEl.value = lv;
            window.location.href = reportUrl(false).toString();
            return;
        }
        dept.value = did;
        syncCourses();
        course.value = cid;
        updateClear();
        loadReport(true);
    });
    window.addEventListener('popstate', function () {
        applyFromUrl(false);
    });
    syncCourses();
    updateClear();
})();
</script>
