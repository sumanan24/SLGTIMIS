<?php
$filters = $filters ?? [];
if (!in_array($basis, ['type', 'subtype', 'student', 'department'], true)) {
    $basis = 'type';
}
$printMode = !empty($printMode);
$summary = $summary ?? ['count' => 0, 'students' => 0, 'amount' => 0, 'types' => [], 'subtypes' => []];
$groups = $groups ?? [];
$paymentSubtypes = $paymentSubtypes ?? [];
$academicYears = $academicYears ?? [];
$departments = $departments ?? [];
$departmentLabel = '';
foreach ($departments as $departmentOption) {
    if (($filters['department'] ?? '') !== '' && ($filters['department'] ?? '') === ($departmentOption['department_id'] ?? '')) {
        $departmentLabel = $departmentOption['department_name'] ?? $departmentOption['department_id'];
        break;
    }
}
$headingTitle = 'Payments Report';
if ($basis === 'department') {
    $headingTitle = 'Department-wise Payments Report';
} elseif ($basis === 'student') {
    $headingTitle = 'Student-wise Payments Report';
} elseif ($basis === 'subtype') {
    $headingTitle = 'Sub Type-wise Payments Report';
}
$headingLines = [];
if (($filters['payment_subtype'] ?? '') !== '') {
    $subjectLine = $filters['payment_subtype'];
    if (($filters['payment_type'] ?? '') !== '') {
        $subjectLine .= ' — ' . $filters['payment_type'];
    }
    $headingLines[] = $subjectLine;
} elseif (($filters['payment_type'] ?? '') !== '') {
    $headingLines[] = $filters['payment_type'];
}
if (($filters['academic_year'] ?? '') !== '') {
    $headingLines[] = 'Academic Year ' . $filters['academic_year'];
}
if ($departmentLabel !== '') {
    $headingLines[] = $departmentLabel;
}
if (($filters['student_id'] ?? '') !== '') {
    $headingLines[] = 'Student ' . $filters['student_id'];
}
if (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '') {
    $fromLabel = ($filters['date_from'] ?? '') !== '' ? date('d F Y', strtotime($filters['date_from'])) : 'Start';
    $toLabel = ($filters['date_to'] ?? '') !== '' ? date('d F Y', strtotime($filters['date_to'])) : 'Today';
    $headingLines[] = $fromLabel . ' to ' . $toLabel;
}
$queryBase = $filters;
$printQuery = $queryBase;
$printQuery['format'] = 'print';
$csvQuery = $queryBase;
$csvQuery['format'] = 'csv';

