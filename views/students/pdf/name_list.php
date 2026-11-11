<?php
/**
 * A4 student name list PDF.
 *
 * @var list<array{student_id:string,name:string,department:string,course:string,academic_year:string}> $rows
 * @var array{academic_year:string,department:string,course:string,generated_at:string,total:int} $meta
 * @var callable $e HTML escaper
 */
$rows = $rows ?? [];
$meta = is_array($meta ?? null) ? $meta : [];
$e = $e ?? static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$total = (int) ($meta['total'] ?? count($rows));
?>
<style>
    @page { margin: 12mm 12mm 14mm 12mm; }
    body {
        margin: 0;
        padding: 0;
        font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
        color: #111;
        font-size: 10pt;
    }
    .hdr {
        border-bottom: 2px solid #111;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }
    .hdr .inst {
        font-size: 13pt;
        font-weight: bold;
        letter-spacing: 0.2px;
    }
    .hdr .sub {
        font-size: 11pt;
        font-weight: bold;
        margin-top: 2px;
    }
    .meta {
        font-size: 9pt;
        margin-top: 4px;
        line-height: 1.45;
    }
    .meta span { white-space: nowrap; margin-right: 14px; }
    table.list {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }
    table.list th,
    table.list td {
        border: 0.5pt solid #333;
        padding: 4px 5px;
        vertical-align: top;
    }
    table.list th {
        background: #f0f0f0;
        font-size: 9pt;
        text-align: left;
    }
    table.list td {
        font-size: 9.5pt;
    }
    table.list .chk { width: 28px; text-align: center; }
    table.list .n { width: 28px; text-align: center; }
    table.list .id { width: 95px; }
    table.list .nm { width: auto; font-weight: bold; }
    table.list .dept { width: 110px; }
    table.list .crs { width: 150px; }
    .tick-box {
        display: inline-block;
        width: 11px;
        height: 11px;
        border: 1.2pt solid #111;
        vertical-align: middle;
    }
    .foot {
        margin-top: 10px;
        font-size: 8.5pt;
        color: #444;
    }
</style>

<div class="hdr">
    <div class="inst">Sri Lanka German Training Institute</div>
    <div class="sub">Student Name List</div>
    <div class="meta">
        <?php if (!empty($meta['academic_year'])): ?>
            <span><strong>Academic Year:</strong> <?php echo $e($meta['academic_year']); ?></span>
        <?php endif; ?>
        <?php if (!empty($meta['department'])): ?>
            <span><strong>Department:</strong> <?php echo $e($meta['department']); ?></span>
        <?php endif; ?>
        <?php if (!empty($meta['course'])): ?>
            <span><strong>Course:</strong> <?php echo $e($meta['course']); ?></span>
        <?php endif; ?>
        <span><strong>Total:</strong> <?php echo $e((string) $total); ?></span>
        <?php if (!empty($meta['generated_at'])): ?>
            <span><strong>Generated:</strong> <?php echo $e($meta['generated_at']); ?></span>
        <?php endif; ?>
    </div>
</div>

<table class="list">
    <thead>
        <tr>
            <th class="chk">✓</th>
            <th class="n">#</th>
            <th class="id">Student ID</th>
            <th class="nm">Student Name</th>
            <th class="dept">Department</th>
            <th class="crs">Course</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $i => $row): ?>
            <tr>
                <td class="chk"><span class="tick-box">&nbsp;</span></td>
                <td class="n"><?php echo $e((string) ($i + 1)); ?></td>
                <td class="id"><?php echo $e($row['student_id'] ?? ''); ?></td>
                <td class="nm"><?php echo $e($row['name'] ?? ''); ?></td>
                <td class="dept"><?php echo $e($row['department'] ?? ''); ?></td>
                <td class="crs"><?php echo $e($row['course'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="foot">Current students only (Following / Internship). Printed from SLGTI MIS.</div>
