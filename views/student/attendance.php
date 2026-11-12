<?php
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$stats = $stats ?? [
    'attendancePercentage' => 0,
    'presentDays' => 0,
    'absentDays' => 0,
    'holidayDays' => 0,
    'totalDays' => 0,
];
$calendarData = $calendarData ?? [];
$selectedMonth = $selectedMonth ?? date('Y-m');
$prevMonth = $prevMonth ?? date('Y-m', strtotime('-1 month'));
$nextMonth = $nextMonth ?? date('Y-m', strtotime('+1 month'));
?>
<style>
.sa-page {
    --sa-navy: #001f3f;
    --sa-blue: #00478a;
    --sa-line: #e6eaf0;
    --sa-muted: #64748b;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
}
.sa-page *,
.sa-page *::before,
.sa-page *::after { box-sizing: border-box; }

.sa-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .65rem;
    margin-bottom: .85rem;
}
.sa-top h1 {
    margin: 0;
    font-size: clamp(1.1rem, 4vw, 1.35rem);
    font-weight: 700;
    color: var(--sa-navy);
    line-height: 1.25;
    min-width: 0;
}
.sa-top h1 i { color: var(--sa-blue); }
.sa-top .btn { flex-shrink: 0; }

.sa-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .55rem;
    margin-bottom: .85rem;
}
.sa-stat {
    background: #fff;
    border: 1px solid var(--sa-line);
    border-radius: 10px;
    border-left: 3px solid var(--sa-navy);
    padding: .7rem .55rem;
    text-align: center;
    min-width: 0;
}
.sa-stat .n {
    font-size: clamp(1.05rem, 4vw, 1.55rem);
    font-weight: 700;
    color: var(--sa-navy);
    line-height: 1.15;
}
.sa-stat .l {
    margin-top: .2rem;
    font-size: .65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--sa-muted);
    line-height: 1.2;
}
.sa-stat.ok .n { color: #198754; }
.sa-stat.bad .n { color: #dc3545; }
.sa-stat.warn .n { color: #d97706; }
.sa-stat.blue .n { color: #0d6efd; }

.sa-cal {
    background: #fff;
    border: 1px solid var(--sa-line);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: .85rem;
    box-shadow: 0 1px 4px rgba(0, 31, 63, .05);
}
.sa-cal-head {
    background: linear-gradient(120deg, var(--sa-navy), var(--sa-blue));
    color: #fff;
    padding: .9rem 1rem;
    display: grid;
    gap: .75rem;
}
.sa-cal-head h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.25;
}
.sa-cal-head small { opacity: .85; font-size: .78rem; }
.sa-nav {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .4rem;
}
.sa-nav .btn {
    width: 100%;
    justify-content: center;
    white-space: nowrap;
    font-size: .8rem;
    padding: .35rem .45rem;
}

.sa-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    width: 100%;
}
.sa-wd {
    background: #f8fafc;
    border-bottom: 1px solid var(--sa-line);
    border-right: 1px solid #eef2f7;
    text-align: center;
    font-size: .68rem;
    font-weight: 700;
    color: #475569;
    padding: .45rem .1rem;
    text-transform: uppercase;
}
.sa-wd:nth-child(7n),
.sa-cell:nth-child(7n) { border-right: 0; }

.sa-cell {
    min-width: 0;
    min-height: 4.6rem;
    border-right: 1px solid #eef2f7;
    border-bottom: 1px solid #eef2f7;
    padding: .28rem .18rem .35rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .12rem;
}
.sa-cell.empty { background: #fff; }
.sa-cell.weekend { background: #f8fafc; }
.sa-cell.present-finger { background: #ecfdf5; }
.sa-cell.present-manual { background: #e0f2fe; }
.sa-cell.absent-finger,
.sa-cell.absent-manual { background: #fef2f2; }
.sa-cell.holiday { background: #fffbeb; }

.sa-num {
    align-self: flex-start;
    font-size: .72rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
}
.sa-icon {
    font-size: 1.05rem;
    line-height: 1;
}
.sa-icon.present { color: #198754; }
.sa-icon.absent { color: #dc3545; }
.sa-icon.holiday { color: #d97706; }
.sa-times {
    font-size: .58rem;
    color: var(--sa-muted);
    text-align: center;
    line-height: 1.2;
    max-width: 100%;
    overflow-wrap: anywhere;
}
.sa-tag {
    font-size: .55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #475569;
    background: rgba(15, 23, 42, .07);
    border-radius: 999px;
    padding: .08rem .3rem;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sa-weeknote {
    font-size: .55rem;
    color: #94a3b8;
    text-align: center;
}

.sa-legend {
    background: #fff;
    border: 1px solid var(--sa-line);
    border-radius: 12px;
    padding: .9rem 1rem;
}
.sa-legend h3 {
    margin: 0 0 .65rem;
    font-size: .9rem;
    font-weight: 700;
    color: var(--sa-navy);
}
.sa-legend-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .65rem .75rem;
}
.sa-legend-item {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    min-width: 0;
}
.sa-legend-item i {
    margin-top: .1rem;
    flex-shrink: 0;
}
.sa-legend-item strong {
    display: block;
    font-size: .8rem;
    color: #0f172a;
}
.sa-legend-item span {
    display: block;
    font-size: .7rem;
    color: var(--sa-muted);
    line-height: 1.3;
}

@media (min-width: 768px) {
    .sa-cal-head {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
    }
    .sa-nav { width: auto; }
    .sa-cell { min-height: 5.4rem; padding: .4rem .3rem; }
    .sa-num { font-size: .82rem; }
    .sa-icon { font-size: 1.2rem; }
    .sa-times { font-size: .62rem; }
    .sa-legend-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (max-width: 575.98px) {
    .sa-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; }
    .sa-stat { padding: .55rem .4rem; border-radius: 8px; }
    .sa-stat .l { font-size: .6rem; }
    .sa-cal { border-radius: 10px; }
    .sa-cal-head { padding: .75rem .8rem; }
    .sa-cal-head h2 { font-size: .98rem; }
    .sa-wd { font-size: .58rem; padding: .35rem 0; }
    .sa-cell {
        min-height: 3.55rem;
        padding: .2rem .08rem .25rem;
        gap: .08rem;
    }
    .sa-num { font-size: .65rem; }
    .sa-icon { font-size: .9rem; }
    .sa-times,
    .sa-weeknote { display: none; }
    .sa-tag {
        font-size: 0;
        letter-spacing: 0;
        padding: .12rem;
        width: 1rem;
        height: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    .sa-tag i { font-size: .55rem; margin: 0; }
    .sa-legend { padding: .75rem .8rem; }
    .sa-top .btn { width: 100%; }
    .sa-top { align-items: stretch; }
}

@media (min-width: 1100px) {
    .sa-page { max-width: 1080px; margin-left: auto; margin-right: auto; }
}
</style>

<div class="sa-page">
    <div class="sa-top">
        <h1><i class="fas fa-calendar-check me-2" aria-hidden="true"></i>My Attendance</h1>
        <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Dashboard
        </a>
    </div>

    <div class="sa-stats">
        <div class="sa-stat blue">
            <div class="n"><?php echo $e($stats['attendancePercentage']); ?>%</div>
            <div class="l">Rate</div>
        </div>
        <div class="sa-stat ok">
            <div class="n"><?php echo (int) $stats['presentDays']; ?></div>
            <div class="l">Present</div>
        </div>
        <div class="sa-stat bad">
            <div class="n"><?php echo (int) $stats['absentDays']; ?></div>
            <div class="l">Absent</div>
        </div>
        <div class="sa-stat warn">
            <div class="n"><?php echo (int) $stats['holidayDays']; ?></div>
            <div class="l">Holiday</div>
        </div>
    </div>

    <div class="sa-cal">
        <div class="sa-cal-head">
            <div>
                <h2><?php echo $e(date('F Y', strtotime($selectedMonth . '-01'))); ?></h2>
                <small>Working days counted: <?php echo (int) $stats['totalDays']; ?></small>
            </div>
            <div class="sa-nav">
                <a href="?month=<?php echo $e($prevMonth); ?>" class="btn btn-light btn-sm"><i class="fas fa-chevron-left"></i></a>
                <a href="?month=<?php echo $e(date('Y-m')); ?>" class="btn btn-light btn-sm">Today</a>
                <a href="?month=<?php echo $e($nextMonth); ?>" class="btn btn-light btn-sm"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="sa-grid" aria-label="Attendance calendar">
            <?php foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $i => $wd): ?>
                <div class="sa-wd" title="<?php echo $e(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$i]); ?>"><?php echo $e($wd); ?></div>
            <?php endforeach; ?>

            <?php foreach ($calendarData as $week): ?>
                <?php foreach ($week as $dayData): ?>
                    <?php
                    $day = $dayData['day'] ?? null;
                    $date = $dayData['date'] ?? null;
                    $status = $dayData['status'] ?? null;
                    $isWeekend = !empty($dayData['isWeekend']);
                    $source = $dayData['source'] ?? null;
                    $timeIn = trim((string) ($dayData['time_in'] ?? ''));
                    $timeOut = trim((string) ($dayData['time_out'] ?? ''));
                    $label = trim((string) ($dayData['label'] ?? ''));

                    $cellClass = 'empty';
                    $icon = '';
                    $iconClass = '';
                    $statusText = $label !== '' ? $label : 'No record';

                    if ($day === null) {
                        $cellClass = 'empty';
                        $statusText = '';
                    } elseif ($isWeekend || $status === 'weekend') {
                        $cellClass = 'weekend';
                        $statusText = 'Weekend';
                    } elseif ($status === 'present') {
                        $cellClass = $source === 'manual' ? 'present-manual' : 'present-finger';
                        $icon = 'fa-check-circle';
                        $iconClass = 'present';
                        $statusText = $source === 'manual' ? 'Present (manual)' : 'Present (finger)';
                    } elseif ($status === 'absent') {
                        $cellClass = $source === 'manual' ? 'absent-manual' : 'absent-finger';
                        $icon = 'fa-times-circle';
                        $iconClass = 'absent';
                        $statusText = $label !== '' ? $label : ($source === 'manual' ? 'Absent (manual)' : 'Absent (finger)');
                    } elseif ($status === 'holiday') {
                        $cellClass = 'holiday';
                        $icon = 'fa-star';
                        $iconClass = 'holiday';
                        $statusText = 'Holiday';
                    }

                    $titleParts = [];
                    if ($date) {
                        $titleParts[] = date('D, j M Y', strtotime($date));
                    }
                    if ($statusText !== '') {
                        $titleParts[] = $statusText;
                    }
                    if ($timeIn !== '' || $timeOut !== '') {
                        $titleParts[] = 'In ' . ($timeIn !== '' ? $timeIn : '—') . ' / Out ' . ($timeOut !== '' ? $timeOut : '—');
                    }
                    $title = implode(' · ', $titleParts);
                    ?>
                    <div class="sa-cell <?php echo $e($cellClass); ?>" title="<?php echo $e($title); ?>">
                        <?php if ($day !== null): ?>
                            <div class="sa-num"><?php echo (int) $day; ?></div>
                            <?php if ($icon !== ''): ?>
                                <div class="sa-icon <?php echo $e($iconClass); ?>"><i class="fas <?php echo $e($icon); ?>" aria-hidden="true"></i></div>
                            <?php endif; ?>
                            <?php if ($isWeekend): ?>
                                <div class="sa-weeknote">Off</div>
                            <?php elseif ($timeIn !== '' || $timeOut !== ''): ?>
                                <div class="sa-times"><?php echo $e(($timeIn !== '' ? $timeIn : '—') . '–' . ($timeOut !== '' ? $timeOut : '—')); ?></div>
                            <?php endif; ?>
                            <?php if ($source === 'finger'): ?>
                                <span class="sa-tag" title="Fingerprint"><i class="fas fa-fingerprint" aria-hidden="true"></i><span class="d-none d-md-inline"> Finger</span></span>
                            <?php elseif ($source === 'manual'): ?>
                                <span class="sa-tag" title="Manual"><i class="fas fa-pen" aria-hidden="true"></i><span class="d-none d-md-inline"> Manual</span></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sa-legend">
        <h3>Legend</h3>
        <div class="sa-legend-grid">
            <div class="sa-legend-item">
                <i class="fas fa-check-circle text-success" aria-hidden="true"></i>
                <div><strong>Present</strong><span>Finger &gt;7h or manual</span></div>
            </div>
            <div class="sa-legend-item">
                <i class="fas fa-times-circle text-danger" aria-hidden="true"></i>
                <div><strong>Absent</strong><span>Under 7h / incomplete / manual</span></div>
            </div>
            <div class="sa-legend-item">
                <i class="fas fa-fingerprint text-secondary" aria-hidden="true"></i>
                <div><strong>Finger</strong><span>Device punch</span></div>
            </div>
            <div class="sa-legend-item">
                <i class="fas fa-pen text-primary" aria-hidden="true"></i>
                <div><strong>Manual</strong><span>Staff override</span></div>
            </div>
        </div>
    </div>
</div>