$lineTotal = function ($payment) {
    $qty = max(1, (int) ($payment['pays_qty'] ?? 1));
    return (float) ($payment['pays_amount'] ?? 0) * $qty;
};
?>
<style>
.pay-report-sheet {
    background: #fff;
}
.pay-report-block {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.pay-report-block h6 {
    margin: 0;
}
.pay-report-heading {
    text-align: center;
    color: #111;
    margin-bottom: 1.5rem;
}
.pay-report-inst {
    font-size: 1.2rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}
.pay-report-addr {
    font-size: 0.92rem;
}
.pay-report-rule {
    border: 0;
    border-top: 2px solid #0d2148;
    margin: 0.65rem auto 0.75rem;
    max-width: 520px;
}
.pay-report-title {
    font-size: 1.45rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.3;
}
.pay-report-line {
    margin-top: 0.15rem;
}
@media print {
    .no-print { display: none !important; }
    .pay-report-sheet { box-shadow: none !important; border: 0 !important; }
    .pay-report-block {
        break-inside: avoid;
        page-break-inside: avoid;
        border-color: #000;
    }
    .pay-report-block + .pay-report-block {
        break-before: page;
        page-break-before: always;
    }
    a { text-decoration: none; color: inherit; }
}
</style>

<div class="container-fluid px-4 py-3 pay-report-page">
    <div class="card shadow-sm border-0 pay-report-sheet">
        <div class="card-header bg-primary text-white no-print">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2"></i>Payments Report</h5>
                <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Payments
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger no-print"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="GET" action="<?php echo APP_URL; ?>/payments/report" class="mb-4 no-print">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label for="date_from" class="form-label fw-semibold">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                               value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label fw-semibold">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                               value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="payment_type" class="form-label fw-semibold">Payment Type</label>
                        <select class="form-select" id="payment_type" name="payment_type">
                            <option value="">All Types</option>
                            <?php foreach ($paymentTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo ($filters['payment_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="payment_subtype" class="form-label fw-semibold">Sub type</label>
                        <select class="form-select" id="payment_subtype" name="payment_subtype">
                            <option value="">All Sub types</option>
                            <?php foreach ($paymentSubtypes as $subtype): ?>
                                <option value="<?php echo htmlspecialchars($subtype['payment_reason']); ?>"
                                    data-type="<?php echo htmlspecialchars($subtype['payment_type']); ?>"
                                    <?php echo ($filters['payment_subtype'] ?? '') === $subtype['payment_reason'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subtype['payment_reason'] . ' (' . $subtype['payment_type'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="academic_year" class="form-label fw-semibold">Academic Year</label>
                        <select class="form-select" id="academic_year" name="academic_year">
                            <option value="">All Years</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"
                                    <?php echo ($filters['academic_year'] ?? '') === $year ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="department" class="form-label fw-semibold">Department</label>
                        <select class="form-select" id="department" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $departmentOption): ?>
                                <option value="<?php echo htmlspecialchars($departmentOption['department_id']); ?>"
                                    <?php echo ($filters['department'] ?? '') === $departmentOption['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($departmentOption['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="student_id" class="form-label fw-semibold">Student</label>
                        <select class="form-select" id="student_id" name="student_id">
                            <option value="">All Students</option>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo htmlspecialchars($student['student_id']); ?>"
                                        <?php echo ($filters['student_id'] ?? '') == $student['student_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['student_fullname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Report</label>
                        <div class="d-flex flex-wrap gap-3 pt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="basis" id="basis_type" value="type"
                                    <?php echo $basis === 'type' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="basis_type">By type</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="basis" id="basis_subtype" value="subtype"
                                    <?php echo $basis === 'subtype' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="basis_subtype">By sub type</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="basis" id="basis_student" value="student"
                                    <?php echo $basis === 'student' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="basis_student">By student (total paid)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="basis" id="basis_department" value="department"
                                    <?php echo $basis === 'department' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="basis_department">By department</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Show Report
                    </button>
                    <a class="btn btn-outline-secondary" target="_blank" rel="noopener"
                       href="<?php echo APP_URL; ?>/payments/report?<?php echo htmlspecialchars(http_build_query($printQuery)); ?>">
                        <i class="fas fa-print me-1"></i>Print
                    </a>
                    <a class="btn btn-outline-success"
                       href="<?php echo APP_URL; ?>/payments/report?<?php echo htmlspecialchars(http_build_query($csvQuery)); ?>">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/payments/report">
                        <i class="fas fa-redo me-1"></i>Reset
                    </a>
                </div>
            </form>

            <header class="pay-report-heading">
                <div class="pay-report-inst">Sri Lanka German Training Institute</div>
                <div class="pay-report-addr">Ariviyal Nagar, Kilinochchi</div>
                <hr class="pay-report-rule">
                <h2 class="pay-report-title"><?php echo htmlspecialchars($headingTitle); ?></h2>
                <?php foreach ($headingLines as $headingLine): ?>
                    <div class="pay-report-line"><?php echo htmlspecialchars($headingLine); ?></div>
                <?php endforeach; ?>
            </header>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Payments</div>
                        <div class="fs-4 fw-bold"><?php echo (int) $summary['count']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Students</div>
                        <div class="fs-4 fw-bold"><?php echo (int) $summary['students']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total amount</div>
                        <div class="fs-4 fw-bold text-success">Rs. <?php echo number_format((float) $summary['amount'], 2); ?></div>
                    </div>
                </div>
            </div>

            <?php if ($basis === 'department' && !empty($summary['departments'])): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Students</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['departments'] as $departmentRow): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(trim(($departmentRow['code'] ?? '') . ' ' . ($departmentRow['name'] ?? ''))); ?></td>
                                    <td class="text-end"><?php echo (int) $departmentRow['count']; ?></td>
                                    <td class="text-end"><?php echo (int) $departmentRow['students']; ?></td>
                                    <td class="text-end">Rs. <?php echo number_format((float) $departmentRow['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($basis === 'subtype' && !empty($summary['subtypes'])): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment Type</th>
                                <th>Sub type</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['subtypes'] as $subtypeRow): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subtypeRow['type']); ?></td>
                                    <td><?php echo htmlspecialchars($subtypeRow['subtype']); ?></td>
                                    <td class="text-end"><?php echo (int) $subtypeRow['count']; ?></td>
                                    <td class="text-end">Rs. <?php echo number_format((float) $subtypeRow['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($basis === 'type' && !empty($summary['types'])): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment Type</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['types'] as $typeName => $typeRow): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($typeName); ?></td>
                                    <td class="text-end"><?php echo (int) $typeRow['count']; ?></td>
                                    <td class="text-end">Rs. <?php echo number_format((float) $typeRow['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (empty($groups)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                    <p class="mb-0">No payments found for this date range and filters.</p>
                </div>
            <?php elseif ($basis === 'student'): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Student ID</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Total paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $studentDepartment = null;
                            $studentDepartmentLabel = '';
                            $studentDepartmentPayments = 0;
                            $studentDepartmentTotal = 0.0;
                            $studentGrandPayments = 0;
                            $studentGrandTotal = 0.0;
                            $writeStudentDepartmentTotal = function () use (&$studentDepartmentLabel, &$studentDepartmentPayments, &$studentDepartmentTotal) {
                                ?>
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-bold"><?php echo htmlspecialchars($studentDepartmentLabel); ?> total</td>
                                    <td class="text-end fw-bold"><?php echo (int) $studentDepartmentPayments; ?></td>
                                    <td class="text-end fw-bold">Rs. <?php echo number_format($studentDepartmentTotal, 2); ?></td>
                                </tr>
                                <?php
                            };
                            foreach ($groups as $group):
                                $departmentKey = (string) ($group['department_code'] ?? '');
                                $departmentText = trim($departmentKey . ' ' . ($group['department_name'] ?? ''));
                                if ($departmentText === '') {
                                    $departmentText = 'Unspecified';
                                }
                                if ($studentDepartment !== null && $departmentKey !== $studentDepartment) {
                                    $writeStudentDepartmentTotal();
                                    $studentDepartmentPayments = 0;
                                    $studentDepartmentTotal = 0.0;
                                }
                                $studentDepartment = $departmentKey;
                                $studentDepartmentLabel = $departmentText;
                                $paymentCount = count($group['payments'] ?? []);
                                $studentDepartmentPayments += $paymentCount;
                                $studentDepartmentTotal += (float) $group['total'];
                                $studentGrandPayments += $paymentCount;
                                $studentGrandTotal += (float) $group['total'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($departmentText); ?></td>
                                    <td><?php echo htmlspecialchars($group['student_reg_no'] ?: $group['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($group['student_fullname'] !== '' ? $group['student_fullname'] : 'Unknown student'); ?></td>
                                    <td><?php echo htmlspecialchars($group['course_name'] ?? ''); ?></td>
                                    <td class="text-end"><?php echo $paymentCount; ?></td>
                                    <td class="text-end">Rs. <?php echo number_format((float) $group['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($studentDepartment !== null) { $writeStudentDepartmentTotal(); } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Grand total</th>
                                <th class="text-end"><?php echo (int) $studentGrandPayments; ?></th>
                                <th class="text-end">Rs. <?php echo number_format($studentGrandTotal, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <section class="pay-report-block">
                        <div class="bg-light px-3 py-2 d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold">
                                    <?php if ($basis === 'department' && !empty($group['code']) && $group['code'] !== $group['title']): ?>
                                        <?php echo htmlspecialchars($group['code']); ?> —
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($group['title']); ?>
                                </h6>
                                <?php if ($basis === 'subtype' && !empty($group['type'])): ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($group['type']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="fw-bold text-success">
                                <?php echo count($group['payments']); ?> payment<?php echo count($group['payments']) === 1 ? '' : 's'; ?>
                                · Rs. <?php echo number_format((float) $group['total'], 2); ?>
                            </div>
                        </div>
                        <?php include BASE_PATH . '/views/payments/_report_lines.php'; ?>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($groups)): ?>
                <div class="d-flex justify-content-end">
                    <div class="fw-bold">Grand total: <span class="text-success">Rs. <?php echo number_format((float) $summary['amount'], 2); ?></span></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.getElementById('payment_type');
    var subtypeSelect = document.getElementById('payment_subtype');
    if (!typeSelect || !subtypeSelect) {
        return;
    }
    function filterSubtypes() {
        var type = typeSelect.value;
        Array.prototype.forEach.call(subtypeSelect.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            var match = !type || option.getAttribute('data-type') === type;
            option.hidden = !match;
            option.disabled = !match;
        });
        if (subtypeSelect.selectedOptions.length && subtypeSelect.selectedOptions[0].disabled) {
            subtypeSelect.value = '';
        }
    }
    typeSelect.addEventListener('change', filterSubtypes);
    filterSubtypes();
});
</script>
<?php if ($printMode): ?>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
