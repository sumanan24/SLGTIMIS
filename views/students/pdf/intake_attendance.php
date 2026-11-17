<?php
declare(strict_types=1);
/**
 * One common A4 attendance sheet for the selected intake students.
 * Series No. on each row matches that student's checklist letter.
 *
 * @var list<array<string,mixed>> $students
 * @var string $logoSrc
 * @var array  $institute
 */
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$rows = is_array($students ?? null) ? $students : [];
usort($rows, static function (array $a, array $b): int {
    return ((int) ($a['series_no'] ?? 0)) <=> ((int) ($b['series_no'] ?? 0));
});
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$year = trim((string) ($rows[0]['academic_year'] ?? ''));
$departments = [];
foreach ($rows as $row) {
    $dept = trim((string) ($row['department'] ?? ''));
    if ($dept !== '') {
        $departments[$dept] = true;
    }
}
$departmentLine = count($departments) === 1 ? (string) array_key_first($departments) : '';
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Registration Attendance Sheet</title>
<style>
@page { size: A4 portrait; margin: 8mm 9mm 10mm; }
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
.lh .c-logo img { height: 12mm; width: auto; display: block; }
.lh .c-mid { width: 72%; text-align: center; padding: 0 2mm; }
.lh .c-side { width: 14%; text-align: right; }
.inst-name {
    font-size: 11pt; font-weight: bold; letter-spacing: 0.02em;
    text-transform: uppercase; color: #0b2545; margin-bottom: 0.5mm;
}
.inst-meta { font-size: 7.5pt; color: #475569; line-height: 1.3; }
.badge {
    display: inline-block; border: 0.8pt solid #0b2545; color: #0b2545;
    font-size: 7.5pt; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase;
    padding: 1.1mm 1.4mm; line-height: 1.2;
}
.rule { border: 0; border-top: 1.2pt solid #0b2545; margin: 0 0 2mm; height: 0; }
.title {
    text-align: center; font-size: 12pt; font-weight: bold; letter-spacing: 0.04em;
    text-transform: uppercase; margin: 0 0 0.6mm; color: #0b2545;
}
.sub { text-align: center; font-size: 8.5pt; color: #64748b; margin: 0 0 2.2mm; }
table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
table.grid th, table.grid td {
    border: 0.5pt solid #94a3b8;
    padding: 1.5mm 1.3mm;
    vertical-align: middle;
}
table.grid th {
    background: #0b2545; color: #fff;
    font-size: 7.5pt; font-weight: bold;
    text-transform: uppercase; text-align: left;
}
table.grid td { font-size: 8.5pt; }
table.grid tr { page-break-inside: avoid; }
table.grid thead { display: table-header-group; }
.sn { font-weight: bold; text-align: center; width: 12%; }
.name { font-weight: bold; }
.sig { width: 22%; height: 8mm; }
.alt td { background: #f4f7fb; }
.foot {
    margin-top: 3mm;
    font-size: 8pt;
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
        <td class="c-side"><span class="badge">Attendance<br>Sheet</span></td>
    </tr>
</table>
<hr class="rule">

<div class="title">Student Registration Attendance Sheet</div>
<div class="sub">
    Academic year:
    <strong><?php echo $year !== '' ? $e($year) : '____________________'; ?></strong>
    <?php if ($departmentLine !== ''): ?>
        · <?php echo $e($departmentLine); ?>
    <?php endif; ?>
    · Full department · Student count: <strong><?php echo (int) $total; ?></strong>
</div>

<table class="grid">
    <thead>
        <tr>
            <th class="sn">Series No.</th>
            <th style="width:18%;">Registration no.</th>
            <th>Student name</th>
            <th style="width:16%;">NIC</th>
            <th class="sig">Signature</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($rows === []): ?>
        <tr><td colspan="5" style="text-align:center;">No students selected.</td></tr>
    <?php else: ?>
        <?php foreach ($rows as $i => $row): ?>
        <tr class="<?php echo ($i % 2) === 1 ? 'alt' : ''; ?>">
            <td class="sn"><?php echo $e($row['series_no'] ?? ''); ?></td>
            <td><?php echo $e($row['student_id'] ?? ''); ?></td>
            <td class="name"><?php echo $e($row['name'] ?? ''); ?></td>
            <td><?php echo $e($row['nic'] ?? ''); ?></td>
            <td class="sig">&nbsp;</td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<div class="foot">
    <?php echo $e($instName); ?> · Common attendance sheet · Series No. matches the checklist letter
</div>

</body>
</html>
