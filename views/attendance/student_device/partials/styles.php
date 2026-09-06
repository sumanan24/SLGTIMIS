<?php
declare(strict_types=1);
if (!empty($GLOBALS['__student_device_styles_loaded'])) {
    return;
}
$GLOBALS['__student_device_styles_loaded'] = true;
?>
<style>
/* Bleed to full main-content width (cancel nested padding) */
.main-content > .student-device-page.sd-fullpage {
    margin: -1.5rem -2rem;
    width: auto;
    max-width: none;
}
.student-device-page.sd-fullpage {
    --sd-surface: #fff;
    --sd-muted: #64748b;
    --sd-text: #0f172a;
    --sd-in: #198754;
    --sd-out: #dc3545;
    --sd-other: #64748b;
    --sd-border: #e2e8f0;
    --sd-soft: #f8fafc;
    --sd-navy: #001f3f;
    padding: 0;
    box-sizing: border-box;
}
.student-device-page.sd-fullpage .card:hover {
    transform: none;
    box-shadow: none;
}
.student-device-page .sd-fullpage-body {
    width: 100%;
    max-width: 100%;
    padding: 1rem 1.25rem 1.5rem;
    box-sizing: border-box;
}
.student-device-page .sd-top-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    width: 100%;
    margin: 0;
    padding: 0.75rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid var(--sd-border);
    box-sizing: border-box;
}
.student-device-page .sd-top-nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    min-height: 38px;
    padding: 0.45rem 0.9rem;
    border: 1px solid var(--sd-border);
    border-radius: 0.5rem;
    background: var(--sd-soft);
    color: #334155;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}
.student-device-page .sd-top-nav-link i {
    color: var(--sd-muted);
    width: 1rem;
    text-align: center;
}
.student-device-page .sd-top-nav-link:hover {
    border-color: #94a3b8;
    color: var(--sd-navy);
    background: #fff;
}
.student-device-page .sd-top-nav-link.is-active {
    background: var(--sd-navy);
    border-color: var(--sd-navy);
    color: #fff;
}
.student-device-page .sd-top-nav-link.is-active i {
    color: rgba(255, 255, 255, 0.95);
}
.student-device-page .sd-page-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.85rem 1rem;
    width: 100%;
    margin: 0 0 1rem;
}
.student-device-page .sd-page-head-text {
    flex: 1 1 16rem;
    min-width: 0;
}
.student-device-page .sd-page-title {
    margin: 0 0 0.25rem;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--sd-text);
    line-height: 1.3;
}
.student-device-page .sd-page-lead {
    margin: 0;
    font-size: 0.875rem;
    color: var(--sd-muted);
    max-width: 46rem;
}
.student-device-page .sd-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    flex: 0 0 auto;
}
.student-device-page .sd-header-actions .btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    white-space: nowrap;
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
.student-device-page .sd-device-mini {
    border: 1px solid var(--sd-border);
    border-radius: .55rem;
    padding: .85rem 1rem;
    background: #fafbfc;
    height: 100%;
}
.student-device-page .sd-device-mini.is-online {
    border-color: #a3cfbb;
    background: #f3faf6;
}
.student-device-page .sd-device-mini.is-offline {
    border-color: #f1aeb5;
    background: #fff8f8;
}
.student-device-page .sd-device-mini-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .5rem;
    margin-bottom: .55rem;
}
.student-device-page .sd-device-mini-label {
    font-weight: 650;
    font-size: .92rem;
    color: #212529;
}
.student-device-page .sd-device-mini-ip {
    font-size: .78rem;
    color: #495057;
}
.student-device-page .sd-device-mini-meta {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
    font-size: .8rem;
    color: var(--sd-muted);
}
.student-device-page .sd-device-mini-sync,
.student-device-page .sd-device-mini-msg {
    margin-top: .4rem;
    font-size: .75rem;
    color: var(--sd-muted);
    line-height: 1.35;
    word-break: break-word;
}
.student-device-page .sd-card {
    border: 1px solid var(--sd-border);
    border-radius: .65rem;
    background: #fff;
    box-shadow: none;
    overflow: hidden;
    width: 100%;
    margin-bottom: 1rem;
}
.student-device-page .sd-card .card-header {
    background: #fff;
    border-bottom: 1px solid var(--sd-border);
    padding: .85rem 1rem;
}
.student-device-page .sd-card .card-body { padding: 1rem; }
.student-device-page .sd-filter-grid,
.student-device-page .sd-month-filter-grid,
.student-device-page .sd-status-filter-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr) auto;
    gap: 0.75rem 0.85rem;
    align-items: end;
    width: 100%;
}
.student-device-page .sd-month-filter-grid {
    grid-template-columns: 180px minmax(0, 1fr) 160px;
}
.student-device-page .sd-status-filter-grid {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}
.student-device-page .sd-field { min-width: 0; width: 100%; }
.student-device-page .sd-field-grow { min-width: 0; }
.student-device-page .sd-field .form-label {
    display: block;
    margin: 0 0 0.35rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--sd-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.student-device-page .sd-field .form-control,
.student-device-page .sd-field .form-select {
    width: 100%;
    min-height: 42px;
    border-color: #cbd5e1;
    box-sizing: border-box;
}
.student-device-page .sd-filter-actions {
    display: flex;
    gap: 0.45rem;
    width: 100%;
}
.student-device-page .sd-filter-actions .btn,
.student-device-page .sd-field-actions .btn {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}
.student-device-page .sd-panel-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    width: 100%;
}
.student-device-page .sd-summary-chip {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    font-size: 0.8125rem;
    color: var(--sd-muted);
    background: var(--sd-soft);
    border: 1px solid var(--sd-border);
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    white-space: nowrap;
    word-break: break-word;
}
.student-device-page .sd-page-head-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    flex: 0 1 auto;
    max-width: 100%;
}
.student-device-page .sd-events-panel {
    margin-bottom: 0;
}
.student-device-page .sd-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.student-device-page .sd-events-table {
    margin: 0;
    width: 100%;
    min-width: 960px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}
