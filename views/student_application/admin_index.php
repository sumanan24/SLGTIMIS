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
/** @var array{url: string, display: string}|null $staff_whatsapp */
$staff_whatsapp = isset($staff_whatsapp) && is_array($staff_whatsapp) && !empty($staff_whatsapp['url'])
    ? $staff_whatsapp
    : null;
/** @var array{total: int, by_status?: array{new: int, approved: int, rejected?: int}, by_level: list<array{level: string, count: int}>, by_district: list<array{label: string, count: int}>, by_course: list<array{label: string, count: int}>, by_department: list<array{label: string, count: int}>} $dashboard_stats */
$dashboard_stats = $dashboard_stats ?? ['total' => 0, 'by_status' => ['new' => 0, 'approved' => 0, 'rejected' => 0], 'by_level' => [], 'by_district' => [], 'by_course' => [], 'by_department' => []];
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
/** @var list<array{department_id: string, department_name: string}> $filter_departments */
$filter_departments = $filter_departments ?? [];
/** @var list<array{course_id: string, course_name: string}> $filter_courses */
$filter_courses = $filter_courses ?? [];

if (defined('BASE_PATH') && is_file(BASE_PATH . '/models/StudentModel.php')) {
    require_once BASE_PATH . '/models/StudentModel.php';
}

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$appBase = rtrim(APP_URL, '/');
$viewUrl = static function (int $id) use ($appBase, $esc): string {
    return $esc($appBase . '/student-applications/view?id=' . $id);
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
$makeListQuery = static function (?string $level, string $tab, int $pn, int $pa, int $pr, ?string $deptId, ?string $courseId, ?string $viewOverride = null) use ($active_view): array {
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
    if ($deptId !== null && $deptId !== '') {
        $q['dept'] = $deptId;
    }
    if ($courseId !== null && $courseId !== '') {
        $q['course'] = $courseId;
    }
    $effView = $viewOverride !== null ? $viewOverride : $active_view;
    if ($effView === 'dashboard') {
        $q['view'] = 'dashboard';
    }
    return $q;
};
$buildListUrl = static function (?string $level, string $tab, int $pn = 1, int $pa = 1, int $pr = 1) use ($listBase, $esc, $makeListQuery, $filter_department_id, $filter_course_id): string {
    return $esc($listBase . '?' . http_build_query($makeListQuery($level, $tab, $pn, $pa, $pr, $filter_department_id, $filter_course_id, null)));
};
$listUrlFromParts = static function (array $q) use ($listBase, $esc): string {
    return $esc($listBase . '?' . http_build_query($q));
};

$excelBase = $appBase . '/student-applications/export-excel';
$excelUrl = static function (?string $status, ?string $level) use ($excelBase, $esc, $filter_department_id, $filter_course_id): string {
    $q = [];
    if ($status === 'new' || $status === 'approved' || $status === 'rejected') {
        $q['status'] = $status;
    }
    if ($level === '04' || $level === '05') {
        $q['level'] = $level;
    }
    if ($filter_department_id !== null && $filter_department_id !== '') {
        $q['dept'] = $filter_department_id;
    }
    if ($filter_course_id !== null && $filter_course_id !== '') {
        $q['course'] = $filter_course_id;
    }
    $qs = http_build_query($q);
    return $esc($excelBase . ($qs !== '' ? '?' . $qs : ''));
};

$total = (int) ($dashboard_stats['total'] ?? 0);
$byStatus = $dashboard_stats['by_status'] ?? ['new' => 0, 'approved' => 0, 'rejected' => 0];
$byLevel = $dashboard_stats['by_level'] ?? [];
$byDistrict = $dashboard_stats['by_district'] ?? [];
$byCourse = $dashboard_stats['by_course'] ?? [];
$byDepartment = $dashboard_stats['by_department'] ?? [];
$saAdminCss = htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/student-applications-admin.css', ENT_QUOTES, 'UTF-8');

$filterContextSuffix = '';
$ctxParts = [];
if ($filter_level !== null) {
    $ctxParts[] = 'NVQ Level ' . $esc($filter_level);
}
if ($filter_department_id !== null) {
    $ctxParts[] = 'Department';
}
if ($filter_course_id !== null) {
    $ctxParts[] = 'Course';
}
if ($ctxParts !== []) {
    $filterContextSuffix = ' · ' . implode(' · ', $ctxParts);
}

$renderAppTable = static function (
    array $rows,
    callable $esc,
    callable $viewUrl,
    string $deleteAction,
    bool $can_delete,
    callable $formatSubmitted,
    string $statusLabel,
    string $badgeClass,
    int $rowNumBase = 0
): void {
    ?>
    <div class="table-responsive sa-apps-table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle w-100 mb-0 sa-apps-table">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="text-end sa-apps-col-num">#</th>
                    <th scope="col">Level</th>
                    <th scope="col">Status</th>
                    <th scope="col">Full name</th>
                    <th scope="col">NIC</th>
                    <th scope="col">District</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Date sent</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="10" class="sa-apps-empty text-secondary text-center py-5 px-3">No <?php echo $esc($statusLabel); ?> applications<?php
                        if ($statusLabel === 'new') {
                            echo ' to review';
                        } elseif ($statusLabel === 'rejected') {
                            echo ' for this view';
                        }
                    ?>.</td>
                </tr>
                <?php else: ?>
                    <?php
                    $rowIx = 0;
                    foreach ($rows as $r):
                        $rowIx++;
                        $id = (int) ($r['application_id'] ?? 0);
                        $seq = $rowNumBase + $rowIx;
                        $submitted = $formatSubmitted(isset($r['created_at']) ? (string) $r['created_at'] : null);
                        $waDigits = StudentModel::digitsForWhatsAppMe($r);
                        ?>
                <tr>
                    <td class="text-muted text-end sa-apps-col-num"><?php echo (int) $seq; ?></td>
                    <td><?php echo $esc((string) ($r['application_level'] ?? '')); ?></td>
                    <td><span class="badge rounded-pill px-2 <?php echo $esc($badgeClass); ?>"><?php echo $esc(ucfirst($statusLabel)); ?></span></td>
                    <td><?php echo $esc((string) ($r['student_full_name'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_nic'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_district'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_email'] ?? '')); ?></td>
                    <td><?php echo $esc((string) ($r['student_phone'] ?? '')); ?></td>
                    <td data-order="<?php echo $submitted['order']; ?>"><?php echo $submitted['display']; ?></td>
                    <td>
                        <?php if ($can_delete): ?>
                        <form id="sa-app-del-<?php echo $id; ?>" method="post" action="<?php echo $deleteAction; ?>" class="d-none"
                              onsubmit="return confirm('Delete application #<?php echo $id; ?>? This will also remove uploaded documents on the server.');">
                            <input type="hidden" name="application_id" value="<?php echo $id; ?>">
                        </form>
                        <?php endif; ?>
                        <div class="btn-group btn-group-sm sa-apps-table-actions" role="group" aria-label="Application #<?php echo $id; ?> actions">
                            <a class="btn btn-outline-primary" href="<?php echo $viewUrl($id); ?>"
                               title="View application">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span class="visually-hidden"> View</span>
                            </a>
                            <?php if ($waDigits !== null): ?>
                            <a class="btn btn-wa-outline" href="<?php echo $esc('https://wa.me/' . $waDigits); ?>"
                               target="_blank" rel="noopener noreferrer"
                               title="WhatsApp <?php echo $esc($waDigits); ?>">
                                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                <span class="visually-hidden"> WhatsApp</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                            <button type="submit" form="sa-app-del-<?php echo $id; ?>" class="btn btn-outline-danger" title="Delete application">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                <span class="visually-hidden"> Delete</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>
<link rel="stylesheet" href="<?php echo $saAdminCss; ?>?v=13">
<div class="sa-admin-page sa-student-apps-index container-fluid py-3 px-lg-4">
    <header class="sa-apps-page-header mb-4 pb-2 border-bottom">
        <h1 class="h4 mb-0 fw-semibold text-dark"><i class="fas fa-file-alt me-2 text-primary" aria-hidden="true"></i>Online applications</h1>
    </header>

    <section class="card shadow-sm border-0 sa-apps-card mb-4" aria-labelledby="sa-apps-viewnav-heading">
        <div class="card-header bg-white border-bottom px-0 pt-0 pb-0">
            <h2 class="visually-hidden" id="sa-apps-viewnav-heading">Applications layout</h2>
            <ul class="nav nav-pills sa-apps-viewnav flex-nowrap px-3 pt-2 pb-2 gap-1" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap py-2 px-3<?php echo $active_view === 'dashboard' ? ' active' : ''; ?>"
                       href="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $filter_department_id, $filter_course_id, 'dashboard')); ?>"
                       <?php echo $active_view === 'dashboard' ? 'aria-current="page"' : ''; ?>>
                        <i class="fas fa-chart-pie me-1" aria-hidden="true"></i>Application dashboard
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap py-2 px-3<?php echo $active_view === 'table' ? ' active' : ''; ?>"
                       href="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $filter_department_id, $filter_course_id, 'table')); ?>"
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
                    <div class="d-flex flex-column gap-1 sa-apps-filter-field">
                        <label for="saDeptSelect" class="form-label small text-secondary text-uppercase mb-0 fw-semibold">Department</label>
                        <select id="saDeptSelect" class="form-select form-select-sm sa-app-filter-select" data-sa-filter-nav="1" aria-label="Filter by department (1st course choice)">
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, null, null)); ?>"<?php echo $filter_department_id === null ? ' selected' : ''; ?>>All departments</option>
                            <?php foreach ($filter_departments as $fd): ?>
                            <?php
                            $did = (string) ($fd['department_id'] ?? '');
                            if ($did === '') {
                                continue;
                            }
                            ?>
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $did, null)); ?>"<?php echo $filter_department_id === $did ? ' selected' : ''; ?>><?php echo $esc((string) ($fd['department_name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex flex-column gap-1 sa-apps-filter-field">
                        <label for="saCourseSelect" class="form-label small text-secondary text-uppercase mb-0 fw-semibold">Course</label>
                        <select id="saCourseSelect" class="form-select form-select-sm sa-app-filter-select" data-sa-filter-nav="1" aria-label="Filter by first course choice">
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $filter_department_id, null)); ?>"<?php echo $filter_course_id === null ? ' selected' : ''; ?>>All courses</option>
                            <?php foreach ($filter_courses as $fc): ?>
                            <?php
                            $cid = (string) ($fc['course_id'] ?? '');
                            if ($cid === '') {
                                continue;
                            }
                            ?>
                            <option value="<?php echo $listUrlFromParts($makeListQuery($filter_level, $active_tab, 1, 1, 1, $filter_department_id, $cid)); ?>"<?php echo $filter_course_id === $cid ? ' selected' : ''; ?>><?php echo $esc((string) ($fc['course_name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    <?php if ($staff_whatsapp !== null): ?>
                    <a href="<?php echo $esc($staff_whatsapp['url']); ?>" class="btn btn-sm btn-wa-outline" target="_blank" rel="noopener noreferrer"
                       title="Open WhatsApp chat — <?php echo $esc($staff_whatsapp['display']); ?>">
                        <i class="fab fa-whatsapp me-1" aria-hidden="true"></i>
                        WhatsApp <span class="small opacity-90"><?php echo $esc($staff_whatsapp['display']); ?></span>
                    </a>
                    <?php endif; ?>
                    <div class="dropdown flex-shrink-0 align-self-md-center">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-excel me-1 text-success" aria-hidden="true"></i>Export Excel
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">All levels</h6></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl(null, null); ?>">All applications</a></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl('new', null); ?>">New only</a></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl('approved', null); ?>">Approved only</a></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl('rejected', null); ?>">Rejected only</a></li>
                        <?php if ($filter_level === '04' || $filter_level === '05'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Level <?php echo $esc($filter_level); ?> only</h6></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl(null, $filter_level); ?>">All statuses</a></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl('new', $filter_level); ?>">New</a></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl('approved', $filter_level); ?>">Approved</a></li>
                        <li><a class="dropdown-item" href="<?php echo $excelUrl('rejected', $filter_level); ?>">Rejected</a></li>
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
                        <span class="badge bg-secondary me-1"><?php echo $count_new; ?></span>
                        New
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap<?php echo $active_tab === 'approved' ? ' active' : ''; ?>"
                       href="<?php echo $buildListUrl($filter_level, 'approved', 1, 1, 1); ?>"
                       <?php echo $active_tab === 'approved' ? 'aria-current="page"' : ''; ?>>
                        <span class="badge bg-success me-1"><?php echo $count_approved; ?></span>
                        Approved
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-nowrap<?php echo $active_tab === 'rejected' ? ' active' : ''; ?>"
                       href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, 1); ?>"
                       <?php echo $active_tab === 'rejected' ? 'aria-current="page"' : ''; ?>>
                        <span class="badge bg-danger me-1"><?php echo $count_rejected; ?></span>
                        Rejected
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
        <div class="card-body sa-apps-card-body pt-0 px-0 pb-3">
            <?php if ($active_view === 'dashboard'): ?>
            <p class="small text-muted mb-3">Summary counts for the current filters (NVQ level, department, and first course choice). Switch to <strong>Application table</strong> to review and act on individual applications.</p>
            <div class="row g-3 mb-0 align-items-stretch">
                <div class="col-md-4 col-lg-3 d-flex">
                    <div class="card border-0 shadow-sm h-100 w-100 bg-primary bg-opacity-10">
                        <div class="card-body">
                            <div class="text-uppercase small text-muted fw-semibold mb-1">Total applications</div>
                            <div class="display-6 fw-bold text-primary"><?php echo $total; ?></div>
                            <div class="mt-2 small d-flex flex-wrap gap-2" role="list">
                                <span class="badge bg-secondary" role="listitem">New: <?php echo (int) ($byStatus['new'] ?? 0); ?></span>
                                <span class="badge bg-success" role="listitem">Approved: <?php echo (int) ($byStatus['approved'] ?? 0); ?></span>
                                <span class="badge bg-danger" role="listitem">Rejected: <?php echo (int) ($byStatus['rejected'] ?? 0); ?></span>
                            </div>
                            <?php if ($byLevel !== []): ?>
                            <div class="mt-2 small" role="list">
                                <?php foreach ($byLevel as $lv): ?>
                                <span class="badge bg-light text-dark border me-1 mb-1" role="listitem">
                                    Level <?php echo $esc((string) ($lv['level'] ?? '')); ?>: <?php echo (int) ($lv['count'] ?? 0); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9 d-flex flex-column">
                    <div class="row g-3 flex-grow-1 align-items-stretch">
                        <div class="col-md-6 d-flex">
                            <section class="card border-0 shadow-sm h-100 w-100 d-flex flex-column" aria-labelledby="sa-dash-district">
                                <div class="card-header py-2 fw-semibold small text-uppercase text-muted" id="sa-dash-district">
                                    <i class="fas fa-map-marker-alt me-1 text-primary" aria-hidden="true"></i> By district
                                </div>
                                <div class="card-body p-0 sa-dash-scroll flex-grow-1">
                                    <table class="table table-sm table-hover mb-0 small sa-dash-table">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th scope="col">District</th>
                                                <th scope="col" class="text-end">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($byDistrict === []): ?>
                                            <tr><td colspan="2" class="text-muted text-center py-3">No data yet</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($byDistrict as $d): ?>
                                            <tr>
                                                <td><?php echo $esc((string) ($d['label'] ?? '')); ?></td>
                                                <td class="text-end fw-semibold"><?php echo (int) ($d['count'] ?? 0); ?></td>
                                            </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                        <div class="col-md-6 d-flex">
                            <section class="card border-0 shadow-sm h-100 w-100 d-flex flex-column" aria-labelledby="sa-dash-course">
                                <div class="card-header py-2 fw-semibold small text-uppercase text-muted" id="sa-dash-course">
                                    <i class="fas fa-graduation-cap me-1 text-primary" aria-hidden="true"></i> By first course choice
                                </div>
                                <div class="card-body p-0 sa-dash-scroll flex-grow-1">
                                    <table class="table table-sm table-hover mb-0 small sa-dash-table">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th scope="col">Course (1st preference)</th>
                                                <th scope="col" class="text-end">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($byCourse === []): ?>
                                            <tr><td colspan="2" class="text-muted text-center py-3">No data yet</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($byCourse as $c): ?>
                                            <tr>
                                                <td class="text-break"><?php echo $esc((string) ($c['label'] ?? '')); ?></td>
                                                <td class="text-end fw-semibold"><?php echo (int) ($c['count'] ?? 0); ?></td>
                                            </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-0">
                <div class="col-12 col-lg-6">
                    <section class="card border-0 shadow-sm h-100 w-100 d-flex flex-column" aria-labelledby="sa-dash-dept">
                        <div class="card-header py-2 fw-semibold small text-uppercase text-muted" id="sa-dash-dept">
                            <i class="fas fa-building me-1 text-primary" aria-hidden="true"></i> By department (from 1st course choice)
                        </div>
                        <div class="card-body p-0 sa-dash-scroll flex-grow-1">
                            <table class="table table-sm table-hover mb-0 small sa-dash-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th scope="col">Department</th>
                                        <th scope="col" class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($byDepartment === []): ?>
                                    <tr><td colspan="2" class="text-muted text-center py-3">No data yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($byDepartment as $d): ?>
                                    <tr>
                                        <td class="text-break"><?php echo $esc((string) ($d['label'] ?? '')); ?></td>
                                        <td class="text-end fw-semibold"><?php echo (int) ($d['count'] ?? 0); ?></td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
            <?php elseif ($active_tab === 'new'): ?>
            <div class="sa-apps-panel-lead px-3 py-3 mb-0 border-bottom bg-white" id="sa-panel-desc">
                <p class="small text-secondary mb-0"><span class="fw-semibold text-dark"><?php echo $count_new; ?></span> to review<?php echo $filterContextSuffix; ?>.</p>
            </div>
            <?php $renderAppTable($applications_new, $esc, $viewUrl, $deleteAction, $can_delete, $formatSubmitted, 'new', 'bg-secondary', ($page_new - 1) * $per_page); ?>
            <?php if ($max_page_new > 1): ?>
            <nav class="sa-apps-pagination pt-3 px-3 border-top mt-3" aria-label="New applications pages">
                <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
                    <li class="page-item<?php echo $page_new <= 1 ? ' disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'new', max(1, $page_new - 1), 1, 1); ?>">Previous</a>
                    </li>
                    <?php
                    $window = 2;
                    $start = max(1, $page_new - $window);
                    $end = min($max_page_new, $page_new + $window);
                    if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $buildListUrl($filter_level, 'new', 1, 1, 1); ?>">1</a></li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item<?php echo $p === $page_new ? ' active' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'new', $p, 1, 1); ?>"><?php echo $p; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($end < $max_page_new): ?>
                    <?php if ($end < $max_page_new - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $buildListUrl($filter_level, 'new', $max_page_new, 1, 1); ?>"><?php echo $max_page_new; ?></a></li>
                    <?php endif; ?>
                    <li class="page-item<?php echo $page_new >= $max_page_new ? ' disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'new', min($max_page_new, $page_new + 1), 1, 1); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php elseif ($active_tab === 'approved'): ?>
            <div class="sa-apps-panel-lead px-3 py-3 mb-0 border-bottom bg-white" id="sa-panel-desc">
                <p class="small text-secondary mb-0"><span class="fw-semibold text-dark"><?php echo $count_approved; ?></span> approved<?php echo $filterContextSuffix; ?>.</p>
            </div>
            <?php $renderAppTable($applications_approved, $esc, $viewUrl, $deleteAction, $can_delete, $formatSubmitted, 'approved', 'bg-success', ($page_approved - 1) * $per_page); ?>
            <?php if ($max_page_approved > 1): ?>
            <nav class="sa-apps-pagination pt-3 px-3 border-top mt-3" aria-label="Approved applications pages">
                <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
                    <li class="page-item<?php echo $page_approved <= 1 ? ' disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'approved', 1, max(1, $page_approved - 1), 1); ?>">Previous</a>
                    </li>
                    <?php
                    $windowAppr = 2;
                    $startA = max(1, $page_approved - $windowAppr);
                    $endA = min($max_page_approved, $page_approved + $windowAppr);
                    if ($startA > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $buildListUrl($filter_level, 'approved', 1, 1, 1); ?>">1</a></li>
                    <?php if ($startA > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $startA; $p <= $endA; $p++): ?>
                    <li class="page-item<?php echo $p === $page_approved ? ' active' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'approved', 1, $p, 1); ?>"><?php echo $p; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($endA < $max_page_approved): ?>
                    <?php if ($endA < $max_page_approved - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $buildListUrl($filter_level, 'approved', 1, $max_page_approved, 1); ?>"><?php echo $max_page_approved; ?></a></li>
                    <?php endif; ?>
                    <li class="page-item<?php echo $page_approved >= $max_page_approved ? ' disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'approved', 1, min($max_page_approved, $page_approved + 1), 1); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php else: ?>
            <div class="sa-apps-panel-lead sa-apps-panel-lead--rejected px-3 py-3 mb-0 border-bottom bg-white" id="sa-panel-desc">
                <p class="small text-secondary mb-0"><span class="fw-semibold text-dark"><?php echo $count_rejected; ?></span> rejected<?php echo $filterContextSuffix; ?>.</p>
            </div>
            <?php $renderAppTable($applications_rejected, $esc, $viewUrl, $deleteAction, $can_delete, $formatSubmitted, 'rejected', 'bg-danger', ($page_rejected - 1) * $per_page); ?>
            <?php if ($max_page_rejected > 1): ?>
            <nav class="sa-apps-pagination pt-3 px-3 border-top mt-3" aria-label="Rejected applications pages">
                <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
                    <li class="page-item<?php echo $page_rejected <= 1 ? ' disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, max(1, $page_rejected - 1)); ?>">Previous</a>
                    </li>
                    <?php
                    $windowR = 2;
                    $startR = max(1, $page_rejected - $windowR);
                    $endR = min($max_page_rejected, $page_rejected + $windowR);
                    if ($startR > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, 1); ?>">1</a></li>
                    <?php if ($startR > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $startR; $p <= $endR; $p++): ?>
                    <li class="page-item<?php echo $p === $page_rejected ? ' active' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, $p); ?>"><?php echo $p; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($endR < $max_page_rejected): ?>
                    <?php if ($endR < $max_page_rejected - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, $max_page_rejected); ?>"><?php echo $max_page_rejected; ?></a></li>
                    <?php endif; ?>
                    <li class="page-item<?php echo $page_rejected >= $max_page_rejected ? ' disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $buildListUrl($filter_level, 'rejected', 1, 1, min($max_page_rejected, $page_rejected + 1)); ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <script>
    (function () {
      document.querySelectorAll('[data-sa-filter-nav="1"]').forEach(function (el) {
        el.addEventListener('change', function () { if (this.value) window.location.href = this.value; });
      });
    })();
    </script>
</div>
