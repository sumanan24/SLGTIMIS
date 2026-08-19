<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$brandModel = trim(($d['brand'] ?? '') . ' ' . ($d['model'] ?? ''));
$storage = trim(($d['storage_type'] ?? '') . ' ' . ($d['storage_capacity'] ?? ''));
$val = static function (?string $v): string {
    $v = trim((string) ($v ?? ''));
    return $v !== '' ? $v : '—';
};

$statusChecks = static function (bool $withMissing): string {
    $opts = ['Good', 'Damaged'];
    if ($withMissing) {
        $opts[] = 'Missing';
    }
    $html = '<div class="status-opts">';
    foreach ($opts as $label) {
        $html .= '<span class="chk"><i class="box" aria-hidden="true"></i>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $html .= '</div>';
    return $html;
};

$accessoryRows = [
    ['name' => 'Charger / Power Adapter', 'missing' => true],
    ['name' => 'Laptop Bag', 'missing' => true],
    ['name' => 'Screen / Display', 'missing' => false],
    ['name' => 'Keyboard', 'missing' => false],
    ['name' => 'Touchpad', 'missing' => false],
];

$aa = is_array($activeAssignment ?? null) ? $activeAssignment : [];
$empName = $val($aa['staff_name'] ?? $d['assigned_staff_name'] ?? null);
$empId = $val($aa['staff_epf'] ?? $d['assigned_staff_epf'] ?? $aa['employee_id'] ?? $d['assigned_employee_id'] ?? null);
$empDept = $val($aa['department_name'] ?? $d['assigned_department_name'] ?? null);
$empIssue = $val($aa['issue_date'] ?? null);

$logoSrc = '';
foreach ([
    BASE_PATH . '/assets/img/logo.png',
    BASE_PATH . '/assets/img/SLGTILogo.png',
    BASE_PATH . '/assets/img/slgtilogo.png',
] as $logoPath) {
    if (!is_file($logoPath)) {
        continue;
    }
    $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : ($ext === 'svg' ? 'svg+xml' : $ext);
    $logoSrc = 'data:image/' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
    break;
}

$signRows = [
    'Employee Signature',
    'Recommended by HOD',
    'Taken Over',
    'Approved by Branch Principal / Deputy Branch Principal',
    'Released by Store MA',
    'Return by Signature',
];
?>
<style>
@page {
    size: A4 portrait;
    margin: 0;
}

@media print {
    .no-print { display: none !important; }
    html, body {
        width: 210mm !important;
        height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        overflow: hidden !important;
    }
    .print-toolbar { display: none !important; }
    .page {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
        overflow: hidden !important;
    }
    .property-receipt {
        width: 186mm !important;
        height: 277mm !important;
        margin: 10mm auto !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        page-break-inside: avoid;
        page-break-after: avoid;
        overflow: hidden !important;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

.print-toolbar {
    width: 210mm;
    margin: 12px auto 8px;
    text-align: right;
}

/* Exact A4 — full content, no page scroll */
.page {
    box-sizing: border-box;
    width: 210mm;
    height: 297mm;
    margin: 0 auto 24px;
    background: #fff;
    overflow: hidden;
}
@media screen {
    .page {
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.25);
    }
}

.property-receipt {
    box-sizing: border-box;
    width: 186mm;
    height: 277mm;
    margin: 10mm auto;
    padding: 0;
    color: #111;
    background: #fff;
    font-family: Arial, Calibri, "Helvetica Neue", Helvetica, sans-serif;
    font-size: 8.5pt;
    line-height: 1.3;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.property-receipt *,
.property-receipt *::before,
.property-receipt *::after { box-sizing: border-box; }

/* Header */
.property-receipt .head {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: 22mm 1fr 32mm;
    align-items: center;
    column-gap: 4mm;
    padding-bottom: 2.5mm;
    border-bottom: 1.4px solid #111;
    margin-bottom: 2mm;
}
.property-receipt .head .side {
    display: flex;
    align-items: center;
    justify-content: center;
}
.property-receipt .head .side.logo {
    justify-content: flex-end;
}
.property-receipt .head .titles {
    text-align: center;
}
.property-receipt .head .org {
    margin: 0;
    font-size: 13pt;
    font-weight: 700;
    letter-spacing: .035em;
    text-transform: uppercase;
    line-height: 1.12;
    color: #000;
}
.property-receipt .head .doc-title {
    margin: 1.6mm 0 0;
    font-size: 14.5pt;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    line-height: 1.05;
    color: #000;
}
.property-receipt .head .qr img {
    width: 18mm;
    height: 18mm;
    display: block;
}
.property-receipt .head .logo img {
    width: 30mm;
    height: 26mm;
    object-fit: contain;
    display: block;
}

.property-receipt .body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.property-receipt .sec { flex: 0 0 auto; }

.property-receipt .sec-h {
    margin: 2.4mm 0 1.2mm;
    padding: 0 0 0.7mm;
    font-size: 9pt;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: #000;
    border-bottom: 0.65px solid #333;
}

/* Shared tables */
.property-receipt table.data,
.property-receipt table.check,
.property-receipt table.signs {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.property-receipt table.data th,
.property-receipt table.data td,
.property-receipt table.check th,
.property-receipt table.check td,
.property-receipt table.signs th,
.property-receipt table.signs td {
    border: 0.55px solid #222;
    padding: 1.2mm 1.8mm;
    vertical-align: middle;
    word-wrap: break-word;
}
.property-receipt table.data th {
    width: 20%;
    background: #f2f2f2;
    font-weight: 700;
    font-size: 8pt;
    text-align: left;
}
.property-receipt table.data td {
    width: 30%;
    font-size: 8.25pt;
    text-align: left;
}
.property-receipt table.data.emp td {
    height: 6.5mm;
}

/* Accessories */
.property-receipt table.check {
    font-size: 7.75pt;
}
.property-receipt table.check col.c-no { width: 6%; }
.property-receipt table.check col.c-item { width: 24%; }
.property-receipt table.check col.c-st { width: 28%; }
.property-receipt table.check col.c-rm { width: 14%; }
.property-receipt table.check thead th {
    background: #f2f2f2;
    font-weight: 700;
    font-size: 7.75pt;
    text-align: center;
    line-height: 1.15;
    padding: 1mm 1.2mm;
}
.property-receipt table.check td.num {
    text-align: center;
    font-weight: 700;
}
.property-receipt table.check td {
    padding: 1mm 1.4mm;
}
.property-receipt table.check td.rmk {
    height: 6mm;
}
.property-receipt .status-opts {
    display: flex;
    flex-wrap: nowrap;
    gap: 0 3mm;
    align-items: center;
    justify-content: flex-start;
}
.property-receipt .chk {
    display: inline-flex;
    align-items: center;
    gap: 1.1mm;
    white-space: nowrap;
    font-size: 7.25pt;
}
.property-receipt .chk .box {
    display: inline-block;
    width: 2.4mm;
    height: 2.4mm;
    border: 0.55px solid #111;
    background: #fff;
    flex: 0 0 auto;
}

/* Terms */
.property-receipt .terms {
    margin: 1mm 0 0;
    padding-left: 5mm;
    flex: 0 0 auto;
}
.property-receipt .terms li {
    margin: 0 0 1.3mm;
    text-align: justify;
    text-justify: inter-word;
    line-height: 1.3;
    font-size: 8pt;
}
.property-receipt .terms li:last-child {
    margin-bottom: 0;
}
.property-receipt .terms .term-label {
    font-weight: 700;
}
.property-receipt .terms .term-emph {
    font-weight: 700;
}

/* Signatures — fill remaining page height evenly */
.property-receipt .sec-signs {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    margin-top: 2mm;
}
.property-receipt .sec-signs .sec-h {
    flex: 0 0 auto;
}
.property-receipt table.signs {
    flex: 1 1 auto;
    width: 100%;
    height: 100%;
    font-size: 8pt;
    table-layout: fixed;
    border-collapse: collapse;
}
.property-receipt table.signs col.c-role { width: 36%; }
.property-receipt table.signs col.c-name { width: 22%; }
.property-receipt table.signs col.c-sig { width: 26%; }
.property-receipt table.signs col.c-date { width: 16%; }
.property-receipt table.signs thead {
    height: 7mm;
}
.property-receipt table.signs thead th {
    background: #f2f2f2;
    font-weight: 700;
    font-size: 8.25pt;
    text-align: center;
    padding: 1.2mm 1.6mm;
    height: 7mm;
}
.property-receipt table.signs tbody {
    height: calc(100% - 7mm);
}
.property-receipt table.signs td.role {
    text-align: left;
    font-weight: 600;
    font-size: 8pt;
    line-height: 1.2;
    vertical-align: middle;
}
.property-receipt table.signs tbody tr {
    height: 16.66%;
}
.property-receipt table.signs tbody td {
    height: 16.66%;
    min-height: 9mm;
    padding: 1.5mm 1.8mm;
    vertical-align: middle;
}

.property-receipt .doc-footer {
    flex: 0 0 auto;
    margin-top: 1.5mm;
    padding-top: 1.2mm;
    border-top: 0.55px solid #666;
    font-size: 7.5pt;
    letter-spacing: .04em;
    color: #333;
    text-align: center;
}
</style>

<div class="print-toolbar no-print">
    <button type="button" onclick="window.print()" class="btn btn-sm btn-dark">
        <i class="fas fa-print me-1"></i> Print A4
    </button>
</div>

<div class="page">
<div class="property-receipt">
    <header class="head">
        <div class="side qr">
            <?php if (!empty($qrDataUri)): ?>
                <img src="<?php echo $qrDataUri; ?>" alt="QR">
            <?php endif; ?>
        </div>
        <div class="titles">
            <p class="org">Sri Lanka German Training Institute</p>
            <p class="doc-title">Property Receipt</p>
        </div>
        <div class="side logo">
            <?php if ($logoSrc !== ''): ?>
                <img src="<?php echo $logoSrc; ?>" alt="SLGTI">
            <?php endif; ?>
        </div>
    </header>

    <div class="body">
        <section class="sec">
            <h2 class="sec-h">Property / Device Information</h2>
            <table class="data">
                <tr>
                    <th>Asset ID</th>
                    <td><?php echo $e($val($d['asset_id'] ?? null)); ?></td>
                    <th>Brand / Model</th>
                    <td><?php echo $e($val($brandModel)); ?></td>
                </tr>
                <tr>
                    <th>Serial No.</th>
                    <td><?php echo $e($val($d['serial_number'] ?? null)); ?></td>
                    <th>Processor</th>
                    <td><?php echo $e($val($d['processor'] ?? null)); ?></td>
                </tr>
                <tr>
                    <th>RAM</th>
                    <td><?php echo $e($val($d['ram'] ?? null)); ?></td>
                    <th>Storage</th>
                    <td><?php echo $e($val($storage)); ?></td>
                </tr>
                <tr>
                    <th>Operating System</th>
                    <td><?php echo $e($val($d['operating_system'] ?? null)); ?></td>
                    <th>Purchase Date</th>
                    <td><?php echo $e($val($d['purchase_date'] ?? null)); ?></td>
                </tr>
                <tr>
                    <th>Warranty Expiry</th>
                    <td colspan="3"><?php echo $e($val($d['warranty_expiry'] ?? null)); ?></td>
                </tr>
            </table>
        </section>

        <section class="sec">
            <h2 class="sec-h">Employee Information</h2>
            <table class="data emp">
                <tr>
                    <th>Name</th>
                    <td><?php echo $e($empName); ?></td>
                    <th>EPF No.</th>
                    <td><?php echo $e($empId); ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php echo $e($empDept); ?></td>
                    <th>Issue Date</th>
                    <td><?php echo $e($empIssue); ?></td>
                </tr>
            </table>
        </section>

        <section class="sec">
            <h2 class="sec-h">Accessories</h2>
            <table class="check">
                <colgroup>
                    <col class="c-no">
                    <col class="c-item">
                    <col class="c-st">
                    <col class="c-st">
                    <col class="c-rm">
                </colgroup>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Accessory</th>
                        <th>Issue Time Status</th>
                        <th>Return Time Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($accessoryRows as $i => $row): ?>
                    <tr>
                        <td class="num"><?php echo (int) ($i + 1); ?></td>
                        <td><?php echo $e($row['name']); ?></td>
                        <td><?php echo $statusChecks(!empty($row['missing'])); ?></td>
                        <td><?php echo $statusChecks(!empty($row['missing'])); ?></td>
                        <td class="rmk">&nbsp;</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <?php if (!empty($fullDetail)): ?>
        <section class="sec">
            <h2 class="sec-h">Configuration</h2>
            <table class="data">
                <tr>
                    <th>Windows Activated</th>
                    <td><?php echo !empty($d['windows_activated']) ? 'Yes' : 'No'; ?></td>
                    <th>MS Office Activated</th>
                    <td><?php echo !empty($d['ms_office_activated']) ? 'Yes' : 'No'; ?></td>
                </tr>
                <tr>
                    <th>BitLocker Enabled</th>
                    <td><?php echo !empty($d['bitlocker_enabled']) ? 'Yes' : 'No'; ?></td>
                    <th>Antivirus Installed</th>
                    <td><?php echo !empty($d['antivirus_installed']) ? 'Yes' : 'No'; ?></td>
                </tr>
            </table>
        </section>
        <?php endif; ?>

        <section class="sec">
            <h2 class="sec-h">Laptop Issue – Terms and Conditions</h2>
            <ol class="terms">
                <li><span class="term-label">Care &amp; Responsibility:</span> The recipient is responsible for the safe custody and proper use of the laptop.</li>
                <li><span class="term-label">Damage &amp; Loss:</span> Any damage, loss, or missing item caused by negligence, misuse, or mishandling shall be the recipient&apos;s responsibility. <span class="term-emph">The applicable repair or replacement cost must be paid by the recipient.</span></li>
                <li><span class="term-label">Technical Issues:</span> All technical issues must be reported immediately to the ICT Department. Repairs must be handled only by authorized ICT Department personnel.</li>
                <li><span class="term-label">No Unauthorized Modification:</span> The laptop must not be opened, repaired, upgraded, or modified without prior approval from the ICT Department.</li>
            </ol>
        </section>

        <section class="sec-signs">
            <h2 class="sec-h">Authorization &amp; Signatures</h2>
            <table class="signs">
                <colgroup>
                    <col class="c-role">
                    <col class="c-name">
                    <col class="c-sig">
                    <col class="c-date">
                </colgroup>
                <thead>
                    <tr>
                        <th>Authorization</th>
                        <th>Name</th>
                        <th>Signature</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($signRows as $role):
                    $tall = (stripos($role, 'Branch Principal') !== false);
                ?>
                    <tr<?php echo $tall ? ' class="tall"' : ''; ?>>
                        <td class="role"><?php echo $e($role); ?></td>
                        <td class="name-cell">&nbsp;</td>
                        <td class="sig-cell">&nbsp;</td>
                        <td class="date-cell">&nbsp;</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <footer class="doc-footer">SLGTI-MAN-FOR-005-v1.1</footer>
</div>
</div>