.student-device-page .sd-events-table col.col-id { width: 16%; }
.student-device-page .sd-events-table col.col-emp { width: 12%; }
.student-device-page .sd-events-table col.col-name { width: 22%; }
.student-device-page .sd-events-table col.col-date { width: 11%; }
.student-device-page .sd-events-table col.col-time { width: 9%; }
.student-device-page .sd-events-table col.col-others { width: 12%; }
.student-device-page .sd-events-table col.col-machine { width: 9%; }
.student-device-page .sd-events-table thead th {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #64748b;
    font-weight: 700;
    white-space: nowrap;
    border-bottom: 1px solid var(--sd-border);
    vertical-align: middle;
    background: var(--sd-soft);
    padding: 0.75rem 1rem;
}
.student-device-page .sd-events-table td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eef2f6;
    overflow: hidden;
    text-overflow: ellipsis;
}
.student-device-page .sd-events-table tbody tr:last-child td { border-bottom: 0; }
.student-device-page .sd-events-table .col-id,
.student-device-page .sd-events-table .col-emp {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .85rem;
}
.student-device-page .sd-events-table .col-name {
    font-weight: 500;
    color: var(--sd-text);
}
.student-device-page .sd-events-table .col-date { white-space: nowrap; }
.student-device-page .sd-events-table .col-time {
    white-space: nowrap;
    text-align: center;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}
