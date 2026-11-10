<?php
/**
 * Shared helpers + styles for Attendance Management, Teaching Diary and Training Programme pages
 * (blue and white SLGTI look).
 */
if (!function_exists('att_e')) {
    function att_e($v) {
        return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('att_time')) {
    function att_time($v) {
        $v = (string) ($v ?? '');
        return $v === '' ? '—' : substr($v, 0, 5);
    }
}
if (!function_exists('att_badge')) {
    /** Indicator / status chip. */
    function att_badge($label) {
        $map = [
            'Present' => 'ok', 'Full Day Present' => 'ok', 'Reviewed by HOD' => 'ok', 'Approved' => 'ok', 'approved' => 'ok', 'Completed' => 'ok', 'Published' => 'ok', 'published' => 'ok', 'Reviewed' => 'ok',
            'Half Day' => 'warn', 'Half Day Present' => 'warn', 'Partial' => 'warn', 'In Progress' => 'warn', 'Submitted' => 'info', 'Scheduled' => 'info', 'Draft' => 'muted', 'draft' => 'muted',
            'Absent' => 'bad', 'Rejected' => 'bad', 'rejected' => 'bad', 'Cancelled' => 'bad', 'cancelled' => 'bad', 'Not Conducted' => 'bad',
            'Pending Verification' => 'pending', 'Pending Submission' => 'pending', 'Pending' => 'pending', 'pending' => 'pending', 'Upcoming' => 'muted',
            'Manually Updated' => 'manual',
        ];
        $cls = $map[$label] ?? 'muted';
        return '<span class="att-chip att-' . $cls . '">' . att_e($label) . '</span>';
    }
}
if (!function_exists('att_status_label')) {
    function att_status_label($status) {
        return $status === 'full' ? 'Full Day' : ($status === 'half' ? 'Half Day' : 'Absent');
    }
}
$GLOBALS['att_ui_loaded'] = true;
?>
<style>
.att-page { color: #1e293b; }
.att-page .att-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
.att-page .att-title { font-size:1.2rem; font-weight:700; color:#0b2545; margin:0; }
.att-page .att-sub { color:#64748b; font-size:.85rem; margin:.15rem 0 0; }
.att-page .att-card { background:#fff; border:1px solid #dbe4f0; border-radius:.6rem; overflow:hidden; margin-bottom:1rem; }
.att-page .att-card-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; padding:.7rem 1rem; border-bottom:1px solid #e2e8f0; background:#f5f8fd; }
.att-page .att-card-head h2 { font-size:.95rem; font-weight:700; margin:0; color:#0b2545; }
.att-page .att-filters { display:flex; flex-wrap:wrap; gap:.6rem; align-items:flex-end; padding:.8rem 1rem; background:#fff; border:1px solid #dbe4f0; border-radius:.6rem; margin-bottom:1rem; }
.att-page .att-filters label { display:block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#64748b; margin-bottom:.2rem; }
.att-page .att-filters .form-control, .att-page .att-filters .form-select { min-width:9rem; font-size:.85rem; }
.att-page .att-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:.7rem; margin-bottom:1rem; }
.att-page .att-stat { background:#fff; border:1px solid #dbe4f0; border-left:4px solid #1d4ed8; border-radius:.5rem; padding:.7rem .9rem; }
.att-page .att-stat .n { font-size:1.4rem; font-weight:700; color:#0b2545; line-height:1.1; }
.att-page .att-stat .l { font-size:.7rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#64748b; }
.att-page .att-stat.ok { border-left-color:#16a34a; } .att-page .att-stat.warn { border-left-color:#f59e0b; }
.att-page .att-stat.bad { border-left-color:#dc2626; } .att-page .att-stat.pending { border-left-color:#7c3aed; }
.att-page .att-table-wrap { overflow-x:auto; }
.att-page table.att-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.att-page table.att-table th { background:#0b2545; color:#fff; font-size:.7rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.55rem .7rem; text-align:left; white-space:nowrap; }
.att-page table.att-table td { padding:.5rem .7rem; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.att-page table.att-table tbody tr:hover td { background:#f5f8fd; }
.att-page .att-mono { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.8rem; }
.att-page .att-muted { color:#64748b; }
.att-page .att-empty { text-align:center; padding:2.2rem 1rem; color:#64748b; }
.att-page .att-chip { display:inline-block; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700; white-space:nowrap; }
.att-page .att-ok { background:#dcfce7; color:#166534; } .att-page .att-warn { background:#fef3c7; color:#92400e; }
.att-page .att-bad { background:#fee2e2; color:#991b1b; } .att-page .att-info { background:#dbeafe; color:#1e40af; }
.att-page .att-pending { background:#ede9fe; color:#5b21b6; } .att-page .att-manual { background:#e0f2fe; color:#075985; }
.att-page .att-muted-chip, .att-page .att-muted.att-chip { background:#e2e8f0; color:#475569; }
.att-page .att-lock { color:#94a3b8; font-size:.8rem; }
.att-page .att-tabs { display:flex; flex-wrap:wrap; gap:.25rem; border-bottom:2px solid #dbe4f0; margin-bottom:1rem; }
.att-page .att-tabs a { padding:.5rem .9rem; font-size:.85rem; font-weight:600; color:#475569; text-decoration:none; border-bottom:3px solid transparent; margin-bottom:-2px; }
.att-page .att-tabs a.active { color:#1d4ed8; border-bottom-color:#1d4ed8; }
.att-page .att-note { background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; border-radius:.5rem; padding:.6rem .9rem; font-size:.82rem; margin-bottom:1rem; }
@media print { .att-page .att-filters, .att-page .att-actions, .sidebar, .top-navbar { display:none !important; } }
</style>
