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
?>
<style>
@page {
    size: A4 portrait;
    margin: 10mm 12mm;
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
    .sheet {
        width: 186mm !important; /* 210 - 12 - 12 */
        height: 277mm !important; /* 297 - 10 - 10 */
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}

/* Screen preview = exact A4 canvas */
.sheet {
    box-sizing: border-box;
    width: 210mm;
    height: 297mm;
    max-width: 100%;
    margin: 12px auto;
    padding: 10mm 12mm; /* equal left/right */
    background: #fff;
    color: #1a1a1a;
    font-family: "Times New Roman", Times, "Liberation Serif", serif;
    font-size: 10pt;
    line-height: 1.35;
    border: 1px solid #d8d8d8;
    box-shadow: 0 4px 18px rgba(0,0,0,.08);
    display: flex;
    flex-direction: column;
}
.sheet *,
.sheet *::before,
.sheet *::after { box-sizing: border-box; }

.sheet .toolbar {
    flex: 0 0 auto;
    text-align: right;
    margin-bottom: 6px;
}

/* Header: equal side columns so title is optically centered */
.sheet .head {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: 64px 1fr 64px;
    align-items: center;
    column-gap: 10px;
    padding-bottom: 8px;
    border-bottom: 2.25px solid #111;
    margin-bottom: 2px;
}
.sheet .head .side {
    width: 64px;
    height: 64px;
}
.sheet .head .brand {
    text-align: center;
    padding: 0 4px;
}
.sheet .head .org {
    margin: 0;
    font-size: 13.5pt;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    line-height: 1.15;
}
.sheet .head .doc-name {
    margin: 4px 0 0;
    font-size: 11.5pt;
    font-weight: 700;
    letter-spacing: .01em;
}
.sheet .head .qr img {
    width: 64px;
    height: 64px;
    display: block;
    margin: 0 auto;
}

.sheet .body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    gap: 0;
}

.sheet .sec { flex: 0 0 auto; }
.sheet .sec-grow {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.sheet .sec-title {
    margin: 10px 0 5px;
    padding: 0 0 3px;
    font-size: 10pt;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    border-bottom: 1px solid #222;
}

/* Data tables */
.sheet table.grid {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.sheet table.grid th,
.sheet table.grid td {
    border: 1px solid #222;
    padding: 5px 7px;
    vertical-align: middle;
    word-wrap: break-word;
}
.sheet table.grid th {
    width: 22%;
    background: #f4f4f4;
    font-weight: 700;
    text-align: left;
    color: #111;
}
.sheet table.grid td {
    width: 28%;
    text-align: left;
}

/* Accessories checklist */
.sheet table.check {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 9pt;
}
.sheet table.check col.c-no { width: 6%; }
.sheet table.check col.c-item { width: 24%; }
.sheet table.check col.c-st { width: 28%; }
.sheet table.check col.c-rm { width: 14%; }
.sheet table.check th,
.sheet table.check td {
    border: 1px solid #222;
    padding: 5px 6px;
    vertical-align: middle;
}
.sheet table.check thead th {
    background: #f4f4f4;
    font-weight: 700;
    text-align: center;
    line-height: 1.2;
}
.sheet table.check td.num {
    text-align: center;
    font-weight: 700;
}
.sheet table.check td.item { text-align: left; }
.sheet table.check td.rmk { min-height: 26px; }

.sheet .status-opts {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 10px;
    align-items: center;
}
.sheet .chk {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.sheet .chk .box {
    display: inline-block;
    width: 10px;
    height: 10px;
    border: 1px solid #111;
    background: #fff;
    flex: 0 0 auto;
}

/* Terms */
.sheet .terms {
    margin: 0;
    padding-left: 1.25rem;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
}
.sheet .terms li {
    margin: 0;
    text-align: justify;
    text-justify: inter-word;
    hyphens: auto;
    line-height: 1.38;
    font-size: 9.25pt;
}
</style>

<div class="sheet">
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-dark">
            <i class="fas fa-print me-1"></i> Print A4
        </button>
    </div>

    <header class="head">
        <div class="side" aria-hidden="true"></div>
        <div class="brand">
            <p class="org">Sri Lanka German Training Institute</p>
            <p class="doc-name">Laptop Asset Management Record</p>
        </div>
        <div class="side qr">
            <?php if (!empty($qrDataUri)): ?>
                <img src="<?php echo $qrDataUri; ?>" alt="QR">
            <?php endif; ?>
        </div>
    </header>

    <div class="body">
        <section class="sec">
            <h2 class="sec-title">Device Information</h2>
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
            <h2 class="sec-title">Accessories</h2>
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
                        <td class="item"><?php echo $e($row['name']); ?></td>
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
            <h2 class="sec-title">Configuration</h2>
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

        <section class="sec-grow">
            <h2 class="sec-title">Laptop Issue – Terms and Conditions</h2>
            <ol class="terms">
                <li>The laptop is issued in good working condition. The recipient is responsible for the safe custody and proper use of the laptop.</li>
                <li>Any damage caused due to negligence, misuse, mishandling, physical impact, liquid damage, or unauthorized modification shall be the responsibility of the recipient. The applicable repair or replacement cost must be paid by the recipient.</li>
                <li>If the laptop develops any technical issue or malfunction, the recipient must immediately inform the ICT Officer/ICT Department.</li>
                <li>The laptop must not be opened, dismantled, repaired, upgraded, or modified by the recipient or by any unauthorized person/service centre outside the institution.</li>
                <li>All repairs, hardware replacements, software-related issues, and technical maintenance must be handled or authorized by the ICT Department.</li>
                <li>The recipient must not remove, replace, or modify any internal components such as RAM, SSD/HDD, battery, motherboard, display, keyboard, or other hardware without prior approval from the ICT Department.</li>
                <li>Any loss of the laptop or issued accessories must be reported immediately and may result in recovery of the applicable cost according to institutional regulations.</li>
                <li>The recipient acknowledges and agrees to comply with the above conditions when accepting the laptop.</li>
            </ol>
        </section>
    </div>
</div>
