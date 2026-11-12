<?php
$filters = $filters ?? [];
$basis = in_array($basis ?? '', ['type', 'subtype', 'student', 'department'], true) ? $basis : 'type';
$printMode = !empty($printMode);
$summary = $summary ?? ['count' => 0, 'students' => 0, 'amount' => 0, 'types' => [], 'subtypes' => [], 'departments' => []];
$groups = $groups ?? [];
$paymentTypes = $paymentTypes ?? [];
$paymentSubtypes = $paymentSubtypes ?? [];
$academicYears = $academicYears ?? [];
$departments = $departments ?? [];
$students = $students ?? [];

$departmentLabel = '';
foreach ($departments as $departmentOption) {
    if (($filters['department'] ?? '') !== '' && ($filters['department'] ?? '') === ($departmentOption['department_id'] ?? '')) {
        $departmentLabel = $departmentOption['department_name'] ?? $departmentOption['department_id'];
        break;
    }
}

$basisTitles = [
    'type' => 'By payment type',
    'subtype' => 'By sub type',
    'student' => 'By student',
    'department' => 'By department',
];
$headingTitle = $basisTitles[$basis] ?? 'Payments Report';

$metaBits = [];
if (($filters['payment_subtype'] ?? '') !== '') {
    $metaBits[] = $filters['payment_subtype'];
} elseif (($filters['payment_type'] ?? '') !== '') {
    $metaBits[] = $filters['payment_type'];
}
if (($filters['academic_year'] ?? '') !== '') {
    $metaBits[] = $filters['academic_year'];
}
if ($departmentLabel !== '') {
    $metaBits[] = $departmentLabel;
}
if (($filters['student_id'] ?? '') !== '') {
    $metaBits[] = $filters['student_id'];
}
if (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '') {
    $fromLabel = ($filters['date_from'] ?? '') !== '' ? date('d M Y', strtotime($filters['date_from'])) : '…';
    $toLabel = ($filters['date_to'] ?? '') !== '' ? date('d M Y', strtotime($filters['date_to'])) : '…';
    $metaBits[] = $fromLabel . ' – ' . $toLabel;
}

$queryBase = $filters;
$printQuery = $queryBase + ['format' => 'print'];
$csvQuery = $queryBase + ['format' => 'csv'];

