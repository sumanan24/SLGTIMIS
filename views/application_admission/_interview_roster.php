<?php
$e = $e ?? static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$roster = is_array($roster ?? null) ? $roster : [];
$filters = is_array($roster['filters'] ?? null) ? $roster['filters'] : [];
$summary = is_array($roster['summary'] ?? null) ? $roster['summary'] : [];
$sections = is_array($roster['sections'] ?? null) ? $roster['sections'] : [];
$courses = is_array($roster['courses'] ?? null) ? $roster['courses'] : [];
$provinceDistricts = is_array($roster['province_districts'] ?? null) ? $roster['province_districts'] : [];
$perPage = max(1, (int) ($roster['per_page'] ?? 25));
$levelFilter = (string) ($levelFilter ?? '');
$rosterBase = $rosterBase ?? (rtrim(APP_URL, '/') . '/application-admission/candidates');
$exportBase = rtrim(APP_URL, '/') . '/application-admission/interviews-export';
$sectionOrder = ['attended', 'absent', 'selected'];
$rosterQuery = static function (array $extra = []) use ($filters, $sections, $levelFilter): string {
    $params = [
        'province' => array_values(array_filter((array) ($filters['provinces'] ?? []), static function ($name): bool {
            return trim((string) $name) !== '';
        })),
        'district' => (string) ($filters['district'] ?? ''),
        'course' => (string) ($filters['course'] ?? ''),
        'gender' => (string) ($filters['gender'] ?? ''),
        'date_from' => (string) ($filters['date_from'] ?? ''),
        'date_to' => (string) ($filters['date_to'] ?? ''),
        'q' => (string) ($filters['q'] ?? ''),
        'level' => $levelFilter,
        'page_attended' => (string) ($sections['attended']['page'] ?? 1),
        'page_absent' => (string) ($sections['absent']['page'] ?? 1),
        'page_selected' => (string) ($sections['selected']['page'] ?? 1),
    ];
    foreach ($extra as $key => $value) {
        $params[$key] = $value;
    }
    $params = array_filter($params, static function ($value, $key): bool {
        if (is_array($value)) {
            return $value !== [];
        }
        if (in_array($key, ['page_attended', 'page_absent', 'page_selected'], true) && (string) $value === '1') {
            return false;
        }

        return trim((string) $value) !== '';
    }, ARRAY_FILTER_USE_BOTH);

    return $params === [] ? '' : ('?' . http_build_query($params));
};
$dash = static function (string $value) use ($e): string {
    $value = trim($value);

    return $value !== '' ? $e($value) : '<span class="text-muted">—</span>';
};
$pill = static function (string $value) use ($e): string {
    $value = trim($value);
    if ($value === '' || $value === '—') {
        return '<span class="text-muted">—</span>';
    }
    $tone = 'is-muted';
    $key = strtolower($value);
    if ($key === 'attended') {
        $tone = 'is-ok';
    } elseif ($key === 'absent') {
        $tone = 'is-warn';
    } elseif ($key === 'selected') {
        $tone = 'is-info';
    }

    return '<span class="aa-pill ' . $tone . '">' . $e($value) . '</span>';
};
$selectedProvinces = array_values(array_filter((array) ($filters['provinces'] ?? []), static function ($name) use ($provinceDistricts): bool {
    return isset($provinceDistricts[(string) $name]);
}));
$districtSource = $selectedProvinces === []
    ? array_values($provinceDistricts)
    : array_map(static function (string $name) use ($provinceDistricts): array {
        return (array) ($provinceDistricts[$name] ?? []);
    }, $selectedProvinces);
