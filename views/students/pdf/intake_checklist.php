<?php
declare(strict_types=1);
/**
 * One-page A4 Student Registration Checklist (SLGTI blue / white).
 * Sized to fit a single A4 portrait page in Dompdf.
 *
 * @var array  $student
 * @var string $logoSrc
 * @var array  $institute
 */
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$s = is_array($student ?? null) ? $student : [];
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$year = trim((string) ($s['academic_year'] ?? ''));
$nvq = trim((string) ($s['nvq'] ?? ''));
$fee = isset($s['applicable_fee']) ? (float) $s['applicable_fee'] : 0.0;

$stations = [
    ['no' => '01', 'title' => 'NIC & Registration Verification', 'items' => [
        'NIC verified',
        'Admission letter checked',
        'Student details verified',
        'Course and NVQ level confirmed',
    ]],
    ['no' => '02', 'title' => 'Document Verification & Personal File', 'items' => [
        'NIC copy collected',
        'Educational certificates collected',
        'Required documents verified',
        'Personal file opened and labelled',
    ]],
    ['no' => '03', 'title' => 'Financial Clearance', 'items' => [
        'Province and NVQ level verified',
        'Correct registration fee calculated',
        'Payment received',
        'Receipt issued',
        'Payment recorded in MIS',
    ]],
    ['no' => '04', 'title' => 'Student Agreement & Code of Conduct', 'items' => [
        'Agreement provided',
        'Institute rules explained',
        'Student signature obtained',
        'Parent / guardian signature obtained',
    ]],
    ['no' => '05', 'title' => 'Student ID Photo Taking', 'items' => [
        'Photograph captured',
        'Photograph verified',
        'Photograph uploaded to MIS',
    ]],
    ['no' => '06', 'title' => 'Hostel Allocation & Agreement', 'items' => [
        'Hostel requirement confirmed',
        'Hostel request form collected',
        'Hostel availability checked',
        'Room / bed allocated',
        'Hostel agreement signed',
    ]],
    ['no' => '07', 'title' => 'Orientation Pack & File Collection', 'items' => [
        'Student handbook distributed',
        'Student record book distributed',
        'Orientation schedule provided',
        'Personal file checked',
        'Final registration confirmed',
    ]],
];

$cell = static function ($v) use ($e): string {
    $t = trim((string) ($v ?? ''));
    return $t === '' ? '&nbsp;' : $e($t);
};

