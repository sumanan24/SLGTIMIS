<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/month_report_data.php';

$pageTitle = 'Month report';
$urls = staff_attendance_dashboard_urls_for_module('dashboard.php');
$staffDeviceSection = 'month';

$tz = new DateTimeZone(STAFF_TIMEZONE);
$defaultMonth = (new DateTimeImmutable('now', $tz))->format('Y-m');

$reportMonth = trim((string) ($_GET['report_month'] ?? ''));
if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
    $reportMonth = $defaultMonth;
}

$employeeNo = trim((string) ($_GET['employee_no'] ?? ''));
$state = staff_attendance_month_report_fetch($reportMonth, $employeeNo);
$pdfRows = staff_attendance_month_report_pdf_rows($state['grouped']);
$employees = $state['employees'];
$grouped = $state['grouped'];
$dbError = $state['dbError'];

require __DIR__ . '/includes/header.php';
$monthBase = $urls['month'];
$hasRows = !empty($grouped);
$pdfPayload = json_encode(
    [
        'reportMonth' => $reportMonth,
        'employeeNo' => $employeeNo,
        'rows' => $pdfRows,
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);
?>
<div class="row g-4">
    <div class="col-lg-2 col-md-3">
        <?php include __DIR__ . '/partials/staff_device_nav.php'; ?>
    </div>
    <div class="col-lg-10 col-md-9">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="mb-0 fw-bold">Month report</h5>
                <?php if ($hasRows): ?>
                <button type="button" class="btn btn-light btn-sm" id="staffMonthReportPdfBtn" title="Download table as PDF">
                    <i class="fas fa-file-pdf me-1"></i>Download PDF
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end mb-4" action="<?php echo attendance_escape($monthBase); ?>">
                    <div class="col-auto">
                        <label class="form-label small mb-0">Month</label>
                        <input type="month" name="report_month" class="form-control" value="<?php echo attendance_escape($reportMonth); ?>">
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label small mb-0">Employee</label>
                        <select name="employee_no" class="form-select form-select-sm">
                            <option value="">All employees</option>
                            <?php foreach ($employees as $em): ?>
                                <?php
                                $eno = (string) $em['employee_no'];
                                $sn = trim((string) ($em['staff_name'] ?? ''));
                                $label = $sn !== '' ? $sn . ' (' . $eno . ')' : $eno;
                                ?>
                                <option value="<?php echo attendance_escape($eno); ?>" <?php echo $employeeNo === $eno ? 'selected' : ''; ?>>
                                    <?php echo attendance_escape($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Show</button>
                    </div>
                </form>

                <p class="text-muted small mb-3">
                    Same layout as the device dashboard: one row per day per employee. The employee list only includes people with at least one punch in the selected month.
                </p>

                <?php if ($dbError !== null): ?>
                    <div class="alert alert-danger">
                        <strong>Database error.</strong> <span class="text-break"><?php echo attendance_escape($dbError); ?></span>
                    </div>
                <?php else: ?>

                <div class="table-responsive shadow-sm bg-white rounded border">
                    <table class="table table-striped table-sm mb-0" id="staffMonthReportTable">
                        <thead class="table-light">
                        <tr>
                            <th>Employee no.</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check-in <span class="text-muted fw-normal">(min)</span></th>
                            <th>Check-out <span class="text-muted fw-normal">(max)</span></th>
                            <th>Other times</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$grouped): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No attendance in this month<?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($grouped as $r): ?>
                                <?php
                                $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
                                $d = (string) $r['d'];
                                $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
                                $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';
                                ?>
                                <tr>
                                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                                    <td><?php echo attendance_escape((string) ($r['department'] ?? '')); ?></td>
                                    <td><span class="text-nowrap"><?php echo attendance_escape($d); ?></span></td>
                                    <td><?php echo attendance_escape($dayLabel); ?></td>
                                    <td><code><?php echo attendance_escape($split['in']); ?></code></td>
                                    <td><code><?php echo attendance_escape($split['out']); ?></code></td>
                                    <td class="small"><?php echo $otherStr === '—' ? '—' : attendance_escape($otherStr); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($hasRows): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    var payload = <?php echo $pdfPayload; ?>;
    var btn = document.getElementById('staffMonthReportPdfBtn');
    if (!btn || typeof window.jspdf === 'undefined') return;
    btn.addEventListener('click', function () {
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        doc.setFontSize(12);
        doc.text('Staff month report — ' + payload.reportMonth, 14, 12);
        var y = 18;
        if (payload.employeeNo) {
            doc.setFontSize(9);
            doc.text('Employee filter: ' + payload.employeeNo, 14, y);
            y += 6;
        }
        var body = (payload.rows || []).map(function (r) {
            return [
                String(r.employee_no || ''),
                String(r.name || ''),
                String(r.department || ''),
                String(r.date || ''),
                String(r.day || ''),
                String(r.check_in || ''),
                String(r.check_out || ''),
                String(r.other || '')
            ];
        });
        doc.autoTable({
            startY: y,
            head: [['Employee no.', 'Name', 'Department', 'Date', 'Day', 'Check-in (min)', 'Check-out (max)', 'Other times']],
            body: body,
            styles: { fontSize: 7, cellPadding: 1.2 },
            headStyles: { fillColor: [13, 110, 253] }
        });
        doc.save('staff-month-report-' + payload.reportMonth + '.pdf');
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
