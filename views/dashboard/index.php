<?php
/**
 * Main dashboard (admin / management view).
 * Sections live in views/dashboard/partials/: students, hostel, staff.
 * Charts are declared with <canvas data-chart='{json}'> and drawn by the script at the bottom.
 */
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$n = function ($value) {
    return number_format((int) $value);
};
$jsonAttr = function (array $data) {
    return htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
};
$modeLabel = function ($mode) {
    if ($mode === 'Full') {
        return 'Full time';
    }
    if ($mode === 'Part') {
        return 'Part time';
    }
    return $mode !== '' ? $mode : '—';
};

$palette = ['#2563eb', '#16a34a', '#f59e0b', '#e11d48', '#7c3aed', '#0d9488', '#ea580c', '#0284c7', '#db2777', '#65a30d'];
$statusColors = [
    'Following' => '#16a34a',
    'Internship' => '#f59e0b',
    'Dropout' => '#e11d48',
    'Completed' => '#0d9488',
    'Long Absent' => '#64748b',
];
$statusNotes = [
    'Following' => 'In the total',
    'Internship' => 'In the total',
    'Dropout' => 'Not in the total',
    'Completed' => 'Not in the total',
    'Long Absent' => 'Not in the total',
];
$genderColors = ['Male' => '#2563eb', 'Female' => '#ec4899'];

// The institute runs on Sri Lanka time; do not depend on the server's default timezone.
$now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$hour = (int) $now->format('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = trim((string) ($user_name ?? 'User'));
$firstName = $firstName !== '' ? $firstName : 'User';

$canViewStudents = !empty($canViewStudents);
$canViewStaff = !empty($canViewStaff);
$canViewCourses = !empty($canViewCourses);
$canViewDepartments = !empty($canViewDepartments);

$labelsOf = function ($map) {
    return array_map('strval', array_keys($map ?? []));
};
$valuesOf = function ($map) {
    return array_map('intval', array_values($map ?? []));
};
$colorsFor = function (array $labels, array $named) use ($palette) {
    $out = [];
    foreach ($labels as $i => $label) {
        $out[] = $named[$label] ?? $palette[$i % count($palette)];
    }
    return $out;
};
$barColors = function (int $count) use ($palette) {
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $out[] = $palette[$i % count($palette)];
    }
    return $out;
};

$deptLabels = [];
$deptValues = [];
foreach ($departmentStats ?? [] as $dept) {
    $deptLabels[] = (string) ($dept['name'] ?? $dept['department_name'] ?? '');
    $deptValues[] = (int) ($dept['count'] ?? 0);
}
$districtLabels = $labelsOf($districtStats ?? []);
$provinceLabels = $labelsOf($provinceStats ?? []);
$genderLabels = $labelsOf($genderStats ?? []);
$religionLabels = $labelsOf($religionStats ?? []);
$hasCourseTable = false;
foreach ($courseEnrollmentByDepartment ?? [] as $d) {
    if (!empty($d['nvq_levels'])) {
        $hasCourseTable = true;
        break;
    }
}