$left = array_slice($stations, 0, 4);
$right = array_slice($stations, 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Registration Checklist</title>
<style>
@page { size: A4 portrait; margin: 7mm 8mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { margin: 0; padding: 0; background: #fff; }
body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 7pt;
    color: #0b2545;
    line-height: 1.2;
}

.lh { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 1.2mm; }
.lh td { padding: 0; vertical-align: middle; }
.lh .c-logo { width: 14%; }
.lh .c-logo img { height: 9mm; width: auto; display: block; }
.lh .c-mid { width: 72%; text-align: center; padding: 0 1.5mm; }
.lh .c-side { width: 14%; text-align: right; }
.inst-name {
    font-size: 9pt; font-weight: bold; letter-spacing: 0.03em;
    text-transform: uppercase; color: #0b2545; margin-bottom: 0.3mm;
}
.inst-meta { font-size: 6pt; color: #475569; line-height: 1.25; }
.badge {
    display: inline-block; border: 0.8pt solid #0b2545; color: #0b2545;
    font-size: 5.6pt; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase;
    padding: 0.9mm 1.2mm; line-height: 1.15;
}
.rule { border: 0; border-top: 1.1pt solid #0b2545; margin: 0 0 1.2mm; height: 0; }
.title {
    text-align: center; font-size: 9.5pt; font-weight: bold; letter-spacing: 0.06em;
    text-transform: uppercase; margin: 0 0 0.4mm; color: #0b2545;
}
.sub { text-align: center; font-size: 6.5pt; color: #64748b; margin: 0 0 1.6mm; }

table.info { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 1.8mm; }
table.info th, table.info td {
    border: 0.5pt solid #94a3b8;
    padding: 0.85mm 1.2mm;
    vertical-align: middle;
}
table.info th {
    width: 15%; background: #e8eef8; color: #334155;
    font-size: 5.8pt; font-weight: bold;
    text-transform: uppercase; text-align: left;
}
table.info td { width: 35%; font-size: 7pt; font-weight: bold; color: #0b2545; }

/* Two-column stations — compact, single page */
table.cols { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 1.2mm; }
table.cols > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
table.cols td.col-l { padding-right: 1.2mm; }
table.cols td.col-r { padding-left: 1.2mm; }

.sec {
    border: 0.5pt solid #0b2545;
    margin: 0 0 1.3mm;
}
.sec-h {
    background: #0b2545; color: #fff;
    font-size: 6.2pt; font-weight: bold;
    letter-spacing: 0.02em; text-transform: uppercase;
    padding: 0.7mm 1.3mm;
}
.sec-b { padding: 0.7mm 1.3mm 0.9mm; }
.item {
    font-size: 6.6pt;
    padding: 0.35mm 0;
    line-height: 1.2;
}
.chk {
    display: inline-block;
    width: 2.4mm; height: 2.4mm;
    border: 0.55pt solid #0b2545;
    margin: 0 1.2mm 0 0;
    vertical-align: middle;
    background: #fff;
}

.h-lab {
    font-size: 6.4pt; font-weight: bold; text-transform: uppercase;
    letter-spacing: 0.02em; margin: 0.8mm 0 0.7mm; color: #0b2545;
}
table.pay { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 1.5mm; }
table.pay th, table.pay td {
    border: 0.5pt solid #94a3b8;
    padding: 0.8mm 1.1mm;
    font-size: 6.6pt;
    vertical-align: middle;
}
table.pay th {
    background: #e8eef8; text-align: left; font-weight: bold; width: 15%;
    color: #334155; text-transform: uppercase; font-size: 5.7pt;
}
table.pay td { font-weight: bold; width: 18.3%; }

table.foot { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 1.3mm; }
table.foot td { vertical-align: top; padding: 0; width: 49%; }
table.foot td.gap { width: 2%; padding: 0; border: 0; }
.box {
    border: 0.5pt solid #0b2545;
    padding: 1.1mm 1.4mm;
}
.box-h {
    font-size: 6.2pt; font-weight: bold; text-transform: uppercase;
    margin: 0 0 0.7mm;
}
.box .item { padding: 0.2mm 0; font-size: 6.5pt; }
.line {
    border-bottom: 0.5pt solid #64748b;
    height: 5mm;
    margin-top: 0.5mm;
}
.subl { font-size: 5.7pt; color: #64748b; margin-top: 0.4mm; }
.remarks { height: 6mm; }

.footer {
    margin-top: 0.4mm;
    padding-top: 0.7mm;
    border-top: 0.5pt solid #0b2545;
    font-size: 5.6pt;
    color: #64748b;
    text-align: center;
}
</style>
</head>
<body>

<table class="lh">
    <tr>
        <td class="c-logo"><?php if ($logoSrc !== ''): ?><img src="<?php echo $e($logoSrc); ?>" alt=""><?php endif; ?></td>
        <td class="c-mid">
            <div class="inst-name"><?php echo $e($instName); ?></div>
            <div class="inst-meta">
                <?php echo $e($instAddr); ?>
                <?php if ($instPhone !== ''): ?> · Tel: <?php echo $e($instPhone); ?><?php endif; ?>
            </div>
        </td>
        <td class="c-side"><span class="badge">Registration<br>Checklist</span></td>
    </tr>
</table>
<hr class="rule">

<div class="title">Student Registration Checklist</div>
<div class="sub">
    Academic year:
    <strong><?php echo $year !== '' ? $e($year) : '____________________'; ?></strong>
    <?php if (!empty($s['department'])): ?>
        · <?php echo $e($s['department']); ?>
    <?php endif; ?>
</div>

<table class="info">
    <tr>
        <th>Student name</th>
        <td><?php echo $cell($s['name'] ?? ''); ?></td>
        <th>Registration no.</th>
        <td><?php echo $cell($s['student_id'] ?? ''); ?></td>
    </tr>
    <tr>
        <th>NIC number</th>
        <td><?php echo $cell($s['nic'] ?? ''); ?></td>
        <th>Contact number</th>
        <td><?php echo $cell($s['phone'] ?? ''); ?></td>
    </tr>
    <tr>
        <th>Course name</th>
        <td><?php echo $cell($s['course'] ?? ''); ?></td>
        <th>NVQ level</th>
        <td><?php echo $cell($nvq); ?></td>
    </tr>
    <tr>
        <th>Permanent address</th>
        <td><?php echo $cell($s['address'] ?? ''); ?></td>
        <th>Province</th>
        <td><?php echo $cell($s['province'] ?? ''); ?></td>
    </tr>
</table>

<table class="cols">
    <tr>
        <td class="col-l">
            <?php foreach ($left as $st): ?>
            <div class="sec">
                <div class="sec-h">Station <?php echo $e($st['no']); ?> — <?php echo $e($st['title']); ?></div>
                <div class="sec-b">
                    <?php foreach ($st['items'] as $item): ?>
                    <div class="item"><span class="chk"></span><?php echo $e($item); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </td>
        <td class="col-r">
            <?php foreach ($right as $st): ?>
            <div class="sec">
                <div class="sec-h">Station <?php echo $e($st['no']); ?> — <?php echo $e($st['title']); ?></div>
                <div class="sec-b">
                    <?php foreach ($st['items'] as $item): ?>
                    <div class="item"><span class="chk"></span><?php echo $e($item); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </td>
    </tr>
</table>

<div class="h-lab">Registration payment verification</div>
<table class="pay">
    <tr>
        <th>Applicable fee</th>
        <td><?php echo $fee > 0 ? 'Rs. ' . number_format($fee, 2) : '_______________'; ?></td>
        <th>Amount paid</th>
        <td>_______________</td>
        <th>Outstanding</th>
        <td>_______________</td>
    </tr>
    <tr>
        <th>Receipt no.</th>
        <td colspan="2">______________________________</td>
        <th>Payment status</th>
        <td colspan="2">Unpaid / Partial / Paid</td>
    </tr>
</table>

<table class="foot">
    <tr>
        <td>
            <div class="box">
                <div class="box-h">Final registration confirmation</div>
                <div class="item"><span class="chk"></span>All required stations completed and registration confirmed</div>
            </div>
        </td>
        <td class="gap"></td>
        <td>
            <div class="box">
                <div class="box-h">Remarks</div>
                <div class="remarks">&nbsp;</div>
            </div>
        </td>
    </tr>
</table>

<table class="foot">
    <tr>
        <td>
            <div class="box">
                <div class="box-h">Registration officer</div>
                <div class="line"></div>
                <div class="subl">Name &amp; signature</div>
            </div>
        </td>
        <td class="gap"></td>
        <td>
            <div class="box">
                <div class="box-h">Registration coordinator</div>
                <div class="line"></div>
                <div class="subl">Name &amp; signature</div>
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    <?php echo $e($instName); ?> · Student Registration Checklist · One page A4
</div>

</body>
</html>
