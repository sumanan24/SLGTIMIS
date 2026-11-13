<?php
declare(strict_types=1);
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$student = is_array($student ?? null) ? $student : [];
$stations = is_array($stations ?? null) ? $stations : [];
$stationData = is_array($stationData ?? null) ? $stationData : [];
$feeInfo = is_array($feeInfo ?? null) ? $feeInfo : [];
$institute = is_array($institute ?? null) ? $institute : [];
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$printMode = !empty($printMode);
$listBack = APP_URL . '/students/registration-checklist?academic_year=' . urlencode((string) ($student['academic_year'] ?? ''));
$payCls = ($paymentStatus ?? '') === 'Paid' ? 'ok' : (($paymentStatus ?? '') === 'Partial' ? 'warn' : 'bad');
?>
<style>
.stu-page { --stu-navy:#0b2545; --stu-ink:#1e293b; --stu-muted:#64748b; --stu-line:#dbe4f0; --stu-soft:#f5f8fd; color:var(--stu-ink); }
.stu-page .stu-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
.stu-page .stu-title { margin:0; font-size:1.25rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-sub { margin:.2rem 0 0; color:var(--stu-muted); font-size:.84rem; }
.stu-page .stu-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:.45rem; font-size:.82rem; font-weight:600; padding:.42rem .8rem; border:1px solid transparent; text-decoration:none; line-height:1.2; cursor:pointer; background:transparent; }
.stu-page .stu-btn-primary { background:var(--stu-navy); color:#fff; }
.stu-page .stu-btn-primary:hover { background:#163a66; color:#fff; }
.stu-page .stu-btn-ghost { background:#fff; color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-ghost:hover { background:var(--stu-soft); color:var(--stu-navy); }
.stu-page .stu-btn-print { background:#1d4ed8; color:#fff; }
.stu-page .stu-btn-print:hover { background:#1e40af; color:#fff; }
.stu-page .stu-panel { background:#fff; border:1px solid var(--stu-line); border-radius:.65rem; margin-bottom:.9rem; overflow:hidden; }
.stu-page .stu-panel-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem; padding:.65rem .9rem; background:var(--stu-soft); border-bottom:1px solid var(--stu-line); }
.stu-page .stu-panel-head h2 { margin:0; font-size:.9rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-panel-body { padding:.85rem .95rem; }
.stu-page .stu-label { display:block; font-size:.66rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--stu-muted); margin-bottom:.22rem; }
.stu-page .stu-field .form-control, .stu-page .stu-field .form-select { font-size:.84rem; border-color:#cfd9e6; border-radius:.4rem; }
.stu-page .stu-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:.7rem .85rem; }
.stu-page .s6 { grid-column:span 6; } .stu-page .s4 { grid-column:span 4; } .stu-page .s3 { grid-column:span 3; } .stu-page .s12 { grid-column:span 12; }
.stu-page .stu-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.35rem 1rem; font-size:.84rem; }
.stu-page .stu-info-grid dt { color:var(--stu-muted); font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin:0; }
.stu-page .stu-info-grid dd { margin:0 0 .35rem; font-weight:600; color:var(--stu-navy); }
.stu-page .stu-station { border:1px solid var(--stu-line); border-radius:.5rem; margin-bottom:.65rem; overflow:hidden; }
.stu-page .stu-station-h { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.4rem; padding:.45rem .7rem; background:#0b2545; color:#fff; font-size:.8rem; font-weight:700; }
.stu-page .stu-station-b { padding:.55rem .7rem; }
.stu-page .stu-checks { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.25rem .75rem; }
.stu-page .stu-checks .form-check-label { font-size:.82rem; }
.stu-page .stu-chip { display:inline-flex; padding:.18rem .5rem; border-radius:.3rem; font-size:.7rem; font-weight:700; }
.stu-page .stu-chip.ok { background:#dcfce7; color:#166534; }
.stu-page .stu-chip.warn { background:#fef3c7; color:#92400e; }
.stu-page .stu-chip.bad { background:#fee2e2; color:#991b1b; }
.stu-page .stu-actions-bar { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:flex-end; margin-top:.5rem; }
@media (max-width:767.98px) {
    .stu-page .s6, .stu-page .s4, .stu-page .s3 { grid-column:span 12; }
    .stu-page .stu-checks, .stu-page .stu-info-grid { grid-template-columns:1fr; }
}

/* ===== A4 printable sheet ===== */
.a4-sheet {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 1.25rem;
    padding: 8mm;
    background: #fff;
    color: #0b2545;
    border: 1px solid #dbe4f0;
    box-shadow: 0 8px 28px rgba(15,23,42,.08);
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    font-size: 8pt;
    line-height: 1.25;
    box-sizing: border-box;
}
.a4-sheet * { box-sizing: border-box; }
.a4-head { width:100%; border-collapse:collapse; margin-bottom:2.2mm; }
.a4-head td { vertical-align:middle; padding:0; }
.a4-logo { width:18%; }
.a4-logo img { height:11mm; width:auto; display:block; }
.a4-mid { width:64%; text-align:center; }
.a4-mid .name { font-size:10pt; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#0b2545; }
.a4-mid .meta { font-size:6.8pt; color:#475569; margin-top:.3mm; }
.a4-side { width:18%; text-align:right; }
.a4-badge {
    display:inline-block; border:1pt solid #0b2545; color:#0b2545;
    font-size:6.5pt; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    padding:1mm 1.5mm; line-height:1.2;
}
.a4-title {
    text-align:center; font-size:11pt; font-weight:700; text-transform:uppercase;
    letter-spacing:.08em; margin:0 0 .8mm; color:#0b2545;
}
.a4-rule { border:0; border-top:1.3pt solid #0b2545; margin:1.2mm 0 1.6mm; }
.a4-rule-soft { border:0; border-top:.6pt solid #94a3b8; margin:1.2mm 0; }
.a4-info { width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:1.6mm; }
.a4-info th, .a4-info td { border:.55pt solid #94a3b8; padding:.7mm 1.2mm; vertical-align:top; }
.a4-info th {
    width:16%; background:#e8eef8; color:#334155; font-size:6.4pt; font-weight:700;
    text-transform:uppercase; letter-spacing:.03em; text-align:left;
}
.a4-info td { width:34%; font-size:7.6pt; font-weight:600; color:#0b2545; }
.a4-stations { width:100%; border-collapse:collapse; table-layout:fixed; }
.a4-stations > tbody > tr > td { width:50%; vertical-align:top; padding:0 1mm 1.4mm; }
.a4-st {
    border:.65pt solid #0b2545; height:100%;
}
.a4-st-h {
    background:#0b2545; color:#fff; font-size:6.8pt; font-weight:700;
    text-transform:uppercase; letter-spacing:.03em; padding:.7mm 1.4mm;
}
.a4-st-b { padding:.6mm 1.3mm .8mm; }
.a4-item { font-size:7pt; padding:.35mm 0; display:flex; align-items:flex-start; gap:1.2mm; }
.a4-chk {
    flex:0 0 2.6mm; width:2.6mm; height:2.6mm; border:.65pt solid #0b2545;
    margin-top:.4mm; display:inline-block; text-align:center; line-height:2.4mm; font-size:6pt; font-weight:700;
}
.a4-chk.on { background:#0b2545; color:#fff; }
.a4-pay { width:100%; border-collapse:collapse; table-layout:fixed; margin-top:.4mm; }
.a4-pay th, .a4-pay td { border:.55pt solid #94a3b8; padding:.65mm 1.1mm; font-size:7pt; }
.a4-pay th { background:#e8eef8; text-align:left; font-weight:700; width:18%; color:#334155; text-transform:uppercase; font-size:6.3pt; }
.a4-pay td { font-weight:600; width:15%; }
.a4-bottom { width:100%; border-collapse:collapse; table-layout:fixed; margin-top:1.4mm; }
.a4-bottom td { vertical-align:top; padding:0; }
.a4-box { border:.65pt solid #0b2545; padding:1.1mm 1.5mm; min-height:14mm; }
.a4-box-h { font-size:6.8pt; font-weight:700; text-transform:uppercase; margin-bottom:.6mm; }
.a4-line { border-bottom:.55pt solid #64748b; height:5.5mm; margin-top:1mm; }
.a4-sub { font-size:6.2pt; color:#64748b; margin-top:.4mm; }
.a4-gap { width:2.5%; }
.a4-foot {
    margin-top:1.4mm; padding-top:.8mm; border-top:.55pt solid #0b2545;
    font-size:6pt; color:#64748b; text-align:center;
}

@media print {
    @page { size: A4 portrait; margin: 8mm; }
    html, body { background:#fff !important; margin:0 !important; padding:0 !important; }
    .sidebar, .sidebar-overlay, .top-navbar, .sidebar-close, .no-print,
    .bare-top, .bare-footer, .alert { display:none !important; }
    .main-content, .content-wrapper, .container-fluid, .stu-page {
        margin:0 !important; padding:0 !important; width:100% !important; max-width:none !important;
    }
    .a4-sheet {
        width:100% !important; min-height:0 !important; margin:0 !important; padding:0 !important;
        border:none !important; box-shadow:none !important;
    }
    .a4-print-only { display:block !important; }
}
<?php if ($printMode): ?>
@media screen {
    .no-print { display:none !important; }
    .stu-page { background:#e8eef8; padding:1rem !important; }
}
<?php endif; ?>
</style>

<div class="container-fluid px-4 py-3 stu-page">
    <div class="stu-head no-print">
        <div>
            <h1 class="stu-title">Registration checklist</h1>
            <p class="stu-sub"><?php echo $e($student['name'] ?? ''); ?> · <?php echo $e($student['student_id'] ?? ''); ?> · <?php echo $e($student['academic_year'] ?? ''); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="stu-btn stu-btn-ghost" href="<?php echo $e($listBack); ?>"><i class="fas fa-arrow-left"></i>List</a>
            <button type="button" class="stu-btn stu-btn-print" onclick="window.print()"><i class="fas fa-print"></i>Print checklist</button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show no-print">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show no-print">
            <i class="fas fa-check-circle me-2"></i><?php echo $e($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>/students/registration-checklist/form" id="regChecklistForm">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
        <input type="hidden" name="student_id" value="<?php echo $e($student['student_id'] ?? ''); ?>">
        <input type="hidden" name="academic_year" value="<?php echo $e($student['academic_year'] ?? ''); ?>">

        <div class="stu-panel no-print">
            <div class="stu-panel-head">
                <h2><i class="fas fa-user-graduate me-2"></i>Student information</h2>
                <span class="stu-chip <?php echo $payCls; ?>"><?php echo $e($paymentStatus ?? 'Unpaid'); ?></span>
            </div>
            <div class="stu-panel-body">
                <dl class="stu-info-grid mb-0">
                    <div><dt>Student name</dt><dd><?php echo $e($student['name'] ?? ''); ?></dd></div>
                    <div><dt>Registration number</dt><dd><?php echo $e($student['student_id'] ?? ''); ?></dd></div>
                    <div><dt>NIC number</dt><dd><?php echo $e($student['nic'] ?? ''); ?></dd></div>
                    <div><dt>Contact number</dt><dd><?php echo $e($student['phone'] ?? '—'); ?></dd></div>
                    <div><dt>Course name</dt><dd><?php echo $e($student['course_name'] ?? ''); ?></dd></div>
                    <div><dt>NVQ level</dt><dd><?php echo $e($student['nvq_level'] ?: '—'); ?></dd></div>
                    <div><dt>Permanent address</dt><dd><?php echo $e($student['address'] ?? '—'); ?></dd></div>
                    <div><dt>Province</dt><dd><?php echo $e($student['province'] ?? '—'); ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="stu-panel no-print">
            <div class="stu-panel-head">
                <h2><i class="fas fa-money-bill-wave me-2"></i>Registration fee &amp; payment</h2>
                <span class="small text-muted"><?php echo $e($feeInfo['category'] ?? ''); ?></span>
            </div>
            <div class="stu-panel-body">
                <div class="stu-grid">
                    <div class="stu-field s3">
                        <label class="stu-label">Applicable fee</label>
                        <input type="text" class="form-control" value="Rs. <?php echo number_format((float) $applicableFee, 2); ?>" readonly>
                    </div>
                    <div class="stu-field s3">
                        <label class="stu-label" for="amount_paid">Amount paid</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount_paid" name="amount_paid"
                               value="<?php echo $e(number_format((float) $amountPaid, 2, '.', '')); ?>">
                    </div>
                    <div class="stu-field s3">
                        <label class="stu-label">Outstanding balance</label>
                        <input type="text" class="form-control" id="outstanding_display"
                               value="Rs. <?php echo number_format((float) $outstanding, 2); ?>" readonly>
                    </div>
                    <div class="stu-field s3">
                        <label class="stu-label" for="receipt_number">Receipt number</label>
                        <input type="text" class="form-control" id="receipt_number" name="receipt_number"
                               value="<?php echo $e($receiptNumber ?? ''); ?>" maxlength="80">
                    </div>
                    <div class="s12 small text-muted">
                        Fee schedule:
                        Northern NVQ 04 — Rs. 2,450 · Outside NVQ 04 — Rs. 4,450 ·
                        Northern NVQ 05 — Rs. 2,500 · Outside NVQ 05 — Rs. 4,500
                    </div>
                </div>
            </div>
        </div>

        <div class="no-print">
            <?php foreach ($stations as $num => $station):
                $key = (string) $num;
                $block = $stationData[$key] ?? ['items' => [], 'staff' => '', 'completed_at' => ''];
            ?>
            <div class="stu-station">
                <div class="stu-station-h">
                    <span>Station <?php echo str_pad((string) $num, 2, '0', STR_PAD_LEFT); ?> — <?php echo $e($station['title']); ?></span>
                    <span class="small fw-normal opacity-75">Staff initials / date</span>
                </div>
                <div class="stu-station-b">
                    <div class="stu-checks mb-2">
                        <?php foreach ($station['items'] as $itemKey => $label):
                            $checked = !empty($block['items'][$itemKey]);
                            $id = 'st_' . $num . '_' . $itemKey;
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="stations[<?php echo $e($key); ?>][items][<?php echo $e($itemKey); ?>]"
                                   value="1" id="<?php echo $e($id); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="<?php echo $e($id); ?>"><?php echo $e($label); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="stu-grid">
                        <div class="stu-field s6">
                            <label class="stu-label" for="staff_<?php echo $num; ?>">Staff name / initials</label>
                            <input type="text" class="form-control form-control-sm" id="staff_<?php echo $num; ?>"
                                   name="stations[<?php echo $e($key); ?>][staff]"
                                   value="<?php echo $e($block['staff'] ?? ''); ?>" maxlength="120">
                        </div>
                        <div class="stu-field s6">
                            <label class="stu-label">Date / time</label>
                            <input type="text" class="form-control form-control-sm"
                                   name="stations[<?php echo $e($key); ?>][completed_at]"
                                   value="<?php echo $e($block['completed_at'] ?? ''); ?>"
                                   placeholder="Auto-filled on save when staff is entered" maxlength="40">
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="stu-panel no-print">
            <div class="stu-panel-head"><h2>Final confirmation &amp; signatures</h2></div>
            <div class="stu-panel-body">
                <div class="stu-grid">
                    <div class="stu-field s12">
                        <label class="stu-label" for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"><?php echo $e($remarks ?? ''); ?></textarea>
                    </div>
                    <div class="stu-field s6">
                        <label class="stu-label" for="officer_name">Registration officer</label>
                        <input type="text" class="form-control" id="officer_name" name="officer_name"
                               value="<?php echo $e($officerName ?? ''); ?>" maxlength="120">
                    </div>
                    <div class="stu-field s6">
                        <label class="stu-label" for="coordinator_name">Registration coordinator</label>
                        <input type="text" class="form-control" id="coordinator_name" name="coordinator_name"
                               value="<?php echo $e($coordinatorName ?? ''); ?>" maxlength="120">
                    </div>
                    <div class="s12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="final_confirmed" value="1" id="final_confirmed"
                                   <?php echo !empty($finalConfirmed) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="final_confirmed">
                                Final registration confirmed — all required stations completed
                            </label>
                        </div>
                    </div>
                </div>
                <div class="stu-actions-bar">
                    <button type="submit" name="action" value="save" class="stu-btn stu-btn-ghost">
                        <i class="fas fa-save"></i>Save
                    </button>
                    <button type="submit" name="action" value="save_print" class="stu-btn stu-btn-primary">
                        <i class="fas fa-print"></i>Save &amp; print
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- ========== A4 PRINT SHEET ========== -->
    <div class="a4-sheet" id="a4PrintSheet">
        <table class="a4-head">
            <tr>
                <td class="a4-logo"><?php if ($logoSrc !== ''): ?><img src="<?php echo $e($logoSrc); ?>" alt="SLGTI"><?php endif; ?></td>
                <td class="a4-mid">
                    <div class="name"><?php echo $e($instName); ?></div>
                    <div class="meta">
                        <?php echo $e($instAddr); ?>
                        <?php if ($instPhone !== ''): ?> · Tel: <?php echo $e($instPhone); ?><?php endif; ?>
                    </div>
                </td>
                <td class="a4-side"><span class="a4-badge">Registration<br>Checklist</span></td>
            </tr>
        </table>
        <div class="a4-title">Student Registration Checklist</div>
        <div style="text-align:center;font-size:7pt;color:#64748b;margin-bottom:1mm;">
            Academic year <?php echo $e($student['academic_year'] ?? ''); ?>
            <?php if (!empty($student['department'])): ?> · <?php echo $e($student['department']); ?><?php endif; ?>
        </div>
        <hr class="a4-rule">

        <table class="a4-info">
            <tr>
                <th>Student name</th>
                <td><?php echo $e($student['name'] ?? ''); ?></td>
                <th>Registration no.</th>
                <td><?php echo $e($student['student_id'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>NIC number</th>
                <td><?php echo $e($student['nic'] ?? ''); ?></td>
                <th>Contact number</th>
                <td><?php echo $e($student['phone'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Course name</th>
                <td><?php echo $e($student['course_name'] ?? ''); ?></td>
                <th>NVQ level</th>
                <td><?php echo $e($student['nvq_level'] ?: ''); ?></td>
            </tr>
            <tr>
                <th>Permanent address</th>
                <td><?php echo $e($student['address'] ?? ''); ?></td>
                <th>Province</th>
                <td><?php echo $e($student['province'] ?? ''); ?></td>
            </tr>
        </table>

        <table class="a4-stations">
            <tr>
                <?php
                $stationKeys = array_keys($stations);
                // Sequential order Station 01→07: left 01–04, right 05–07
                $left = array_slice($stationKeys, 0, 4, true);
                $right = array_slice($stationKeys, 4, null, true);
                $renderCol = static function (array $keys) use ($stations, $stationData, $e): void {
                    foreach ($keys as $num) {
                        $station = $stations[$num];
                        $key = (string) $num;
                        $block = $stationData[$key] ?? ['items' => [], 'staff' => '', 'completed_at' => ''];
                        echo '<div class="a4-st" style="margin-bottom:1.3mm;">';
                        echo '<div class="a4-st-h">Station ' . str_pad((string) $num, 2, '0', STR_PAD_LEFT) . ' — ' . $e($station['title']) . '</div>';
                        echo '<div class="a4-st-b">';
                        foreach ($station['items'] as $itemKey => $label) {
                            $on = !empty($block['items'][$itemKey]);
                            echo '<div class="a4-item"><span class="a4-chk' . ($on ? ' on' : '') . '">' . ($on ? '✓' : '') . '</span><span>' . $e($label) . '</span></div>';
                        }
                        echo '</div></div>';
                    }
                };
                ?>
                <td><?php $renderCol($left); ?></td>
                <td><?php $renderCol($right); ?></td>
            </tr>
        </table>

        <div class="a4-box-h" style="margin-top:.4mm;">Registration payment verification</div>
        <table class="a4-pay">
            <tr>
                <th>Applicable fee</th>
                <td>Rs. <?php echo number_format((float) $applicableFee, 2); ?></td>
                <th>Amount paid</th>
                <td>Rs. <?php echo number_format((float) $amountPaid, 2); ?></td>
                <th>Outstanding</th>
                <td>Rs. <?php echo number_format((float) $outstanding, 2); ?></td>
            </tr>
            <tr>
                <th>Receipt no.</th>
                <td colspan="2"><?php echo $e($receiptNumber ?: '____________________'); ?></td>
                <th>Payment status</th>
                <td colspan="2"><?php echo $e($paymentStatus ?? 'Unpaid'); ?></td>
            </tr>
        </table>

        <table class="a4-bottom">
            <tr>
                <td style="width:48.75%;">
                    <div class="a4-box">
                        <div class="a4-box-h">Final registration confirmation</div>
                        <div class="a4-item">
                            <span class="a4-chk<?php echo !empty($finalConfirmed) ? ' on' : ''; ?>"><?php echo !empty($finalConfirmed) ? '✓' : ''; ?></span>
                            <span>All required stations completed and registration confirmed</span>
                        </div>
                        <div class="a4-sub" style="margin-top:1mm;">Category: <?php echo $e($feeInfo['category'] ?? ''); ?></div>
                    </div>
                </td>
                <td class="a4-gap"></td>
                <td style="width:48.75%;">
                    <div class="a4-box">
                        <div class="a4-box-h">Remarks</div>
                        <div style="min-height:8mm;font-size:7pt;"><?php echo $e($remarks ?? '') !== '' ? $e($remarks) : '&nbsp;'; ?></div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="a4-bottom" style="margin-top:1.5mm;">
            <tr>
                <td style="width:48.75%;">
                    <div class="a4-box">
                        <div class="a4-box-h">Registration officer</div>
                        <div class="a4-line"><?php echo $e($officerName ?? ''); ?></div>
                        <div class="a4-sub">Name &amp; signature</div>
                    </div>
                </td>
                <td class="a4-gap"></td>
                <td style="width:48.75%;">
                    <div class="a4-box">
                        <div class="a4-box-h">Registration coordinator</div>
                        <div class="a4-line"><?php echo $e($coordinatorName ?? ''); ?></div>
                        <div class="a4-sub">Name &amp; signature</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="a4-foot">
            SLGTI Student Registration Checklist · Generated <?php echo date('Y-m-d H:i'); ?> · One page A4
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fee = <?php echo json_encode((float) $applicableFee); ?>;
    var paid = document.getElementById('amount_paid');
    var out = document.getElementById('outstanding_display');
    function refreshOut() {
        if (!paid || !out) return;
        var p = parseFloat(paid.value || '0') || 0;
        var bal = Math.max(0, Math.round((fee - p) * 100) / 100);
        out.value = 'Rs. ' + bal.toFixed(2);
    }
    if (paid) paid.addEventListener('input', refreshOut);

    <?php if ($printMode): ?>
    setTimeout(function() { window.print(); }, 350);
    <?php endif; ?>
});
</script>