$districtChoices = array_values(array_unique(array_merge(...($districtSource ?: [[]]))));
sort($districtChoices);
?>
<style>
.aa-roster { margin: 0; }
.aa-roster-cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.aa-roster-card {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.65rem;
    padding: 0.9rem 1rem;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
a.aa-roster-card:hover { border-color: #b6d4fe; color: inherit; }
.aa-roster-ico {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex: 0 0 auto;
}
.aa-roster-card.is-total .aa-roster-ico { background: #eef2ff; color: #3730a3; }
.aa-roster-card.is-attended .aa-roster-ico { background: #e8f6ee; color: #157347; }
.aa-roster-card.is-absent .aa-roster-ico { background: #fff4e8; color: #c2410c; }
.aa-roster-card.is-selected .aa-roster-ico { background: #e7f1ff; color: #0d6efd; }
.aa-roster-card .aa-roster-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 650;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.aa-roster-card strong {
    display: block;
    margin-top: 0.1rem;
    font-size: 1.45rem;
    line-height: 1.15;
    color: #212529;
    font-variant-numeric: tabular-nums;
}
.aa-roster-note { font-size: 0.8125rem; color: #6c757d; margin: 0 0 0.85rem; }
.aa-roster-filters {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.65rem;
    padding: 0.9rem 1rem 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.aa-roster-filters-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    margin-bottom: 0.75rem;
}
.aa-roster-filter-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 0.75rem 1rem;
    align-items: end;
}
.aa-roster-filters label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.aa-span-12 { grid-column: 1 / -1; }
.aa-span-5 { grid-column: span 5; }
.aa-span-4 { grid-column: span 4; }
.aa-span-3 { grid-column: span 3; }
.aa-span-2 { grid-column: span 2; }
.aa-roster-actions-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.aa-roster-provinces {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    padding: 0.55rem 0.7rem;
    border: 1px solid #dee2e6;
    border-radius: 0.45rem;
    background: #f8f9fa;
}
.aa-roster-provinces .form-check { margin: 0; min-width: 9rem; }
.aa-roster-provinces .form-check-label { font-size: 0.8125rem; }
.aa-roster-section {
    margin-top: 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.65rem;
    overflow: hidden;
    scroll-margin-top: 4.5rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.aa-roster-section.is-attended { border-top: 3px solid #198754; }
.aa-roster-section.is-absent { border-top: 3px solid #fd7e14; }
.aa-roster-section.is-selected { border-top: 3px solid #0d6efd; }
.aa-roster-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #eef1f4;
    background: #fbfcfd;
}
.aa-roster-head h2 {
    font-size: 1rem;
    font-weight: 650;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.aa-roster-count-badge {
    display: inline-flex;
    align-items: center;
    min-width: 1.6rem;
    justify-content: center;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: #e9ecef;
    color: #495057;
    font-size: 0.75rem;
    font-weight: 700;
}
.aa-roster-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.aa-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.aa-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
.aa-table thead th {
    padding: 0.65rem 0.75rem;
    font-size: 0.6875rem;
    font-weight: 650;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    vertical-align: middle;
}
.aa-table tbody td {
    padding: 0.6rem 0.75rem;
    font-size: 0.875rem;
    vertical-align: middle;
    border-bottom: 1px solid #eef1f4;
    color: #212529;
}
.aa-table tbody tr:last-child td { border-bottom: none; }
.aa-table tbody tr:hover td { background: rgba(13, 110, 253, 0.035); }
.aa-table .aa-col-no { width: 3rem; text-align: center; color: #6c757d; }
.aa-table .aa-col-name { font-weight: 600; min-width: 12rem; }
.aa-table .aa-col-nic, .aa-table .aa-col-date { white-space: nowrap; }
.aa-table .aa-col-nic { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.8125rem; }
.aa-empty { text-align: center; color: #6c757d; padding: 2rem 1rem !important; }
.aa-pill {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 650;
    background: #f1f3f5;
    color: #495057;
    white-space: nowrap;
}
.aa-pill.is-ok { background: #d1e7dd; color: #0f5132; }
.aa-pill.is-warn { background: #ffe5d0; color: #9a3412; }
.aa-pill.is-info { background: #cfe2ff; color: #084298; }
.aa-roster-page {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #eef1f4;
}
.aa-roster-page .pagination { margin: 0; }
@media (max-width: 1199.98px) {
    .aa-span-5, .aa-span-4 { grid-column: span 6; }
    .aa-span-3, .aa-span-2 { grid-column: span 4; }
}
@media (max-width: 991.98px) {
    .aa-roster-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .aa-span-5, .aa-span-3, .aa-span-2 { grid-column: span 6; }
}
@media (max-width: 575.98px) {
    .aa-roster-cards { grid-template-columns: 1fr; }
    .aa-span-5, .aa-span-4, .aa-span-3, .aa-span-2 { grid-column: 1 / -1; }
}
</style>
<section class="aa-roster" aria-label="Interview student lists">
    <h2 class="visually-hidden">Interview candidate lists</h2>
    <div class="aa-roster-cards">
        <div class="aa-roster-card is-total">
            <span class="aa-roster-ico" aria-hidden="true"><i class="fas fa-users"></i></span>
            <span>
                <span class="aa-roster-label">Total applicants</span>
                <strong><?php echo (int) ($summary['applicants'] ?? 0); ?></strong>
            </span>
        </div>
        <?php
        $cardMeta = [
            'attended' => ['Interview attended', 'fa-user-check'],
            'absent' => ['Interview absent', 'fa-user-times'],
            'selected' => ['Selected students', 'fa-award'],
        ];
        foreach ($cardMeta as $cardKey => $cardInfo):
        ?>
        <a class="aa-roster-card is-<?php echo $e($cardKey); ?>" href="#aa-section-<?php echo $e($cardKey); ?>">
            <span class="aa-roster-ico" aria-hidden="true"><i class="fas <?php echo $e($cardInfo[1]); ?>"></i></span>
            <span>
                <span class="aa-roster-label"><?php echo $e($cardInfo[0]); ?></span>
                <strong><?php echo (int) ($summary[$cardKey] ?? 0); ?></strong>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <p class="aa-roster-note">Each list is separate. Attended students have interview marks. Absent students are recorded as <strong>ab</strong> on an interview date that has passed. Selected students have selection status <strong>Selected</strong>.</p>

    <?php if (!empty($roster['date_error'])): ?>
    <div class="alert alert-danger py-2"><?php echo $e((string) $roster['date_error']); ?></div>
    <?php endif; ?>

    <form method="get" action="<?php echo $e($rosterBase); ?>" class="aa-roster-filters" id="aa-roster-filters">
        <div class="aa-roster-filters-title">Filters</div>
        <div class="aa-roster-filter-grid">
        <?php if ($levelFilter !== ''): ?>
        <input type="hidden" name="level" value="<?php echo $e($levelFilter); ?>">
        <?php endif; ?>
        <div class="aa-span-12">
            <label for="aa_roster_province_search">Province</label>
            <input type="search" id="aa_roster_province_search" class="form-control form-control-sm mb-1" placeholder="Search province" autocomplete="off">
            <div class="aa-roster-provinces" id="aa_roster_provinces">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aa_roster_province_all" <?php echo $selectedProvinces === [] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="aa_roster_province_all">All provinces</label>
                </div>
                <?php foreach (array_keys($provinceDistricts) as $provinceName):
                    $provinceId = 'aa_roster_province_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) $provinceName);
                ?>
                <div class="form-check aa-roster-province-item">
                    <input class="form-check-input" type="checkbox" name="province[]" id="<?php echo $e($provinceId); ?>" value="<?php echo $e((string) $provinceName); ?>" <?php echo in_array($provinceName, $selectedProvinces, true) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo $e($provinceId); ?>"><?php echo $e((string) $provinceName); ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="aa-span-3">
            <label for="aa_roster_district_search">District</label>
            <input type="search" id="aa_roster_district_search" class="form-control form-control-sm mb-1 aa-roster-select-search" data-filter-select="aa_roster_district" placeholder="Search district" autocomplete="off">
            <select name="district" id="aa_roster_district" class="form-select form-select-sm">
                <option value="">All districts</option>
                <?php foreach ($districtChoices as $districtName): ?>
                <option value="<?php echo $e((string) $districtName); ?>" <?php echo ((string) ($filters['district'] ?? '')) === $districtName ? 'selected' : ''; ?>><?php echo $e((string) $districtName); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="aa-span-4">
            <label for="aa_roster_course_search">Course</label>
            <input type="search" id="aa_roster_course_search" class="form-control form-control-sm mb-1 aa-roster-select-search" data-filter-select="aa_roster_course" placeholder="Search course" autocomplete="off">
            <select name="course" id="aa_roster_course" class="form-select form-select-sm">
                <option value="">All courses</option>
                <?php foreach ($courses as $courseName): ?>
                <option value="<?php echo $e((string) $courseName); ?>" <?php echo ((string) ($filters['course'] ?? '')) === $courseName ? 'selected' : ''; ?>><?php echo $e((string) $courseName); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="aa-span-2">
            <label for="aa_roster_gender">Gender</label>
            <select name="gender" id="aa_roster_gender" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach (['Male', 'Female', 'Other'] as $genderName): ?>
                <option value="<?php echo $e($genderName); ?>" <?php echo ((string) ($filters['gender'] ?? '')) === $genderName ? 'selected' : ''; ?>><?php echo $e($genderName); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="aa-span-3">
            <label for="aa_roster_from">Interview from</label>
            <input type="date" name="date_from" id="aa_roster_from" class="form-control form-control-sm" value="<?php echo $e((string) ($filters['date_from'] ?? '')); ?>">
        </div>
        <div class="aa-span-3">
            <label for="aa_roster_to">Interview to</label>
            <input type="date" name="date_to" id="aa_roster_to" class="form-control form-control-sm" value="<?php echo $e((string) ($filters['date_to'] ?? '')); ?>">
        </div>
        <div class="aa-span-5">
            <label for="aa_roster_q">Name, application no., or NIC</label>
            <input type="search" name="q" id="aa_roster_q" class="form-control form-control-sm" value="<?php echo $e((string) ($filters['q'] ?? '')); ?>" maxlength="100" placeholder="Search">
        </div>
        <div class="aa-span-4">
            <label>&nbsp;</label>
            <div class="aa-roster-actions-inline">
                <button type="submit" class="btn btn-sm btn-primary" id="aa-roster-apply"><i class="fas fa-filter me-1"></i> Apply</button>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($rosterBase . ($levelFilter !== '' ? ('?level=' . rawurlencode($levelFilter)) : '')); ?>">Clear filters</a>
            </div>
        </div>
        </div>
    </form>

    <?php foreach ($sectionOrder as $sectionKey):
        $section = is_array($sections[$sectionKey] ?? null) ? $sections[$sectionKey] : [];
        $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        $total = (int) ($section['total'] ?? 0);
        $page = max(1, (int) ($section['page'] ?? 1));
        $pages = max(1, (int) ($section['pages'] ?? 1));
        $fromRow = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $toRow = min($total, $page * $perPage);
        $pageParam = 'page_' . $sectionKey;
    ?>
    <section class="aa-roster-section is-<?php echo $e($sectionKey); ?>" id="aa-section-<?php echo $e($sectionKey); ?>">
        <div class="aa-roster-head">
            <h2><?php echo $e((string) ($section['title'] ?? $sectionKey)); ?> <span class="aa-roster-count-badge"><?php echo (int) $total; ?></span></h2>
            <div class="aa-roster-actions">
                <span class="small text-muted"><?php echo $total === 0 ? 'No records' : ('Showing ' . (int) $fromRow . '–' . (int) $toRow . ' of ' . (int) $total); ?></span>
                <a class="btn btn-sm btn-outline-success" href="<?php echo $e($exportBase . $rosterQuery(['list' => $sectionKey, 'format' => 'xls', $pageParam => ''])); ?>"><i class="fas fa-file-excel me-1"></i> Excel</a>
                <a class="btn btn-sm btn-outline-success" href="<?php echo $e($exportBase . $rosterQuery(['list' => $sectionKey, 'format' => 'csv', $pageParam => ''])); ?>"><i class="fas fa-file-csv me-1"></i> CSV</a>
            </div>
        </div>
        <div class="aa-table-wrap">
            <table class="table aa-table mb-0">
                <thead>
                    <tr>
                        <th class="aa-col-no">#</th>
                        <th>Application no.</th>
                        <th>Registration no.</th>
                        <th>Full name</th>
                        <th>NIC</th>
                        <th>Gender</th>
                        <th>Telephone</th>
                        <th>Province</th>
                        <th>District</th>
                        <th>Course applied for</th>
                        <th>Interview date</th>
                        <th>Attendance</th>
                        <th>Selection</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="13" class="aa-empty">No students in this list for the current filters.</td>
                    </tr>
                <?php else: ?>
                    <?php $n = $fromRow; foreach ($rows as $row): ?>
                    <tr>
                        <td class="aa-col-no"><?php echo (int) $n++; ?></td>
                        <td><?php echo (int) ($row['application_id'] ?? 0); ?></td>
                        <td><?php echo $dash((string) ($row['registration_number'] ?? '')); ?></td>
                        <td class="aa-col-name"><?php echo $dash((string) ($row['full_name'] ?? '')); ?></td>
                        <td class="aa-col-nic"><?php echo $dash((string) ($row['nic'] ?? '')); ?></td>
                        <td><?php echo $dash((string) ($row['gender'] ?? '')); ?></td>
                        <td><?php echo $dash((string) ($row['telephone'] ?? '')); ?></td>
                        <td><?php echo $dash((string) ($row['province'] ?? '')); ?></td>
                        <td><?php echo $dash((string) ($row['district'] ?? '')); ?></td>
                        <td><?php echo $dash((string) ($row['course'] ?? '')); ?></td>
                        <td class="aa-col-date"><?php echo $dash((string) ($row['interview_date'] ?? '')); ?></td>
                        <td><?php echo $pill((string) ($row['attendance_status'] ?? '')); ?></td>
                        <td><?php echo $pill((string) ($row['selection_status'] ?? '')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
        <div class="aa-roster-page">
            <span class="small text-muted">Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?></span>
            <nav aria-label="<?php echo $e((string) ($section['title'] ?? 'List')); ?> pages">
                <ul class="pagination pagination-sm">
                    <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
                        <?php if ($page <= 1): ?>
                        <span class="page-link">Previous</span>
                        <?php else: ?>
                        <a class="page-link" href="<?php echo $e($rosterBase . $rosterQuery([$pageParam => (string) ($page - 1)]) . '#aa-section-' . $sectionKey); ?>">Previous</a>
                        <?php endif; ?>
                    </li>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($pages, $startPage + 4);
                    $startPage = max(1, $endPage - 4);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                    <li class="page-item<?php echo $p === $page ? ' active' : ''; ?>">
                        <a class="page-link" href="<?php echo $e($rosterBase . $rosterQuery([$pageParam => (string) $p]) . '#aa-section-' . $sectionKey); ?>"><?php echo (int) $p; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item<?php echo $page >= $pages ? ' disabled' : ''; ?>">
                        <?php if ($page >= $pages): ?>
                        <span class="page-link">Next</span>
                        <?php else: ?>
                        <a class="page-link" href="<?php echo $e($rosterBase . $rosterQuery([$pageParam => (string) ($page + 1)]) . '#aa-section-' . $sectionKey); ?>">Next</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
</section>
<script>
(function () {
    var map = <?php echo json_encode($provinceDistricts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?> || {};
    var provinceBox = document.getElementById('aa_roster_provinces');
    var provinceAll = document.getElementById('aa_roster_province_all');
    var provinceSearch = document.getElementById('aa_roster_province_search');
    var district = document.getElementById('aa_roster_district');
    var form = document.getElementById('aa-roster-filters');
    var applyBtn = document.getElementById('aa-roster-apply');

    function allDistricts() {
        var seen = {};
        var out = [];
        Object.keys(map).forEach(function (key) {
            (map[key] || []).forEach(function (name) {
                if (!seen[name]) {
                    seen[name] = true;
                    out.push(name);
                }
            });
        });
        out.sort();
        return out;
    }

    function selectedProvinceNames() {
        if (!provinceBox) return [];
        return Array.prototype.map.call(provinceBox.querySelectorAll('input[name="province[]"]:checked'), function (box) {
            return box.value;
        });
    }

    function fillDistricts(keep) {
        if (!district) return;
        var chosen = selectedProvinceNames();
        var list = [];
        var seen = {};
        var source = chosen.length ? chosen : Object.keys(map);
        source.forEach(function (name) {
            (map[name] || []).forEach(function (districtName) {
                if (!seen[districtName]) {
                    seen[districtName] = true;
                    list.push(districtName);
                }
            });
        });
        list.sort();
        var current = keep || '';
        if (current && list.indexOf(current) === -1) {
            current = '';
        }
        district.innerHTML = '';
        var all = document.createElement('option');
        all.value = '';
        all.textContent = 'All districts';
        district.appendChild(all);
        list.forEach(function (name) {
            var opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (name === current) opt.selected = true;
            district.appendChild(opt);
        });
    }

    if (provinceBox && district) {
        provinceBox.addEventListener('change', function (event) {
            var target = event.target;
            if (!target || target.type !== 'checkbox') return;
            var items = provinceBox.querySelectorAll('input[name="province[]"]');
            if (target === provinceAll) {
                if (provinceAll.checked) {
                    Array.prototype.forEach.call(items, function (box) { box.checked = false; });
                }
            } else if (target.name === 'province[]') {
                if (provinceAll) provinceAll.checked = selectedProvinceNames().length === 0;
            }
            fillDistricts(district.value);
        });
    }

    if (provinceSearch && provinceBox) {
        provinceSearch.addEventListener('input', function () {
            var q = provinceSearch.value.trim().toLowerCase();
            provinceBox.querySelectorAll('.aa-roster-province-item').forEach(function (item) {
                var label = item.querySelector('.form-check-label');
                var text = label ? label.textContent.toLowerCase() : '';
                item.hidden = q !== '' && text.indexOf(q) === -1;
            });
        });
    }

    document.querySelectorAll('.aa-roster-select-search').forEach(function (input) {
        input.addEventListener('input', function () {
            var select = document.getElementById(input.getAttribute('data-filter-select') || '');
            if (!select) return;
            var q = input.value.trim().toLowerCase();
            Array.prototype.forEach.call(select.options, function (opt) {
                if (opt.value === '') {
                    opt.hidden = false;
                    return;
                }
                opt.hidden = q !== '' && opt.value !== select.value && opt.textContent.toLowerCase().indexOf(q) === -1;
            });
        });
    });

    if (form && applyBtn) {
        form.addEventListener('submit', function () {
            applyBtn.disabled = true;
            applyBtn.textContent = 'Loading…';
        });
    }
})();
</script>
