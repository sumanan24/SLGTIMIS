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
$level = (string) ($level ?? '04');
$departmentId = trim((string) ($department_id ?? ''));
$courseId = trim((string) ($course_id ?? ''));
$departments = is_array($departments ?? null) ? $departments : [];
$canManage = !empty($canManage);
$courses = is_array($courses ?? null) ? $courses : [];
$cutoffMap = is_array($cutoffMap ?? null) ? $cutoffMap : [];
$groups = is_array($groups ?? null) ? $groups : [];
$filterQuery = (string) ($filter_query ?? ('level=' . rawurlencode($level)));
$qualifyTotalAll = (int) ($qualify_total_all ?? 0);
$langMediums = ApplicationAdmissionCutoffModel::MEDIUMS;
$allMedium = ApplicationAdmissionCutoffModel::MEDIUM_ALL;
$autoGroups = ApplicationAdmissionCutoffModel::automobileCutoffGroups();
$autoCourses = [];
$otherCourses = [];
foreach ($courses as $course) {
    if (ApplicationAdmissionCutoffModel::isAutomobileLevel04($level, $course)) {
        $autoCourses[] = $course;
    } else {
        $otherCourses[] = $course;
    }
}
$cutoffVals = static function (array $map, string $cid, $mediums) use ($fmt, $langMediums, $allMedium): array {
    if (!is_array($mediums)) {
        $mediums = [(string) $mediums];
    }
    $cut = [];
    foreach ($mediums as $medium) {
        $medium = (string) $medium;
        if (!empty($map[$cid][$medium])) {
            $cut = $map[$cid][$medium];
            break;
        }
    }
    if ($cut === [] && in_array($allMedium, $mediums, true)) {
        foreach ($langMediums as $fb) {
            if (!empty($map[$cid][$fb])) {
                $cut = $map[$cid][$fb];
                break;
            }
        }
    }
    $nVal = $fmt($cut['cutoff_northern'] ?? '');
    $oVal = $fmt($cut['cutoff_other'] ?? '');

    return [
        'n' => $nVal === '—' ? '' : $nVal,
        'o' => $oVal === '—' ? '' : $oVal,
    ];
};
$aaNavActive = 'cutoff';
$totalQualify = 0;
foreach ($groups as $g) {
    $totalQualify += (int) ($g['qualify_count'] ?? 0);
}
if ($qualifyTotalAll < 1) {
    $qualifyTotalAll = $totalQualify;
}
$listFiltered = $departmentId !== '' || $courseId !== '';
?>
<style>
.aa-page { width: 100%; max-width: none; margin: 0; padding-bottom: 1.5rem; }
.aa-page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
}
.aa-page-header h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.25rem; }
.aa-page-header .aa-sub { margin: 0; font-size: 0.8125rem; color: #6c757d; }
.aa-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}
.aa-filters label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.aa-filters .form-select { min-width: 12rem; }
.aa-list-toolbar .form-select { min-width: 14rem; max-width: 22rem; }
.aa-list-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 0.85rem 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.aa-list-toolbar .aa-filters {
    margin: 0;
    padding: 0;
    border: none;
    background: transparent;
}
.aa-list-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.aa-card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background: #fff;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.aa-card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.aa-card-head h2 { font-size: 0.95rem; font-weight: 600; margin: 0; }
.aa-card-head .aa-meta { font-size: 0.8125rem; color: #6c757d; }
.aa-table { width: 100%; margin: 0; }
.aa-table thead th {
    padding: 0.45rem 0.65rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
}
.aa-table thead th.aa-th-left { text-align: left; }
.aa-table tbody td {
    padding: 0.45rem 0.65rem;
    font-size: 0.875rem;
    vertical-align: middle;
    border-bottom: 1px solid #eef1f4;
}
.aa-table tbody tr:last-child td { border-bottom: none; }
.aa-table .aa-col-marks { width: 5.25rem; text-align: center; }
.aa-table .aa-col-no { width: 3rem; text-align: center; color: #6c757d; }
.aa-table .aa-col-roll,
.aa-table .aa-col-nic {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8125rem;
    white-space: nowrap;
}
.aa-list-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #eef3f7;
}
.aa-list-table tbody tr:nth-child(even) td { background: #f8fafc; }
.aa-list-table tbody tr:hover td { background: #eef6ff; }
.aa-list-table .aa-col-name { min-width: 12rem; text-align: left; }
.aa-list-table .aa-col-prov { min-width: 7.5rem; text-align: left; }
.aa-list-table .aa-col-center { text-align: center; }
.aa-list-table .aa-col-marks { font-variant-numeric: tabular-nums; }
.aa-course-block .aa-card-head { background: #eef4f9; }
.aa-course-block .aa-card-head h2 { font-size: 1rem; }
.aa-table .aa-cutoff-input {
    width: 4.5rem;
    text-align: center;
    margin: 0 auto;
    font-variant-numeric: tabular-nums;
}
.aa-region-north {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #cfe2ff;
    color: #084298;
}
.aa-region-other {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #e9ecef;
    color: #495057;
}
.aa-medium-chip {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #e7f1ff;
    color: #0d6efd;
}
.aa-choice-1 {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #d1e7dd;
    color: #0f5132;
}
.aa-choice-2 {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #fff3cd;
    color: #664d03;
}
.aa-list-table .aa-col-choice2 { min-width: 9rem; max-width: 16rem; }
.aa-empty { text-align: center; color: #6c757d; padding: 1.5rem 1rem !important; }
.aa-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-top: 1px solid #eef1f4; }
.aa-course-block { margin-bottom: 1.25rem; }
.aa-medium-head {
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    background: #f1f3f5;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.35rem 1rem;
}
</style>

<div class="container-fluid px-3 px-md-4 aa-page">
    <div class="aa-page-header">
        <div>
            <h1>Cutoff marks</h1>
            <p class="aa-sub"><strong>Automobile Level 04:</strong> Tamil has its own cutoff; <strong>Sinhala and English share the same</strong> cutoff. All other courses use one Northern cutoff and one other-province cutoff (no language split).</p>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php require BASE_PATH . '/views/application_admission/_module_nav.php'; ?>

    <form method="get" action="<?php echo APP_URL; ?>/application-admission/cutoffs" class="aa-filters">
        <div>
            <label for="aa_cutoff_level">NVQ level</label>
            <select name="level" id="aa_cutoff_level" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="04" <?php echo $level === '04' ? 'selected' : ''; ?>>Level 04</option>
                <option value="05" <?php echo $level === '05' ? 'selected' : ''; ?>>Level 05</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Show</button>
        </div>
        <p class="small text-muted mb-0"><?php echo (int) $qualifyTotalAll; ?> student(s) currently meet a cutoff.</p>
    </form>

    <?php if ($canManage && $courses !== []): ?>
    <form method="post" action="<?php echo APP_URL; ?>/application-admission/cutoffs-save">
        <input type="hidden" name="level" value="<?php echo $e($level); ?>">
        <input type="hidden" name="department_id" value="<?php echo $e($departmentId); ?>">
        <input type="hidden" name="course_id" value="<?php echo $e($courseId); ?>">
    <?php endif; ?>

    <?php if ($autoCourses !== []): ?>
    <div class="aa-card">
        <div class="aa-card-head">
            <h2>Automobile Level 04 — language cutoffs</h2>
            <span class="aa-meta">Tamil is separate. Sinhala and English use the same Northern / other-province minimums</span>
        </div>
            <div class="table-responsive">
                <table class="table aa-table mb-0">
                    <thead>
                        <tr>
                            <th class="aa-th-left" rowspan="2">Department</th>
                            <th class="aa-th-left" rowspan="2">Course</th>
                            <?php foreach ($autoGroups as $autoGroup): ?>
                            <th colspan="2"><?php echo $e($autoGroup['label'] ?? ''); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($autoGroups as $autoGroup): ?>
                            <th>Northern</th>
                            <th>Other</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($autoCourses as $course):
                            $cid = trim((string) ($course['course_id'] ?? ''));
                        ?>
                        <tr>
                            <td><?php echo $e($course['department_name'] ?? '—'); ?></td>
                            <td><?php echo $e($course['course_name'] ?? $cid); ?></td>
                            <?php foreach ($autoGroups as $autoGroup):
                                $groupKey = (string) ($autoGroup['key'] ?? '');
                                $store = is_array($autoGroup['store'] ?? null) ? $autoGroup['store'] : [$groupKey];
                                $vals = $cutoffVals($cutoffMap, $cid, $store);
                            ?>
                            <td class="aa-col-marks">
                                <?php if ($canManage): ?>
                                <input type="text" class="form-control form-control-sm aa-cutoff-input"
                                       name="cutoffs[<?php echo $e($cid); ?>][<?php echo $e($groupKey); ?>][northern]"
                                       value="<?php echo $e($vals['n']); ?>" maxlength="6" autocomplete="off"
                                       title="<?php echo $e($autoGroup['label'] ?? $groupKey); ?> — Northern province">
                                <?php else: ?>
                                <?php echo $vals['n'] !== '' ? $e($vals['n']) : '<span class="text-muted">—</span>'; ?>
                                <?php endif; ?>
                            </td>
                            <td class="aa-col-marks">
                                <?php if ($canManage): ?>
                                <input type="text" class="form-control form-control-sm aa-cutoff-input"
                                       name="cutoffs[<?php echo $e($cid); ?>][<?php echo $e($groupKey); ?>][other]"
                                       value="<?php echo $e($vals['o']); ?>" maxlength="6" autocomplete="off"
                                       title="<?php echo $e($autoGroup['label'] ?? $groupKey); ?> — other provinces">
                                <?php else: ?>
                                <?php echo $vals['o'] !== '' ? $e($vals['o']) : '<span class="text-muted">—</span>'; ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
    <?php endif; ?>

    <div class="aa-card">
        <div class="aa-card-head">
            <h2><?php echo $autoCourses !== [] ? 'Other courses' : 'Course cutoffs'; ?> — NVQ Level <?php echo $e($level); ?></h2>
            <span class="aa-meta">One cutoff for Northern province, one for all other provinces. No language split.</span>
        </div>
            <div class="table-responsive">
                <table class="table aa-table mb-0">
                    <thead>
                        <tr>
                            <th class="aa-th-left">Department</th>
                            <th class="aa-th-left">Course</th>
                            <th>Northern</th>
                            <th>Other</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($otherCourses === []): ?>
                        <tr><td colspan="4" class="aa-empty"><?php echo $autoCourses !== [] ? 'No other courses for this NVQ level.' : 'No active courses for this NVQ level.'; ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($otherCourses as $course):
                            $cid = trim((string) ($course['course_id'] ?? ''));
                            $vals = $cutoffVals($cutoffMap, $cid, $allMedium);
                        ?>
                        <tr>
                            <td><?php echo $e($course['department_name'] ?? '—'); ?></td>
                            <td><?php echo $e($course['course_name'] ?? $cid); ?></td>
                            <td class="aa-col-marks">
                                <?php if ($canManage): ?>
                                <input type="text" class="form-control form-control-sm aa-cutoff-input"
                                       name="cutoffs[<?php echo $e($cid); ?>][<?php echo $e($allMedium); ?>][northern]"
                                       value="<?php echo $e($vals['n']); ?>" maxlength="6" autocomplete="off"
                                       title="Northern province">
                                <?php else: ?>
                                <?php echo $vals['n'] !== '' ? $e($vals['n']) : '<span class="text-muted">—</span>'; ?>
                                <?php endif; ?>
                            </td>
                            <td class="aa-col-marks">
                                <?php if ($canManage): ?>
                                <input type="text" class="form-control form-control-sm aa-cutoff-input"
                                       name="cutoffs[<?php echo $e($cid); ?>][<?php echo $e($allMedium); ?>][other]"
                                       value="<?php echo $e($vals['o']); ?>" maxlength="6" autocomplete="off"
                                       title="Other provinces">
                                <?php else: ?>
                                <?php echo $vals['o'] !== '' ? $e($vals['o']) : '<span class="text-muted">—</span>'; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php if ($canManage && $courses !== []): ?>
            <div class="aa-toolbar">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Save cutoffs</button>
                <span class="small text-muted">Leave a box empty if that group has no cutoff yet. Students with <strong>ab</strong> or no marks are not listed.</span>
            </div>
        </form>
        <?php elseif (!$canManage): ?>
            <div class="aa-toolbar"><span class="small text-muted">View only. SAO, REG, and ADM can update cutoffs.</span></div>
        <?php endif; ?>
    </div>

    <h2 class="h6 mb-2">Students who meet the minimum — course wise</h2>
    <p class="small text-muted mb-2">Includes 1st-choice applicants who met the cutoff. The 2nd choice column is the student’s second preference. Students below cutoff are on <strong>2nd option</strong>.</p>
    <div class="aa-list-toolbar">
        <form method="get" action="<?php echo APP_URL; ?>/application-admission/cutoffs" class="aa-filters" id="aa_student_list_filter">
            <input type="hidden" name="level" value="<?php echo $e($level); ?>">
            <div>
                <label for="aa_list_dept">Department</label>
                <select name="department_id" id="aa_list_dept" class="form-select form-select-sm" onchange="this.form.submit()">
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
                <label for="aa_list_course">Course</label>
                <select name="course_id" id="aa_list_course" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $c):
                        $cidOpt = trim((string) ($c['course_id'] ?? ''));
                        $cDept = trim((string) ($c['department_id'] ?? ''));
                    ?>
                    <option value="<?php echo $e($cidOpt); ?>" data-dept="<?php echo $e($cDept); ?>"
                        <?php echo strcasecmp($courseId, $cidOpt) === 0 ? 'selected' : ''; ?>>
                        <?php echo $e($c['course_name'] ?? $cidOpt); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Show</button>
            </div>
            <?php if ($listFiltered): ?>
            <div>
                <label>&nbsp;</label>
                <a href="<?php echo APP_URL; ?>/application-admission/cutoffs?level=<?php echo $e($level); ?>" class="btn btn-sm btn-link">Clear</a>
            </div>
            <?php endif; ?>
            <p class="small text-muted mb-0">
                <?php echo (int) $totalQualify; ?> qualified
                <?php echo $listFiltered ? ' in this filter' : ' (course-wise)'; ?>
            </p>
        </form>
        <div class="aa-list-actions">
            <a href="<?php echo APP_URL; ?>/application-admission/export-cutoffs?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-cutoffs?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <?php
    $shown = 0;
    foreach ($groups as $g):
        if (empty($g['has_cutoff'])) {
            continue;
        }
        $shown++;
        $byMedium = is_array($g['by_medium'] ?? null) ? $g['by_medium'] : [];
        $usesLanguage = !empty($g['uses_language']);
        $blocks = $usesLanguage
            ? array_values(array_map(static fn (array $grp): string => (string) ($grp['key'] ?? ''), $autoGroups))
            : [$allMedium];
    ?>
    <div class="aa-card aa-course-block">
        <div class="aa-card-head">
            <div>
                <h2><?php echo $e($g['course_name'] ?? ''); ?></h2>
                <div class="aa-meta"><?php echo $e($g['department_name'] ?? ''); ?><?php echo $usesLanguage ? ' · language-wise cutoff' : ''; ?></div>
            </div>
            <div class="aa-meta text-end">
                <?php echo (int) ($g['qualify_count'] ?? 0); ?> qualified
                · <?php echo (int) ($g['sat_count'] ?? 0); ?> with marks
            </div>
        </div>
        <?php foreach ($blocks as $medium):
            $block = $byMedium[$medium] ?? null;
            if (!is_array($block) || empty($block['has_cutoff'])) {
                continue;
            }
            $students = is_array($block['students'] ?? null) ? $block['students'] : [];
        ?>
        <?php if ($usesLanguage): ?>
        <div class="aa-medium-head">
            <span><?php echo $e($block['label'] ?? $medium); ?></span>
            <span class="aa-meta">
                Northern min <?php echo $e($fmt($block['cutoff_northern'] ?? null)); ?>
                · Other min <?php echo $e($fmt($block['cutoff_other'] ?? null)); ?>
                · <?php echo (int) ($block['qualify_count'] ?? 0); ?> qualified
                · <?php echo (int) ($block['sat_count'] ?? 0); ?> with marks
            </span>
        </div>
        <?php else: ?>
        <div class="aa-medium-head">
            <span>Northern / other provinces</span>
            <span class="aa-meta">
                Northern min <?php echo $e($fmt($block['cutoff_northern'] ?? null)); ?>
                · Other min <?php echo $e($fmt($block['cutoff_other'] ?? null)); ?>
            </span>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table aa-table aa-list-table mb-0">
                <thead>
                    <tr>
                        <th class="aa-col-no">#</th>
                        <th class="aa-col-roll aa-th-left">Roll / Index</th>
                        <th class="aa-th-left aa-col-name">Name</th>
                        <th class="aa-col-nic aa-th-left">NIC</th>
                        <th class="aa-th-left aa-col-choice2">2nd choice</th>
                        <th class="aa-th-left aa-col-prov">Province</th>
                        <th class="aa-col-center">Region</th>
                        <?php if ($usesLanguage): ?><th class="aa-col-center">Medium</th><?php endif; ?>
                        <th class="aa-col-marks">Marks</th>
                        <th class="aa-col-marks">Min</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students === []): ?>
                    <tr><td colspan="<?php echo $usesLanguage ? 10 : 9; ?>" class="aa-empty"><?php echo $usesLanguage ? 'No ' . $e($block['label'] ?? $medium) . ' students met this cutoff.' : 'No students met this cutoff.'; ?></td></tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($students as $row): $n++;
                        $roll = trim((string) ($row['roll_number'] ?? ''));
                        $isNorth = ($row['region'] ?? '') === 'Northern';
                        $second = trim((string) ($row['course_priority_2'] ?? ''));
                    ?>
                    <tr>
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-roll"><?php echo $roll !== '' ? $e($roll) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="aa-col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="aa-col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="aa-col-choice2" title="<?php echo $e($second); ?>">
                            <?php echo $second !== '' ? $e($second) : '<span class="text-muted">—</span>'; ?>
                        </td>
                        <td class="aa-col-prov"><?php echo $e($row['student_province'] ?? '—'); ?></td>
                        <td class="aa-col-center">
                            <?php if ($isNorth): ?>
                                <span class="aa-region-north">Northern</span>
                            <?php else: ?>
                                <span class="aa-region-other">Other</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($usesLanguage): ?>
                        <td class="aa-col-center"><span class="aa-medium-chip"><?php echo $e($row['medium'] ?? $medium); ?></span></td>
                        <?php endif; ?>
                        <td class="aa-col-marks"><strong><?php echo $e($fmt($row['marks_num'] ?? $row['exam_marks'] ?? '')); ?></strong></td>
                        <td class="aa-col-marks"><?php echo $e($fmt($row['cutoff_applied'] ?? null)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php if ($shown === 0): ?>
        <div class="aa-card"><p class="aa-empty mb-0"><?php
            if ($listFiltered) {
                echo 'No qualifying students for the selected department / course. Set a cutoff above, or clear the filter.';
            } else {
                echo 'Set at least one cutoff above, then this list will show students whose exam marks meet the minimum.';
            }
        ?></p></div>
    <?php endif; ?>
</div>
<script>
(function () {
    var dept = document.getElementById('aa_list_dept');
    var course = document.getElementById('aa_list_course');
    if (!dept || !course) return;
    function filterCourses() {
        var d = dept.value;
        var opts = course.querySelectorAll('option[data-dept]');
        var keep = course.value;
        var visibleKeep = !keep;
        opts.forEach(function (opt) {
            var show = !d || opt.getAttribute('data-dept') === d;
            opt.hidden = !show;
            opt.disabled = !show;
            if (show && opt.value === keep) visibleKeep = true;
        });
        if (keep && !visibleKeep) {
            course.value = '';
        }
    }
    dept.addEventListener('change', filterCourses);
    filterCourses();
})();
</script>
