<?php
$groupPayments = $group['payments'] ?? [];
$reportColumns = 5;
if ($basis !== 'student') {
    $reportColumns++;
}
if ($basis !== 'type') {
    $reportColumns++;
}
if ($basis !== 'subtype') {
    $reportColumns++;
}
if ($basis !== 'department') {
    $reportColumns++;
}
?>
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Date</th>
                <th>Receipt</th>
                <?php if ($basis !== 'student'): ?>
                    <th>Student</th>
                <?php endif; ?>
                <?php if ($basis !== 'type'): ?>
                    <th>Type</th>
                <?php endif; ?>
                <?php if ($basis !== 'subtype'): ?>
                    <th>Sub type</th>
                <?php endif; ?>
                <?php if ($basis !== 'department'): ?>
                    <th>Department</th>
                <?php endif; ?>
                <th>Method</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupPayments as $payment): ?>
                <?php
                $qty = max(1, (int) ($payment['pays_qty'] ?? 1));
                $rowTotal = $lineTotal($payment);
                ?>
                <tr>
                    <td><?php echo !empty($payment['pays_date']) ? date('d M Y', strtotime($payment['pays_date'])) : '-'; ?></td>
                    <td>#<?php echo (int) $payment['pays_id']; ?></td>
                    <?php if ($basis !== 'student'): ?>
                        <td>
                            <div><?php echo htmlspecialchars($payment['student_reg_no'] ?? $payment['student_id'] ?? '-'); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($payment['student_fullname'] ?? ''); ?></small>
                        </td>
                    <?php endif; ?>
                    <?php if ($basis !== 'type'): ?>
                        <td><?php echo htmlspecialchars($payment['payment_type'] ?? '-'); ?></td>
                    <?php endif; ?>
                    <?php if ($basis !== 'subtype'): ?>
                        <td><?php echo htmlspecialchars($payment['payment_reason'] ?? '-'); ?></td>
                    <?php endif; ?>
                    <?php if ($basis !== 'department'): ?>
                        <td><?php echo htmlspecialchars($payment['pays_department_name'] ?? $payment['department_name'] ?? '-'); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($payment['payment_method'] ?? '-'); ?></td>
                    <td class="text-end"><?php echo $qty; ?></td>
                    <td class="text-end">Rs. <?php echo number_format($rowTotal, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-light">
                <td colspan="<?php echo $reportColumns - 1; ?>" class="text-end fw-bold">Section total</td>
                <td class="text-end fw-bold">Rs. <?php echo number_format((float) ($group['total'] ?? 0), 2); ?></td>
            </tr>
        </tbody>
    </table>
</div>
