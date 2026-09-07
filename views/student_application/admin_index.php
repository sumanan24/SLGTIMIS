<?php
/** @var string|null $filter_level '04', '05', or null */
$filter_level = isset($filter_level) && ($filter_level === '04' || $filter_level === '05') ? $filter_level : null;
/** @var list<array<string, mixed>> $applications_new */
$applications_new = $applications_new ?? [];
/** @var list<array<string, mixed>> $applications_approved */
$applications_approved = $applications_approved ?? [];
/** @var list<array<string, mixed>> $applications_rejected */
$applications_rejected = $applications_rejected ?? [];
/** @var bool $can_delete */
$can_delete = (bool) ($can_delete ?? false);
/** @var bool $can_edit ADM / system admin — edit application fields */
$can_edit = (bool) ($can_edit ?? false);
/** @var bool $can_update_rejection_reason SAO / ADM — edit rejection reason on rejected tab */
$can_update_rejection_reason = (bool) ($can_update_rejection_reason ?? false);
/** @var string $update_reason_action POST URL for rejection reason updates */
$update_reason_action = isset($update_reason_action) ? (string) $update_reason_action : '';
/** @var string $rejection_reason_return_path Relative redirect after updating reason (no leading slash) */
$rejection_reason_return_path = isset($rejection_reason_return_path) ? (string) $rejection_reason_return_path : 'student-applications?tab=rejected';
/** @var array{url: string, display: string}|null $staff_whatsapp */
$staff_whatsapp = isset($staff_whatsapp) && is_array($staff_whatsapp) && !empty($staff_whatsapp['url'])
    ? $staff_whatsapp
    : null;
/** @var array{total: int, by_status?: array{new: int, approved: int, rejected?: int}, by_level: list<array{level: string, count: int}>, by_district: list<array{label: string, count: int}>, by_course: list<array{label: string, count: int}>, by_department: list<array{label: string, count: int}>, by_gender: list<array{label: string, count: int}>} $dashboard_stats */
$dashboard_stats = $dashboard_stats ?? ['total' => 0, 'by_status' => ['new' => 0, 'approved' => 0, 'rejected' => 0], 'by_level' => [], 'by_district' => [], 'by_course' => [], 'by_department' => [], 'by_gender' => []];
/** @var int $per_page */
$per_page = (int) ($per_page ?? 20);
/** @var int $page_new */
$page_new = max(1, (int) ($page_new ?? 1));
/** @var int $page_approved */
$page_approved = max(1, (int) ($page_approved ?? 1));
/** @var int $page_rejected */
$page_rejected = max(1, (int) ($page_rejected ?? 1));
/** @var int $count_new */
$count_new = (int) ($count_new ?? 0);
/** @var int $count_approved */
$count_approved = (int) ($count_approved ?? 0);
/** @var int $count_rejected */
$count_rejected = (int) ($count_rejected ?? 0);
/** @var int $max_page_new */
$max_page_new = max(1, (int) ($max_page_new ?? 1));
/** @var int $max_page_approved */
$max_page_approved = max(1, (int) ($max_page_approved ?? 1));
/** @var int $max_page_rejected */
$max_page_rejected = max(1, (int) ($max_page_rejected ?? 1));
/** @var string $active_tab 'new', 'approved', or 'rejected' */
$active_tab = strtolower(trim((string) ($active_tab ?? 'new')));
$active_tab = in_array($active_tab, ['approved', 'rejected'], true) ? $active_tab : 'new';

/** @var string $active_view 'dashboard' or 'table' */
$active_view = strtolower(trim((string) ($active_view ?? 'table')));
$active_view = $active_view === 'dashboard' ? 'dashboard' : 'table';

/** @var string|null $filter_department_id */
$filter_department_id = isset($filter_department_id) && trim((string) $filter_department_id) !== '' ? trim((string) $filter_department_id) : null;
/** @var string|null $filter_course_id */
$filter_course_id = isset($filter_course_id) && trim((string) $filter_course_id) !== '' ? trim((string) $filter_course_id) : null;
/** @var int $filter_course_priority 1, 2, or 3 (primary active slot for table highlight fallback) */
$filter_course_priority = (int) ($filter_course_priority ?? 1);
$filter_course_priority = in_array($filter_course_priority, [1, 2, 3], true) ? $filter_course_priority : 1;
/** @var array<int, array{dept_id: ?string, course_id: ?string}> $filter_choice_filters */
$filter_choice_filters = isset($filter_choice_filters) && is_array($filter_choice_filters) ? $filter_choice_filters : [
    1 => ['dept_id' => null, 'course_id' => null],
    2 => ['dept_id' => null, 'course_id' => null],
    3 => ['dept_id' => null, 'course_id' => null],
];
foreach ([1, 2, 3] as $_n) {
    if (!isset($filter_choice_filters[$_n]) || !is_array($filter_choice_filters[$_n])) {
        $filter_choice_filters[$_n] = ['dept_id' => null, 'course_id' => null];
    }
    $d = trim((string) ($filter_choice_filters[$_n]['dept_id'] ?? ''));
    $c = trim((string) ($filter_choice_filters[$_n]['course_id'] ?? ''));
    $filter_choice_filters[$_n] = [
        'dept_id' => $d !== '' ? $d : null,
        'course_id' => $c !== '' ? $c : null,
    ];
}
/** @var list<int> $filter_active_choices */
$filter_active_choices = isset($filter_active_choices) && is_array($filter_active_choices)
    ? array_values(array_filter(array_map('intval', $filter_active_choices), static function ($n) {
        return in_array($n, [1, 2, 3], true);
    }))
    : [];
