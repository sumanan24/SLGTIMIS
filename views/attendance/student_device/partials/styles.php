<?php
declare(strict_types=1);
if (!empty($GLOBALS['__student_device_styles_loaded'])) {
    return;
}
$GLOBALS['__student_device_styles_loaded'] = true;
?>
<style>
.student-device-page {
    --sd-surface: #fff;
    --sd-muted: #6c757d;
    --sd-in: #198754;
    --sd-out: #dc3545;
    --sd-other: #6c757d;
    --sd-border: #e9ecef;
}
.student-device-page .sd-stat {
    border: 1px solid var(--sd-border);
    border-radius: .5rem;
    background: var(--sd-surface);
    height: 100%;
    padding: 1rem 1.1rem;
}
.student-device-page .sd-stat .sd-label {
    font-size: .75rem;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: var(--sd-muted);
    margin-bottom: .35rem;
}
.student-device-page .sd-stat .sd-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: #212529;
}
.student-device-page .sd-stat .sd-meta {
    font-size: .8rem;
    color: var(--sd-muted);
    margin-top: .25rem;
}
.student-device-page .sd-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .2rem .55rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
}
.student-device-page .sd-status-pill.is-on { background: #d1e7dd; color: #0f5132; }
.student-device-page .sd-status-pill.is-off { background: #f8d7da; color: #842029; }
.student-device-page .sd-status-pill.is-unknown { background: #e9ecef; color: #495057; }
.student-device-page .sd-card {
    border: 1px solid var(--sd-border);
    border-radius: .5rem;
    background: #fff;
    box-shadow: none;
}
.student-device-page .sd-card .card-header {
    background: #fff;
    border-bottom: 1px solid var(--sd-border);
    padding: .85rem 1rem;
}
.student-device-page .sd-card .card-body { padding: 1rem; }
.student-device-page .sd-events-table {
    margin: 0;
    width: 100%;
}
.student-device-page .sd-events-table thead th {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #6c757d;
    font-weight: 600;
    white-space: nowrap;
    border-bottom-width: 1px;
    vertical-align: middle;
}
.student-device-page .sd-events-table td {
    vertical-align: middle;
    padding-top: .65rem;
    padding-bottom: .65rem;
}
.student-device-page .sd-events-table .col-id { min-width: 9.5rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .85rem; }
.student-device-page .sd-events-table .col-emp { min-width: 6.5rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .85rem; }
.student-device-page .sd-events-table .col-name { min-width: 10rem; }
.student-device-page .sd-events-table .col-date { min-width: 6.5rem; white-space: nowrap; }
.student-device-page .sd-events-table .col-time { min-width: 5.5rem; white-space: nowrap; text-align: center; font-variant-numeric: tabular-nums; font-weight: 600; }
.student-device-page .sd-events-table .col-others { min-width: 8rem; font-size: .85rem; color: var(--sd-other); }
.student-device-page .sd-events-table .col-machine { min-width: 7rem; font-size: .85rem; color: var(--sd-muted); }
.student-device-page .sd-time-in { color: var(--sd-in); }
.student-device-page .sd-time-out { color: var(--sd-out); }
.student-device-page .sd-time-empty { color: #adb5bd; font-weight: 500; }
.student-device-page .sd-legend span {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-right: .85rem;
    font-size: .8rem;
    color: var(--sd-muted);
}
.student-device-page .sd-legend .dot {
    width: .55rem;
    height: .55rem;
    border-radius: 50%;
    display: inline-block;
}
.student-device-page .sd-legend .dot.in { background: var(--sd-in); }
.student-device-page .sd-legend .dot.out { background: var(--sd-out); }
.student-device-page .sd-legend .dot.other { background: var(--sd-other); }
.student-device-page .sd-sync-grid .btn { min-height: 38px; }
@media (max-width: 767.98px) {
    .student-device-page .student-device-side-nav { margin-bottom: 1rem; }
}
</style>
