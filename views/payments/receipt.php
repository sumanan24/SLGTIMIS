<?php
$p = $payment;
$receiptNo = (int)($p['pays_id'] ?? 0);
$qty = max(1, (int)($p['pays_qty'] ?? 1));
$unitAmount = (float)($p['pays_amount'] ?? 0);
$lineTotal = $lineTotal ?? ($unitAmount * $qty);
$payDate = !empty($p['pays_date']) ? date('d/m/Y', strtotime($p['pays_date'])) : '—';
$payTime = date('h:i A');
$studentId = $p['student_id'] ?? $p['student_reg_no'] ?? '—';
$studentName = $p['student_fullname'] ?? '—';
$department = $p['department_name'] ?? '';
$paymentType = $p['payment_type'] ?? '—';
$paymentReason = $p['payment_reason'] ?? '—';
$paymentMethod = trim($p['payment_method'] ?? '');
$referenceNo = trim($p['reference_no'] ?? '');
$notes = trim($p['pays_note'] ?? '');
$cashierName = $cashierName ?? ($_SESSION['user_name'] ?? 'Staff');
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
    max-width: 320px;
    margin-bottom: 1rem;
}

.pos-receipt {
    width: 80mm;
    max-width: 80mm;
    margin: 0 auto;
    padding: 3mm 4mm 4mm;
    background: #fff;
    color: #000;
    font-family: "Courier New", Courier, monospace;
    font-size: 11px;
    line-height: 1.4;
    box-sizing: border-box;
}

.pos-receipt .pos-center {
    text-align: center;
}

.pos-receipt .pos-title {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.05em;
    margin: 0 0 2px;
}

.pos-receipt .pos-sub {
    font-size: 10px;
    margin: 0 0 6px;
}

.pos-receipt .pos-heading {
    font-size: 12px;
    font-weight: 700;
    margin: 6px 0 4px;
    letter-spacing: 0.15em;
}

.pos-receipt .pos-rule {
    border: none;
    border-top: 1px dashed #000;
    margin: 6px 0;
}

.pos-receipt .pos-row {
    display: flex;
    justify-content: space-between;
    gap: 6px;
    margin: 2px 0;
    word-break: break-word;
}

.pos-receipt .pos-row .pos-label {
    flex-shrink: 0;
    max-width: 42%;
}

.pos-receipt .pos-row .pos-val {
    text-align: right;
    flex: 1;
}

.pos-receipt .pos-block {
    margin: 3px 0;
}

.pos-receipt .pos-total {
    text-align: center;
    margin: 8px 0 4px;
}

.pos-receipt .pos-total .pos-total-label {
    font-size: 10px;
    letter-spacing: 0.2em;
}

.pos-receipt .pos-total .pos-total-amt {
    font-size: 18px;
    font-weight: 700;
    margin: 2px 0;
}

.pos-receipt .pos-total .pos-total-sub {
    font-size: 10px;
}

.pos-receipt .pos-footer {
    text-align: center;
    font-size: 10px;
    margin-top: 6px;
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
            <p class="pos-heading">PAYMENT RECEIPT</p>
        </div>

        <hr class="pos-rule">

        <div class="pos-row">
            <span class="pos-label">Receipt #</span>
            <span class="pos-val"><?php echo str_pad((string)$receiptNo, 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="pos-row">
            <span class="pos-label">Date</span>
            <span class="pos-val"><?php echo htmlspecialchars($payDate); ?></span>
        </div>
        <div class="pos-row">
            <span class="pos-label">Time</span>
            <span class="pos-val"><?php echo htmlspecialchars($payTime); ?></span>
        </div>

        <hr class="pos-rule">

        <div class="pos-block pos-center" style="text-align: left;">
            <div class="pos-row" style="flex-direction: column; gap: 0;">
                <span><strong><?php echo htmlspecialchars($studentName); ?></strong></span>
            </div>
            <div class="pos-row">
                <span class="pos-label">ID</span>
                <span class="pos-val"><?php echo htmlspecialchars($studentId); ?></span>
            </div>
            <?php if ($department !== ''): ?>
            <div class="pos-row">
                <span class="pos-label">Dept</span>
                <span class="pos-val"><?php echo htmlspecialchars($department); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <hr class="pos-rule">

        <div class="pos-row">
            <span class="pos-label">Type</span>
            <span class="pos-val"><?php echo htmlspecialchars($paymentType); ?></span>
        </div>
        <div class="pos-row">
            <span class="pos-label">Reason</span>
            <span class="pos-val"><?php echo htmlspecialchars($paymentReason); ?></span>
        </div>
        <?php if ($paymentMethod !== ''): ?>
        <div class="pos-row">
            <span class="pos-label">Method</span>
            <span class="pos-val"><?php echo htmlspecialchars($paymentMethod); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($referenceNo !== ''): ?>
        <div class="pos-row">
            <span class="pos-label">Ref</span>
            <span class="pos-val"><?php echo htmlspecialchars($referenceNo); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($notes !== ''): ?>
        <div class="pos-block" style="margin-top: 4px;">
            <div>Notes:</div>
            <div><?php echo htmlspecialchars($notes); ?></div>
        </div>
        <?php endif; ?>

        <hr class="pos-rule">

        <div class="pos-total">
            <div class="pos-total-label">AMOUNT PAID</div>
            <div class="pos-total-amt">Rs.<?php echo number_format($lineTotal, 2); ?></div>
            <?php if ($qty > 1): ?>
                <div class="pos-total-sub"><?php echo $qty; ?> x Rs.<?php echo number_format($unitAmount, 2); ?></div>
            <?php endif; ?>
        </div>

        <hr class="pos-rule">

        <div class="pos-row">
            <span class="pos-label">Cashier</span>
            <span class="pos-val"><?php echo htmlspecialchars($cashierName); ?></span>
        </div>

        <div class="pos-footer">
            <div>Thank you</div>
            <div style="margin-top: 4px;">*** <?php echo htmlspecialchars(APP_NAME); ?> ***</div>
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