if ($filter_active_choices === []) {
    foreach ([1, 2, 3] as $_n) {
        if (($filter_choice_filters[$_n]['dept_id'] ?? null) !== null || ($filter_choice_filters[$_n]['course_id'] ?? null) !== null) {
            $filter_active_choices[] = $_n;
        }
    }
}
/** @var string|null $filter_language Tamil, Sinhala, English, or null */
$filter_language = isset($filter_language) && $filter_language !== '' ? (string) $filter_language : null;
if ($filter_language !== null && !in_array($filter_language, StudentApplicationModel::STAFF_LANGUAGE_FILTER_VALUES, true)) {
    $filter_language = null;
}
/** @var list<array{department_id: string, department_name: string}> $filter_departments */
$filter_departments = $filter_departments ?? [];
/** @var list<array{course_id: string, course_name: string}> $filter_courses */
$filter_courses = $filter_courses ?? [];
/** @var array<int, list<array{course_id: string, course_name: string}>> $filter_courses_by_choice */
$filter_courses_by_choice = isset($filter_courses_by_choice) && is_array($filter_courses_by_choice) ? $filter_courses_by_choice : [
    1 => $filter_courses,
    2 => [],
    3 => [],
];
/** @var string $ajax_table_url JSON endpoint for NIC-filtered table refresh */
$ajax_table_url = isset($ajax_table_url) && trim((string) $ajax_table_url) !== '' ? trim((string) $ajax_table_url) : '';

if (defined('BASE_PATH') && is_file(BASE_PATH . '/models/StudentModel.php')) {
    require_once BASE_PATH . '/models/StudentModel.php';
}
if (!class_exists('StudentApplicationModel', false) && defined('BASE_PATH')) {
    require_once BASE_PATH . '/models/StudentApplicationModel.php';
}

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$appBase = rtrim(APP_URL, '/');
if ($ajax_table_url === '') {
    $ajax_table_url = $appBase . '/student-applications/ajax-table';
}
$viewUrl = static function (int $id) use ($appBase, $esc): string {
    return $esc($appBase . '/student-applications/view?id=' . $id);
};
$editUrl = static function (int $id) use ($appBase, $esc): string {
    return $esc($appBase . '/student-applications/edit?id=' . $id);
};
$deleteAction = $esc($appBase . '/student-applications/delete');

$formatSubmitted = static function (?string $createdAt) use ($esc): array {
    if ($createdAt === null || trim($createdAt) === '') {
        return ['order' => '', 'display' => ''];
    }
    $raw = trim($createdAt);
    $ts = strtotime($raw);
    $display = $ts ? date('Y-m-d H:i', $ts) : $raw;
    return ['order' => $esc($raw), 'display' => $esc($display)];
};

$listBase = $appBase . '/student-applications';
$appendChoiceQuery = static function (array $q, array $choices): array {
    unset($q['prio'], $q['dept'], $q['course'], $q['dept1'], $q['dept2'], $q['dept3']);
    foreach ([1, 2, 3] as $n) {
        unset($q['dept' . $n]);
        $slot = is_array($choices[$n] ?? null) ? $choices[$n] : [];
        $course = trim((string) ($slot['course_id'] ?? ''));
        if ($course !== '') {
            $q['course' . $n] = $course;
        } else {
            unset($q['course' . $n]);
        }
    }

    return $q;
};
$makeListQuery = static function (?string $level, string $tab, int $pn, int $pa, int $pr, ?array $choices = null, ?string $viewOverride = null, ?string $language = null) use ($active_view, $filter_choice_filters, $filter_language, $appendChoiceQuery): array {
    $tab = in_array($tab, ['approved', 'rejected'], true) ? $tab : 'new';
    $q = ['tab' => $tab];
    if ($level === '04' || $level === '05') {
        $q['level'] = $level;
    }
    if ($tab === 'new' && $pn > 1) {
        $q['pn'] = $pn;
    }
    if ($tab === 'approved' && $pa > 1) {
        $q['pa'] = $pa;
    }
    if ($tab === 'rejected' && $pr > 1) {
        $q['pr'] = $pr;
    }
    $langVal = $language;
    if ($langVal === null) {
        $langVal = $filter_language;
    }
    if ($langVal !== null && $langVal !== '') {
        $q['lang'] = $langVal;
    }
    $effView = $viewOverride !== null ? $viewOverride : $active_view;
    if ($effView === 'dashboard') {
        $q['view'] = 'dashboard';
    }
    $useChoices = is_array($choices) ? $choices : $filter_choice_filters;

    return $appendChoiceQuery($q, $useChoices);
};
$withChoiceCourse = static function (array $choices, int $n, ?string $courseId): array {
    $out = $choices;
    foreach ([1, 2, 3] as $i) {
        if (!isset($out[$i]) || !is_array($out[$i])) {
            $out[$i] = ['dept_id' => null, 'course_id' => null];
        }
    }
    $cid = ($courseId !== null && trim($courseId) !== '') ? trim($courseId) : null;
    $out[$n] = [
        'dept_id' => null,
        'course_id' => $cid,
    ];
    // Same course cannot be used on more than one choice filter.
    if ($cid !== null) {
        foreach ([1, 2, 3] as $i) {
            if ($i === $n) {
                continue;
            }
            if (($out[$i]['course_id'] ?? null) === $cid) {
                $out[$i] = ['dept_id' => null, 'course_id' => null];
            }
        }
    }

    return $out;
};
$buildListUrl = static function (?string $level, string $tab, int $pn = 1, int $pa = 1, int $pr = 1) use ($listBase, $esc, $makeListQuery): string {
    return $esc($listBase . '?' . http_build_query($makeListQuery($level, $tab, $pn, $pa, $pr)));
};
$listUrlFromParts = static function (array $q) use ($listBase, $esc): string {
    return $esc($listBase . '?' . http_build_query($q));
};

