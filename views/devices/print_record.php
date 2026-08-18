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
$empId = $val($aa['employee_id'] ?? $d['assigned_employee_id'] ?? null);
$empDept = $val($aa['department_name'] ?? $d['assigned_department_name'] ?? null);
$empIssue = $val($aa['issue_date'] ?? null);
?>
<style>
@page {
    size: A4 portrait;
    margin: 10mm;
}

@media print {
    .no-print { display: none !important; }
    html, body {
        width: 210mm !important;
        height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .receipt {
        width: 190mm !important;
        height: 277mm !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        page-break-after: avoid;
    }
}

.receipt {
    box-sizing: border-box;
    width: 210mm;
    height: 297mm;
    max-width: 100%;
    margin: 10px auto;
    padding: 10mm;
    background: #fff;
    color: #000;
    font-family: "Times New Roman", Times, "Liberation Serif", serif;
    font-size: 9pt;
    line-height: 1.28;
    border: 1px solid #ccc;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    display: flex;
    flex-direction: column;
}
.receipt *,
.receipt *::before,
.receipt *::after { box-sizing: border-box; }

.receipt .toolbar {
    flex: 0 0 auto;
    text-align: right;
    margin: 0 0 4px;
}

/* Header — optically centred title with equal side gutters */
.receipt .head {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: 52px 1fr 52px;
    align-items: center;
    column-gap: 8px;
    padding-bottom: 5px;
    border-bottom: 2px solid #000;
}
.receipt .head .gutter {
    width: 52px;
    height: 52px;
}
.receipt .head .titles {
    text-align: center;
}
.receipt .head .org {
    margin: 0;
    font-size: 12.5pt;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    line-height: 1.15;
}
.receipt .head .doc-title {
    margin: 3px 0 0;
    font-size: 14pt;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    line-height: 1.1;
}
.receipt .head .qr img {
    width: 52px;
    height: 52px;
    display: block;
    margin: 0 auto;
}

.receipt .body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.receipt .sec { flex: 0 0 auto; }
.receipt .sec-terms {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.receipt .sec-h {
    margin: 6px 0 2px;
    padding: 0 0 1px;
    font-size: 8.5pt;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    border-bottom: 1px solid #000;
}

/* Uniform data grid */
.receipt table.grid {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.receipt table.grid th,
.receipt table.grid td {
    border: 1px solid #000;
    padding: 2.5px 5px;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}
.receipt table.grid th {
    width: 22%;
    background: #f0f0f0;
    font-weight: 700;
    text-align: left;
}
.receipt table.grid td {
    width: 28%;
    text-align: left;
}

/* Accessories checklist */
.receipt table.check {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 8pt;
}
.receipt table.check col.c-no { width: 5%; }
.receipt table.check col.c-item { width: 23%; }
.receipt table.check col.c-st { width: 29%; }
.receipt table.check col.c-rm { width: 14%; }
.receipt table.check th,
.receipt table.check td {
    border: 1px solid #000;
    padding: 2.5px 4px;
    vertical-align: middle;
}
.receipt table.check thead th {
    background: #f0f0f0;
    font-weight: 700;
    text-align: center;
    line-height: 1.15;
}
.receipt table.check td.num {
    text-align: center;
    font-weight: 700;
}
.receipt table.check td.rmk {
    height: 18px;
}

.receipt .status-opts {
    display: flex;
    flex-wrap: wrap;
    gap: 2px 6px;
    align-items: center;
}
.receipt .chk {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    white-space: nowrap;
}
.receipt .chk .box {
    display: inline-block;
    width: 8px;
    height: 8px;
    border: 1px solid #000;
    background: #fff;
    flex: 0 0 auto;
}

/* Terms */
.receipt .terms {
    margin: 0;
    padding-left: 1.1rem;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
}
.receipt .terms li {
    margin: 0;
    text-align: justify;
    text-justify: inter-word;
    hyphens: auto;
    line-height: 1.22;
    font-size: 7.75pt;
}

/* Signatures */
.receipt table.signs {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 7.5pt;
}
.receipt table.signs th,
.receipt table.signs td {
    border: 1px solid #000;
    padding: 3px 4px;
    vertical-align: top;
}
.receipt table.signs th {
    width: 33.333%;
    background: #f0f0f0;
    font-weight: 700;
    text-align: center;
    line-height: 1.15;
}
.receipt table.signs .pad {
    height: 28px;
}
.receipt table.signs .meta {
    margin-top: 1px;
    line-height: 1.2;
}
.receipt table.signs .meta span {
    display: inline-block;
    min-width: 48%;
}
</style>

<div class="receipt">
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-dark">
            <i class="fas fa-print me-1"></i> Print A4
        </button>
    </div>

    <header class="head">
        <div class="gutter" aria-hidden="true"></div>
        <div class="titles">
            <p class="org">Sri Lanka German Training Institute</p>
            <p class="doc-title">Property Receipt</p>
        </div>
        <div class="gutter qr">
            <?php if (!empty($qrDataUri)): ?>
                <img src="<?php echo $qrDataUri; ?>" alt="QR">
            <?php endif; ?>
        </div>
    </header>

    <div class="body">
        <section class="sec">
            <h2 class="sec-h">Property / Device Information</h2>
            <table class="grid">
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
            <table class="grid">
                <tr>
                    <th>Name</th>
                    <td><?php echo $e($empName); ?></td>
                    <th>Employee ID</th>
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
            <table class="grid">
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

        <section class="sec-terms">
            <h2 class="sec-h">Laptop Issue – Terms and Conditions</h2>
            <ol class="terms">
                <li>The laptop is issued in good working condition. The recipient is responsible for the safe custody and proper use of the laptop.</li>
                <li>Any damage caused due to negligence, misuse, mishandling, physical impact, liquid damage, or unauthorized modification shall be the responsibility of the recipient. The applicable repair or replacement cost must be paid by the recipient.</li>
                <li>If the laptop develops any technical issue or malfunction, the recipient must immediately inform the ICT Officer / ICT Department.</li>
                <li>The laptop must not be opened, dismantled, repaired, upgraded, or modified by the recipient or by any unauthorized person / service centre outside the institution.</li>
                <li>All repairs, hardware replacements, software-related issues, and technical maintenance must be handled or authorized by the ICT Department.</li>
                <li>The recipient must not remove, replace, or modify any internal components such as RAM, SSD/HDD, battery, motherboard, display, keyboard, or other hardware without prior approval from the ICT Department.</li>
                <li>Any loss of the laptop or issued accessories must be reported immediately and may result in recovery of the applicable cost according to institutional regulations.</li>
                <li>The recipient acknowledges and agrees to comply with the above conditions when accepting the laptop.</li>
            </ol>
        </section>

        <section class="sec">
            <h2 class="sec-h">Authorization &amp; Signatures</h2>
            <table class="signs">
                <tr>
                    <th>Employee Signature</th>
                    <th>Recommended by HOD</th>
                    <th>Taken Over</th>
                </tr>
                <tr>
                    <td>
                        <div class="pad"></div>
                        <div class="meta"><span>Name: ........................</span><span>Date: ............</span></div>
                    </td>
                    <td>
                        <div class="pad"></div>
                        <div class="meta"><span>Name: ........................</span><span>Date: ............</span></div>
                    </td>
                    <td>
                        <div class="pad"></div>
                        <div class="meta"><span>Name: ........................</span><span>Date: ............</span></div>
                    </td>
                </tr>
                <tr>
                    <th>Approved by Branch Principal / Deputy Branch Principal</th>
                    <th>Released by Store MA</th>
                    <th>Return by Signature</th>
                </tr>
                <tr>
                    <td>
                        <div class="pad"></div>
                        <div class="meta"><span>Name: ........................</span><span>Date: ............</span></div>
                    </td>
                    <td>
                        <div class="pad"></div>
                        <div class="meta"><span>Name: ........................</span><span>Date: ............</span></div>
                    </td>
                    <td>
                        <div class="pad"></div>
                        <div class="meta"><span>Name: ........................</span><span>Date: ............</span></div>
                    </td>
                </tr>
            </table>
        </section>
    </div>
</div>