.student-device-page .sd-events-table .col-others { font-size: .85rem; color: var(--sd-other); }
.student-device-page .sd-events-table .col-machine { font-size: .85rem; color: var(--sd-muted); }
.student-device-page .sd-section-row td {
    background: var(--sd-soft);
    color: var(--sd-navy);
    font-size: 0.8125rem;
}
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
.student-device-page .sd-empty {
    text-align: center;
    padding: 2.75rem 1rem;
    color: var(--sd-muted);
}
.student-device-page .sd-empty i {
    display: block;
    font-size: 2rem;
    margin-bottom: 0.75rem;
    color: #94a3b8;
}
.student-device-page .sd-pager {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    width: 100%;
    background: #fff;
    padding: 0.85rem 1rem;
}
.student-device-page .sd-pager .page-link { color: var(--sd-navy); }
.student-device-page .sd-pager .page-item.active .page-link {
    background: var(--sd-navy);
    border-color: var(--sd-navy);
}
.student-device-page .sd-card-list {
    display: grid;
    gap: 0.75rem;
    padding: 0.85rem;
}
.student-device-page .sd-day-card {
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    background: #fff;
    padding: 0.9rem 0.95rem;
}
.student-device-page .sd-day-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.student-device-page .sd-day-name {
    font-weight: 700;
    color: var(--sd-text);
    line-height: 1.35;
}
.student-device-page .sd-day-id,
.student-device-page .sd-day-date {
    font-size: 0.8125rem;
    color: var(--sd-muted);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.student-device-page .sd-day-times {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}
.student-device-page .sd-day-times > div {
    background: var(--sd-soft);
    border: 1px solid #eef2f6;
    border-radius: 0.5rem;
    padding: 0.55rem 0.7rem;
}
.student-device-page .sd-mini-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sd-muted);
    margin-bottom: 0.15rem;
}
.student-device-page .sd-day-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem 0.75rem;
    margin-top: 0.65rem;
    font-size: 0.78rem;
    color: var(--sd-muted);
}
.student-device-page .sd-mobile-section {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--sd-navy);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.35rem 0.15rem 0;
}
.student-device-page .sd-report-banner .card-body { padding: 1.15rem 1rem; }
.student-device-page .sd-report-org {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--sd-muted);
}
.student-device-page .sd-report-title {
    margin: 0.25rem 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--sd-text);
}
.student-device-page .sd-report-month { color: var(--sd-muted); margin-bottom: 0.15rem; }
.student-device-page .sd-report-meta { font-size: 0.875rem; color: var(--sd-muted); }
.student-device-page .sd-report-stats {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem 1.25rem;
    margin-top: 0.75rem;
    font-size: 0.8125rem;
    color: var(--sd-muted);
}
.student-device-page .sd-student-report-card {
    margin-bottom: 1rem;
}
.student-device-page .sd-student-kpis {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
    justify-content: flex-end;
}
.student-device-page .sd-kpi {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--sd-soft);
    color: var(--sd-muted);
}
.student-device-page .sd-kpi.present { background: #d1e7dd; color: #0f5132; }
.student-device-page .sd-kpi.absent { background: #f8d7da; color: #842029; }
.student-device-page .sd-kpi.late { background: #fff3cd; color: #664d03; }
.student-device-page .sd-kpi.leave { background: #e7e3fc; color: #432874; }
.student-device-page .sd-status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}
.student-device-page .sd-badge-present { background: #d1e7dd; color: #0f5132; }
.student-device-page .sd-badge-absent { background: #f8d7da; color: #842029; }
.student-device-page .sd-badge-late { background: #fff3cd; color: #664d03; }
.student-device-page .sd-badge-leave { background: #e7e3fc; color: #432874; }
.student-device-page .sd-badge-holiday { background: #cff4fc; color: #055160; }
.student-device-page .sd-sao-filter-grid {
    grid-template-columns: repeat(6, minmax(0, 1fr));
}
.student-device-page .sd-sao-layout {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.student-device-page .sd-sao-filters {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.85rem 1rem;
}
.student-device-page .sd-sao-filters-grid {
    display: grid;
    grid-template-columns: 130px minmax(120px, 1fr) minmax(130px, 1.1fr) minmax(110px, 0.9fr) minmax(110px, 0.9fr) minmax(130px, 1.1fr) 120px 100px;
    gap: 0.65rem 0.75rem;
    align-items: end;
}
.student-device-page .sd-sao-empty {
    background: #fff;
    border: 1px dashed var(--sd-border);
    border-radius: 0.65rem;
    padding: 2rem 1rem;
    text-align: center;
    color: var(--sd-muted);
    font-size: 0.9rem;
}
.student-device-page .sd-sao-empty-in {
    border: none;
    border-radius: 0;
    padding: 1.5rem 1rem;
}
.student-device-page .sd-sao-summary {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.85rem 1rem;
}
.student-device-page .sd-sao-summary-main {
    font-size: 0.9rem;
    color: var(--sd-text);
    margin-bottom: 0.75rem;
}
.student-device-page .sd-sao-dot {
    color: #cbd5e1;
    margin: 0 0.25rem;
}
.student-device-page .sd-sao-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.55rem;
}
.student-device-page .sd-sao-kpi {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 0.5rem;
    padding: 0.65rem 0.75rem;
    min-width: 0;
}
.student-device-page .sd-sao-kpi-label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--sd-muted);
}
.student-device-page .sd-sao-kpi-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--sd-text);
    line-height: 1.15;
    font-variant-numeric: tabular-nums;
}
.student-device-page .sd-sao-kpi.is-ok .sd-sao-kpi-value { color: #15803d; }
.student-device-page .sd-sao-kpi.is-bad .sd-sao-kpi-value { color: #b91c1c; }
.student-device-page .sd-sao-kpi.is-warn .sd-sao-kpi-value { color: #a16207; }
.student-device-page .sd-sao-table-card {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    overflow: hidden;
}
.student-device-page .sd-sao-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--sd-border);
    font-size: 0.875rem;
}
.student-device-page .sd-sao-table-head span {
    color: var(--sd-muted);
    font-size: 0.8125rem;
    font-weight: 600;
}
.student-device-page .sd-sao-table-wrap {
    overflow: auto;
    max-height: min(68vh, 780px);
}
.student-device-page .sd-sao-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.8125rem;
}
.student-device-page .sd-sao-table th,
.student-device-page .sd-sao-table td {
    padding: 0.65rem 0.85rem;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
    background: #fff;
    white-space: nowrap;
}
.student-device-page .sd-sao-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8fafc;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
}
.student-device-page .sd-sao-table tbody tr:hover td {
    background: #f8fafc;
}
.student-device-page .sd-sao-id {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
}
.student-device-page .sd-sao-name {
    font-weight: 600;
    color: var(--sd-text);
}
.student-device-page .sd-sao-dates {
    white-space: normal;
    min-width: 10rem;
    max-width: 16rem;
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.35;
}
.student-device-page .sd-sao-tag {
    display: inline-flex;
    align-items: center;
    min-height: 1.5rem;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
}
.student-device-page .sd-sao-tag.is-flag {
    background: #fee2e2;
    color: #b91c1c;
}
.student-device-page .sd-sao-tag.is-other {
    background: #fef3c7;
    color: #a16207;
}
.student-device-page .sd-sao-mobile {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    padding: 0.85rem;
}
.student-device-page .sd-sao-mobile-card {
    border: 1px solid var(--sd-border);
    border-radius: 0.55rem;
    padding: 0.75rem 0.85rem;
    background: #fff;
}
.student-device-page .sd-sao-mobile-top {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
}
.student-device-page .sd-sao-mobile-meta {
    font-size: 0.75rem;
    color: var(--sd-muted);
    margin: 0.35rem 0;
}

/* Month matrix report */
.student-device-page .sd-month-layout {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.student-device-page .sd-month-filters {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.85rem 1rem;
}
.student-device-page .sd-month-filters-grid {
    display: grid;
    grid-template-columns: 148px minmax(140px, 1.2fr) minmax(140px, 1.2fr) 120px 120px 120px 110px;
    gap: 0.65rem 0.75rem;
    align-items: end;
}
.student-device-page .sd-month-empty {
    background: #fff;
    border: 1px dashed var(--sd-border);
    border-radius: 0.65rem;
    padding: 2rem 1rem;
    text-align: center;
    color: var(--sd-muted);
    font-size: 0.9rem;
}
.student-device-page .sd-month-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem 1.25rem;
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.75rem 1rem;
}
.student-device-page .sd-month-summary-main {
    font-size: 0.9rem;
    color: var(--sd-text);
    min-width: 0;
}
.student-device-page .sd-month-dot {
    color: #cbd5e1;
    margin: 0 0.25rem;
}
.student-device-page .sd-month-summary-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem 1.1rem;
    font-size: 0.8125rem;
    color: var(--sd-muted);
}
.student-device-page .sd-month-summary-stats b {
    color: var(--sd-text);
    font-weight: 700;
}

/* Student machine users — fingerprint enroll cards */
.student-device-page .sd-users-layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
}
.student-device-page .sd-users-panel {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.student-device-page .sd-users-search {
    padding: 1rem 1.25rem;
}
.student-device-page .sd-users-search-grid,
.student-device-page .sd-users-filters-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.75rem 0.85rem;
    align-items: end;
}
.student-device-page .sd-users-search-field {
    min-width: 0;
}
.student-device-page .sd-users-search-btns {
    min-width: 0;
}
.student-device-page .sd-users-btn-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.5rem;
}
.student-device-page .sd-users-btn-row .btn {
    min-height: 38px;
    flex: 1 1 auto;
}
.student-device-page .sd-users-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.65rem 1rem;
    padding: 0.7rem 1.25rem;
    border-top: 1px solid var(--sd-border);
    background: var(--sd-soft);
}
.student-device-page .sd-users-toolbar-form {
    margin: 0;
    flex: 0 0 auto;
}
.student-device-page .sd-users-toolbar-hint {
    flex: 1 1 200px;
    font-size: 0.8125rem;
    color: var(--sd-muted);
    line-height: 1.4;
    margin: 0;
}
.student-device-page .sd-users-empty {
    background: #fff;
    border: 1px dashed var(--sd-border);
    border-radius: 0.75rem;
    padding: 2.5rem 1.25rem;
    text-align: center;
    color: var(--sd-muted);
    font-size: 0.9rem;
}

