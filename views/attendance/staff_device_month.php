<?php
declare(strict_types=1);
/** @var array $urls */
/** @var string $reportMonth */
/** @var string $employeeNo */
/** @var array $employees */
/** @var array $grouped */
/** @var array $pdfRows */
/** @var string|null $dbError */
/** @var string $monthDisplay */
/** @var string $employeeFilterLabel */
$employeeNo = $employeeNo ?? '';
$monthDisplay = $monthDisplay ?? '';
$employeeFilterLabel = $employeeFilterLabel ?? 'All employees';
$monthBase = $urls['month'];
$pdfPayload = json_encode(
    [
        'reportMonth' => $reportMonth,
        'monthDisplay' => $monthDisplay,
        'employeeFilterLabel' => $employeeFilterLabel,
        'rows' => $pdfRows ?? [],
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);
$hasRows = !empty($grouped);
?>
<div class="container-fluid px-4 py-3">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">
            <?php include BASE_PATH . '/staff_attendance/partials/staff_device_nav.php'; ?>
        </div>
        <div class="col-lg-10 col-md-9">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Staff Attendance Summary</h5>
                    <?php if ($hasRows): ?>
                    <button type="button" class="btn btn-light btn-sm" id="staffMonthReportPdfBtn" title="Download table as PDF">
                        <i class="fas fa-file-pdf me-1"></i>Download PDF
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-2 align-items-end mb-4" action="<?php echo htmlspecialchars($monthBase, ENT_QUOTES, 'UTF-8'); ?>" id="staffMonthReportForm">
                        <div class="col-auto">
                            <label class="form-label small mb-0">Month</label>
                            <input type="month" name="report_month" class="form-control" value="<?php echo htmlspecialchars($reportMonth, ENT_QUOTES, 'UTF-8'); ?>">
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
                                    <option value="<?php echo htmlspecialchars($eno, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $employeeNo === $eno ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Show</button>
                        </div>
                    </form>

                    <div class="text-center border rounded bg-light py-3 px-2 mb-4">
                        <div class="fw-semibold small text-secondary">Sri Lanka German Training Institute</div>
                        <div class="h5 mb-1 fw-bold">Staff Attendance Summary</div>
                        <?php if ($monthDisplay !== ''): ?>
                            <div class="text-muted"><?php echo htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <div class="text-muted small">Employee: <?php echo htmlspecialchars($employeeFilterLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>

                    <p class="text-muted small mb-3">
                        One row per day per employee (check-in / check-out from device). Employees listed have at least one punch in the selected month.
                    </p>

                    <?php if ($dbError !== null): ?>
                        <div class="alert alert-danger">
                            <strong>Database error.</strong> <span class="text-break"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php else: ?>

                    <div class="table-responsive shadow-sm bg-white rounded border">
                        <table class="table table-striped table-sm mb-0" id="staffMonthReportTable">
                            <thead class="table-light">
                            <tr>
                                <th>Employee no.</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Other times</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$grouped): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No attendance in this month<?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.</td></tr>
                            <?php else: ?>
                                <?php foreach ($grouped as $r): ?>
                                    <?php
                                    $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
                                    $d = (string) $r['d'];
                                    $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
                                    $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $r['employee_no'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $r['staff_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="text-nowrap"><?php echo htmlspecialchars($d, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td><?php echo htmlspecialchars($dayLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><code><?php echo htmlspecialchars($split['in'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td><code><?php echo htmlspecialchars($split['out'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td class="small"><?php echo $otherStr === '—' ? '—' : htmlspecialchars($otherStr, ENT_QUOTES, 'UTF-8'); ?></td>
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
        var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        var pageW = doc.internal.pageSize.getWidth();
        var marginX = 14;
        var y = 16;
        doc.setFontSize(13);
        doc.text('Sri Lanka German Training Institute', pageW / 2, y, { align: 'center' });
        y += 7;
        doc.setFontSize(11);
        doc.text('Staff Attendance Summary', pageW / 2, y, { align: 'center' });
        y += 6;
        doc.setFontSize(10);
        if (payload.monthDisplay) {
            doc.text(payload.monthDisplay, pageW / 2, y, { align: 'center' });
            y += 6;
        }
        doc.setFontSize(9);
        doc.setTextColor(80, 80, 80);
        doc.text('Employee: ' + payload.employeeFilterLabel, pageW / 2, y, { align: 'center' });
        doc.setTextColor(0, 0, 0);
        y += 10;
        var body = (payload.rows || []).map(function (r) {
            return [
                String(r.employee_no || ''),
                String(r.name || ''),
                String(r.date || ''),
                String(r.day || ''),
                String(r.check_in || ''),
                String(r.check_out || ''),
                String(r.other || '')
            ];
        });
        doc.autoTable({
            startY: y,
            margin: { left: marginX, right: marginX },
            tableWidth: pageW - marginX * 2,
            head: [['Employee no.', 'Name', 'Date', 'Day', 'Check-in', 'Check-out', 'Other times']],
            body: body,
            styles: {
                fontSize: 8,
                cellPadding: 1.5,
                valign: 'middle',
                overflow: 'linebreak',
                lineColor: [220, 220, 220],
                lineWidth: 0.1
            },
            headStyles: {
                fillColor: [13, 110, 253],
                textColor: 255,
                halign: 'center',
                valign: 'middle',
                fontStyle: 'bold'
            },
            columnStyles: {
                0: { halign: 'center', cellWidth: 18 },
                1: { halign: 'left', cellWidth: 36 },
                2: { halign: 'center', cellWidth: 18 },
                3: { halign: 'center', cellWidth: 24 },
                4: { halign: 'center', cellWidth: 16 },
                5: { halign: 'center', cellWidth: 16 },
                6: { halign: 'left', cellWidth: 54 }
            }
        });
        doc.save('staff-attendance-summary-' + payload.reportMonth + '.pdf');
    });
})();
</script>
<?php endif; ?>
