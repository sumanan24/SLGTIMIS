<?php
/**
 * Student portal dashboard.
 * Data comes from StudentDashboardController::index().
 */
$e = static fn ($s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$dash = static function ($s): string {
    $s = trim((string) $s);
    return $s === '' ? '—' : htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};
$fmtDate = static function ($s): string {
    $s = trim((string) $s);
    $ts = $s !== '' ? strtotime($s) : false;
    return $ts ? date('d M Y', $ts) : '';
};

$recentPayments = $recentPayments ?? [];
$busSeasonPayments = $busSeasonPayments ?? [];
$currentGroup = $currentGroup ?? null;
$student = $student ?? [];
$currentEnrollment = $currentEnrollment ?? null;
$hostelAllocation = $hostelAllocation ?? null;
$roommates = $roommates ?? [];
$hasAcceptedConduct = !empty($hasAcceptedConduct);

require_once BASE_PATH . '/models/StudentModel.php';
$studentModelHelper = new StudentModel();
$profileImageUrl = $studentModelHelper->getProfileImagePath($student);

// Header facts
$fullName = trim((string) ($student['student_fullname'] ?? ''));
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$modeRaw = (string) ($currentEnrollment['course_mode'] ?? '');
$modeLabel = $modeRaw === 'Part' || strcasecmp($modeRaw, 'Part Time') === 0
    ? 'Part Time'
    : ($modeRaw === 'Full' || strcasecmp($modeRaw, 'Full Time') === 0 ? 'Full Time' : ($modeRaw !== '' ? $modeRaw : ''));
$status = (string) ($student['student_status'] ?? 'Active');
$statusClass = strcasecmp($status, 'Active') === 0 ? 'success' : 'warning';
$enrollStatus = (string) ($currentEnrollment['student_enroll_status'] ?? '');
$courseName = (string) ($currentEnrollment['course_name'] ?? $currentEnrollment['course_id'] ?? '');
$phone = (string) ($student['student_phone'] ?? '');
$phone = ($phone === '0') ? '' : $phone;
$address = implode(', ', array_filter([
    trim((string) ($student['student_address'] ?? '')),
    trim((string) ($student['student_district'] ?? '')),
    trim((string) ($student['student_provice'] ?? '')),
]));

// Attendance
$attendancePercentage = (float) ($attendancePercentage ?? 0);
$presentDays = (int) ($presentDays ?? 0);
$absentDays = (int) ($absentDays ?? 0);
$holidayDays = (int) ($holidayDays ?? 0);
$totalDays = (int) ($totalDays ?? 0);
$attTone = $totalDays === 0 ? 'muted' : ($attendancePercentage >= 85 ? 'ok' : ($attendancePercentage >= 75 ? 'warn' : 'bad'));
$attLabel = $totalDays === 0 ? 'No records yet' : ($attendancePercentage >= 85 ? 'Good standing' : ($attendancePercentage >= 75 ? 'Needs attention' : 'Low attendance'));
$ringDeg = (int) round(max(0, min(100, $attendancePercentage)) * 3.6);
$currentMonth = $currentMonth ?? date('Y-m');
$attendanceRecords = $attendanceRecords ?? [];
$attendanceDayMap = is_array($attendanceDayMap ?? null) ? $attendanceDayMap : [];
$autoQuickSync = !empty($autoQuickSync);
$quickSyncUrl = (string) ($quickSyncUrl ?? (rtrim(APP_URL, '/') . '/student/attendance/quick-sync'));

// One combined, newest-first payment list
$payRows = [];
foreach ($busSeasonPayments as $p) {
    $payRows[] = [
        'ts' => !empty($p['payment_date']) ? (int) strtotime((string) $p['payment_date']) : 0,
        'desc' => 'Bus season ticket',
        'amount' => (float) ($p['paid_amount'] ?? 0),
        'status' => trim((string) ($p['status'] ?? '')),
    ];
}
foreach ($recentPayments as $p) {
    $payRows[] = [
        'ts' => !empty($p['pays_date']) ? (int) strtotime((string) $p['pays_date']) : 0,
        'desc' => trim((string) ($p['payment_reason'] ?? '')) ?: 'Payment',
        'amount' => (float) ($p['pays_amount'] ?? 0),
        'status' => '',
    ];
}
usort($payRows, static fn ($a, $b) => $b['ts'] <=> $a['ts']);
$payRows = array_slice($payRows, 0, 5);

$studentNotices = is_array($studentNotices ?? null) ? $studentNotices : [];
$firstNotice = $studentNotices[0] ?? null;

$shortcuts = [
    ['student/profile', 'fa-user', 'My Profile', false],
    ['student/attendance', 'fa-calendar-check', 'Attendance', false],
    ['student/my-attendance', 'fa-fingerprint', 'Biometric', false],
    ['student/my-timetable', 'fa-calendar-week', 'Timetable', false],
    ['student/training-programmes', 'fa-people-carry-box', 'Training', false],
    ['student/payments', 'fa-money-bill-wave', 'Payments', false],
    ['student/notices', 'fa-bullhorn', 'Notices', false],
    ['student/documents-pdf', 'fa-file-pdf', 'Documents', true],
    ['student/forms', 'fa-file-alt', 'Forms', false],
];
?>
<style>
.sd-page {
    --sd-navy: #001f3f;
    --sd-blue: #00478a;
    --sd-line: #e6eaf0;
    --sd-muted: #6c757d;
    --sd-ok: #198754;
    --sd-warn: #d97706;
    --sd-bad: #dc3545;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
}
.sd-page *,
.sd-page *::before,
.sd-page *::after { box-sizing: border-box; }
.sd-page .min-w-0 { min-width: 0; }

/* Hero */
.sd-hero {
    position: relative;
    overflow: hidden;
    color: #fff;
    border-radius: 16px;
    background: linear-gradient(120deg, var(--sd-navy) 0%, var(--sd-blue) 100%);
    padding: 1.25rem 1.35rem;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1.15rem 1.4rem;
    align-items: center;
    box-shadow: 0 6px 18px rgba(0, 31, 63, .18);
}
.sd-hero::after {
    content: "";
    position: absolute;
    right: -60px;
    top: -80px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .06);
    pointer-events: none;
}
.sd-hero-main {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
    position: relative;
    z-index: 1;
}
.sd-photo {
    width: 84px;
    height: 84px;
    object-fit: cover;
    border-radius: 50%;
    flex-shrink: 0;
    border: 3px solid rgba(255, 255, 255, .9);
    background: rgba(255, 255, 255, .15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
}
.sd-photo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}
.sd-greet { font-size: .78rem; opacity: .8; margin: 0 0 .15rem; }
.sd-name {
    font-size: clamp(1.05rem, 2.8vw, 1.4rem);
    font-weight: 700;
    line-height: 1.25;
    margin: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.sd-meta {
    margin: .35rem 0 0;
    font-size: .8rem;
    opacity: .92;
    overflow-wrap: anywhere;
}
.sd-meta .mono {
    font-family: ui-monospace, Menlo, Consolas, monospace;
}
.sd-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .7rem;
}
.sd-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    max-width: 100%;
    padding: .2rem .55rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 500;
    line-height: 1.25;
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .22);
    overflow-wrap: anywhere;
}
.sd-chip i { flex-shrink: 0; }
.sd-chip.ok i { color: #6ee7a8; }
.sd-chip.warn i { color: #fcd34d; }
.sd-hero-ring {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: .85rem;
    min-width: 0;
}
.sd-ring {
    --deg: 0deg;
    --clr: #6ee7a8;
    width: 92px;
    height: 92px;
    border-radius: 50%;
    flex-shrink: 0;
    background: conic-gradient(var(--clr) var(--deg), rgba(255, 255, 255, .18) 0);
    display: flex;
    align-items: center;
    justify-content: center;
}
.sd-ring.warn { --clr: #fcd34d; }
.sd-ring.bad { --clr: #fca5a5; }
.sd-ring.muted { --clr: rgba(255, 255, 255, .35); }
.sd-ring > span {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: var(--sd-navy);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1.1;
}
.sd-ring b { font-size: 1.15rem; }
.sd-ring small { font-size: .58rem; opacity: .75; }
.sd-ring-txt {
    font-size: .74rem;
    line-height: 1.35;
    min-width: 0;
}
.sd-ring-txt strong {
    display: block;
    font-size: .84rem;
    margin-bottom: .1rem;
}

/* Notice strip */
.sd-notice {
    display: flex;
    gap: .75rem;
    align-items: center;
    text-decoration: none;
    color: inherit;
    background: #fff;
    border: 1px solid var(--sd-line);
    border-left: 4px solid var(--sd-blue);
    border-radius: 12px;
    padding: .8rem .95rem;
    min-width: 0;
}
.sd-notice:hover { box-shadow: 0 3px 10px rgba(0, 31, 63, .08); color: inherit; }
.sd-notice .ic { color: var(--sd-blue); font-size: 1.1rem; flex-shrink: 0; }
.sd-notice .t {
    font-weight: 600;
    font-size: .84rem;
    color: #1b2733;
    overflow-wrap: anywhere;
}
.sd-notice p {
    margin: .15rem 0 0;
    font-size: .76rem;
    color: #5c6570;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sd-notice .go { margin-left: auto; color: var(--sd-muted); flex-shrink: 0; }

/* KPI tiles */
.sd-kpi {
    background: #fff;
    border: 1px solid var(--sd-line);
    border-radius: 12px;
    height: 100%;
    padding: .85rem .9rem;
    display: flex;
    align-items: center;
    gap: .7rem;
    box-shadow: 0 1px 3px rgba(0, 31, 63, .05);
    min-width: 0;
}
.sd-kpi > div:last-child { min-width: 0; }
.sd-kpi .ic {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.sd-kpi .ic.blue { background: #e6eefb; color: var(--sd-blue); }
.sd-kpi .ic.ok { background: #e3f4ea; color: var(--sd-ok); }
.sd-kpi .ic.bad { background: #fde8ea; color: var(--sd-bad); }
.sd-kpi .ic.warn { background: #fdf1dc; color: var(--sd-warn); }
.sd-kpi .n {
    font-size: clamp(1.05rem, 3.5vw, 1.4rem);
    font-weight: 700;
    line-height: 1.1;
    color: #1b2733;
}
.sd-kpi .l {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--sd-muted);
    font-weight: 600;
    line-height: 1.25;
}

/* Shortcuts */
.sd-dock {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .55rem;
}
.sd-dock a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    padding: .75rem .35rem;
    min-height: 84px;
    min-width: 0;
    text-decoration: none;
    color: #1b2733;
    text-align: center;
    background: #fff;
    border: 1px solid var(--sd-line);
    border-radius: 12px;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease, color .15s ease;
}
.sd-dock a:hover,
.sd-dock a:focus-visible {
    border-color: var(--sd-navy);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 31, 63, .1);
    color: var(--sd-navy);
}
.sd-dock .ic {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sd-navy);
    color: #fff;
    font-size: .9rem;
    flex-shrink: 0;
}
.sd-dock span.t {
    font-size: .72rem;
    font-weight: 600;
    line-height: 1.2;
    overflow-wrap: anywhere;
    max-width: 100%;
}

/* Cards */
.sd-card {
    background: #fff;
    border: 1px solid var(--sd-line);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 31, 63, .05);
    min-width: 0;
    overflow: hidden;
}
.sd-card-h {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .65rem;
    padding: .75rem 1rem;
    border-bottom: 1px solid #eef1f4;
}
.sd-card-h h2 {
    margin: 0;
    font-size: .9rem;
    font-weight: 700;
    color: var(--sd-navy);
    display: flex;
    align-items: center;
    gap: .45rem;
    min-width: 0;
    line-height: 1.3;
}
.sd-card-h h2 i { color: var(--sd-blue); flex-shrink: 0; }
.sd-card-h h2 span { min-width: 0; overflow-wrap: anywhere; }
.sd-card-h a {
    font-size: .74rem;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
}
.sd-card-b { padding: .95rem 1rem; }

/* Label / value lists */
.sd-kv {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .8rem 1.25rem;
    margin: 0;
}
.sd-kv.one { grid-template-columns: minmax(0, 1fr); gap: 0; }
.sd-kv dt {
    font-size: .66rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--sd-muted);
    font-weight: 600;
    margin: 0 0 .12rem;
}
.sd-kv dd {
    margin: 0;
    font-size: .86rem;
    font-weight: 600;
    color: #1b2733;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.sd-kv .span { grid-column: 1 / -1; }
.sd-kv.one > div {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .85rem;
    padding: .55rem 0;
    border-bottom: 1px solid #f1f3f6;
}
.sd-kv.one > div:last-child { border-bottom: 0; }
.sd-kv.one dt {
    margin: 0;
    flex: 0 0 auto;
    max-width: 42%;
}
.sd-kv.one dd {
    text-align: right;
    font-size: .82rem;
    flex: 1 1 auto;
    min-width: 0;
}

.sd-empty {
    text-align: center;
    color: var(--sd-muted);
    padding: 1.15rem .5rem;
    font-size: .84rem;
}
.sd-empty i {
    display: block;
    font-size: 1.4rem;
    margin-bottom: .35rem;
    opacity: .5;
}

/* Payments */
.sd-pay { width: 100%; font-size: .8rem; table-layout: fixed; }
.sd-pay td {
    padding: .5rem 0;
    border-bottom: 1px solid #f1f3f6;
    vertical-align: top;
}
.sd-pay tr:last-child td { border-bottom: 0; }
.sd-pay td:first-child {
    padding-right: .75rem;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.sd-pay .d {
    color: var(--sd-muted);
    font-size: .7rem;
    display: block;
    margin-top: .1rem;
}
.sd-pay .a {
    width: 7.5rem;
    text-align: right;
    font-weight: 700;
    white-space: nowrap;
}

/* Calendar */
.sd-cal {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 4px;
    width: 100%;
}
.sd-cal-hd {
    text-align: center;
    font-size: .68rem;
    font-weight: 700;
    color: var(--sd-muted);
    padding-bottom: .2rem;
    text-transform: uppercase;
}
.attendance-calendar-day {
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .05rem;
    border-radius: 7px;
    font-weight: 600;
    font-size: clamp(.62rem, 2.2vw, .8rem);
    min-height: 0;
    padding: .15rem .1rem;
    line-height: 1.1;
}
.sd-cal-rich .attendance-calendar-day {
    aspect-ratio: auto;
    min-height: 2.6rem;
}
.sd-day-num {
    display: inline-flex;
    align-items: center;
    gap: .12rem;
}
.sd-day-times {
    display: block;
    font-size: .52rem;
    font-weight: 600;
    opacity: .85;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sd-att-rule {
    margin: 0;
    font-size: .78rem;
    color: var(--sd-muted);
}
.attendance-pad { visibility: hidden; }
.attendance-present { background: #d4edda; color: #155724; }
.attendance-present-finger { background: #ecfdf5; color: #047857; }
.attendance-present-manual { background: #e0f2fe; color: #0369a1; }
.attendance-absent { background: #f8d7da; color: #721c24; }
.attendance-holiday { background: #fff3cd; color: #856404; }
.attendance-empty { background: #eef0f3; color: #6c757d; }
.attendance-weekend { background: #f8f9fa; color: #b6bdc4; }
.attendance-today { outline: 2px solid var(--sd-blue); outline-offset: 1px; }
.sd-sync-bar {
    display: none;
    margin: .85rem 0 0;
    padding: .55rem .7rem;
    border-radius: 8px;
    border: 1px solid #cfe2ff;
    background: #f0f7ff;
    font-size: .78rem;
    color: #1e3a5f;
}
.sd-sync-bar.show { display: block; }
.sd-sync-bar.ok { border-color: #badbcc; background: #edf7f0; color: #0f5132; }
.sd-sync-bar.bad { border-color: #f5c2c7; background: #f8d7da; color: #842029; }
.sd-sync-bar .sd-sync-track {
    height: 4px;
    background: rgba(15, 23, 42, .08);
    border-radius: 999px;
    margin-top: .4rem;
    overflow: hidden;
}
.sd-sync-bar .sd-sync-fill {
    height: 100%;
    width: 0;
    background: var(--sd-blue, #0d6efd);
    transition: width .25s ease;
}
.sd-att-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    align-items: center;
}
.sd-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem .85rem;
    justify-content: center;
    font-size: .72rem;
    color: var(--sd-muted);
    margin-top: .85rem;
}
.sd-legend .sw {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    display: inline-block;
    vertical-align: -2px;
    margin-right: .28rem;
}

/* Tablet */
@media (max-width: 1199.98px) {
    .sd-dock { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
    .sd-hero {
        grid-template-columns: 1fr;
        align-items: stretch;
    }
    .sd-hero-ring {
        justify-content: flex-start;
        padding-top: .85rem;
        border-top: 1px solid rgba(255, 255, 255, .18);
    }
    .sd-dock { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

/* Phone / small tablet */
@media (max-width: 767.98px) {
    .sd-hero {
        padding: 1rem;
        border-radius: 14px;
        gap: .85rem;
    }
    .sd-hero-main {
        align-items: flex-start;
        gap: .75rem;
    }
    .sd-photo {
        width: 58px;
        height: 58px;
        border-width: 2px;
    }
    .sd-photo-fallback { font-size: 1.35rem; }
    .sd-greet { font-size: .72rem; }
    .sd-meta { font-size: .74rem; }
    .sd-chips { gap: .3rem; margin-top: .55rem; }
    .sd-chip { font-size: .66rem; padding: .15rem .5rem; }
    .sd-hero-ring { gap: .7rem; }
    .sd-ring { width: 68px; height: 68px; }
    .sd-ring > span { width: 52px; height: 52px; }
    .sd-ring b { font-size: .95rem; }
    .sd-ring small { font-size: .52rem; }
    .sd-ring-txt { font-size: .7rem; }
    .sd-ring-txt strong { font-size: .78rem; }

    .sd-notice { padding: .7rem .8rem; gap: .6rem; }
    .sd-notice .t { font-size: .8rem; }
    .sd-notice p { font-size: .72rem; -webkit-line-clamp: 3; line-clamp: 3; }

    .sd-kpi {
        padding: .7rem .65rem;
        gap: .55rem;
        border-radius: 10px;
    }
    .sd-kpi .ic {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        font-size: .9rem;
    }
    .sd-kpi .l { font-size: .62rem; letter-spacing: .03em; }

    .sd-dock { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .sd-dock a {
        flex-direction: row;
        justify-content: flex-start;
        align-items: center;
        min-height: 54px;
        padding: .55rem .7rem;
        gap: .65rem;
        text-align: left;
        border-radius: 10px;
    }
    .sd-dock .ic {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        font-size: .82rem;
    }
    .sd-dock span.t { font-size: .78rem; }

    .sd-card { border-radius: 11px; }
    .sd-card-h {
        flex-wrap: wrap;
        align-items: flex-start;
        padding: .7rem .85rem;
    }
    .sd-card-h h2 { font-size: .84rem; }
    .sd-card-b { padding: .85rem; }

    .sd-kv { grid-template-columns: 1fr; gap: 0; }
    .sd-kv > div {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .85rem;
        padding: .55rem 0;
        border-bottom: 1px solid #f1f3f6;
    }
    .sd-kv > div:last-child { border-bottom: 0; }
    .sd-kv dt {
        margin: 0;
        flex: 0 0 auto;
        max-width: 42%;
        align-self: center;
    }
    .sd-kv dd {
        text-align: right;
        font-size: .82rem;
        flex: 1 1 auto;
        min-width: 0;
    }
    .sd-kv .span {
        flex-direction: column;
        align-items: flex-start;
        gap: .2rem;
    }
    .sd-kv .span dt,
    .sd-kv .span dd {
        max-width: 100%;
        text-align: left;
    }

    .sd-pay .a { width: 6.5rem; font-size: .78rem; }
    .sd-cal { gap: 3px; }
    .sd-cal .attendance-calendar-day i { display: none; }
    .attendance-calendar-day { border-radius: 6px; }
    .sd-legend {
        gap: .4rem .7rem;
        font-size: .68rem;
        justify-content: flex-start;
    }
}

/* Very small phones */
@media (max-width: 399.98px) {
    .sd-hero { padding: .85rem; }
    .sd-photo { width: 52px; height: 52px; }
    .sd-kpi .ic { display: none; }
    .sd-kpi { padding: .65rem .55rem; }
    .sd-dock a { padding: .5rem .55rem; min-height: 50px; }
    .sd-dock span.t { font-size: .72rem; }
    .sd-pay .a { width: 5.75rem; }
}
</style>

<div class="sd-page">

    <!-- Hero -->
    <section class="sd-hero mb-3" aria-label="Student summary">
        <div class="sd-hero-main">
            <?php if ($profileImageUrl): ?>
                <img src="<?php echo $e($profileImageUrl); ?>" alt="Student photo" class="sd-photo">
            <?php else: ?>
                <div class="sd-photo sd-photo-fallback"><i class="fas fa-user-graduate"></i></div>
            <?php endif; ?>
            <div class="min-w-0">
                <p class="sd-greet"><?php echo $e($greeting); ?>,</p>
                <h1 class="sd-name"><?php echo $dash($fullName); ?></h1>
                <p class="sd-meta">Student ID <span class="mono fw-semibold"><?php echo $dash($student['student_id'] ?? ''); ?></span></p>
                <div class="sd-chips">
                    <span class="sd-chip <?php echo $statusClass === 'success' ? 'ok' : 'warn'; ?>"><i class="fas fa-circle" style="font-size:.5rem;"></i><?php echo $dash($status); ?></span>
                    <?php if ($courseName !== ''): ?>
                        <span class="sd-chip"><i class="fas fa-book-open"></i><?php echo $e($courseName); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($currentEnrollment['academic_year'])): ?>
                        <span class="sd-chip"><i class="fas fa-calendar-alt"></i><?php echo $e($currentEnrollment['academic_year']); ?></span>
                    <?php endif; ?>
                    <?php if ($modeLabel !== ''): ?>
                        <span class="sd-chip"><i class="fas fa-clock"></i><?php echo $e($modeLabel); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($currentGroup['name'])): ?>
                        <span class="sd-chip"><i class="fas fa-users"></i><?php echo $e($currentGroup['name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sd-hero-ring">
            <div class="sd-ring <?php echo $attTone === 'ok' ? '' : $e($attTone); ?>" style="--deg: <?php echo $ringDeg; ?>deg;" role="img"
                 aria-label="Attendance this month <?php echo (int) round($attendancePercentage); ?> percent">
                <span><b><?php echo $totalDays === 0 ? '—' : (int) round($attendancePercentage) . '%'; ?></b><small>this month</small></span>
            </div>
            <div class="sd-ring-txt">
                <strong>Attendance</strong>
                <?php echo $e($attLabel); ?><br>
                <?php echo $presentDays; ?> of <?php echo $totalDays; ?> days present
            </div>
        </div>
    </section>

    <?php if ($firstNotice): ?>
    <a href="<?php echo APP_URL; ?>/student/notices" class="sd-notice mb-3">
        <span class="ic"><i class="fas fa-bullhorn"></i></span>
        <div class="min-w-0">
            <div class="t"><?php echo $e($firstNotice['title'] ?? 'SLGTI notice'); ?></div>
            <p><?php echo $e($firstNotice['body'] ?? ''); ?></p>
        </div>
        <span class="go"><i class="fas fa-chevron-right"></i></span>
    </a>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="sd-kpi">
                <span class="ic blue"><i class="fas fa-percent"></i></span>
                <div class="min-w-0">
                    <div class="n"><?php echo $totalDays === 0 ? '—' : (int) round($attendancePercentage) . '%'; ?></div>
                    <div class="l">Attendance</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="sd-kpi">
                <span class="ic ok"><i class="fas fa-check"></i></span>
                <div class="min-w-0">
                    <div class="n"><?php echo $presentDays; ?></div>
                    <div class="l">Days present</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="sd-kpi">
                <span class="ic bad"><i class="fas fa-times"></i></span>
                <div class="min-w-0">
                    <div class="n"><?php echo $absentDays; ?></div>
                    <div class="l">Days absent</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="sd-kpi">
                <span class="ic warn"><i class="fas fa-star"></i></span>
                <div class="min-w-0">
                    <div class="n"><?php echo $holidayDays; ?></div>
                    <div class="l">Holidays</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <nav class="sd-dock mb-3" aria-label="Student shortcuts">
        <?php foreach ($shortcuts as [$path, $icon, $label, $newTab]): ?>
            <a href="<?php echo APP_URL . '/' . $path; ?>"<?php echo $newTab ? ' target="_blank" rel="noopener"' : ''; ?>>
                <span class="ic"><i class="fas <?php echo $icon; ?>"></i></span><span class="t"><?php echo $e($label); ?></span>
            </a>
        <?php endforeach; ?>
        <a href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <span class="ic"><i class="fas fa-key"></i></span><span class="t">Password</span>
        </a>
    </nav>

    <div class="row g-2 g-md-3 mb-3">
        <!-- Left column: attendance calendar first (existing 7h P/A), then enrollment -->
        <div class="col-12 col-lg-8 min-w-0">
            <section class="sd-card mb-2 mb-md-3">
                <div class="sd-card-h">
                    <h2><i class="fas fa-calendar-check"></i><span>Attendance · <?php echo $e(date('F Y', strtotime($currentMonth . '-01'))); ?></span></h2>
                    <div class="sd-att-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="sdQuickSyncBtn" title="Pull today's punches from fingerprint readers">
                            <i class="fas fa-sync-alt"></i> Quick sync
                        </button>
                        <a href="<?php echo APP_URL; ?>/student/attendance">Full calendar</a>
                    </div>
                </div>
                <div class="sd-card-b">
                    <p class="sd-att-rule mb-2">Fingerprint: <strong>Present</strong> if In→Out &gt; 7 hours · otherwise <strong>Absent</strong>. Manual marks override.</p>
                    <?php
                    $firstDay = strtotime($currentMonth . '-01');
                    $daysInMonth = (int) date('t', $firstDay);
                    $firstDayOfWeek = (int) date('w', $firstDay);
                    $weekDays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
                    $weekDaysFull = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    $todayYmd = date('Y-m-d');
                    ?>
                    <div class="sd-cal sd-cal-rich" id="sdAttendanceCal">
                        <?php foreach ($weekDaysFull as $i => $day): ?>
                            <div class="sd-cal-hd"><span class="d-none d-sm-inline"><?php echo $day; ?></span><span class="d-sm-none"><?php echo $weekDays[$i]; ?></span></div>
                        <?php endforeach; ?>
                        <?php
                        for ($i = 0; $i < $firstDayOfWeek; $i++) {
                            echo '<div class="attendance-calendar-day attendance-pad"></div>';
                        }
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $date = $currentMonth . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                            $cell = $attendanceDayMap[$date] ?? null;
                            $status = is_array($cell) ? ($cell['status'] ?? null) : null;
                            $source = is_array($cell) ? (string) ($cell['source'] ?? '') : '';
                            $label = is_array($cell) ? (string) ($cell['label'] ?? '') : '';
                            $timeIn = is_array($cell) ? trim((string) ($cell['time_in'] ?? '')) : '';
                            $timeOut = is_array($cell) ? trim((string) ($cell['time_out'] ?? '')) : '';
                            $durSec = is_array($cell) ? (int) ($cell['duration_seconds'] ?? 0) : 0;

                            $class = 'attendance-empty';
                            $icon = '';
                            if ($status === 'weekend') {
                                $class = 'attendance-weekend';
                            } elseif ($status === 'present') {
                                $class = $source === 'manual' ? 'attendance-present-manual' : 'attendance-present-finger';
                                $icon = '<i class="fas fa-check"></i>';
                            } elseif ($status === 'absent') {
                                $class = 'attendance-absent';
                                $icon = '<i class="fas fa-times"></i>';
                            } elseif ($status === 'holiday') {
                                $class = 'attendance-holiday';
                                $icon = '<i class="fas fa-star"></i>';
                            }
                            if ($date === $todayYmd) {
                                $class .= ' attendance-today';
                            }
                            $titleBits = [date('l, F j, Y', strtotime($date))];
                            if ($label !== '') {
                                $titleBits[] = $label;
                            }
                            if ($timeIn !== '' || $timeOut !== '') {
                                $titleBits[] = 'In ' . ($timeIn !== '' ? $timeIn : '—') . ' / Out ' . ($timeOut !== '' ? $timeOut : '—');
                            }
                            if ($durSec > 0) {
                                $titleBits[] = sprintf('%dh %02dm', intdiv($durSec, 3600), intdiv($durSec % 3600, 60));
                            }
                            $inner = '<span class="sd-day-num">' . $icon . $day . '</span>';
                            if ($status !== 'weekend' && ($timeIn !== '' || $timeOut !== '')) {
                                $inner .= '<span class="sd-day-times">' . $e(($timeIn !== '' ? $timeIn : '—') . '–' . ($timeOut !== '' ? $timeOut : '—')) . '</span>';
                            }
                            echo '<div class="attendance-calendar-day ' . $class . '" title="' . $e(implode(' · ', $titleBits)) . '">' . $inner . '</div>';
                        }
                        ?>
                    </div>
                    <div class="sd-legend">
                        <span><span class="sw attendance-present-finger"></span>Present (&gt;7h)</span>
                        <span><span class="sw attendance-present-manual"></span>Present (manual)</span>
                        <span><span class="sw attendance-absent"></span>Absent (&lt;7h / incomplete)</span>
                        <span><span class="sw attendance-holiday"></span>Holiday</span>
                        <span><span class="sw attendance-empty"></span>No record</span>
                        <span><span class="sw attendance-weekend" style="border:1px solid #e3e6ea;"></span>Weekend</span>
                    </div>
                    <div class="sd-sync-bar" id="sdQuickSyncBar" role="status" aria-live="polite">
                        <div>
                            <strong id="sdQuickSyncTitle">Syncing today…</strong>
                            <span id="sdQuickSyncSpin" class="ms-1"><i class="fas fa-spinner fa-spin"></i></span>
                        </div>
                        <div id="sdQuickSyncMsg">Connecting to readers…</div>
                        <div class="sd-sync-track"><div class="sd-sync-fill" id="sdQuickSyncProgress"></div></div>
                    </div>
                </div>
            </section>

            <section class="sd-card mb-2 mb-lg-0">
                <div class="sd-card-h"><h2><i class="fas fa-university"></i>Academic enrollment</h2></div>
                <div class="sd-card-b">
                    <?php if ($currentEnrollment): ?>
                    <dl class="sd-kv">
                        <div class="span"><dt>Course / programme</dt><dd><?php echo $dash($courseName); ?></dd></div>
                        <div><dt>Department</dt><dd><?php echo $dash($currentEnrollment['department_name'] ?? ''); ?></dd></div>
                        <div><dt>Academic year</dt><dd><?php echo $dash($currentEnrollment['academic_year'] ?? ''); ?></dd></div>
                        <div><dt>Shift / mode</dt><dd><?php echo $dash($modeLabel); ?></dd></div>
                        <div><dt>Group / batch</dt><dd><?php echo $dash($currentGroup['name'] ?? ''); ?></dd></div>
                        <div><dt>Enrollment status</dt><dd><?php echo $dash($enrollStatus); ?></dd></div>
                        <div><dt>Enrolled on</dt><dd><?php echo $dash($fmtDate($currentEnrollment['student_enroll_date'] ?? '')); ?></dd></div>
                        <div><dt>NVQ level</dt><dd><?php echo $dash($currentEnrollment['course_nvq_level'] ?? ''); ?></dd></div>
                    </dl>
                    <?php else: ?>
                        <div class="sd-empty"><i class="fas fa-graduation-cap"></i>No current enrollment is linked to this account.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Right column -->
        <div class="col-12 col-lg-4 min-w-0">
            <section class="sd-card mb-2 mb-md-3">
                <div class="sd-card-h">
                    <h2><i class="fas fa-id-badge"></i>Personal details</h2>
                    <a href="<?php echo APP_URL; ?>/student/profile">View</a>
                </div>
                <div class="sd-card-b">
                    <dl class="sd-kv one">
                        <div><dt>Student ID</dt><dd><?php echo $dash($student['student_id'] ?? ''); ?></dd></div>
                        <div><dt>NIC</dt><dd><?php echo $dash($student['student_nic'] ?? ''); ?></dd></div>
                        <div><dt>Gender</dt><dd><?php echo $dash($student['student_gender'] ?? ''); ?></dd></div>
                        <div><dt>Date of birth</dt><dd><?php echo $dash($fmtDate($student['student_dob'] ?? '')); ?></dd></div>
                        <div><dt>Email</dt><dd><?php echo $dash($student['student_email'] ?? ''); ?></dd></div>
                        <div><dt>Phone</dt><dd><?php echo $dash($phone); ?></dd></div>
                        <div><dt>WhatsApp</dt><dd><?php echo $dash($student['student_whatsapp'] ?? ''); ?></dd></div>
                        <div><dt>Address</dt><dd><?php echo $dash($address); ?></dd></div>
                    </dl>
                </div>
            </section>

            <section class="sd-card mb-2 mb-md-3">
                <div class="sd-card-h"><h2><i class="fas fa-bed"></i>Hostel</h2></div>
                <div class="sd-card-b">
                    <?php if ($hostelAllocation): ?>
                        <dl class="sd-kv one mb-2">
                            <div><dt>Hostel</dt><dd><?php echo $dash($hostelAllocation['hostel_name'] ?? ''); ?></dd></div>
                            <div><dt>Room</dt><dd><?php echo $dash($hostelAllocation['room_no'] ?? ''); ?></dd></div>
                        </dl>
                        <div class="small fw-semibold text-muted mt-2 mb-1">Roommates</div>
                        <?php if (!empty($roommates)): ?>
                            <ul class="list-unstyled mb-0 small">
                                <?php foreach ($roommates as $rm): ?>
                                    <li class="py-1"><?php echo $dash($rm['student_fullname'] ?? ''); ?> <span class="text-muted">&middot; <?php echo $dash($rm['student_id'] ?? ''); ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted small mb-0">No other active roommates.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="sd-empty"><i class="fas fa-bed"></i>No hostel allocation.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="sd-card">
                <div class="sd-card-h">
                    <h2><i class="fas fa-money-bill-wave"></i>Recent payments</h2>
                    <a href="<?php echo APP_URL; ?>/student/payments">View all</a>
                </div>
                <div class="sd-card-b">
                    <?php if (!empty($payRows)): ?>
                        <table class="sd-pay">
                            <tbody>
                            <?php foreach ($payRows as $p): ?>
                                <tr>
                                    <td>
                                        <?php echo $e($p['desc']); ?>
                                        <span class="d"><?php echo $e($p['ts'] ? date('d M Y', $p['ts']) : '—'); ?><?php echo $p['status'] !== '' ? ' &middot; ' . $e($p['status']) : ''; ?></span>
                                    </td>
                                    <td class="a">Rs. <?php echo number_format($p['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="sd-empty"><i class="fas fa-receipt"></i>No payments recorded.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/student/change-password" id="changePasswordForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-key me-2 text-danger"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="old_password" class="form-label fw-semibold">Old Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="old_password" name="old_password" required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="old_password" aria-label="Show password" title="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="new_password" aria-label="Show password" title="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="form-text">Minimum 8 characters, must include 1 capital, 1 small, and 1 number.</div>
                    </div>
                    <div class="mb-0">
                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save me-1"></i>Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$hasAcceptedConduct): ?>
<div class="modal fade" id="conductModal" tabindex="-1" aria-labelledby="conductModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="conductModalLabel"><i class="fas fa-file-contract me-2"></i>SLGTI STUDENT CODE OF CONDUCT</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Important:</strong> Please read the following declaration carefully before accepting.</div>
                <div class="border rounded p-3 p-md-4 mb-3" style="background-color: #f8f9fa; max-height: min(400px, 45vh); overflow-y: auto;">
                    <h6 class="fw-bold mb-3">SLGTI Student Code of Conduct and Honor</h6>
                    <p>I hereby confirm that I have read, understood, and agreed to comply with the SLGTI Student Code of Conduct and Honor, including all rules, regulations, policies, and procedures of the Sri Lanka–German Training Institute (SLGTI). I acknowledge my responsibility to maintain discipline, academic integrity, professional conduct, and respect for all members of the SLGTI community and its property. I understand that this Code applies to my conduct on campus, off campus, and during all SLGTI-authorized activities, including industrial training and On-the-Job Training (OJT). I further understand that any violation of this Code may result in disciplinary action in accordance with SLGTI regulations, including warnings, suspension, or expulsion. By submitting this declaration electronically, I confirm that this acceptance is legally binding and equivalent to my handwritten signature.</p>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="agreeCheckbox" required>
                    <label class="form-check-label" for="agreeCheckbox"><strong>I agree to the SLGTI Student Code of Conduct and Honor</strong></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="acceptConductBtn" disabled><i class="fas fa-check me-2"></i>Accept &amp; Continue</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!$hasAcceptedConduct): ?>
document.addEventListener('DOMContentLoaded', function() {
    const conductModal = new bootstrap.Modal(document.getElementById('conductModal'));
    conductModal.show();
    const agreeCheckbox = document.getElementById('agreeCheckbox');
    const acceptBtn = document.getElementById('acceptConductBtn');
    agreeCheckbox.addEventListener('change', function() { acceptBtn.disabled = !this.checked; });
    acceptBtn.addEventListener('click', function() {
        if (!agreeCheckbox.checked) { alert('Please check the agreement box to continue.'); return; }
        acceptBtn.disabled = true;
        const originalText = acceptBtn.innerHTML;
        acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        fetch('<?php echo rtrim(APP_URL, '/'); ?>/student/accept-conduct', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(async response => {
            const text = await response.text();
            let data;
            try { data = text ? JSON.parse(text) : {}; } catch (e) { throw new Error('Server returned an invalid response. Please refresh and try again.'); }
            if (!response.ok && !data.error) { data.error = 'Request failed (' + response.status + ').'; }
            return data;
        })
        .then(data => {
            if (data.success) {
                conductModal.hide();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Code of conduct accepted successfully!') + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                const page = document.querySelector('.sd-page');
                if (page) page.insertBefore(alertDiv, page.firstChild);
            } else {
                alert('Error: ' + (data.error || 'Failed to accept code of conduct. Please try again.'));
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert(error.message || 'An error occurred. Please try again.');
            acceptBtn.disabled = false;
            acceptBtn.innerHTML = originalText;
        });
    });
});
<?php endif; ?>

document.querySelectorAll('.st-pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        }
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.setAttribute('title', show ? 'Hide password' : 'Show password');
    });
});

(function() {
    const form = document.getElementById('changePasswordForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('confirm_password');
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        if (newPass && confirmPass && newPass.value !== confirmPass.value) {
            e.preventDefault();
            e.stopPropagation();
            confirmPass.setCustomValidity('Passwords do not match');
            confirmPass.classList.add('is-invalid');
        } else if (confirmPass) {
            confirmPass.setCustomValidity('');
        }
        form.classList.add('was-validated');
    });
})();

(function () {
    var autoRun = <?php echo $autoQuickSync ? 'true' : 'false'; ?>;
    var baseUrl = <?php echo json_encode($quickSyncUrl, JSON_UNESCAPED_SLASHES); ?>;
    if (!baseUrl) return;

    var bar = document.getElementById('sdQuickSyncBar');
    var titleEl = document.getElementById('sdQuickSyncTitle');
    var msgEl = document.getElementById('sdQuickSyncMsg');
    var prog = document.getElementById('sdQuickSyncProgress');
    var spin = document.getElementById('sdQuickSyncSpin');
    var btn = document.getElementById('sdQuickSyncBtn');
    var running = false;
    var savedTotal = 0;

    function setBusy(on) {
        running = on;
        if (btn) {
            btn.disabled = on;
            var icon = btn.querySelector('i');
            if (icon) icon.className = on ? 'fas fa-spinner fa-spin' : 'fas fa-sync-alt';
        }
    }

    function showBar(title) {
        if (!bar) return;
        bar.classList.add('show');
        bar.classList.remove('ok', 'bad');
        if (spin) spin.style.display = '';
        if (titleEl) titleEl.textContent = title;
        if (prog) prog.style.width = '0%';
    }

    function finish(ok, summary, reload) {
        setBusy(false);
        if (spin) spin.style.display = 'none';
        if (bar) {
            bar.classList.add('show');
            bar.classList.toggle('ok', !!ok);
            bar.classList.toggle('bad', !ok);
        }
        if (titleEl) titleEl.textContent = ok ? 'Quick sync complete' : 'Quick sync issue';
        if (msgEl) msgEl.textContent = summary || (ok ? 'Done' : 'Failed');
        if (ok && reload) {
            setTimeout(function () {
                var u = new URL(window.location.href);
                u.searchParams.set('nosync', '1');
                window.location.href = u.toString();
            }, 700);
        }
    }

    function fetchJson(url) {
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.text().then(function (text) {
                var j = null;
                try { j = JSON.parse(text); } catch (e) { j = null; }
                if (!j || j.success === undefined) {
                    throw new Error((j && j.message) || ('HTTP ' + r.status));
                }
                return j;
            });
        });
    }

    function runQuickSync(forceReload) {
        if (running || !baseUrl) return;
        setBusy(true);
        savedTotal = 0;
        showBar('Today sync (readers)');
        if (msgEl) msgEl.textContent = 'Syncing fingerprint machines…';

        function step(chunk) {
            if (msgEl) msgEl.textContent = 'Reader ' + (chunk + 1) + '…';
            var url = baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'chunk=' + encodeURIComponent(String(chunk));
            return fetchJson(url).then(function (data) {
                if (!data || data.success === false) {
                    throw new Error((data && data.message) || 'Chunk failed');
                }
                if (data.skipped && data.done) {
                    finish(true, data.summary || data.message || 'Already synced', false);
                    return;
                }
                var total = parseInt(data.total, 10) || 0;
                var label = data.label || data.host || ('#' + (chunk + 1));
                savedTotal += parseInt(data.saved, 10) || 0;
                if (prog && total > 0) {
                    var pct = Math.round(((parseInt(data.chunk, 10) || chunk) + 1) / total * 100);
                    prog.style.width = pct + '%';
                }
                if (msgEl) {
                    msgEl.textContent = label + ': ' + (data.message || (data.ok ? 'OK' : 'Failed'))
                        + (total ? (' (' + (chunk + 1) + '/' + total + ')') : '');
                }
                if (data.done) {
                    finish(true, data.summary || ('Saved ' + savedTotal + ' new punch(es)'), forceReload !== false);
                    return;
                }
                return step(parseInt(data.next_chunk, 10) || (chunk + 1));
            });
        }

        step(0).catch(function (err) {
            finish(false, (err && err.message) ? err.message : 'Quick sync failed', false);
        });
    }

    if (btn) {
        btn.addEventListener('click', function () {
            runQuickSync(true);
        });
    }

    // Existing 7h calendar is already rendered from DB; sync machines after it is visible.
    function startAutoSyncAfterCalendar() {
        if (!autoRun) return;
        var start = function () {
            window.setTimeout(function () {
                runQuickSync(true);
            }, 1200);
        };
        if (document.readyState === 'complete') {
            requestAnimationFrame(function () { requestAnimationFrame(start); });
            return;
        }
        window.addEventListener('load', function () {
            requestAnimationFrame(function () { requestAnimationFrame(start); });
        });
    }
    startAutoSyncAfterCalendar();
})();
</script>
