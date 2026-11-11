<?php
/**
 * Student biometric attendance (read only): month summary, calendar and class sessions.
 *
 * @var string     $month      YYYY-MM
 * @var string     $prevMonth
 * @var string     $nextMonth
 * @var array|null $summary    working_days, full_days, half_days, absent_days, percentage
 * @var array      $dayMap     [Y-m-d => full|half|absent]
 * @var array      $sessions   [['date','session','status'], ...]
 * @var array|null $group
 * @var array|null $student
 */
$app = rtrim((string) APP_URL, '/');
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$statusMeta = [
    'full'   => ['Full day', 'ok', 'fa-circle-check'],
    'half'   => ['Half day', 'warn', 'fa-circle-half-stroke'],
    'absent' => ['Absent', 'bad', 'fa-circle-xmark'],
];

$firstTs = strtotime($month . '-01');
$daysInMonth = (int) date('t', $firstTs);
$leadBlanks = (int) date('N', $firstTs) - 1;
$today = date('Y-m-d');
$isCurrentMonth = ($month === date('Y-m'));
$hasSummary = is_array($summary);
$hasDays = $hasSummary && !empty($dayMap);

$pct = $hasDays ? (float) ($summary['percentage'] ?? 0) : 0.0;
$tone = !$hasDays ? 'muted' : ($pct >= 85 ? 'ok' : ($pct >= 75 ? 'warn' : 'bad'));
$toneText = !$hasDays
    ? 'No records yet'
    : ($pct >= 85 ? 'Good standing' : ($pct >= 75 ? 'Needs attention' : 'Low attendance'));
$pctDisplay = rtrim(rtrim(number_format($pct, 1, '.', ''), '0'), '.');
$ringDeg = (int) round(max(0, min(100, $pct)) * 3.6);
$fmt = static function ($n): string {
    $n = (float) $n;
    return rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.');
};
$attended = $hasDays
    ? $fmt(((float) ($summary['full_days'] ?? 0)) + 0.5 * ((float) ($summary['half_days'] ?? 0)))
    : '0';
$working = $hasDays ? (string) ($summary['working_days'] ?? 0) : '0';
$groupName = trim((string) (($group['name'] ?? '') ?: ($group['group_name'] ?? '')));
$studentId = (string) (($student['student_id'] ?? '') ?: ($_SESSION['user_name'] ?? ''));
$monthLabel = date('F Y', $firstTs);
$sessionCount = count($sessions);

$dayList = [];
if ($hasDays) {
    krsort($dayMap);
    foreach ($dayMap as $d => $st) {
        $dayList[] = ['date' => $d, 'status' => $st];
    }
}
?>
<style>
.ba {
    --navy:#001f3f; --blue:#00478a; --line:#e6eaf0; --muted:#64748b;
    --ok:#198754; --warn:#d97706; --bad:#dc3545; --soft:#f8fafc;
    max-width: 1120px; margin: 0 auto;
}
.ba * { box-sizing: border-box; }