$excelBase = $appBase . '/student-applications/export-excel';
$excelUrl = static function (?string $status, ?string $level) use ($excelBase, $esc, $filter_choice_filters, $filter_language, $appendChoiceQuery): string {
    $q = [];
    if ($status === 'new' || $status === 'approved' || $status === 'rejected') {
        $q['status'] = $status;
    }
    if ($level === '04' || $level === '05') {
        $q['level'] = $level;
    }
    if ($filter_language !== null && $filter_language !== '') {
        $q['lang'] = $filter_language;
    }
    $q = $appendChoiceQuery($q, $filter_choice_filters);
    $qs = http_build_query($q);
    return $esc($excelBase . ($qs !== '' ? '?' . $qs : ''));
};

$dashPdfBase = $appBase . '/student-applications/export-dashboard-pdf';
$dashPdfUrl = static function (string $block, ?int $choice = null) use ($dashPdfBase, $esc, $filter_level, $filter_choice_filters, $filter_language, $appendChoiceQuery): string {
    $q = ['block' => $block];
    if ($choice !== null && in_array($choice, [1, 2, 3], true)) {
        $q['choice'] = (string) $choice;
    }
    if ($filter_level === '04' || $filter_level === '05') {
        $q['level'] = $filter_level;
    }
    if ($filter_language !== null && $filter_language !== '') {
        $q['lang'] = $filter_language;
    }
    $q = $appendChoiceQuery($q, $filter_choice_filters);

    return $esc($dashPdfBase . '?' . http_build_query($q));
};

$total = (int) ($dashboard_stats['total'] ?? 0);
$byStatus = $dashboard_stats['by_status'] ?? ['new' => 0, 'approved' => 0, 'rejected' => 0];
$byLevel = $dashboard_stats['by_level'] ?? [];
$byDistrict = $dashboard_stats['by_district'] ?? [];
$byCourse = $dashboard_stats['by_course'] ?? [];
$byDepartment = $dashboard_stats['by_department'] ?? [];
$byGender = $dashboard_stats['by_gender'] ?? [];
$byCoursePriority = $dashboard_stats['by_course_priority'] ?? [
    1 => ['course' => [], 'department' => []],
    2 => ['course' => [], 'department' => []],
    3 => ['course' => [], 'department' => []],
];
$prioLabels = [1 => '1st choice', 2 => '2nd choice', 3 => '3rd choice'];
$saAdminCss = htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/student-applications-admin.css', ENT_QUOTES, 'UTF-8');

/** @return list<array{label: string, count: int}> */
$saDashCapRows = static function (array $rows, int $max = 10): array {
    if (count($rows) <= $max) {
        return $rows;
    }

    return array_slice($rows, 0, $max);
};
$chartDistrictRaw = $byDistrict;
usort($chartDistrictRaw, static function (array $a, array $b): int {
    return ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0));
});
$chartDistrict = $saDashCapRows($chartDistrictRaw, 40);
$chartCourse = $saDashCapRows($byCourse, 40);
$chartDepartment = $saDashCapRows($byDepartment, 40);
$chartGenderRaw = $byGender;
usort($chartGenderRaw, static function (array $a, array $b): int {
    return ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0));
});
$chartGender = $saDashCapRows($chartGenderRaw, 40);

