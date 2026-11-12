<?php
$p = $payment;
$receiptNo = (int)($p['pays_id'] ?? 0);
$qty = max(1, (int)($p['pays_qty'] ?? 1));
$unitAmount = (float)($p['pays_amount'] ?? 0);
$lineTotal = $lineTotal ?? ($unitAmount * $qty);
$payDate = !empty($p['pays_date']) ? date('d/m/Y', strtotime($p['pays_date'])) : '—';
$studentId = $p['student_id'] ?? $p['student_reg_no'] ?? '—';
$studentName = $p['student_fullname'] ?? '—';
$department = $p['department_name'] ?? '';
$paymentType = $p['payment_type'] ?? '—';
$paymentReason = $p['payment_reason'] ?? '—';
$paymentMethod = trim($p['payment_method'] ?? '');
$notes = trim($p['pays_note'] ?? '');
$cashierName = trim((string) ($cashierName ?? ''));
if ($cashierName === '') {
    $cashierName = trim((string) ($payment['collected_by'] ?? ''));
}
if ($cashierName === '') {
    $cashierName = 'Staff';
}
$receiptPayments = (isset($payments) && is_array($payments) && count($payments) > 0) ? $payments : [$p];
$referenceNos = [];
foreach ($receiptPayments as $row) {
    $ref = trim((string) ($row['reference_no'] ?? ''));
    if ($ref !== '' && !in_array($ref, $referenceNos, true)) {
        $referenceNos[] = $ref;
    }
}
$referenceNo = implode(', ', $referenceNos);
$receiptNos = array_map(function ($row) {
    return str_pad((string) (int) ($row['pays_id'] ?? 0), 6, '0', STR_PAD_LEFT);
}, $receiptPayments);
$receiptLabel = count($receiptNos) === 1 ? $receiptNos[0] : $receiptNos[0] . '–' . $receiptNos[count($receiptNos) - 1];
$grandTotal = isset($lineTotal) ? (float) $lineTotal : ($unitAmount * $qty);
?>
<style>
.pos-receipt-page {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pos-receipt-toolbar {
    width: 100%;
    max-width: 80mm;
    margin-bottom: 1rem;
}

.pos-receipt {
    width: 80mm;
    max-width: 80mm;
    margin: 0 auto;
    padding: 4mm 4mm 5mm;
    background: #fff;
    color: #000;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.35;
    box-sizing: border-box;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.pos-receipt,
.pos-receipt * {
    color: #000;
    font-weight: 700;
}

.pos-receipt .pos-center {
    text-align: center;
}

.pos-receipt .pos-title {
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 0.08em;
    margin: 0;
}

.pos-receipt .pos-sub,
.pos-receipt .pos-addr {
    font-size: 10px;
    font-weight: 700;
    margin: 1px 0 0;
    line-height: 1.3;
}

.pos-receipt .pos-heading {
    display: inline-block;
    font-size: 12px;
    font-weight: 900;
    margin: 8px 0 0;
    padding: 2px 8px;
    letter-spacing: 0.14em;
    border: 2px solid #000;
}

.pos-receipt .pos-rule {
    border: none;
    border-top: 2px solid #000;
    margin: 7px 0;
}

.pos-receipt .pos-rule-light {
    border: none;
    border-top: 1px solid #000;
    margin: 5px 0;
}

.pos-receipt .pos-sec {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin: 0 0 3px;
}

.pos-receipt .pos-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    margin: 2px 0;
    word-break: break-word;
}

.pos-receipt .pos-row .pos-label {
    flex-shrink: 0;
    max-width: 52%;
    font-weight: 700;
}

.pos-receipt .pos-row .pos-val {
    text-align: right;
    flex: 1;
    font-weight: 900;
}

.pos-receipt .pos-cols {
    margin-bottom: 2px;
}

.pos-receipt .pos-cols .pos-label,
.pos-receipt .pos-cols .pos-val {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.pos-receipt .pos-name {
    display: block;
    font-size: 14px;
    font-weight: 900;
    line-height: 1.25;
    margin: 0 0 3px;
}

.pos-receipt .pos-amt {
    font-size: 13px;
    font-weight: 900;
    white-space: nowrap;
}

.pos-receipt .pos-block {
    margin: 3px 0;
}

.pos-receipt .pos-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 4px 0 0;
    background: transparent;
    color: #000;
}

.pos-receipt .pos-total,
.pos-receipt .pos-total * {
    color: #000;
    font-weight: 900;
    background: transparent;
}

.pos-receipt .pos-total .pos-total-label {
    font-size: 11px;
    letter-spacing: 0.08em;
}

.pos-receipt .pos-total .pos-total-amt {
    font-size: 16px;
    letter-spacing: 0.02em;
    text-align: right;
}

.pos-receipt .pos-total-sub {
    text-align: right;
    font-size: 11px;
    font-weight: 700;
    margin-top: 3px;
}

.pos-receipt .pos-officer {
    text-align: center;
    margin-top: 12px;
}

.pos-receipt .pos-officer-line {
    width: 70%;
    margin: 0 auto 4px;
    border-top: 1px solid #000;
}

.pos-receipt .pos-officer-role {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.04em;
}

.pos-receipt .pos-officer-name {
    font-size: 12px;
    font-weight: 700;
    margin-top: 1px;
}

.pos-receipt .pos-footer {
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    margin-top: 10px;
    line-height: 1.4;
}

@media screen {
    .pos-receipt {
        border: 1px solid #ccc;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
}

@media print {
    @page {
        size: 80mm auto;
        margin: 2mm;
    }

    html, body {
        width: 80mm;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    body.print-layout-body {
        background: #fff !important;
    }

    .pos-receipt-page {
        padding: 0;
    }

    .pos-receipt-toolbar,
    .no-print {
        display: none !important;
    }

    .pos-receipt {
        width: 76mm;
        max-width: 76mm;
        border: none;
        box-shadow: none;
        padding: 0;
        color: #000 !important;
        font-weight: 700 !important;
    }

    .pos-receipt .pos-total,
    .pos-receipt .pos-total * {
        background: transparent !important;
        color: #000 !important;
    }
}
</style>

<div class="pos-receipt-page">
    <div class="pos-receipt-toolbar no-print d-flex flex-wrap gap-2 justify-content-center">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success py-2 px-3 mb-0 w-100 small text-center">
                <i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print();">
            <i class="fas fa-print me-1"></i>Print
        </button>
        <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-plus me-1"></i>New
        </a>
        <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-list me-1"></i>Back
        </a>
    </div>

    <div class="pos-receipt" id="posReceipt">
        <div class="pos-center">
            <p class="pos-title">SLGTI</p>
            <p class="pos-sub">Sri Lanka German Training Institute</p>
            <p class="pos-addr">Ariviyal Nagar, Kilinochchi</p>
            <p class="pos-heading">PAYMENT BILL</p>
        </div>

        <hr class="pos-rule">

        <div class="pos-row">
            <span class="pos-label">Bill No.</span>
            <span class="pos-val"><?php echo htmlspecialchars($receiptLabel); ?></span>
        </div>
        <div class="pos-row">
            <span class="pos-label">Date</span>
            <span class="pos-val"><?php echo htmlspecialchars($payDate); ?></span>
        </div>

        <hr class="pos-rule">

        <div class="pos-sec">Billed to</div>
        <span class="pos-name"><?php echo htmlspecialchars($studentName); ?></span>
        <div class="pos-row">
            <span class="pos-label">Student ID</span>
            <span class="pos-val"><?php echo htmlspecialchars($studentId); ?></span>
        </div>
        <?php if ($department !== ''): ?>
        <div class="pos-row">
            <span class="pos-label">Department</span>
            <span class="pos-val"><?php echo htmlspecialchars($department); ?></span>
        </div>
        <?php endif; ?>

        <hr class="pos-rule">

        <div class="pos-sec">Particulars</div>
        <div class="pos-row pos-cols">
            <span class="pos-label">Description</span>
            <span class="pos-val">Amount</span>
        </div>
        <hr class="pos-rule-light">
        <?php foreach ($receiptPayments as $index => $item): ?>
            <?php
            $itemQty = max(1, (int) ($item['pays_qty'] ?? 1));
            $itemUnit = (float) ($item['pays_amount'] ?? 0);
            $itemTotal = $itemUnit * $itemQty;
            $subType = trim((string) ($item['payment_reason'] ?? ''));
            if ($subType === '') {
                $subType = '—';
            }
            if (count($receiptPayments) > 1) {
                $subType = ((int) $index + 1) . '. ' . $subType;
            }
            ?>
            <div class="pos-row">
                <span class="pos-label"><?php echo htmlspecialchars($subType); ?></span>
                <span class="pos-val pos-amt">Rs.<?php echo number_format($itemTotal, 2); ?></span>
            </div>
            <?php if ($itemQty > 1): ?>
            <div class="pos-row">
                <span class="pos-label"><?php echo $itemQty; ?> x Rs.<?php echo number_format($itemUnit, 2); ?></span>
                <span class="pos-val"></span>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($paymentMethod !== ''): ?>
        <div class="pos-row">
            <span class="pos-label">Payment method</span>
            <span class="pos-val"><?php echo htmlspecialchars($paymentMethod); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($referenceNo !== ''): ?>
        <div class="pos-row">
            <span class="pos-label">Reference</span>
            <span class="pos-val"><?php echo htmlspecialchars($referenceNo); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($notes !== ''): ?>
        <div class="pos-block" style="margin-top: 4px;">
            <div>Notes</div>
            <div><?php echo htmlspecialchars($notes); ?></div>
        </div>
        <?php endif; ?>

        <hr class="pos-rule">

        <div class="pos-total">
            <div class="pos-total-label">TOTAL PAID</div>
            <div class="pos-total-amt">Rs.<?php echo number_format($grandTotal, 2); ?></div>
        </div>
        <?php if (count($receiptPayments) === 1 && $qty > 1): ?>
            <div class="pos-total-sub"><?php echo $qty; ?> x Rs.<?php echo number_format($unitAmount, 2); ?></div>
        <?php elseif (count($receiptPayments) > 1): ?>
            <div class="pos-total-sub"><?php echo count($receiptPayments); ?> items</div>
        <?php endif; ?>

        <div class="pos-officer">
            <div class="pos-officer-line"></div>
            <div class="pos-officer-role">Finance Officer</div>
            <div class="pos-officer-name"><?php echo htmlspecialchars($cashierName); ?></div>
        </div>

        <div class="pos-footer">
            <div>Thank you for your payment.</div>
            <div>This is a computer-generated bill.</div>
        </div>
    </div>
</div>

<?php if (!empty($autoPrint)): ?>
<script>
(function () {
    function triggerPrint() {
        window.print();
    }
    if (document.readyState === 'complete') {
        setTimeout(triggerPrint, 350);
    } else {
        window.addEventListener('load', function () {
            setTimeout(triggerPrint, 350);
        });
    }
})();
</script>
<?php endif; ?>