$navItems = [];
if ($canViewStudents) {
    $navItems[] = ['sec-students', 'fa-user-graduate', 'Students'];
}
$navItems[] = ['sec-hostel', 'fa-bed', 'Hostel'];
if ($canViewStaff) {
    $navItems[] = ['sec-staff', 'fa-chalkboard-user', 'Staff'];
}
$navItems[] = ['sec-analytics', 'fa-chart-pie', 'Analytics'];
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .dash-page {
        --ink: #0f172a;
        --muted: #64748b;
        --line: #e5eaf2;
        --bg-soft: #f6f8fc;
        --blue: #2563eb;   --blue-soft: #dbeafe;
        --green: #16a34a;  --green-soft: #dcfce7;
        --amber: #d97706;  --amber-soft: #fef3c7;
        --teal: #0d9488;   --teal-soft: #ccfbf1;
        --violet: #7c3aed; --violet-soft: #ede9fe;
        --rose: #e11d48;   --rose-soft: #ffe4e6;
        --pink: #db2777;   --pink-soft: #fce7f3;
        --cyan: #0891b2;   --cyan-soft: #cffafe;
        --indigo: #4f46e5; --indigo-soft: #e0e7ff;
        --slate: #475569;  --slate-soft: #e2e8f0;
        color: var(--ink);
        max-width: 1500px;
        margin: 0 auto;
    }
    .dash-page * { box-sizing: border-box; }
    .dash-page .tone-blue   { --tone: var(--blue);   --tone-soft: var(--blue-soft); }
    .dash-page .tone-green  { --tone: var(--green);  --tone-soft: var(--green-soft); }
    .dash-page .tone-amber  { --tone: var(--amber);  --tone-soft: var(--amber-soft); }
    .dash-page .tone-teal   { --tone: var(--teal);   --tone-soft: var(--teal-soft); }
    .dash-page .tone-violet { --tone: var(--violet); --tone-soft: var(--violet-soft); }
    .dash-page .tone-rose   { --tone: var(--rose);   --tone-soft: var(--rose-soft); }
    .dash-page .tone-pink   { --tone: var(--pink);   --tone-soft: var(--pink-soft); }
    .dash-page .tone-cyan   { --tone: var(--cyan);   --tone-soft: var(--cyan-soft); }
    .dash-page .tone-indigo { --tone: var(--indigo); --tone-soft: var(--indigo-soft); }

    /* ---------- Hero ---------- */
    .dh-hero {
        position: relative;
        overflow: hidden;
        color: #fff;
        border-radius: 20px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1rem;
        background: linear-gradient(120deg, #0b2545 0%, #1d4ed8 55%, #7c3aed 100%);
        box-shadow: 0 10px 30px rgba(29, 78, 216, 0.25);
    }
    .dh-hero::before, .dh-hero::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.08); pointer-events: none; }
    .dh-hero::before { width: 280px; height: 280px; right: -70px; top: -110px; }
    .dh-hero::after { width: 200px; height: 200px; right: 180px; bottom: -120px; background: rgba(255, 255, 255, 0.06); }
    .dh-hero-top { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
    .dh-hero h1 { font-size: 1.55rem; font-weight: 800; margin: 0 0 0.3rem; line-height: 1.25; }
    .dh-hero p { margin: 0; color: #dbe7ff; font-size: 0.95rem; }
    .dh-chips { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.85rem; }
    .dh-chip {
        display: inline-flex; align-items: center; gap: 0.4rem;
        background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 999px; padding: 0.3rem 0.8rem; font-size: 0.8rem; font-weight: 600;
    }
    .dh-actions { position: relative; z-index: 1; display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1.1rem; }
    .dh-action {
        display: inline-flex; align-items: center; gap: 0.5rem;
        background: #fff; color: #1e3a8a; text-decoration: none;
        border-radius: 12px; padding: 0.55rem 1rem; font-size: 0.875rem; font-weight: 700;
        min-height: 42px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .dh-action i { color: var(--blue); }
    .dh-action:hover { color: #1e3a8a; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18); }
    .dh-action.alt { background: rgba(255, 255, 255, 0.16); color: #fff; box-shadow: none; border: 1px solid rgba(255, 255, 255, 0.28); }
    .dh-action.alt i { color: #fff; }
    .dh-action.alt:hover { background: rgba(255, 255, 255, 0.26); color: #fff; }

    .dash-alert { margin-bottom: 1rem; border-radius: 12px; }

    /* ---------- KPI cards ---------- */
    .dh-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; margin-bottom: 1.1rem; }
    .dh-kpi {
        position: relative; display: flex; align-items: center; gap: 0.9rem;
        background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.05rem;
        text-decoration: none; color: inherit; overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .dh-kpi::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--tone); }
    a.dh-kpi:hover { color: inherit; transform: translateY(-3px); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.1); border-color: var(--tone); }
    .dh-kpi-icon {
        flex: none; width: 3rem; height: 3rem; border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--tone-soft); color: var(--tone); font-size: 1.25rem;
    }
    .dh-kpi-body { min-width: 0; }
    .dh-kpi-label { display: block; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); }
    .dh-kpi strong { display: block; font-size: 1.85rem; line-height: 1.15; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; }
    .dh-kpi em { display: block; font-style: normal; font-size: 0.76rem; color: var(--muted); margin-top: 0.1rem; }

    /* ---------- Section nav ---------- */
    .dh-nav {
        position: sticky; top: 0.5rem; z-index: 20;
        display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;
        background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px);
        border: 1px solid var(--line); border-radius: 14px; padding: 0.4rem;
        margin-bottom: 1.25rem; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
    }
    .dh-nav a {
        display: inline-flex; align-items: center; gap: 0.45rem;
        padding: 0.5rem 0.95rem; border-radius: 10px; text-decoration: none;
        font-size: 0.85rem; font-weight: 700; color: var(--slate);
        transition: background .15s ease, color .15s ease;
    }
    .dh-nav a:hover { background: var(--bg-soft); color: var(--ink); }
    .dh-nav a.active { background: var(--blue); color: #fff; }

    /* ---------- Sections & cards ---------- */
    .dh-section { margin-bottom: 1.75rem; scroll-margin-top: 4.5rem; }
    .dh-sec-head { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.85rem; flex-wrap: wrap; }
    .dh-sec-icon {
        width: 2.6rem; height: 2.6rem; border-radius: 12px; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--tone-soft); color: var(--tone); font-size: 1.1rem;
    }
    .dh-sec-head h2 { font-size: 1.2rem; font-weight: 800; margin: 0; }
    .dh-sec-head p { margin: 0; font-size: 0.82rem; color: var(--muted); }
    .dh-sec-head .dh-link { margin-left: auto; font-size: 0.82rem; font-weight: 700; text-decoration: none; color: var(--tone); white-space: nowrap; }
    .dh-sec-head .dh-link:hover { text-decoration: underline; }

    .dh-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05); overflow: hidden; min-width: 0; }
    .dh-card-head { padding: 0.9rem 1.1rem 0.5rem; display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; }
    .dh-card-head h3 { margin: 0; font-size: 0.98rem; font-weight: 800; }
    .dh-card-head span { font-size: 0.78rem; color: var(--muted); }
    .dh-card-body { padding: 0.25rem 1.1rem 1.1rem; }
    .dh-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .dh-grid-2.wide-left { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); }
    .dh-grid-2.wide-right { grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); }
    .dh-grid-2 > .dh-card, .dh-grid-2 + .dh-card { margin-bottom: 0; }
    .dh-chart { position: relative; height: 260px; }
    .dh-empty { margin: 0; padding: 1.6rem 1rem; text-align: center; color: var(--muted); font-size: 0.9rem; }
    .dh-empty i { display: block; font-size: 1.6rem; margin-bottom: 0.4rem; color: #cbd5e1; }

    /* Legend lists next to donuts */
    .dh-legend { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.4rem; }
    .dh-legend li { display: flex; align-items: center; gap: 0.6rem; padding: 0.45rem 0.65rem; border-radius: 10px; background: var(--bg-soft); font-size: 0.85rem; }
    .dh-legend .dot { width: 0.7rem; height: 0.7rem; border-radius: 50%; flex: none; }
    .dh-legend .name { font-weight: 700; }
    .dh-legend .note { color: var(--muted); font-size: 0.74rem; }
    .dh-legend .val { margin-left: auto; font-weight: 800; font-variant-numeric: tabular-nums; }
    .dh-donut-wrap { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 1rem; align-items: center; }

    /* ---------- Tables ---------- */
    .dh-table { width: 100%; margin: 0; font-size: 0.875rem; border-collapse: separate; border-spacing: 0; }
    .dh-table th {
        background: var(--bg-soft); color: #475569; font-size: 0.72rem; font-weight: 800;
        letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap;
        padding: 0.7rem 0.85rem; border-bottom: 1px solid var(--line);
    }
    .dh-table td { padding: 0.65rem 0.85rem; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
    .dh-table tbody tr:last-child td { border-bottom: 0; }
    .dh-table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .dh-table tfoot td { background: var(--bg-soft); font-weight: 800; border-top: 1px solid var(--line); }
    .dh-table .th-dot { display: inline-block; width: 0.55rem; height: 0.55rem; border-radius: 50%; margin-right: 0.3rem; vertical-align: 0.05rem; }
    .dh-muted { color: #a8b3c4; }
    .dh-pill {
        display: inline-block; min-width: 2.4rem; text-align: center; padding: 0.1rem 0.55rem;
        border-radius: 999px; font-size: 0.82rem; font-weight: 800; background: var(--tone-soft); color: var(--tone);
    }
    .dh-sub { display: block; font-size: 0.72rem; color: var(--muted); margin-top: 0.1rem; }

    /* Year table */
    .dh-year-row.is-clickable { cursor: pointer; }
    .dh-year-row.is-clickable:hover > td { background: #f3f7ff; }
    .dh-toggle { border: 0; background: none; padding: 0; font-weight: 800; color: var(--ink); display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; }
    .dh-toggle::before {
        content: "\f054"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 0.7rem;
        display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem;
        border-radius: 8px; background: var(--blue-soft); color: var(--blue); transition: transform .15s ease;
    }
    .dh-toggle[aria-expanded="true"]::before { transform: rotate(90deg); background: var(--blue); color: #fff; }
    .dh-toggle:disabled { color: var(--muted); font-weight: 600; }
    .dh-toggle:disabled::before { background: var(--slate-soft); color: #94a3b8; }
    .dh-bar { height: 8px; background: var(--slate-soft); border-radius: 4px; overflow: hidden; min-width: 90px; }
    .dh-bar > span { display: block; height: 100%; border-radius: 4px; background: linear-gradient(90deg, #2563eb, #7c3aed); }
    .dh-detail { background: var(--bg-soft); padding: 0.85rem 1rem 1rem 2.4rem; }
    .dh-detail .dh-table-wrap { background: #fff; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
    .dh-dept td { background: #eef3ff !important; color: #1e3a8a; font-weight: 800; }
    .dh-course td:first-child { padding-left: 1.6rem; }

    /* ---------- Hostel ---------- */
    .dh-hostel-layout { display: grid; grid-template-columns: 17rem minmax(0, 1fr); gap: 1rem; align-items: start; }
    .dh-ring-card { padding: 1.2rem 1rem; text-align: center; }
    .dh-ring {
        --p: 0; --c: #16a34a;
        width: 9.5rem; height: 9.5rem; border-radius: 50%; margin: 0 auto 1rem;
        background: conic-gradient(var(--c) calc(var(--p) * 1%), var(--slate-soft) 0);
        display: grid; place-items: center;
    }
    .dh-ring::before { content: ""; grid-area: 1 / 1; width: 7.2rem; height: 7.2rem; border-radius: 50%; background: #fff; }
    .dh-ring div { grid-area: 1 / 1; text-align: center; line-height: 1.1; }
    .dh-ring strong { display: block; font-size: 1.9rem; font-weight: 800; }
    .dh-ring span { font-size: 0.7rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); font-weight: 800; }
    .dh-mini-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; }
    .dh-mini { background: var(--bg-soft); border-radius: 12px; padding: 0.55rem 0.3rem; }
    .dh-mini span { display: block; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); }
    .dh-mini strong { display: block; font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .dh-mini.good strong { color: var(--green); }
    .dh-hostel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); gap: 0.9rem; }
    .dh-hostel { display: flex; flex-direction: column; gap: 0.75rem; padding: 1.05rem; border-top: 4px solid var(--tone); }
    .dh-hostel-top { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
    .dh-hostel h4 { margin: 0; font-size: 1.05rem; font-weight: 800; }
    .dh-tag { font-size: 0.7rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: 999px; background: var(--tone-soft); color: var(--tone); }
    .dh-free { display: flex; align-items: baseline; gap: 0.4rem; }
    .dh-free strong { font-size: 2rem; font-weight: 800; line-height: 1; color: var(--free, var(--green)); font-variant-numeric: tabular-nums; }
    .dh-free span { font-size: 0.8rem; color: var(--muted); font-weight: 600; }
    .dh-occ { height: 10px; border-radius: 6px; background: var(--slate-soft); overflow: hidden; }
    .dh-occ > span { display: block; height: 100%; border-radius: 6px; background: var(--c, var(--green)); }
    .dh-occ-meta { display: flex; justify-content: space-between; font-size: 0.76rem; color: var(--muted); font-weight: 600; margin-top: -0.35rem; }
    .dh-hostel-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; text-align: center; }
    .dh-hostel-stats div { background: var(--bg-soft); border-radius: 10px; padding: 0.4rem 0.2rem; }
    .dh-hostel-stats span { display: block; font-size: 0.64rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); }
    .dh-hostel-stats strong { font-size: 0.98rem; font-variant-numeric: tabular-nums; }
    .dh-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
        min-height: 40px; border-radius: 10px; border: 1.5px solid var(--tone); background: #fff; color: var(--tone);
        font-weight: 700; font-size: 0.85rem; padding: 0.4rem 0.9rem; cursor: pointer; text-decoration: none;
        transition: background .15s ease, color .15s ease;
    }
    .dh-btn:hover { background: var(--tone); color: #fff; }
    .dh-room-filter .btn { font-weight: 700; }

    /* ---------- Staff ---------- */
    .dh-tabs { display: flex; gap: 0.3rem; padding: 0.6rem 1.1rem 0; border-bottom: 1px solid var(--line); }
    .dh-tabs button {
        border: 0; background: none; padding: 0.5rem 0.9rem; font-size: 0.85rem; font-weight: 700; color: var(--muted);
        border-bottom: 3px solid transparent; margin-bottom: -1px; border-radius: 8px 8px 0 0;
    }
    .dh-tabs button:hover { color: var(--ink); background: var(--bg-soft); }
    .dh-tabs button.active { color: var(--violet); border-bottom-color: var(--violet); }

    /* ---------- Collapsible blocks ---------- */
    .dh-details > summary {
        list-style: none; cursor: pointer; padding: 0.85rem 1.1rem; font-weight: 800; font-size: 0.95rem;
        display: flex; align-items: center; gap: 0.6rem; color: var(--ink);
    }
    .dh-details > summary::-webkit-details-marker { display: none; }
    .dh-details > summary::after { content: "\f078"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 0.7rem; margin-left: auto; color: var(--muted); transition: transform .15s ease; }
    .dh-details[open] > summary::after { transform: rotate(180deg); }
    .dh-details > summary small { font-weight: 500; color: var(--muted); }

    /* ---------- Responsive ---------- */
    @media (max-width: 1100px) {
        .dh-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dh-grid-2, .dh-grid-2.wide-left, .dh-grid-2.wide-right { grid-template-columns: 1fr; }
        .dh-hostel-layout { grid-template-columns: 1fr; }
        .dh-ring-card { display: grid; grid-template-columns: auto 1fr; gap: 1rem; align-items: center; text-align: left; }
        .dh-ring { margin: 0; }
    }
    @media (max-width: 767.98px) {
        .dh-hero { padding: 1.15rem 1.05rem; border-radius: 16px; }
        .dh-hero h1 { font-size: 1.3rem; }
        .dh-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .dh-action { justify-content: center; }
        .dh-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.6rem; }
        .dh-kpi { flex-direction: column; align-items: flex-start; gap: 0.5rem; padding: 0.85rem; }
        .dh-kpi strong { font-size: 1.5rem; }
        .dh-nav { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .dh-nav::-webkit-scrollbar { display: none; }
        .dh-nav a { white-space: nowrap; }
        .dh-donut-wrap { grid-template-columns: 1fr; }
        .dh-ring-card { grid-template-columns: 1fr; text-align: center; }
        .dh-ring { margin: 0 auto 0.5rem; }
        .dh-detail { padding-left: 0.75rem; padding-right: 0.75rem; }
        .dh-sec-head .dh-link { margin-left: 0; width: 100%; }
        .dh-chart { height: 230px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .dash-page * { transition: none !important; }
    }
</style>

<div class="dash-page">
    <?php if (!empty($error)): ?>
        <div class="alert alert-warning dash-alert" role="alert"><i class="fas fa-triangle-exclamation me-2"></i><?php echo $h($error); ?></div>
    <?php endif; ?>
    <?php foreach (($overviewErrors ?? []) as $msg): ?>
        <div class="alert alert-warning dash-alert py-2" role="alert"><i class="fas fa-triangle-exclamation me-2"></i><?php echo $h($msg); ?></div>
    <?php endforeach; ?>

    <?php /* ================= Hero ================= */ ?>
    <header class="dh-hero">
        <div class="dh-hero-top">
            <div>
                <h1><?php echo $h($greeting); ?>, <?php echo $h($firstName); ?> 👋</h1>
                <p>Here is what is happening across SLGTI today.</p>
                <div class="dh-chips">
                    <span class="dh-chip"><i class="far fa-calendar"></i> <?php echo $h($now->format('l, d F Y')); ?></span>
                    <span class="dh-chip"><i class="fas fa-layer-group"></i> All academic years</span>
                </div>
            </div>
        </div>
        <nav class="dh-actions" aria-label="Quick actions">
            <?php if ($canViewStudents): ?>
                <a class="dh-action" href="<?php echo APP_URL; ?>/students"><i class="fas fa-user-graduate"></i> Students</a>
            <?php endif; ?>
            <?php if ($canViewStaff): ?>
                <a class="dh-action" href="<?php echo APP_URL; ?>/staff"><i class="fas fa-chalkboard-user"></i> Staff</a>
            <?php endif; ?>
            <?php if ($canViewCourses): ?>
                <a class="dh-action alt" href="<?php echo APP_URL; ?>/courses"><i class="fas fa-book-open"></i> Courses</a>
            <?php endif; ?>
            <?php if ($canViewDepartments): ?>
                <a class="dh-action alt" href="<?php echo APP_URL; ?>/departments"><i class="fas fa-building"></i> Departments</a>
            <?php endif; ?>
            <a class="dh-action alt" href="<?php echo APP_URL; ?>/hostel-report"><i class="fas fa-bed"></i> Hostel report</a>
        </nav>
    </header>

    <?php /* ================= KPI cards ================= */ ?>
    <section class="dh-kpis" aria-label="Key figures">
        <?php
        $kpis = [
            ['tone-blue', 'fa-user-graduate', 'Students', $totalStudents ?? 0, 'Following + Internship', $canViewStudents ? APP_URL . '/students' : ''],
            ['tone-green', 'fa-book-reader', 'Following', $enrollStatusCounts['Following'] ?? 0, 'Currently studying', ''],
            ['tone-amber', 'fa-briefcase', 'Internship', $enrollStatusCounts['Internship'] ?? 0, 'On-the-job training', ''],
            ['tone-teal', 'fa-graduation-cap', 'Completed', $enrollStatusCounts['Completed'] ?? 0, 'Finished · not in total', ''],
            ['tone-indigo', 'fa-sun', 'Full time', $studyModeTotals['full_time'] ?? 0, 'Current students', ''],
            ['tone-cyan', 'fa-clock', 'Part time', $studyModeTotals['part_time'] ?? 0, 'Current students', ''],
            ['tone-violet', 'fa-chalkboard-user', 'Staff', $totalStaff ?? 0, 'Registered staff', $canViewStaff ? APP_URL . '/staff' : ''],
            ['tone-rose', 'fa-book-open', 'Courses', $totalCourses ?? 0, 'Available courses', $canViewCourses ? APP_URL . '/courses' : ''],
        ];
        foreach ($kpis as $k):
            [$tone, $icon, $label, $value, $note, $href] = $k;
            $tag = $href !== '' ? 'a' : 'div';
        ?>
            <<?php echo $tag; ?> class="dh-kpi <?php echo $tone; ?>"<?php echo $href !== '' ? ' href="' . $h($href) . '"' : ''; ?>>
                <span class="dh-kpi-icon" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span>
                <span class="dh-kpi-body">
                    <span class="dh-kpi-label"><?php echo $h($label); ?></span>
                    <strong><?php echo $n($value); ?></strong>
                    <em><?php echo $h($note); ?></em>
                </span>
            </<?php echo $tag; ?>>
        <?php endforeach; ?>
    </section>

    <?php /* ================= Section navigation ================= */ ?>
    <nav class="dh-nav" id="dhNav" aria-label="Jump to section">
        <?php foreach ($navItems as $i => $item): ?>
            <a href="#<?php echo $item[0]; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>"><i class="fas <?php echo $item[1]; ?>"></i> <?php echo $h($item[2]); ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($canViewStudents): ?>
        <?php require BASE_PATH . '/views/dashboard/partials/students.php'; ?>
    <?php endif; ?>

    <?php require BASE_PATH . '/views/dashboard/partials/hostel.php'; ?>

    <?php if ($canViewStaff && !empty($staffBreakdown)): ?>
        <?php require BASE_PATH . '/views/dashboard/partials/staff.php'; ?>
    <?php endif; ?>

    <?php /* ================= Analytics ================= */ ?>
    <section class="dh-section tone-blue" id="sec-analytics">
        <div class="dh-sec-head">
            <span class="dh-sec-icon"><i class="fas fa-chart-pie"></i></span>
            <div>
                <h2>Student analytics</h2>
                <p>Active students who are Following, across all academic years</p>
            </div>
        </div>

        <div class="dh-grid-2 wide-left">
            <div class="dh-card">
                <div class="dh-card-head"><h3>By gender</h3></div>
                <div class="dh-card-body">
                    <?php if (empty($genderStats)): ?>
                        <p class="dh-empty"><i class="fas fa-chart-pie"></i>No records.</p>
                    <?php else: ?>
                        <div class="dh-donut-wrap">
                            <div class="dh-chart" style="height:220px"><canvas role="img" aria-label="Students by gender" data-chart="<?php echo $jsonAttr(['type' => 'doughnut', 'labels' => $genderLabels, 'values' => $valuesOf($genderStats), 'colors' => $colorsFor($genderLabels, $genderColors)]); ?>"></canvas></div>
                            <ul class="dh-legend">
                                <?php $gc = $colorsFor($genderLabels, $genderColors); $gi = 0; foreach ($genderStats as $label => $count): ?>
                                    <li><span class="dot" style="background:<?php echo $h($gc[$gi]); ?>"></span><span class="name"><?php echo $h($label); ?></span><span class="val"><?php echo $n($count); ?></span></li>
                                <?php $gi++; endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dh-card">
                <div class="dh-card-head"><h3>By department</h3><span>Students per department</span></div>
                <div class="dh-card-body">
                    <?php if (empty($deptLabels)): ?>
                        <p class="dh-empty"><i class="fas fa-building"></i>No records.</p>
                    <?php else: ?>
                        <div class="dh-chart" style="height:<?php echo (int) max(240, count($deptLabels) * 38); ?>px"><canvas role="img" aria-label="Students by department" data-chart="<?php echo $jsonAttr(['type' => 'bar', 'horizontal' => true, 'labels' => $deptLabels, 'datasets' => [['label' => 'Students', 'data' => $deptValues, 'colors' => $barColors(count($deptLabels))]]]); ?>"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dh-grid-2">
            <div class="dh-card">
                <div class="dh-card-head"><h3>By religion</h3></div>
                <div class="dh-card-body">
                    <?php if (empty($religionStats)): ?>
                        <p class="dh-empty"><i class="fas fa-chart-column"></i>No records.</p>
                    <?php else: ?>
                        <div class="dh-chart"><canvas role="img" aria-label="Students by religion" data-chart="<?php echo $jsonAttr(['type' => 'bar', 'horizontal' => false, 'labels' => $religionLabels, 'datasets' => [['label' => 'Students', 'data' => $valuesOf($religionStats), 'colors' => $barColors(count($religionLabels))]]]); ?>"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dh-card">
                <div class="dh-card-head"><h3>By province</h3></div>
                <div class="dh-card-body">
                    <?php if (empty($provinceStats)): ?>
                        <p class="dh-empty"><i class="fas fa-map"></i>No records.</p>
                    <?php else: ?>
                        <?php $manyProvinces = count($provinceLabels) > 8; ?>
                        <div class="dh-chart" style="height:<?php echo $manyProvinces ? (int) max(240, count($provinceLabels) * 32) : 260; ?>px"><canvas role="img" aria-label="Students by province" data-chart="<?php echo $jsonAttr($manyProvinces
                            ? ['type' => 'bar', 'horizontal' => true, 'labels' => $provinceLabels, 'datasets' => [['label' => 'Students', 'data' => $valuesOf($provinceStats), 'colors' => $barColors(count($provinceLabels))]]]
                            : ['type' => 'doughnut', 'labels' => $provinceLabels, 'values' => $valuesOf($provinceStats), 'colors' => $barColors(count($provinceLabels))]); ?>"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dh-card" style="margin-bottom:1rem">
            <div class="dh-card-head"><h3>Top 10 districts</h3><span>Students per district</span></div>
            <div class="dh-card-body">
                <?php if (empty($districtStats)): ?>
                    <p class="dh-empty"><i class="fas fa-location-dot"></i>No records.</p>
                <?php else: ?>
                    <div class="dh-chart" style="height:<?php echo (int) max(240, count($districtLabels) * 34); ?>px"><canvas role="img" aria-label="Students by district" data-chart="<?php echo $jsonAttr(['type' => 'bar', 'horizontal' => true, 'labels' => $districtLabels, 'datasets' => [['label' => 'Students', 'data' => $valuesOf($districtStats), 'colors' => $barColors(count($districtLabels))]]]); ?>"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dh-card">
            <?php if (!$hasCourseTable): ?>
                <div class="dh-card-head"><h3>Course enrolment by department</h3></div>
                <p class="dh-empty"><i class="fas fa-book-open"></i>No course enrolment found.</p>
            <?php else: ?>
                <details class="dh-details">
                    <summary><i class="fas fa-book-open text-primary"></i> Course enrolment by department <small>Following students · tap to expand</small></summary>
                    <div class="table-responsive">
                        <table class="dh-table">
                            <thead>
                                <tr>
                                    <th>Course</th><th>NVQ</th><th>Mode</th>
                                    <th class="num">Female</th><th class="num">Male</th><th class="num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courseEnrollmentByDepartment as $dept): ?>
                                    <?php if (empty($dept['nvq_levels'])) { continue; } ?>
                                    <tr class="dh-dept">
                                        <td colspan="5"><?php echo $h($dept['department_name'] ?? ''); ?></td>
                                        <td class="num"><?php echo $n($dept['total_enrollment'] ?? 0); ?></td>
                                    </tr>
                                    <?php foreach ($dept['nvq_levels'] as $nvqLevel => $courses): ?>
                                        <?php foreach ($courses as $course): ?>
                                            <tr class="dh-course">
                                                <td><?php echo $h($course['course_name'] ?? ''); ?></td>
                                                <td><?php echo $h($nvqLevel); ?></td>
                                                <td><?php echo $h($modeLabel((string) ($course['course_mode'] ?? ''))); ?></td>
                                                <td class="num"><?php echo $n($course['female_count'] ?? 0); ?></td>
                                                <td class="num"><?php echo $n($course['male_count'] ?? 0); ?></td>
                                                <td class="num"><strong><?php echo $n($course['total_count'] ?? 0); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ---- Section nav: highlight the section being read ---- */
    var links = [].slice.call(document.querySelectorAll('#dhNav a'));
    var targets = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
    function spy() {
        var current = 0;
        targets.forEach(function (t, i) {
            if (t && t.getBoundingClientRect().top <= 140) { current = i; }
        });
        links.forEach(function (a, i) { a.classList.toggle('active', i === current); });
    }
    links.forEach(function (a) {
        a.addEventListener('click', function (e) {
            var t = document.getElementById(a.getAttribute('href').slice(1));
            if (t) {
                e.preventDefault();
                t.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            }
        });
    });
    // The scrolling element is whichever ancestor scrolls in this layout, so listen on capture.
    window.addEventListener('scroll', spy, true);
    spy();

    /* ---- Charts: every <canvas data-chart='{json}'> ---- */
    if (typeof Chart === 'undefined') { return; }
    Chart.defaults.font.family = 'inherit';
    Chart.defaults.color = '#475569';
    var gridColor = '#e8edf5';

    function sum(a) { return (a || []).reduce(function (s, v) { return s + (+v || 0); }, 0); }
    function empty(canvas) {
        var p = document.createElement('p');
        p.className = 'dh-empty';
        p.textContent = 'No records.';
        canvas.parentNode.replaceChildren(p);
    }
    function doughnut(canvas, c) {
        var labels = [], values = [], colors = [];
        (c.labels || []).forEach(function (l, i) {
            if ((c.values[i] || 0) > 0) { labels.push(l); values.push(c.values[i]); colors.push(c.colors[i]); }
        });
        if (!values.length) { return empty(canvas); }
        new Chart(canvas, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '64%', plugins: { legend: { display: false } } }
        });
    }
    function bar(canvas, c) {
        var all = [];
        (c.datasets || []).forEach(function (d) { all = all.concat(d.data); });
        if (!sum(all)) { return empty(canvas); }
        var horizontal = !!c.horizontal, stacked = !!c.stacked;
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: c.labels,
                datasets: c.datasets.map(function (d) {
                    return { label: d.label, data: d.data, backgroundColor: d.colors || d.color, borderRadius: 6, maxBarThickness: 30 };
                })
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: c.datasets.length > 1, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } } },
                scales: {
                    x: { stacked: stacked, beginAtZero: true, grid: { display: !horizontal ? false : true, color: gridColor }, ticks: { precision: 0 } },
                    y: { stacked: stacked, beginAtZero: true, grid: { display: horizontal ? false : true, color: gridColor }, ticks: { precision: 0 } }
                }
            }
        });
    }
    document.querySelectorAll('canvas[data-chart]').forEach(function (canvas) {
        var c;
        try { c = JSON.parse(canvas.getAttribute('data-chart')); } catch (e) { return; }
        if (c.type === 'doughnut') { doughnut(canvas, c); } else { bar(canvas, c); }
    });
});
</script>