$filterContextSuffix = '';
$ctxParts = [];
if ($filter_level !== null) {
    $ctxParts[] = 'NVQ Level ' . $esc($filter_level);
}
foreach ([1, 2, 3] as $n) {
    $slot = $filter_choice_filters[$n] ?? [];
    $cid = trim((string) ($slot['course_id'] ?? ''));
    if ($cid === '') {
        continue;
    }
    $cname = $cid;
    foreach (($filter_courses_by_choice[$n] ?? $filter_courses) as $fc) {
        if ((string) ($fc['course_id'] ?? '') === $cid) {
            $cname = trim((string) ($fc['course_name'] ?? $cid));
            break;
        }
    }
    $ctxParts[] = ($prioLabels[$n] ?? ('Choice ' . $n)) . ': ' . $esc($cname);
}
if ($filter_language !== null && $filter_language !== '') {
    $ctxParts[] = $esc($filter_language);
}
if ($ctxParts !== []) {
    $filterContextSuffix = ' · ' . implode(' · ', $ctxParts);
}
?>
<link rel="stylesheet" href="<?php echo $saAdminCss; ?>?v=27">
<div class="sa-admin-page sa-student-apps-index container-fluid py-3 px-lg-4<?php echo $active_view === 'dashboard' ? ' sa-page-dashboard' : ''; ?>">
    <header class="sa-apps-page-header mb-4 pb-2 border-bottom<?php echo $active_view === 'dashboard' ? ' sa-apps-page-header--dash' : ''; ?>">
        <h1 class="h4 mb-0 fw-semibold text-dark"><i class="fas fa-file-alt me-2 text-primary" aria-hidden="true"></i>Online applications</h1>
    </header>

    <section class="card shadow-sm border-0 sa-apps-card mb-4" aria-labelledby="sa-apps-viewnav-heading">
        <div class="card-header bg-white border-bottom px-0 pt-0 pb-0">
            <h2 class="visually-hidden" id="sa-apps-viewnav-heading">Applications layout</h2>
            <ul class="nav nav-pills sa-apps-viewnav flex-nowrap px-3 pt-2 pb-2 gap-1" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap py-2 px-3<?php echo $active_view === 'dashboard' ? ' active' : ''; ?>"
                       href="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, null, 'dashboard')); ?>"
                       <?php echo $active_view === 'dashboard' ? 'aria-current="page"' : ''; ?>>
                        <i class="fas fa-chart-pie me-1" aria-hidden="true"></i>Application dashboard
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap py-2 px-3<?php echo $active_view === 'table' ? ' active' : ''; ?>"
                       href="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, null, 'table')); ?>"
                       <?php echo $active_view === 'table' ? 'aria-current="page"' : ''; ?>>
                        <i class="fas fa-table me-1" aria-hidden="true"></i>Application table
                    </a>
                </li>
            </ul>
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 px-3 py-3 border-top border-bottom sa-apps-toolbar bg-light">
                <div class="d-flex flex-wrap align-items-end gap-3 gap-lg-4">
                    <div class="d-flex flex-column gap-1 sa-apps-filter-field">
                        <label for="saNvqLevelSelect" class="form-label small text-secondary text-uppercase mb-0 fw-semibold">NVQ level</label>
                        <select id="saNvqLevelSelect" class="form-select form-select-sm sa-nvq-level-select" data-sa-filter-nav="1" aria-label="Filter by NVQ level">
                            <option value="<?php echo $buildListUrl(null, $active_tab, 1, 1, 1); ?>"<?php echo $filter_level === null ? ' selected' : ''; ?>>All levels</option>
                            <option value="<?php echo $buildListUrl('04', $active_tab, 1, 1, 1); ?>"<?php echo $filter_level === '04' ? ' selected' : ''; ?>>Level 04</option>
                            <option value="<?php echo $buildListUrl('05', $active_tab, 1, 1, 1); ?>"<?php echo $filter_level === '05' ? ' selected' : ''; ?>>Level 05</option>
                        </select>
                    </div>
                    <?php
                    $filterCoursesList = $filter_courses;
                    if ($filterCoursesList === [] && isset($filter_courses_by_choice[1]) && is_array($filter_courses_by_choice[1])) {
                        $filterCoursesList = $filter_courses_by_choice[1];
                    }
                    // Map course_id → lowercase name for cross-slot exclusion.
                    $courseNameById = [];
                    foreach ($filterCoursesList as $fcMap) {
                        $mid = trim((string) ($fcMap['course_id'] ?? ''));
                        $mname = preg_replace('/\s+/u', ' ', trim((string) ($fcMap['course_name'] ?? '')));
                        if ($mid !== '' && $mname !== '') {
                            $courseNameById[$mid] = function_exists('mb_strtolower') ? mb_strtolower($mname, 'UTF-8') : strtolower($mname);
                        }
                    }
                    foreach ([1 => '1st choice course', 2 => '2nd choice course', 3 => '3rd choice course'] as $choiceN => $choiceLbl):
                        $slotCourse = isset($filter_choice_filters[$choiceN]['course_id'])
                            ? trim((string) $filter_choice_filters[$choiceN]['course_id'])
                            : '';
                        if ($slotCourse === '') {
                            $slotCourse = null;
                        }
                        // Exclude courses already picked on the other choice filters (by id and by name).
                        $excludeCourseIds = [];
                        $excludeCourseNames = [];
                        foreach ([1, 2, 3] as $otherN) {
                            if ($otherN === $choiceN) {
                                continue;
                            }
                            $otherCid = trim((string) ($filter_choice_filters[$otherN]['course_id'] ?? ''));
                            if ($otherCid === '') {
                                continue;
                            }
                            $excludeCourseIds[$otherCid] = true;
                            if (isset($courseNameById[$otherCid])) {
                                $excludeCourseNames[$courseNameById[$otherCid]] = true;
                            }
                        }
                    ?>
                    <div class="d-flex flex-column gap-1 sa-apps-filter-field sa-apps-choice-filter" role="group" aria-label="<?php echo $esc($choiceLbl); ?>">
                        <?php if ($choiceN === 1): ?>
                        <span class="visually-hidden">1st choice course filter. That course is then removed from the 2nd and 3rd choice lists.</span>
                        <?php endif; ?>
                        <label for="saCourseSelect<?php echo (int) $choiceN; ?>" class="form-label small text-secondary text-uppercase mb-0 fw-semibold"><?php echo $esc($choiceLbl); ?></label>
                        <select id="saCourseSelect<?php echo (int) $choiceN; ?>" class="form-select form-select-sm sa-app-filter-select" data-sa-filter-nav="1" aria-label="<?php echo $esc($choiceLbl); ?>">
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $withChoiceCourse($filter_choice_filters, $choiceN, null))); ?>"<?php echo $slotCourse === null ? ' selected' : ''; ?>>All courses</option>
                            <?php foreach ($filterCoursesList as $fc): ?>
                            <?php
                            $cid = trim((string) ($fc['course_id'] ?? ''));
                            $cname = preg_replace('/\s+/u', ' ', trim((string) ($fc['course_name'] ?? '')));
                            if ($cid === '' || $cname === '') {
                                continue;
                            }
                            $cnameKey = function_exists('mb_strtolower') ? mb_strtolower($cname, 'UTF-8') : strtolower($cname);
                            $isCurrent = ($slotCourse !== null && $slotCourse === $cid);
                            if (!$isCurrent && (isset($excludeCourseIds[$cid]) || isset($excludeCourseNames[$cnameKey]))) {
                                continue; // e.g. 1st=4AT → hide Automobile Technician from 2nd/3rd lists
                            }
                            ?>
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $withChoiceCourse($filter_choice_filters, $choiceN, $cid))); ?>"<?php echo $isCurrent ? ' selected' : ''; ?>><?php echo $esc($cname); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($choiceN > 1 && $excludeCourseIds !== []): ?>
                        <span class="form-text small text-muted">Other courses only (not the 1st choice)</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="d-flex flex-column gap-1 sa-apps-filter-field">
                        <label for="saLangSelect" class="form-label small text-secondary text-uppercase mb-0 fw-semibold">Language</label>
                        <select id="saLangSelect" class="form-select form-select-sm sa-app-filter-select" data-sa-filter-nav="1" aria-label="Filter by applicant language">
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, null, null, '')); ?>"<?php echo $filter_language === null ? ' selected' : ''; ?>>All languages</option>
                            <?php foreach (StudentApplicationModel::STAFF_LANGUAGE_FILTER_VALUES as $langOpt): ?>
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, null, null, $langOpt)); ?>"<?php echo $filter_language === $langOpt ? ' selected' : ''; ?>><?php echo $esc($langOpt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($active_view === 'table'): ?>
                    <div class="d-flex flex-column gap-1 sa-apps-filter-field sa-apps-filter-nic">
                        <label for="saNicFilter" class="form-label small text-secondary text-uppercase mb-0 fw-semibold">NIC</label>
                        <input type="search" id="saNicFilter" class="form-control form-control-sm" maxlength="20" placeholder="Filter as you type" autocomplete="off" spellcheck="false" aria-label="Filter table by NIC" style="min-width: 9rem;">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    <?php if ($staff_whatsapp !== null): ?>
                    <a href="<?php echo $esc($staff_whatsapp['url']); ?>" class="btn btn-sm btn-wa-outline" target="_blank" rel="noopener noreferrer"
                       title="Open WhatsApp chat — <?php echo $esc($staff_whatsapp['display']); ?>">
                        <i class="fab fa-whatsapp me-1" aria-hidden="true"></i>
                        WhatsApp <span class="small opacity-90"><?php echo $esc($staff_whatsapp['display']); ?></span>
                    </a>
                    <?php endif; ?>
                    <div class="dropdown flex-shrink-0">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-excel me-1 text-success" aria-hidden="true"></i>Export Excel
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">All levels</h6></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl(null, null); ?>">All applications</a></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl('new', null); ?>">New only</a></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl('approved', null); ?>">Approved only</a></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl('rejected', null); ?>">Rejected only</a></li>
                        <?php if ($filter_level === '04' || $filter_level === '05'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Level <?php echo $esc($filter_level); ?> only</h6></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl(null, $filter_level); ?>">All statuses</a></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl('new', $filter_level); ?>">New</a></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl('approved', $filter_level); ?>">Approved</a></li>
                        <li><a class="dropdown-item sa-excel-export-item" href="<?php echo $excelUrl('rejected', $filter_level); ?>">Rejected</a></li>
                        <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php if ($active_view === 'table'): ?>
            <h2 class="visually-hidden" id="sa-apps-subnav-heading">Applications by status</h2>
            <ul class="nav nav-tabs sa-apps-subnav px-3 border-bottom-0 flex-nowrap" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap<?php echo $active_tab === 'new' ? ' active' : ''; ?>"
                       href="<?php echo $buildListUrl($filter_level, 'new', 1, 1, 1); ?>"
                       <?php echo $active_tab === 'new' ? 'aria-current="page"' : ''; ?>>
                        <span class="badge bg-secondary me-1" id="saTabBadgeNew"><?php echo $count_new; ?></span>
                        New
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap<?php echo $active_tab === 'approved' ? ' active' : ''; ?>"
                       href="<?php echo $buildListUrl($filter_level, 'approved', 1, 1, 1); ?>"
                       <?php echo $active_tab === 'approved' ? 'aria-current="page"' : ''; ?>>
                        <span class="badge bg-success me-1" id="saTabBadgeApproved"><?php echo $count_approved; ?></span>
                        Approved
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap<?php echo $active_tab === 'rejected' ? ' active' : ''; ?>"
                       href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, 1); ?>"
                       <?php echo $active_tab === 'rejected' ? 'aria-current="page"' : ''; ?>>
                        <span class="badge bg-danger me-1" id="saTabBadgeRejected"><?php echo $count_rejected; ?></span>
                        Rejected
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
        <div class="card-body sa-apps-card-body pt-0 px-0 pb-3">
            <?php if ($active_view === 'dashboard'): ?>
            <?php
            $renderDashBreakdownTable = static function (
                string $headingId,
                string $iconClass,
                string $title,
                array $rows,
                string $defaultBarKey,
                callable $esc,
                string $sectionExtraClass = '',
                ?string $pdfUrl = null
            ): void {
                $max = 1;
                foreach ($rows as $r) {
                    $max = max($max, (int) ($r['count'] ?? 0));
                }
                $secClass = 'sa-dash-panel' . ($sectionExtraClass !== '' ? ' ' . trim($sectionExtraClass) : '');
                ?>
            <section class="<?php echo $esc($secClass); ?>" aria-labelledby="<?php echo $esc($headingId); ?>">
                <div class="sa-dash-panel-head" id="<?php echo $esc($headingId); ?>">
                    <span class="sa-dash-panel-head-title">
                        <i class="<?php echo $esc($iconClass); ?> me-1" aria-hidden="true"></i><?php echo $esc($title); ?>
                    </span>
                    <?php if ($pdfUrl !== null && $pdfUrl !== ''): ?>
                    <a href="<?php echo $pdfUrl; ?>" class="btn btn-sm btn-outline-secondary sa-dash-pdf-btn" title="Download PDF" download>
                        <i class="fas fa-file-pdf" aria-hidden="true"></i><span class="visually-hidden"> Download PDF</span>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="sa-dash-panel-body">
                    <table class="table table-sm sa-dash-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Category</th>
                                <th scope="col" class="text-end sa-dash-th-count">Count</th>
                                <th scope="col" class="sa-dash-th-bar">Relative share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rows === []): ?>
                            <tr>
                                <td colspan="3" class="text-muted text-center py-3 small">No rows for this filter.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <?php
                                    $lbl = (string) ($row['label'] ?? '');
                                    $cnt = (int) ($row['count'] ?? 0);
                                    $pct = $max > 0 ? (int) min(100, (int) round(100 * $cnt / $max)) : 0;
                                    $barKey = trim((string) ($row['bar'] ?? $defaultBarKey));
                                    if ($barKey === '') {
                                        $barKey = $defaultBarKey;
                                    }
                                    ?>
                            <tr>
                                <td class="sa-dash-td-label text-break" title="<?php echo $esc($lbl); ?>"><?php echo $esc($lbl); ?></td>
                                <td class="text-end fw-semibold sa-dash-td-count text-nowrap"><?php echo $cnt; ?></td>
                                <td class="sa-dash-td-bar">
                                    <div class="sa-dash-bar-track" role="img" aria-label="Bar: <?php echo $pct; ?> percent within this block">
                                        <div class="sa-dash-bar-fill sa-dash-bar-fill--<?php echo $esc($barKey); ?>" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
                <?php
            };
            $statusRowsTable = [
                ['label' => 'New', 'count' => (int) ($byStatus['new'] ?? 0), 'bar' => 'status-new'],
                ['label' => 'Approved', 'count' => (int) ($byStatus['approved'] ?? 0), 'bar' => 'status-approved'],
                ['label' => 'Rejected', 'count' => (int) ($byStatus['rejected'] ?? 0), 'bar' => 'status-rejected'],
            ];
            ?>
            <div class="sa-dashboard-root" id="saDashboardRoot">
                <?php if ($total === 0): ?>
                <div class="sa-dashboard-empty d-flex flex-column align-items-center justify-content-center text-center p-4">
                    <div class="sa-dashboard-empty-icon mb-3" aria-hidden="true"><i class="fas fa-chart-pie"></i></div>
                    <p class="fw-semibold text-secondary mb-1">No applications match these filters</p>
                    <p class="small text-muted mb-0">Try clearing NVQ level, department, or course filters, or switch to the application table when new submissions arrive.</p>
                </div>
                <?php else: ?>
                <div class="sa-dashboard-row-kpi">
                    <div class="sa-dash-kpi-card">
                        <div class="sa-dash-kpi-total">
                            <span class="sa-dash-kpi-label">Total</span>
                            <span class="sa-dash-kpi-value"><?php echo $total; ?></span>
                        </div>
                        <div class="sa-dash-kpi-meta">
                            <span class="sa-dash-pill sa-dash-pill--new"><span class="sa-dash-pill-dot"></span>New <?php echo (int) ($byStatus['new'] ?? 0); ?></span>
                            <span class="sa-dash-pill sa-dash-pill--ok"><span class="sa-dash-pill-dot"></span>Approved <?php echo (int) ($byStatus['approved'] ?? 0); ?></span>
                            <span class="sa-dash-pill sa-dash-pill--no"><span class="sa-dash-pill-dot"></span>Rejected <?php echo (int) ($byStatus['rejected'] ?? 0); ?></span>
                            <?php if ($byLevel !== []): ?>
                            <div class="sa-dash-levels w-100 mt-1 pt-1 border-top border-light-subtle">
                                <?php foreach ($byLevel as $lv): ?>
                                <span class="badge rounded-pill bg-light text-dark border fw-normal"><?php echo $esc((string) ($lv['level'] ?? '')); ?>: <?php echo (int) ($lv['count'] ?? 0); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $renderDashBreakdownTable('sa-dash-status', 'fas fa-list-check', 'Application status', $statusRowsTable, 'status-new', $esc, 'sa-dash-panel--kpi-side', $dashPdfUrl('status')); ?>
                </div>
                <div class="sa-dashboard-row-charts" role="group" aria-label="Application counts by course priority, district, and gender">
                    <?php foreach ([1, 2, 3] as $prioN): ?>
                        <?php
                        $prioCourseRows = $saDashCapRows($byCoursePriority[$prioN]['course'] ?? [], 15);
                        $prioDeptRows = $saDashCapRows($byCoursePriority[$prioN]['department'] ?? [], 15);
                        $prioBar = $prioN === 1 ? 'course' : ($prioN === 2 ? 'dept' : 'district');
                        ?>
                    <div class="sa-dash-priority-block">
                        <h3 class="sa-dash-priority-title h6 text-primary fw-bold mb-2">
                            <i class="fas fa-list-ol me-1" aria-hidden="true"></i><?php echo $esc($prioLabels[$prioN]); ?>
                        </h3>
                        <div class="sa-dash-priority-grid">
                            <?php $renderDashBreakdownTable('sa-dash-course-p' . $prioN, 'fas fa-graduation-cap', 'Courses', $prioCourseRows, $prioBar, $esc, 'sa-dash-panel--compact', $dashPdfUrl('course', $prioN)); ?>
                            <?php $renderDashBreakdownTable('sa-dash-dept-p' . $prioN, 'fas fa-building', 'Departments', $prioDeptRows, 'dept', $esc, 'sa-dash-panel--compact', $dashPdfUrl('department', $prioN)); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php $renderDashBreakdownTable('sa-dash-district', 'fas fa-map-marker-alt', 'By district', $chartDistrict, 'district', $esc, '', $dashPdfUrl('district')); ?>
                    <?php $renderDashBreakdownTable('sa-dash-gender', 'fas fa-venus-mars', 'By gender', $chartGender, 'gender', $esc, '', $dashPdfUrl('gender')); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($active_view === 'table'): ?>
            <div id="saAppsTableMount" class="sa-apps-table-mount" role="region" aria-live="polite" aria-busy="false"
                 data-sa-ajax="<?php echo $esc($ajax_table_url); ?>"
                 data-sa-tab="<?php echo $esc($active_tab); ?>"
                 data-sa-pn="<?php echo (int) $page_new; ?>"
                 data-sa-pa="<?php echo (int) $page_approved; ?>"
                 data-sa-pr="<?php echo (int) $page_rejected; ?>"
                 <?php if ($filter_level !== null): ?>data-sa-level="<?php echo $esc($filter_level); ?>"<?php endif; ?>
                 <?php foreach ([1, 2, 3] as $n): ?>
                 <?php $mountCourse = $filter_choice_filters[$n]['course_id'] ?? null; ?>
                 <?php if ($mountCourse !== null && $mountCourse !== ''): ?>data-sa-course<?php echo (int) $n; ?>="<?php echo $esc($mountCourse); ?>"<?php endif; ?>
                 <?php endforeach; ?>
                 <?php if ($filter_language !== null && $filter_language !== ''): ?>data-sa-lang="<?php echo $esc($filter_language); ?>"<?php endif; ?>>
                <?php
                $ajax_pagination = true;
                $rejection_reason_return_path = 'student-applications?' . http_build_query(
                    $makeListQuery($filter_level, 'rejected', $page_new, $page_approved, $page_rejected)
                );
                if ($update_reason_action === '') {
                    $update_reason_action = $appBase . '/student-applications/update-rejection-reason';
                }
                require BASE_PATH . '/views/student_application/admin_ajax_table_inner.php';
                ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
    (function () {
      document.querySelectorAll('[data-sa-filter-nav="1"]').forEach(function (el) {
        el.addEventListener('change', function () { if (this.value) window.location.href = this.value; });
      });
      var mount = document.getElementById('saAppsTableMount');
      var nic = document.getElementById('saNicFilter');
      if (!mount || !nic || !mount.getAttribute('data-sa-ajax')) return;
      var ajaxUrl = mount.getAttribute('data-sa-ajax');
      var abort;
      var debTimer;
      function buildQuery(resetPage) {
        if (resetPage) {
          mount.setAttribute('data-sa-pn', '1');
          mount.setAttribute('data-sa-pa', '1');
          mount.setAttribute('data-sa-pr', '1');
        }
        var p = new URLSearchParams();
        var tab = mount.getAttribute('data-sa-tab') || 'new';
        p.set('tab', tab);
        var lev = mount.getAttribute('data-sa-level');
        if (lev) p.set('level', lev);
        [1, 2, 3].forEach(function (n) {
          var course = mount.getAttribute('data-sa-course' + n);
          if (course) p.set('course' + n, course);
        });
        var lang = mount.getAttribute('data-sa-lang');
        if (lang) p.set('lang', lang);
        var nicVal = nic.value.trim();
        if (nicVal) p.set('nic', nicVal);
        if (tab === 'new') p.set('pn', mount.getAttribute('data-sa-pn') || '1');
        else if (tab === 'approved') p.set('pa', mount.getAttribute('data-sa-pa') || '1');
        else p.set('pr', mount.getAttribute('data-sa-pr') || '1');
        return p.toString();
      }
      function runFetch(resetPage) {
        if (abort) abort.abort();
        abort = new AbortController();
        mount.setAttribute('aria-busy', 'true');
        var q = buildQuery(resetPage);
        fetch(ajaxUrl + (q ? '?' + q : ''), { signal: abort.signal, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            mount.setAttribute('aria-busy', 'false');
            if (!j || !j.ok) return;
            var inner = mount.querySelector('.sa-apps-table-mount-inner');
            if (inner) inner.outerHTML = j.html;
            if (j.counts) {
              var bN = document.getElementById('saTabBadgeNew');
              var bA = document.getElementById('saTabBadgeApproved');
              var bR = document.getElementById('saTabBadgeRejected');
              if (bN) bN.textContent = String(j.counts.new);
              if (bA) bA.textContent = String(j.counts.approved);
              if (bR) bR.textContent = String(j.counts.rejected);
            }
          })
          .catch(function () { mount.setAttribute('aria-busy', 'false'); });
      }
      nic.addEventListener('input', function () {
        clearTimeout(debTimer);
        debTimer = setTimeout(function () { runFetch(true); }, 300);
      });
      mount.addEventListener('click', function (e) {
        var btn = e.target.closest('.sa-nic-ajax-pag');
        if (!btn || !mount.contains(btn) || btn.disabled) return;
        e.preventDefault();
        var tab = btn.getAttribute('data-sa-tab') || 'new';
        var pn = parseInt(btn.getAttribute('data-sa-pn'), 10) || 1;
        mount.setAttribute('data-sa-tab', tab);
        if (tab === 'new') mount.setAttribute('data-sa-pn', String(pn));
        else if (tab === 'approved') mount.setAttribute('data-sa-pa', String(pn));
        else mount.setAttribute('data-sa-pr', String(pn));
        runFetch(false);
      });
      document.querySelectorAll('a.sa-excel-export-item').forEach(function (a) {
        a.addEventListener('click', function (ev) {
          var inp = document.getElementById('saNicFilter');
          if (!inp) return;
          var v = inp.value.trim();
          if (!v) return;
          ev.preventDefault();
          var u = new URL(this.getAttribute('href'), window.location.href);
          u.searchParams.set('nic', v);
          window.location.href = u.pathname + u.search;
        });
      });
    })();
    </script>
</div>
