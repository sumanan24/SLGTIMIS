<?php
$groupPayments = $group['payments'] ?? [];
if (!isset($paymentOfficer) || !is_callable($paymentOfficer)) {
    $paymentOfficer = static function ($payment) {
        $stored = trim((string) ($payment['collected_by'] ?? ''));
        if ($stored === '') {
            $stored = trim((string) ($payment['approved_by'] ?? ''));
        }
        return $stored !== '' ? $stored : '—';
    };
}
$colsBeforeAmount = 3; // Date, Receipt, Method always shown before qty/amount; student/type/subtype/dept optional
if ($basis !== 'student') {
    $colsBeforeAmount++;
}
if ($basis !== 'type') {
    $colsBeforeAmount++;
}
if ($basis !== 'subtype') {
    $colsBeforeAmount++;
}
if ($basis !== 'department') {
    $colsBeforeAmount++;
}
$colsBeforeAmount++; // Officer
$colsBeforeAmount++; // Qty
?>
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0 pay-table">
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
                <th>Officer</th>
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
                    <td class="num"><?php echo !empty($payment['pays_date']) ? date('d M Y', strtotime($payment['pays_date'])) : '—'; ?></td>
                    <td class="num">#<?php echo (int) $payment['pays_id']; ?></td>
                    <?php if ($basis !== 'student'): ?>
                        <td>
                            <div><?php echo htmlspecialchars($payment['student_reg_no'] ?? $payment['student_id'] ?? '—'); ?></div>
                            <?php if (!empty($payment['student_fullname'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($payment['student_fullname']); ?></small>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($basis !== 'type'): ?>
                        <td><?php echo htmlspecialchars($payment['payment_type'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <?php if ($basis !== 'subtype'): ?>
                        <td><?php echo htmlspecialchars($payment['payment_reason'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <?php if ($basis !== 'department'): ?>
                        <td><?php echo htmlspecialchars($payment['pays_department_name'] ?? $payment['department_name'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <td class="num"><?php
                        $methodCode = strtoupper(trim((string) ($payment['payment_method'] ?? '')));
                        $methodLabels = ['CASH' => 'Cash', 'BANK' => 'Bank', 'ONLINE' => 'Online transfer'];
                        echo htmlspecialchars($methodLabels[$methodCode] ?? (($payment['payment_method'] ?? '') !== '' ? $payment['payment_method'] : '—'));
                    ?></td>
                    <td><?php echo htmlspecialchars($paymentOfficer($payment)); ?></td>
                    <td class="text-end num"><?php echo $qty; ?></td>
                    <td class="text-end num">Rs. <?php echo number_format($rowTotal, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-light">
                <td colspan="<?php echo $colsBeforeAmount; ?>" class="text-end fw-bold">Section total</td>
                <td class="text-end num fw-bold">Rs. <?php echo number_format((float) ($group['total'] ?? 0), 2); ?></td>
            </tr>
        </tbody>
    </table>
</div>