/* Student cards grid */
.student-device-page .sd-users-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    width: 100%;
    align-items: stretch;
}
.student-device-page .sd-stu-card {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.8rem;
    padding: 1rem;
    min-width: 0;
    max-width: 100%;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.student-device-page .sd-stu-card.is-off {
    border-style: dashed;
    background: #fcfcfd;
}
.student-device-page .sd-stu-photos {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}
.student-device-page .sd-stu-photo {
    margin: 0;
    position: relative;
    aspect-ratio: 1;
    border-radius: 0.65rem;
    overflow: hidden;
    background: #e2e8f0;
    border: 1px solid var(--sd-border);
}
.student-device-page .sd-stu-photo.has-face {
    border-color: #86efac;
}
.student-device-page .sd-stu-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.student-device-page .sd-stu-photo-fallback {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 1.6rem;
    background: #f1f5f9;
}
.student-device-page .sd-stu-photo figcaption {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 0.2rem 0.35rem;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    text-align: center;
    color: #fff;
    background: rgba(15, 23, 42, 0.68);
}
.student-device-page .sd-stu-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    flex: 1 1 auto;
    min-width: 0;
}
.student-device-page .sd-stu-name {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--sd-text);
    line-height: 1.25;
    word-break: break-word;
}
.student-device-page .sd-stu-meta {
    display: grid;
    gap: 0.4rem;
}
.student-device-page .sd-stu-meta-row {
    display: grid;
    grid-template-columns: 6.5rem minmax(0, 1fr);
    gap: 0.4rem;
    align-items: center;
}
.student-device-page .sd-stu-label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--sd-muted);
}
.student-device-page .sd-stu-emp {
    display: inline-block;
    width: fit-content;
    max-width: 100%;
    background: #eef2ff;
    color: #3730a3;
    border-radius: 0.35rem;
    padding: 0.18rem 0.45rem;
    font-size: 0.84rem;
    font-weight: 700;
    word-break: break-all;
}
.student-device-page .sd-stu-sid {
    font-size: 0.875rem;
    color: var(--sd-text);
    word-break: break-word;
}
.student-device-page .sd-stu-actions {
    display: grid;
    gap: 0.45rem;
    margin-top: auto;
    padding-top: 0.75rem;
    border-top: 1px solid var(--sd-border);
}
.student-device-page .sd-stu-action {
    display: grid;
    grid-template-columns: 5rem minmax(0, 1fr);
    gap: 0.45rem;
    align-items: center;
}
.student-device-page .sd-stu-action-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--sd-muted);
    letter-spacing: 0.02em;
}
.student-device-page .sd-stu-btns {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.35rem;
    align-items: center;
}
.student-device-page .sd-stu-btns form,
.student-device-page .sd-stu-action-full {
    margin: 0;
}
.student-device-page .sd-stu-action-full .btn {
    width: 100%;
    min-height: 36px;
}
.student-device-page .sd-stu-btns .btn {
    min-height: 34px;
    min-width: 4.75rem;
    justify-content: center;
    flex: 1 1 auto;
}
.student-device-page .sd-stu-btns .btn-outline-danger {
    flex: 0 0 auto;
    min-width: 34px;
    width: 34px;
    padding-left: 0;
    padding-right: 0;
}
.student-device-page .sd-summary-chip-muted {
    background: #f1f5f9;
    color: #475569;
}