.ba-top {
    display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start;
    gap:.85rem; margin-bottom:1rem;
}
.ba-top h1 {
    margin:0 0 .2rem; font-size:1.45rem; font-weight:700; color:var(--navy);
    display:flex; align-items:center; gap:.55rem;
}
.ba-top h1 i { color:var(--blue); }
.ba-top p { margin:0; color:var(--muted); font-size:.88rem; }
.ba-chips { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.55rem; }
.ba-chip {
    display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem; color:#475569;
    background:#f1f5f9; border-radius:999px; padding:.18rem .65rem;
}
.ba-nav { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
.ba-nav .btn {
    border:1px solid var(--line); background:#fff; color:var(--navy); border-radius:10px;
    min-width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center;
}
.ba-nav .btn:hover { background:#f1f5fb; }
.ba-nav form { margin:0; }
.ba-nav input[type=month] {
    border:1px solid var(--line); border-radius:10px; padding:.4rem .7rem; height:36px;
    font-size:.9rem; font-weight:600; color:var(--navy); background:#fff;
}

.ba-hero {
    position:relative; overflow:hidden; color:#fff; border-radius:16px;
    background:linear-gradient(120deg, var(--navy) 0%, var(--blue) 100%);
    padding:1.25rem 1.35rem; display:grid; grid-template-columns:auto minmax(0,1fr); gap:1.15rem;
    align-items:center; box-shadow:0 6px 18px rgba(0,31,63,.16); margin-bottom:1rem;
}
.ba-hero::after {
    content:""; position:absolute; right:-50px; top:-70px; width:220px; height:220px;
    border-radius:50%; background:rgba(255,255,255,.06); pointer-events:none;
}
.ba-ring {
    --deg:0deg; width:86px; height:86px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:conic-gradient(#6ee7a1 var(--deg), rgba(255,255,255,.18) 0);
    position:relative; z-index:1;
}
.ba-ring.warn { background:conic-gradient(#fcd34d var(--deg), rgba(255,255,255,.18) 0); }
.ba-ring.bad { background:conic-gradient(#fca5a5 var(--deg), rgba(255,255,255,.18) 0); }
.ba-ring.muted { background:conic-gradient(rgba(255,255,255,.35) var(--deg), rgba(255,255,255,.18) 0); }
.ba-ring span {
    width:66px; height:66px; border-radius:50%; background:var(--navy);
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.05rem;
}
.ba-hero .kicker {
    font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; opacity:.8; margin-bottom:.15rem;
}
.ba-hero .status { font-size:1.2rem; font-weight:700; line-height:1.25; margin-bottom:.2rem; }
.ba-hero .meta { font-size:.84rem; opacity:.88; }
.ba-hero .hint {
    margin-top:.55rem; font-size:.76rem; opacity:.8;
    display:flex; flex-wrap:wrap; gap:.35rem .9rem;
}
.ba-hero .hint span { display:inline-flex; align-items:center; gap:.35rem; }

.ba-kpis {
    display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:.75rem; margin-bottom:1rem;
}
.ba-kpi {
    background:#fff; border:1px solid var(--line); border-radius:14px; padding:.9rem 1rem;
    display:flex; align-items:center; gap:.75rem; box-shadow:0 1px 3px rgba(15,23,42,.04);
}
.ba-kpi .ic {
    width:42px; height:42px; border-radius:12px; display:flex; align-items:center;
    justify-content:center; flex-shrink:0; font-size:1.05rem;
}
.ba-kpi .ic.blue { background:#e8eefb; color:var(--blue); }
.ba-kpi .ic.ok { background:#e3f4ea; color:var(--ok); }
.ba-kpi .ic.warn { background:#fdf1dc; color:var(--warn); }
.ba-kpi .ic.bad { background:#fde8ea; color:var(--bad); }
.ba-kpi .n { font-size:1.4rem; font-weight:700; line-height:1.1; color:#0f172a; }
.ba-kpi .l { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:600; margin-top:.1rem; }

.ba-note {
    background:#eff6ff; border:1px solid #dbeafe; color:#1e40af; border-radius:12px;
    padding:.7rem 1rem; font-size:.82rem; margin-bottom:1rem;
    display:flex; gap:.6rem; align-items:flex-start;
}
.ba-note i { margin-top:.15rem; }

.ba-grid { display:grid; grid-template-columns:minmax(0, .95fr) minmax(0, 1.05fr); gap:1rem; }
.ba-card {
    background:#fff; border:1px solid var(--line); border-radius:16px;
    box-shadow:0 1px 4px rgba(15,23,42,.05); overflow:hidden; height:100%;
    display:flex; flex-direction:column;
}
.ba-card-h {
    padding:.9rem 1.1rem; border-bottom:1px solid var(--line);
    display:flex; align-items:center; justify-content:space-between; gap:.5rem; background:#fff;
}
.ba-card-h h2 {
    margin:0; font-size:.95rem; font-weight:700; color:var(--navy);
    display:flex; align-items:center; gap:.5rem;
}
.ba-card-h h2 i { color:var(--blue); }
.ba-card-h .sub { font-size:.78rem; color:var(--muted); }
.ba-card-b { padding:1rem 1.1rem 1.15rem; flex:1 1 auto; }

.ba-cal { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); gap:6px; }
.ba-cal .hd {
    text-align:center; font-size:.68rem; font-weight:700; color:var(--muted);
    text-transform:uppercase; letter-spacing:.04em; padding-bottom:.15rem;
}
.ba-cal .cell {
    min-height:54px; border-radius:12px; background:var(--soft); color:#94a3b8;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:.15rem; position:relative; border:1px solid transparent; padding:.35rem .2rem;
}
.ba-cal .cell.blank { background:transparent; min-height:0; }
.ba-cal .cell .n { font-size:.9rem; font-weight:700; line-height:1; }
.ba-cal .cell .s {
    font-size:.58rem; font-weight:700; letter-spacing:.02em; text-transform:uppercase; opacity:.9;
}
.ba-cal .cell.wk { background:#f8fafc; color:#cbd5e1; border-color:#eef2f7; }
.ba-cal .cell.ok { background:#ecfdf3; color:#14683f; border-color:#bbf7d0; }
.ba-cal .cell.warn { background:#fff7ed; color:#9a5700; border-color:#fed7aa; }
.ba-cal .cell.bad { background:#fef2f2; color:#b91c1c; border-color:#fecaca; }
.ba-cal .cell.today { box-shadow:0 0 0 2px var(--blue); }
.ba-legend {
    display:flex; flex-wrap:wrap; gap:.45rem 1rem; justify-content:center;
    font-size:.76rem; color:var(--muted); margin-top:.95rem;
}
.ba-legend i {
    display:inline-block; width:11px; height:11px; border-radius:3px;
    margin-right:.35rem; vertical-align:-1px; border:1px solid transparent;
}

.ba-scroll { max-height:560px; overflow:auto; }
.ba-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.ba-table th {
    font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted);
    font-weight:700; text-align:left; padding:.55rem 1rem; background:var(--soft);
    border-bottom:1px solid var(--line); position:sticky; top:0; z-index:1;
}
.ba-table td { padding:.7rem 1rem; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.ba-table tr:last-child td { border-bottom:0; }
.ba-table tr.dayrow td {
    background:#f8fafc; font-size:.75rem; font-weight:700; color:var(--navy);
    letter-spacing:.03em; padding:.45rem 1rem;
}
.ba-mod { font-weight:600; color:#0f172a; }
.ba-type { font-size:.75rem; color:var(--muted); margin-top:.1rem; }
.ba-badge {
    display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:700;
    border-radius:999px; padding:.2rem .65rem; white-space:nowrap;
}
.ba-badge.ok { background:#e3f4ea; color:#14683f; }
.ba-badge.warn { background:#fdf1dc; color:#a85a05; }
.ba-badge.bad { background:#fde8ea; color:#b02a37; }

.ba-daylist { display:flex; flex-direction:column; gap:.45rem; }
.ba-dayitem {
    display:flex; align-items:center; justify-content:space-between; gap:.75rem;
    border:1px solid var(--line); border-radius:12px; padding:.7rem .9rem; background:#fff;
}
.ba-dayitem .d { font-weight:700; color:#0f172a; font-size:.9rem; }
.ba-dayitem .w { font-size:.75rem; color:var(--muted); margin-top:.1rem; }

.ba-empty {
    text-align:center; color:var(--muted); padding:2.4rem 1rem; font-size:.9rem;
}
.ba-empty i { display:block; font-size:1.9rem; color:#c5cfdd; margin-bottom:.55rem; }
.ba-empty .t { font-weight:700; color:#334155; margin-bottom:.2rem; }

@media (max-width: 991px) {
    .ba-grid { grid-template-columns:1fr; }
    .ba-kpis { grid-template-columns:repeat(2, minmax(0,1fr)); }
}
@media (max-width: 575px) {
    .ba-hero { grid-template-columns:1fr; text-align:left; }
    .ba-kpis { grid-template-columns:1fr 1fr; }
    .ba-kpi { padding:.75rem; }
    .ba-kpi .n { font-size:1.2rem; }
    .ba-cal .cell { min-height:46px; border-radius:10px; }
    .ba-cal .cell .s { display:none; }
    .ba-table th, .ba-table td { padding:.55rem .7rem; }
}
</style>

<div class="container-fluid px-2 px-md-3 px-lg-4 py-3">
<div class="ba">

    <div class="ba-top">
        <div>
            <h1><i class="fas fa-fingerprint"></i>Biometric Attendance</h1>
            <p>Your monthly fingerprint attendance. Read only.</p>
            <div class="ba-chips">
                <?php if ($studentId !== ''): ?>
                    <span class="ba-chip"><i class="fas fa-id-card"></i><?php echo $e($studentId); ?></span>
                <?php endif; ?>
                <?php if ($groupName !== ''): ?>
                    <span class="ba-chip"><i class="fas fa-users"></i><?php echo $e($groupName); ?></span>
                <?php endif; ?>
                <span class="ba-chip"><i class="fas fa-calendar"></i><?php echo $e($monthLabel); ?></span>
            </div>
        </div>
        <div class="ba-nav">
            <a class="btn btn-sm" href="<?php echo $e($app . '/student/my-attendance?month=' . $prevMonth); ?>" title="Previous month" aria-label="Previous month"><i class="fas fa-chevron-left"></i></a>
            <form method="get" action="<?php echo $e($app . '/student/my-attendance'); ?>">
                <input type="month" name="month" value="<?php echo $e($month); ?>" onchange="this.form.submit()" aria-label="Select month">
            </form>
            <a class="btn btn-sm" href="<?php echo $e($app . '/student/my-attendance?month=' . $nextMonth); ?>" title="Next month" aria-label="Next month"><i class="fas fa-chevron-right"></i></a>
            <?php if (!$isCurrentMonth): ?>
                <a class="btn btn-sm" href="<?php echo $e($app . '/student/my-attendance'); ?>">This month</a>
            <?php endif; ?>
            <a class="btn btn-sm" href="<?php echo $e($app . '/student/dashboard'); ?>"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
        </div>
    </div>

    <div class="ba-hero">
        <div class="ba-ring <?php echo $e($tone); ?>" style="--deg:<?php echo $hasDays ? $ringDeg : 0; ?>deg">
            <span><?php echo $hasDays ? $e($pctDisplay) . '%' : '&ndash;'; ?></span>
        </div>
        <div>
            <div class="kicker">Attendance &middot; <?php echo $e($monthLabel); ?></div>
            <div class="status"><?php echo $e($toneText); ?></div>
            <div class="meta">
                <?php if ($hasDays): ?>
                    <?php echo $e($attended); ?> of <?php echo $e($working); ?> working days counted
                <?php else: ?>
                    Nothing recorded for this month yet
                <?php endif; ?>
            </div>
            <div class="hint">
                <span><i class="fas fa-circle-check"></i>Full day = 1</span>
                <span><i class="fas fa-circle-half-stroke"></i>Half day = 0.5</span>
                <span><i class="fas fa-circle-xmark"></i>Absent = 0</span>
            </div>
        </div>
    </div>

    <?php if ($hasSummary): ?>
    <div class="ba-kpis">
        <?php foreach ([
            ['Working days', $summary['working_days'] ?? 0, 'fa-calendar-day', 'blue'],
            ['Full days', $summary['full_days'] ?? 0, 'fa-circle-check', 'ok'],
            ['Half days', $summary['half_days'] ?? 0, 'fa-circle-half-stroke', 'warn'],
            ['Absent', $summary['absent_days'] ?? 0, 'fa-circle-xmark', 'bad'],
        ] as $k): ?>
            <div class="ba-kpi">
                <div class="ic <?php echo $e($k[3]); ?>"><i class="fas <?php echo $e($k[2]); ?>"></i></div>
                <div>
                    <div class="n"><?php echo $e($k[1]); ?></div>
                    <div class="l"><?php echo $e($k[0]); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="ba-note">
        <i class="fas fa-info-circle"></i>
        <div>This page is read only. If a day looks wrong, contact your Head of Department with the date and your punch time.</div>
    </div>

    <div class="ba-grid">
        <div class="ba-card">
            <div class="ba-card-h">
                <h2><i class="fas fa-calendar-alt"></i>Daily calendar</h2>
                <span class="sub"><?php echo $e($monthLabel); ?></span>
            </div>
            <div class="ba-card-b">
                <div class="ba-cal">
                    <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dn): ?>
                        <div class="hd"><?php echo $dn; ?></div>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < $leadBlanks; $i++): ?>
                        <div class="cell blank"></div>
                    <?php endfor; ?>
                    <?php for ($day = 1; $day <= $daysInMonth; $day++):
                        $date = sprintf('%s-%02d', $month, $day);
                        $st = $dayMap[$date] ?? null;
                        $meta = $st !== null ? ($statusMeta[$st] ?? null) : null;
                        $dow = (int) date('N', strtotime($date));
                        $cls = $meta ? $meta[1] : ($dow >= 6 ? 'wk' : '');
                        if ($date === $today) {
                            $cls .= ' today';
                        }
                        $short = $st === 'full' ? 'Full' : ($st === 'half' ? 'Half' : ($st === 'absent' ? 'Abs' : ''));
                        $cellTitle = date('D, d M Y', strtotime($date)) . ($meta ? ' — ' . $meta[0] : '');
                    ?>
                        <div class="cell <?php echo $e(trim($cls)); ?>" title="<?php echo $e($cellTitle); ?>">
                            <span class="n"><?php echo $day; ?></span>
                            <?php if ($short !== ''): ?><span class="s"><?php echo $short; ?></span><?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="ba-legend">
                    <span><i style="background:#ecfdf3;border-color:#bbf7d0"></i>Full day</span>
                    <span><i style="background:#fff7ed;border-color:#fed7aa"></i>Half day</span>
                    <span><i style="background:#fef2f2;border-color:#fecaca"></i>Absent</span>
                    <span><i style="background:#f8fafc;border-color:#e2e8f0"></i>No class / weekend</span>
                </div>
            </div>
        </div>

        <div class="ba-card">
            <div class="ba-card-h">
                <h2><i class="fas fa-chalkboard-teacher"></i><?php echo $sessionCount ? 'Class sessions' : 'Daily results'; ?></h2>
                <span class="sub">
                    <?php if ($sessionCount): ?>
                        <?php echo (int) $sessionCount; ?> session<?php echo $sessionCount === 1 ? '' : 's'; ?>
                    <?php elseif ($dayList): ?>
                        <?php echo count($dayList); ?> day<?php echo count($dayList) === 1 ? '' : 's'; ?>
                    <?php else: ?>
                        No data
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($sessionCount): ?>
                <div class="ba-scroll">
                    <table class="ba-table">
                        <thead>
                            <tr><th>Time</th><th>Module</th><th>Result</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        $lastDate = null;
                        foreach ($sessions as $x):
                            $s = $x['session'];
                            $meta = $statusMeta[$x['status']] ?? ['-', 'muted', 'fa-circle'];
                            if ($x['date'] !== $lastDate):
                                $lastDate = $x['date'];
                        ?>
                            <tr class="dayrow"><td colspan="3"><?php echo $e(date('l, d F Y', strtotime($x['date']))); ?></td></tr>
                        <?php endif; ?>
                            <tr>
                                <td class="text-nowrap"><?php echo $e(substr((string) ($s['start_clock'] ?? ''), 0, 5) . ' – ' . substr((string) ($s['end_clock'] ?? ''), 0, 5)); ?></td>
                                <td>
                                    <div class="ba-mod"><?php echo $e(($s['module_name'] ?? '') !== '' ? $s['module_name'] : ($s['subject'] ?? 'Session')); ?></div>
                                    <?php if (!empty($s['session_type'])): ?>
                                        <div class="ba-type"><?php echo $e($s['session_type']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ba-badge <?php echo $e($meta[1]); ?>">
                                        <i class="fas <?php echo $e($meta[2]); ?>"></i><?php echo $e($meta[0]); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($dayList): ?>
                <div class="ba-card-b">
                    <div class="ba-daylist">
                        <?php foreach ($dayList as $row):
                            $meta = $statusMeta[$row['status']] ?? ['-', 'muted', 'fa-circle'];
                        ?>
                            <div class="ba-dayitem">
                                <div>
                                    <div class="d"><?php echo $e(date('d M Y', strtotime($row['date']))); ?></div>
                                    <div class="w"><?php echo $e(date('l', strtotime($row['date']))); ?></div>
                                </div>
                                <span class="ba-badge <?php echo $e($meta[1]); ?>">
                                    <i class="fas <?php echo $e($meta[2]); ?>"></i><?php echo $e($meta[0]); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="ba-empty">
                    <i class="fas fa-calendar-times"></i>
                    <div class="t">No attendance for this month</div>
                    <div>Try another month, or check again after classes start.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
