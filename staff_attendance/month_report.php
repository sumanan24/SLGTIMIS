<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/dashboard_data.php';
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

$staffNameFilter = trim((string) ($_GET['staff_name'] ?? ''));
$rows = staff_attendance_month_report_rows($reportMonth, $staffNameFilter);

require __DIR__ . '/includes/header.php';
$monthBase = $urls['month'];
$pdfPayload = json_encode(
    [
        'reportMonth' => $reportMonth,
        'staffNameFilter' => $staffNameFilter,
        'rows' => $rows,
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
                <?php if (!empty($rows)): ?>
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
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small mb-0">Staff name <span class="text-muted fw-normal">(contains)</span></label>
                        <input type="text" name="staff_name" class="form-control" placeholder="Filter by name"
                               value="<?php echo attendance_escape($staffNameFilter); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Show</button>
                    </div>
                </form>

                <p class="text-muted small">Days present = distinct calendar days with at least one punch. Staff with resolved names only. Leave name empty to list everyone.</p>

                <div class="table-responsive shadow-sm bg-white rounded border">
                    <table class="table table-striped table-sm mb-0" id="staffMonthReportTable">
                        <thead class="table-light">
                        <tr>
                            <th>Employee no.</th>
                            <th>Staff name</th>
                            <th>Department</th>
                            <th class="text-end">Days present</th>
                            <th class="text-end">Punch count</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="5" class="text-center py-4">No attendance for this month<?php echo $staffNameFilter !== '' ? ' (try clearing the name filter)' : ''; ?>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo attendance_escape((string) $r['employee_no']); ?></td>
                                    <td><?php echo attendance_escape((string) $r['staff_name']); ?></td>
                                    <td><?php echo attendance_escape((string) ($r['department'] ?? '')); ?></td>
                                    <td class="text-end"><?php echo (int) ($r['days_present'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($r['punch_count'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($rows)): ?>
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
        if (payload.staffNameFilter) {
            doc.setFontSize(9);
            doc.text('Staff name filter: ' + payload.staffNameFilter, 14, y);
            y += 6;
        }
        var body = (payload.rows || []).map(function (r) {
            return [
                String(r.employee_no || ''),
                String(r.staff_name || ''),
                String(r.department || ''),
                String(r.days_present != null ? r.days_present : ''),
                String(r.punch_count != null ? r.punch_count : '')
            ];
        });
        doc.autoTable({
            startY: y,
            head: [['Employee no.', 'Staff name', 'Department', 'Days present', 'Punch count']],
            body: body,
            styles: { fontSize: 8, cellPadding: 1.5 },
            headStyles: { fillColor: [13, 110, 253] },
            columnStyles: { 3: { halign: 'right' }, 4: { halign: 'right' } }
        });
        doc.save('staff-month-report-' + payload.reportMonth + '.pdf');
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
