<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/month_report_data.php';

$pageTitle = 'Staff Attendance Summary';
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
$stateAll = staff_attendance_month_report_fetch($reportMonth, '');
$groupedSorted = staff_attendance_month_report_sort_grouped($state['grouped']);
$pdfSectionsCurrent = staff_attendance_month_report_sections_from_grouped($state['grouped']);
$pdfSectionsAll = staff_attendance_month_report_sections_from_grouped($stateAll['grouped']);
$employees = $state['employees'];
$grouped = $state['grouped'];
$dbError = $state['dbError'];
$headerMeta = staff_attendance_month_report_header_meta($reportMonth, $employeeNo, $employees);
$monthDisplay = $headerMeta['monthDisplay'];
$employeeFilterLabel = $headerMeta['employeeFilterLabel'];
$monthBase = $urls['month'];
$hasRows = !empty($grouped);
$hasRowsAll = $stateAll['grouped'] !== [];
$showMonthPdfCurrentBtn = $hasRows;
$showMonthPdfAllBtn = $hasRowsAll;
$pdfPayloadCurrent = json_encode(
    [
        'reportMonth' => $reportMonth,
        'monthDisplay' => $monthDisplay,
        'sections' => $pdfSectionsCurrent,
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);
$pdfPayloadAll = json_encode(
    [
        'reportMonth' => $reportMonth,
        'monthDisplay' => $monthDisplay,
        'sections' => $pdfSectionsAll,
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);

require __DIR__ . '/includes/header.php';
?>
<div class="staff-device-page">
<?php include __DIR__ . '/partials/staff_device_embed_styles.php'; ?>
<div class="row g-3 g-lg-4">
    <div class="col-12 col-md-3 col-lg-2">
        <?php include __DIR__ . '/partials/staff_device_nav.php'; ?>
    </div>
    <div class="col-12 col-md-9 col-lg-10 min-w-0">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-2 gap-md-3">
                <h5 class="mb-0 fw-bold text-break">Staff Attendance Summary</h5>
                <?php if ($showMonthPdfCurrentBtn || $showMonthPdfAllBtn): ?>
                <div class="d-flex flex-wrap gap-2 staff-device-card-header-actions">
                    <?php if ($showMonthPdfCurrentBtn): ?>
                    <button type="button" class="btn btn-light btn-sm" id="staffMonthReportPdfBtn" title="A4 portrait, one page per employee for this view">
                        <i class="fas fa-file-pdf me-1"></i>Download PDF
                    </button>
                    <?php endif; ?>
                    <?php if ($showMonthPdfAllBtn): ?>
                    <button type="button" class="btn btn-outline-light btn-sm" id="staffMonthReportPdfAllBtn" title="All employees for this month, one page per employee">
                        <i class="fas fa-file-download me-1"></i>All employees (PDF)
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end mb-4 staff-device-filter-form" action="<?php echo attendance_escape($monthBase); ?>">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label class="form-label small mb-0 text-body-secondary" for="staffMonthReportMonthStandalone">Month</label>
                        <input type="month" id="staffMonthReportMonthStandalone" name="report_month" class="form-control" value="<?php echo attendance_escape($reportMonth); ?>">
                    </div>
                    <div class="col-12 col-md min-w-0">
                        <label class="form-label small mb-0 text-body-secondary" for="staffMonthReportEmployeeStandalone">Employee</label>
                        <div class="staff-device-ts-wrap">
                        <select id="staffMonthReportEmployeeStandalone" name="employee_no" class="form-select js-employee-select-search" aria-label="Employee">
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
                    </div>
                    <div class="col-12 col-sm-6 col-xl-auto d-grid d-sm-block align-self-end">
                        <button type="submit" class="btn btn-primary">Show</button>
                    </div>
                </form>

                <div class="text-center border rounded bg-light py-3 px-2 mb-4">
                    <div class="fw-semibold small text-secondary">Sri Lanka German Training Institute</div>
                    <div class="h5 mb-1 fw-bold">Staff Attendance Summary</div>
                    <?php if ($monthDisplay !== ''): ?>
                        <div class="text-muted"><?php echo attendance_escape($monthDisplay); ?></div>
                    <?php endif; ?>
                    <div class="text-muted small">Employee: <?php echo attendance_escape($employeeFilterLabel); ?></div>
                </div>

                <p class="text-muted small mb-3">
                    One row per day. Employee name and number appear in the header or in section headings when viewing all staff.
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
                            <th>Date</th>
                            <th>Day</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Other</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$grouped): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No attendance in this month<?php echo $employeeNo !== '' ? ' for this employee' : ''; ?>.</td></tr>
                        <?php else: ?>
                            <?php
                            $prevEmp = null;
                            foreach ($groupedSorted as $r):
                                $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
                                $d = (string) $r['d'];
                                $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
                                $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';
                                $enoRow = (string) ($r['employee_no'] ?? '');
                                $snRow = trim((string) ($r['staff_name'] ?? ''));
                                $empHeading = $snRow !== '' ? $snRow . ' (' . $enoRow . ')' : $enoRow;
                                if ($employeeNo === '' && $prevEmp !== $enoRow):
                                    $prevEmp = $enoRow;
                            ?>
                            <tr class="table-secondary">
                                <td colspan="5" class="fw-semibold small py-2"><?php echo attendance_escape($empHeading); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
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
</div>
<?php
if ($showMonthPdfCurrentBtn || $showMonthPdfAllBtn) {
    include __DIR__ . '/partials/month_report_pdf_scripts.php';
}
include __DIR__ . '/partials/employee_select_search_assets.php';
?>
<?php require __DIR__ . '/includes/footer.php'; ?>