$lineTotal = static function ($payment) {
    $qty = max(1, (int) ($payment['pays_qty'] ?? 1));
    return (float) ($payment['pays_amount'] ?? 0) * $qty;
};
?>
<style>
.pay-page { width: 100%; }
.pay-page .pay-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .75rem 1rem;
    align-items: end;
}
.pay-page .pay-basis {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem 1rem;
    align-items: center;
}
.pay-page .pay-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
}
.pay-page .pay-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .75rem;
}
.pay-page .pay-stat {
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    padding: .75rem;
    text-align: center;
    background: #fff;
}
.pay-page .pay-stat .lbl { color: #6c757d; font-size: .75rem; margin-bottom: .2rem; }
.pay-page .pay-stat .val { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
.pay-page .pay-table-wrap {
    overflow: auto;
    border: 1px solid #dee2e6;
    border-radius: .5rem;
}
.pay-page .pay-table {
    width: 100%;
    margin: 0;
    font-size: .875rem;
}
.pay-page .pay-table th,
.pay-page .pay-table td {
    vertical-align: middle;
    padding: .45rem .55rem;
    white-space: nowrap;
}
.pay-page .pay-table thead th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom-width: 1px;
}
.pay-page .pay-block {
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    overflow: hidden;
    margin-bottom: 1rem;
}
.pay-page .pay-block-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .65rem .85rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.pay-page .pay-print-head { display: none; text-align: center; margin-bottom: 1rem; }
@media (max-width: 767.98px) {
    .pay-page .pay-stats { grid-template-columns: 1fr; }
}
@media print {
    .no-print { display: none !important; }
    .pay-page .pay-print-head { display: block; }
    .pay-page .card, .pay-page .card-body { border: 0 !important; box-shadow: none !important; }
    .pay-page .pay-block { break-inside: avoid; page-break-inside: avoid; border-color: #000; }
    .pay-page .pay-block + .pay-block { break-before: page; page-break-before: always; }
    a { text-decoration: none; color: inherit; }
}
</style>

<div class="container-fluid px-3 px-lg-4 py-3 pay-page">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom no-print">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Payments Report</h5>
                    <div class="text-muted small mt-1"><?php echo htmlspecialchars($headingTitle); ?></div>
                </div>
                <div class="pay-actions">
                    <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"
                       href="<?php echo APP_URL; ?>/payments/report?<?php echo htmlspecialchars(http_build_query($printQuery)); ?>">
                        <i class="fas fa-print me-1"></i>Print
                    </a>
                    <a class="btn btn-outline-success btn-sm"
                       href="<?php echo APP_URL; ?>/payments/report?<?php echo htmlspecialchars(http_build_query($csvQuery)); ?>">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/payments">
                        <i class="fas fa-arrow-left me-1"></i>Payments
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 no-print"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="GET" action="<?php echo APP_URL; ?>/payments/report" class="mb-3 no-print">
                <div class="pay-filters">
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="date_from">From</label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from"
                               value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="date_to">To</label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to"
                               value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="payment_type">Type</label>
                        <select class="form-select form-select-sm" id="payment_type" name="payment_type">
                            <option value="">All</option>
                            <?php foreach ($paymentTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo ($filters['payment_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="payment_subtype">Sub type</label>
                        <select class="form-select form-select-sm" id="payment_subtype" name="payment_subtype">
                            <option value="">All</option>
                            <?php foreach ($paymentSubtypes as $subtype): ?>
                                <option value="<?php echo htmlspecialchars($subtype['payment_reason']); ?>"
                                    data-type="<?php echo htmlspecialchars($subtype['payment_type']); ?>"
                                    <?php echo ($filters['payment_subtype'] ?? '') === $subtype['payment_reason'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subtype['payment_reason']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="academic_year">Year</label>
                        <select class="form-select form-select-sm" id="academic_year" name="academic_year">
                            <option value="">All</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"
                                    <?php echo ($filters['academic_year'] ?? '') === $year ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="department">Department</label>
                        <select class="form-select form-select-sm" id="department" name="department">
                            <option value="">All</option>
                            <?php foreach ($departments as $departmentOption): ?>
                                <option value="<?php echo htmlspecialchars($departmentOption['department_id']); ?>"
                                    <?php echo ($filters['department'] ?? '') === $departmentOption['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($departmentOption['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1" for="student_id">Student</label>
                        <select class="form-select form-select-sm" id="student_id" name="student_id">
                            <option value="">All</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['student_id']); ?>"
                                    <?php echo ($filters['student_id'] ?? '') == $student['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['student_id'] . ' — ' . ($student['student_fullname'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <div class="pay-basis">
                        <span class="small text-muted fw-semibold me-1">Group:</span>
                        <?php foreach ($basisTitles as $value => $label): ?>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="basis" id="basis_<?php echo $value; ?>"
                                       value="<?php echo $value; ?>" <?php echo $basis === $value ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="basis_<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pay-actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i>Show
                        </button>
                        <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/payments/report">Clear</a>
                    </div>
                </div>
            </form>

            <div class="pay-print-head">
                <div class="fw-bold">Sri Lanka German Training Institute</div>
                <div class="small">Ariviyal Nagar, Kilinochchi</div>
                <div class="fw-bold mt-2"><?php echo htmlspecialchars($headingTitle); ?></div>
                <?php if ($metaBits !== []): ?>
                    <div class="small"><?php echo htmlspecialchars(implode(' · ', $metaBits)); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($metaBits !== []): ?>
                <div class="text-muted small mb-3 no-print"><?php echo htmlspecialchars(implode(' · ', $metaBits)); ?></div>
            <?php endif; ?>

            <div class="pay-stats mb-3">
                <div class="pay-stat">
                    <div class="lbl">Payments</div>
                    <div class="val"><?php echo (int) $summary['count']; ?></div>
                </div>
                <div class="pay-stat">
                    <div class="lbl">Students</div>
                    <div class="val"><?php echo (int) $summary['students']; ?></div>
                </div>
                <div class="pay-stat">
                    <div class="lbl">Total amount</div>
                    <div class="val text-success" style="font-size:1.1rem;">Rs. <?php echo number_format((float) $summary['amount'], 2); ?></div>
                </div>
            </div>

            <?php if ($basis === 'type' && !empty($summary['types'])): ?>
                <div class="pay-table-wrap mb-3">
                    <table class="table table-sm table-bordered pay-table mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
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
            <?php elseif ($basis === 'subtype' && !empty($summary['subtypes'])): ?>
                <div class="pay-table-wrap mb-3">
                    <table class="table table-sm table-bordered pay-table mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
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
            <?php elseif ($basis === 'department' && !empty($summary['departments'])): ?>
                <div class="pay-table-wrap mb-3">
                    <table class="table table-sm table-bordered pay-table mb-0">
                        <thead>
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

            <?php if (empty($groups)): ?>
                <div class="alert alert-light border text-center mb-0">No payments for this selection.</div>
            <?php elseif ($basis === 'student'): ?>
                <div class="pay-table-wrap">
                    <table class="table table-sm table-bordered pay-table mb-0">
                        <thead>
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
                            $writeStudentDepartmentTotal = static function () use (&$studentDepartmentLabel, &$studentDepartmentPayments, &$studentDepartmentTotal) {
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
                    <section class="pay-block">
                        <div class="pay-block-head">
                            <div>
                                <div class="fw-bold">
                                    <?php if ($basis === 'department' && !empty($group['code']) && $group['code'] !== $group['title']): ?>
                                        <?php echo htmlspecialchars($group['code']); ?> —
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($group['title']); ?>
                                </div>
                                <?php if ($basis === 'subtype' && !empty($group['type'])): ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($group['type']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="small fw-semibold text-success">
                                <?php echo count($group['payments']); ?> · Rs. <?php echo number_format((float) $group['total'], 2); ?>
                            </div>
                        </div>
                        <?php include BASE_PATH . '/views/payments/_report_lines.php'; ?>
                    </section>
                <?php endforeach; ?>
                <div class="text-end fw-bold">
                    Grand total: <span class="text-success">Rs. <?php echo number_format((float) $summary['amount'], 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.getElementById('payment_type');
    var subtypeSelect = document.getElementById('payment_subtype');
    if (!typeSelect || !subtypeSelect) return;
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
