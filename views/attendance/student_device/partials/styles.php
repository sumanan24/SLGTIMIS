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
    font-size: 0.8125rem;
    color: var(--sd-muted);
    background: var(--sd-soft);
    border: 1px solid var(--sd-border);
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    white-space: nowrap;
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
    grid-template-columns: 148px minmax(140px, 1.2fr) minmax(120px, 1fr) minmax(140px, 1.2fr) 130px 110px;
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