/* Multi-device credential sync */
.student-device-page .sd-devices-layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.student-device-page .sd-devices-hint {
    background: #f8fafc;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.85rem 1.1rem;
    font-size: 0.875rem;
    color: var(--sd-text);
    line-height: 1.45;
}
.student-device-page .sd-devices-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.student-device-page .sd-devices-actions form {
    margin: 0;
}
.student-device-page .sd-devices-panel {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.student-device-page .sd-devices-panel-head {
    padding: 0.7rem 1.1rem;
    border-bottom: 1px solid var(--sd-border);
    background: #f8fafc;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--sd-muted);
}
.student-device-page .sd-devices-table th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--sd-muted);
    white-space: nowrap;
}
.student-device-page .sd-devices-role,
.student-device-page .sd-devices-msg {
    font-size: 0.75rem;
    color: var(--sd-muted);
    margin-top: 0.15rem;
}
.student-device-page .sd-devices-err {
    font-size: 0.78rem;
    color: #9a3412;
    max-width: 280px;
    word-break: break-word;
}
.student-device-page .sd-dev-status {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    border-radius: 0.3rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}
.student-device-page .sd-dev-status.is-on { background: #dcfce7; color: #166534; }
.student-device-page .sd-dev-status.is-off { background: #fee2e2; color: #991b1b; }
.student-device-page .sd-sync-pill {
    display: inline-block;
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 650;
    text-transform: uppercase;
    background: #f1f5f9;
    color: #475569;
}
.student-device-page .sd-sync-pill.is-pending { background: #fef3c7; color: #92400e; }
.student-device-page .sd-sync-pill.is-syncing { background: #dbeafe; color: #1e40af; }
.student-device-page .sd-sync-pill.is-success { background: #dcfce7; color: #166534; }
.student-device-page .sd-sync-pill.is-failed { background: #fee2e2; color: #991b1b; }
.student-device-page .sd-devices-sync-user {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: end;
    padding: 1rem 1.1rem;
}
.student-device-page .sd-devices-sync-user .sd-field {
    flex: 1 1 220px;
    max-width: 320px;
}
.student-device-page .sd-devices-delete-check {
    flex: 0 1 auto;
    max-width: none;
}
.student-device-page .sd-devices-delete-check .form-check {
    min-height: 38px;
    display: flex;
    align-items: center;
    margin: 0;
}
.student-device-page .sd-devices-delete-hint {
    font-size: 0.8125rem;
    color: var(--sd-muted);
    line-height: 1.4;
}
.student-device-page .sd-subnav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0 0 1rem;
    padding: 0.35rem;
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.75rem;
}
.student-device-page .sd-subnav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 38px;
    padding: 0.4rem 0.85rem;
    border-radius: 0.55rem;
    border: 1px solid transparent;
    color: #334155;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
}
.student-device-page .sd-subnav-link i { color: var(--sd-muted); width: 1rem; text-align: center; }
.student-device-page .sd-subnav-link:hover { background: var(--sd-soft); color: var(--sd-navy); }
.student-device-page .sd-subnav-link.is-active {
    background: var(--sd-navy);
    color: #fff;
}
.student-device-page .sd-subnav-link.is-active i { color: rgba(255,255,255,.95); }
.student-device-page .sd-subnav-badge {
    font-style: normal;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 1.25rem;
    padding: 0.1rem 0.35rem;
    border-radius: 999px;
    background: #fef3c7;
    color: #92400e;
    text-align: center;
}
.student-device-page .sd-subnav-link.is-active .sd-subnav-badge {
    background: rgba(255,255,255,.2);
    color: #fff;
}
.student-device-page .sd-devices-kpi {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 0.85rem;
}
.student-device-page .sd-kpi {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.75rem 0.9rem;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.student-device-page .sd-kpi-label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--sd-muted);
}
.student-device-page .sd-kpi strong {
    font-size: 1.25rem;
    color: var(--sd-text);
    line-height: 1.2;
}
.student-device-page .sd-devices-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 0.55rem 0.75rem;
    margin-bottom: 0.75rem;
}
.student-device-page .sd-devices-toolbar form { margin: 0; }
.student-device-page .sd-toolbar-meta {
    font-size: 0.78rem;
    color: var(--sd-muted);
    margin-left: auto;
}
.student-device-page .sd-devices-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem 0.75rem;
    align-items: end;
    flex: 1 1 auto;
}
.student-device-page .sd-devices-filter .sd-field { min-width: 140px; }
.student-device-page .sd-devices-filter .sd-field-grow { flex: 1 1 200px; min-width: 180px; }
.student-device-page .sd-filter-summary {
    font-size: 0.8125rem;
    color: var(--sd-muted);
    margin: 0 0 0.65rem;
}
.student-device-page .sd-devices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.85rem;
}
.student-device-page .sd-dev-card {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.75rem;
    padding: 0.9rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    min-width: 0;
}
.student-device-page .sd-dev-card-top {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    align-items: flex-start;
}
.student-device-page .sd-dev-card-title {
    margin: 0 0 0.15rem;
    font-size: 0.95rem;
    font-weight: 700;
}
.student-device-page .sd-dev-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    font-size: 0.78rem;
    color: var(--sd-muted);
}
.student-device-page .sd-dev-card-msg {
    margin: 0;
    font-size: 0.75rem;
    color: var(--sd-muted);
    word-break: break-word;
}
.student-device-page .sd-dev-card-action { margin-top: auto; }
.student-device-page .sd-devices-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 2rem 1rem;
    color: var(--sd-muted);
    background: #fff;
    border: 1px dashed var(--sd-border);
    border-radius: 0.75rem;
}
.student-device-page .sd-devices-panel-foot {
    display: flex;
    justify-content: flex-end;
    padding: 0.65rem 0.9rem;
    border-top: 1px solid var(--sd-border);
    background: #fafbfc;
}
.student-device-page .sd-tools-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
}
.student-device-page .sd-tools-form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem 1.1rem 0.5rem;
}
.student-device-page .sd-tools-help {
    margin: 0;
    padding: 0 1.1rem 1rem;
    font-size: 0.8rem;
    color: var(--sd-muted);
    line-height: 1.4;
}
.student-device-page .sd-tools-delete-bar {
    align-items: end;
}
.student-device-page .sd-tools-main-check {
    min-height: 31px;
    display: flex;
    align-items: center;
    margin: 0 0 0.15rem;
}
.student-device-page .sd-tools-check-col {
    width: 2.25rem;
    text-align: center;
    vertical-align: middle;
}
.student-device-page .sd-dev-status.is-unknown { background: #e9ecef; color: #495057; }
.student-device-page .sd-presence-table th {
    vertical-align: bottom;
    font-size: 0.72rem;
    text-transform: none;
    letter-spacing: 0;
    white-space: nowrap;
}
.student-device-page .sd-presence-host {
    font-size: 0.7rem;
    color: #64748b;
}
.student-device-page .sd-presence-pill {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    border-radius: 0.3rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.student-device-page .sd-presence-pill.is-yes {
    background: #dcfce7;
    color: #166534;
}
.student-device-page .sd-presence-pill.is-no {
    background: #fee2e2;
    color: #991b1b;
}
.student-device-page .sd-presence-bio {
    margin-top: 0.15rem;
    font-size: 0.65rem;
    color: var(--sd-muted);
}
.student-device-page .sd-presence-missing {
    font-size: 0.78rem;
    color: #9a3412;
    max-width: 220px;
    word-break: break-word;
}
.student-device-page .sd-presence-row-missing {
    background: #fffbeb;
}
.student-device-page .sd-presence-legend {
    display: inline-flex;
    gap: 0.4rem;
    margin-left: 0.5rem;
    vertical-align: middle;
}
@media (max-width: 991.98px) {
    .student-device-page .sd-devices-kpi {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .student-device-page .sd-tools-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 767.98px) {
    .student-device-page .sd-devices-kpi {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .student-device-page .sd-subnav {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .student-device-page .sd-subnav-link {
        width: 100%;
        justify-content: flex-start;
    }
    .student-device-page .sd-devices-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    .student-device-page .sd-toolbar-meta { margin-left: 0; }
    .student-device-page .sd-devices-filter {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }
    .student-device-page .sd-devices-filter .sd-field,
    .student-device-page .sd-devices-filter .sd-field-grow {
        width: 100%;
        min-width: 0;
    }
    .student-device-page .sd-devices-toolbar .btn,
    .student-device-page .sd-tools-form .btn {
        width: 100%;
    }
    .student-device-page .sd-devices-sync-user {
        flex-direction: column;
        align-items: stretch;
    }
    .student-device-page .sd-devices-sync-user .sd-field {
        max-width: none;
        width: 100%;
    }
    .student-device-page .sd-devices-sync-user .btn {
        width: 100%;
    }
    .student-device-page .sd-presence-table {
        min-width: 640px;
    }
    .student-device-page .sd-devices-panel-foot {
        justify-content: center;
    }
}

@media (max-width: 1100px) {
    .student-device-page .sd-users-filters-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 991.98px) {
    .student-device-page .sd-users-filters-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .student-device-page .sd-users-search-field,
    .student-device-page .sd-users-search-btns {
        grid-column: 1 / -1;
    }
}
@media (max-width: 768px) {
    .student-device-page .sd-users-search {
        padding: 0.85rem 0.9rem;
    }
    .student-device-page .sd-users-search-grid,
    .student-device-page .sd-users-filters-grid {
        grid-template-columns: 1fr;
        gap: 0.65rem;
    }
    .student-device-page .sd-users-search-field,
    .student-device-page .sd-users-search-btns {
        grid-column: auto;
    }
    .student-device-page .sd-users-btns-label {
        display: none;
    }
    .student-device-page .sd-users-btn-row {
        flex-wrap: wrap;
        width: 100%;
    }
    .student-device-page .sd-users-btn-row .btn {
        flex: 1 1 calc(50% - 0.25rem);
        min-width: 0;
    }
    .student-device-page .sd-users-toolbar {
        flex-direction: column;
        align-items: stretch;
        padding: 0.75rem 0.9rem;
        gap: 0.5rem;
    }
    .student-device-page .sd-users-toolbar-form .btn {
        width: 100%;
    }
    .student-device-page .sd-users-toolbar-hint {
        flex: none;
        width: 100%;
    }
    .student-device-page .sd-users-cards {
        grid-template-columns: 1fr;
        gap: 0.85rem;
    }
    .student-device-page .sd-stu-card {
        padding: 0.85rem;
        gap: 0.75rem;
    }
    .student-device-page .sd-stu-photos {
        max-width: 280px;
        margin: 0 auto;
        width: 100%;
    }
    .student-device-page .sd-stu-name {
        font-size: 1rem;
        text-align: center;
    }
    .student-device-page .sd-stu-meta-row {
        grid-template-columns: 5.5rem minmax(0, 1fr);
        gap: 0.35rem 0.5rem;
        align-items: start;
    }
    .student-device-page .sd-stu-emp,
    .student-device-page .sd-stu-sid {
        justify-self: start;
        text-align: left;
    }
    .student-device-page .sd-stu-action {
        grid-template-columns: 4.75rem minmax(0, 1fr);
        gap: 0.4rem 0.5rem;
        align-items: center;
    }
    .student-device-page .sd-stu-action-label {
        line-height: 1.2;
    }
    .student-device-page .sd-stu-btns {
        width: 100%;
    }
    .student-device-page .sd-stu-btns .btn {
        min-width: 0;
        flex: 1 1 auto;
    }
    .student-device-page .sd-page-head-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        width: 100%;
    }
    .student-device-page .sd-summary-chip {
        white-space: normal;
    }
}
@media (max-width: 420px) {
    .student-device-page .sd-stu-photos {
        max-width: 100%;
    }
    .student-device-page .sd-stu-meta-row {
        grid-template-columns: 1fr;
        gap: 0.15rem;
    }
    .student-device-page .sd-stu-action {
        grid-template-columns: 1fr;
        gap: 0.3rem;
        padding: 0.35rem 0;
        border-bottom: 1px solid var(--sd-border);
    }
    .student-device-page .sd-stu-action:last-child {
        border-bottom: 0;
    }
    .student-device-page .sd-stu-btns .btn {
        min-height: 38px;
    }
}
@media (min-width: 1400px) {
    .student-device-page .sd-users-cards {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }
}

/* Student Information Excel Export */
.student-device-page .sd-excel-layout {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}
.student-device-page .sd-excel-hint {
    background: #f8fafc;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.75rem 1rem;
}
.student-device-page .sd-excel-hint-title {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--sd-muted);
    margin-bottom: 0.25rem;
}
.student-device-page .sd-excel-hint-text {
    font-size: 0.875rem;
    color: var(--sd-text);
    line-height: 1.45;
}
.student-device-page .sd-excel-hint-text code {
    background: #e2e8f0;
    border-radius: 0.25rem;
    padding: 0.05rem 0.35rem;
    font-size: 0.8125rem;
}
.student-device-page .sd-excel-hint-badge {
    display: inline-block;
    margin-left: 0.35rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #e0f2fe;
    color: #075985;
    font-size: 0.75rem;
    font-weight: 600;
}
.student-device-page .sd-excel-filters {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 1rem 1.1rem;
}
.student-device-page .sd-excel-filters-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.75rem 0.85rem;
    align-items: end;
}
.student-device-page .sd-excel-actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.student-device-page .sd-excel-actions-row .btn {
    flex: 1 1 auto;
    min-width: 7.5rem;
    white-space: nowrap;
}
.student-device-page .sd-excel-empty {
    background: #fff;
    border: 1px dashed var(--sd-border);
    border-radius: 0.65rem;
    padding: 2.25rem 1.25rem;
    text-align: center;
    color: var(--sd-muted);
    font-size: 0.9rem;
}
.student-device-page .sd-excel-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    padding: 0.8rem 1.1rem;
}
.student-device-page .sd-excel-summary-left {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.4rem 0.65rem;
    min-width: 0;
}
.student-device-page .sd-excel-count {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--sd-text);
    line-height: 1;
}
.student-device-page .sd-excel-count-label {
    font-size: 0.875rem;
    color: var(--sd-muted);
}
.student-device-page .sd-excel-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #ecfdf5;
    color: #065f46;
    font-size: 0.75rem;
    font-weight: 600;
}
.student-device-page .sd-excel-table-card {
    overflow: hidden;
}
.student-device-page .sd-excel-table-card .sd-table-wrap {
    max-height: 62vh;
}
.student-device-page .sd-excel-table thead th {
    white-space: nowrap;
    vertical-align: middle;
}
.student-device-page .sd-excel-table tbody td {
    vertical-align: middle;
}
@media (max-width: 1200px) {
    .student-device-page .sd-excel-filters-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .student-device-page .sd-excel-actions {
        grid-column: 1 / -1;
    }
}
@media (max-width: 768px) {
    .student-device-page .sd-excel-filters-grid {
        grid-template-columns: 1fr;
    }
    .student-device-page .sd-excel-actions-row .btn {
        width: 100%;
    }
}

