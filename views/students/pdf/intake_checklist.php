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

$seriesNo = trim((string) ($s['series_no'] ?? ''));
$stations = [
    ['no' => '01', 'title' => 'NIC & Registration Verification', 'text' =>
        'National Identity Card verified, admission letter checked, student details verified, and the course and NVQ level confirmed.'],
    ['no' => '02', 'title' => 'Document Verification & Personal File', 'text' =>
        'NIC copy and educational certificates collected, required documents verified, and the personal file opened and labelled.'],
    ['no' => '03', 'title' => 'Financial Clearance', 'payment' => true, 'text' =>
        'Province and NVQ level verified, correct registration fee calculated, payment received, receipt issued, and payment recorded in the MIS.'],
    ['no' => '04', 'title' => 'Student Agreement & Code of Conduct', 'text' =>
        'Student agreement provided, institute rules explained, and signatures obtained from the student and the parent or guardian.'],
    ['no' => '05', 'title' => 'Student ID Photo Taking', 'text' =>
        'Student ID photograph captured, verified, and uploaded to the MIS.'],
    ['no' => '06', 'title' => 'Hostel Allocation & Agreement', 'text' =>
        'Hostel requirement confirmed, hostel request form collected, hostel availability checked, room or bed allocated, and hostel agreement signed.'],
    ['no' => '07', 'title' => 'Orientation Pack & File Collection', 'text' =>
        'Student handbook and record book distributed, orientation schedule provided, personal file checked, and final registration confirmed.'],
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
@page { size: A4 portrait; margin: 8mm 9mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { margin: 0; padding: 0; background: #fff; }
body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 9pt;
    color: #0b2545;
    line-height: 1.25;
}

.lh { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 2mm; }
.lh td { padding: 0; vertical-align: middle; }
.lh .c-logo { width: 14%; }
.lh .c-logo img { height: 13mm; width: auto; display: block; }
.lh .c-mid { width: 62%; text-align: center; padding: 0 2mm; }
.lh .c-side { width: 24%; text-align: right; }
.series-lab {
    font-size: 7.5pt; font-weight: bold; letter-spacing: 0.06em;
    text-transform: uppercase; color: #64748b; margin-bottom: 0.3mm;
}
.series-no {
    font-size: 14pt; font-weight: bold; letter-spacing: 0.06em;
    color: #0b2545; line-height: 1; margin-bottom: 1mm;
}
.inst-name {
    font-size: 11pt; font-weight: bold; letter-spacing: 0.02em;
    text-transform: uppercase; color: #0b2545; margin-bottom: 0.6mm;
}
.inst-meta { font-size: 7.5pt; color: #475569; line-height: 1.3; }
.badge {
    display: inline-block; border: 0.8pt solid #0b2545; color: #0b2545;
    font-size: 7.5pt; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase;
    padding: 1.1mm 1.6mm; line-height: 1.2;
}
.rule { border: 0; border-top: 1.2pt solid #0b2545; margin: 0 0 2mm; height: 0; }
.title {
    text-align: center; font-size: 12pt; font-weight: bold; letter-spacing: 0.04em;
    text-transform: uppercase; margin: 0 0 0.8mm; color: #0b2545;
}
.sub { text-align: center; font-size: 8.5pt; color: #64748b; margin: 0 0 2.4mm; }

table.info { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 2.4mm; }
table.info th, table.info td {
    border: 0.5pt solid #94a3b8;
    padding: 1.5mm 1.6mm;
    vertical-align: middle;
}
table.info th {
    width: 16%; background: #e8eef8; color: #334155;
    font-size: 7.5pt; font-weight: bold;
    text-transform: uppercase; text-align: left;
}
table.info td { width: 34%; font-size: 9pt; font-weight: bold; color: #0b2545; }

table.cols { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 2mm; }
table.cols > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
table.cols td.col-l { padding-right: 1.6mm; }
table.cols td.col-r { padding-left: 1.6mm; }

.sec {
    border: 0.6pt solid #0b2545;
    margin: 0 0 1.8mm;
}
.sec-h {
    background: #0b2545; color: #fff;
    font-size: 8pt; font-weight: bold;
    letter-spacing: 0.02em; text-transform: uppercase;
    padding: 1.2mm 1.6mm;
}
.sec-b { padding: 1.3mm 1.6mm 1.5mm; }
.item {
    font-size: 8.5pt;
    padding: 0.2mm 0;
    line-height: 1.28;
}
.chk {
    display: inline-block;
    width: 3.6mm; height: 3.6mm;
    border: 0.7pt solid #0b2545;
    margin: 0.4mm 1.5mm 0 0;
    vertical-align: top;
    background: #fff;
}

table.pay { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 1.2mm; }
table.pay th, table.pay td {
    border: 0.4pt solid #94a3b8;
    padding: 0.8mm 1mm;
    font-size: 7.5pt;
    vertical-align: middle;
}
table.pay th {
    width: 42%; background: #e8eef8; text-align: left; font-weight: bold;
    color: #334155; text-transform: uppercase; font-size: 6.8pt;
}
table.pay td { font-weight: bold; }

.footer {
    margin-top: 1mm;
    padding-top: 1.2mm;
    border-top: 0.6pt solid #0b2545;
    font-size: 7.5pt;
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
        <td class="c-side">
            <div class="series-lab">Series No.</div>
            <div class="series-no"><?php echo $seriesNo !== '' ? $e($seriesNo) : '________'; ?></div>
            <span class="badge">Checklist<br>Letter</span>
        </td>
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
                    <div class="item"><span class="chk"></span><?php echo $e($st['text']); ?></div>
                    <?php if (!empty($st['payment'])): ?>
                    <table class="pay">
                        <tr>
                            <th>Applicable fee</th>
                            <td><?php echo $fee > 0 ? 'Rs. ' . number_format($fee, 2) : '_______________'; ?></td>
                        </tr>
                        <tr>
                            <th>Amount paid</th>
                            <td>_______________</td>
                        </tr>
                        <tr>
                            <th>Outstanding</th>
                            <td>_______________</td>
                        </tr>
                        <tr>
                            <th>Receipt no.</th>
                            <td>_______________</td>
                        </tr>
                        <tr>
                            <th>Payment method</th>
                            <td>Cash / Bank / Online transfer</td>
                        </tr>
                        <tr>
                            <th>Payment status</th>
                            <td>Unpaid / Partial / Paid</td>
                        </tr>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </td>
        <td class="col-r">
            <?php foreach ($right as $st): ?>
            <div class="sec">
                <div class="sec-h">Station <?php echo $e($st['no']); ?> — <?php echo $e($st['title']); ?></div>
                <div class="sec-b">
                    <div class="item"><span class="chk"></span><?php echo $e($st['text']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </td>
    </tr>
</table>

<div class="footer">
    <?php echo $e($instName); ?> · Student Registration Checklist
</div>

</body>
</html>