.student-device-page .sd-month-total {
    color: #0f5132;
    font-weight: 700;
}
.student-device-page .sd-month-legend {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.65rem;
    font-size: 0.75rem;
    color: var(--sd-muted);
    width: 100%;
    padding-top: 0.55rem;
    border-top: 1px solid var(--sd-border);
}
.student-device-page .sd-month-pager {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.8125rem;
    color: var(--sd-muted);
}
.student-device-page .sd-month-pager-actions {
    display: flex;
    gap: 0.4rem;
}
.student-device-page .sd-month-table-card {
    background: #fff;
    border: 1px solid var(--sd-border);
    border-radius: 0.65rem;
    overflow: hidden;
}
.student-device-page .sd-matrix-wrap {
    max-height: min(72vh, 860px);
    overflow: auto;
}
.student-device-page .sd-matrix-table {
    width: max-content;
    min-width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.75rem;
    line-height: 1.25;
    color: var(--sd-text);
}
.student-device-page .sd-matrix-table th,
.student-device-page .sd-matrix-table td {
    border-bottom: 1px solid #eef2f7;
    border-right: 1px solid #eef2f7;
    padding: 0.4rem 0.45rem;
    vertical-align: middle;
    background: #fff;
}
.student-device-page .sd-matrix-table th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafc;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #64748b;
    white-space: nowrap;
}
.student-device-page .sd-mx-sticky,
.student-device-page .sd-mx-sticky-2 {
    position: sticky;
    z-index: 4;
    background: #fff;
}
.student-device-page .sd-mx-sticky { left: 0; }
.student-device-page .sd-mx-sticky-2 { left: 8.75rem; }
.student-device-page thead .sd-mx-sticky,
.student-device-page thead .sd-mx-sticky-2 {
    z-index: 6;
    background: #f8fafc;
}
.student-device-page .sd-mx-id {
    min-width: 8.75rem;
    max-width: 8.75rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.72rem;
    font-weight: 600;
}
.student-device-page .sd-mx-name {
    min-width: 10.5rem;
    max-width: 12rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 600;
}
.student-device-page .sd-mx-mode {
    min-width: 2.6rem;
    text-align: center;
    color: var(--sd-muted);
    font-weight: 600;
}
.student-device-page .sd-mx-bank,
.student-device-page .sd-mx-branch {
    min-width: 6.5rem;
    max-width: 8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #475569;
}
.student-device-page .sd-mx-acc {
    min-width: 7rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.7rem;
    color: #475569;
    white-space: nowrap;
}
.student-device-page .sd-mx-day {
    min-width: 2rem;
    width: 2rem;
    padding: 0.3rem 0.15rem !important;
    text-align: center;
}
.student-device-page .sd-mx-day-num {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--sd-text);
    text-transform: none;
    letter-spacing: 0;
}
.student-device-page .sd-mx-dow {
    display: block;
    font-size: 0.58rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.student-device-page .sd-mx-day-cell {
    padding: 0.2rem !important;
    text-align: center;
}
.student-device-page .sd-mx-num {
    min-width: 2.6rem;
    text-align: center;
    font-weight: 650;
    font-variant-numeric: tabular-nums;
}
.student-device-page .sd-mx-allow {
    min-width: 4.5rem;
    text-align: right;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    padding-right: 0.65rem !important;
}
.student-device-page .sd-mx-cell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 0.3rem;
    font-weight: 700;
    font-size: 0.7rem;
    line-height: 1;
}
.student-device-page .sd-mx-cell.present { background: #16a34a; color: #fff; }
.student-device-page .sd-mx-cell.absent { background: #fee2e2; color: #b91c1c; }
.student-device-page .sd-mx-cell.holiday { background: #f97316; color: #fff; }
.student-device-page .sd-mx-cell.empty { background: #f1f5f9; color: #94a3b8; }
.student-device-page .sd-pct-high { color: #15803d; }
.student-device-page .sd-pct-mid { color: #a16207; }
.student-device-page .sd-pct-low { color: #b91c1c; }
.student-device-page .sd-mx-total td {
    background: #f8fafc;
    border-top: 2px solid #cbd5e1;
    font-weight: 700;
}
.student-device-page .sd-mx-total .sd-mx-sticky { background: #f8fafc; }
.student-device-page .sd-mx-allow-total { color: #15803d; }
.student-device-page .sd-sync-grid .btn { min-height: 38px; }

/* Legacy shell pages that still use container-fluid */
.student-device-page.container-fluid {
    width: 100%;
    max-width: 100%;
}

@media (max-width: 1199.98px) {
    .student-device-page .sd-filter-grid,
    .student-device-page .sd-status-filter-grid,
    .student-device-page .sd-sao-filter-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .student-device-page .sd-month-filters-grid,
    .student-device-page .sd-sao-filters-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .student-device-page .sd-sao-kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .student-device-page .sd-field-actions { grid-column: 1 / -1; }
}
@media (max-width: 991.98px) {
    .main-content > .student-device-page.sd-fullpage {
        margin: -1rem;
    }
    .student-device-page .sd-fullpage-body {
        padding: 0.9rem 1rem 1.25rem;
    }
    .student-device-page .sd-top-nav {
        padding: 0.7rem 1rem;
    }
}
@media (max-width: 767.98px) {
    .main-content > .student-device-page.sd-fullpage {
        margin: -0.75rem;
    }
    .student-device-page .sd-fullpage-body {
        padding: 0.75rem 0.75rem 1rem;
    }
    .student-device-page .sd-top-nav {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.4rem;
        padding: 0.65rem 0.75rem;
    }
    .student-device-page .sd-top-nav-link {
        width: 100%;
        justify-content: flex-start;
    }
    .student-device-page .sd-page-head {
        flex-direction: column;
        align-items: stretch;
    }
    .student-device-page .sd-header-actions .btn {
        flex: 1 1 calc(50% - 0.45rem);
    }
    .student-device-page .sd-filter-grid,
    .student-device-page .sd-month-filter-grid,
    .student-device-page .sd-status-filter-grid,
    .student-device-page .sd-sao-filter-grid,
    .student-device-page .sd-month-filters-grid,
    .student-device-page .sd-sao-filters-grid,
    .student-device-page .sd-sao-kpis {
        grid-template-columns: 1fr;
    }
    .student-device-page .sd-filter-actions { flex-direction: column; }
    .student-device-page .sd-filter-actions .btn,
    .student-device-page .sd-field-actions .btn { width: 100%; }
    .student-device-page .sd-pager {
        flex-direction: column;
        align-items: stretch;
        padding: 0.75rem;
    }
    .student-device-page .sd-pager .pagination {
        justify-content: center !important;
    }
}
</style>
