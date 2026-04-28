<?php
/**
 * Student ID card — PDF (2 pages: front then back). Paper size is set by controller.
 *
 * Variables provided:
 * - $student, $enrollment
 * - $qrDataUri (png data uri)
 * - $profileDataUri (image data uri or null)
 * - $logoDataUri, $crestDataUri
 * - $principalSigDataUri (png data uri or null)
 * - $enrollDateDmy, $expiryDateDmy
 * - $e (escape callable)
 * - $style array{name:int,label:int,value:int,qr:int}
 */
$student = $student ?? [];
$enrollment = $enrollment ?? null;
$e = $e ?? static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$style = is_array($style ?? null) ? $style : ['name' => 13, 'label' => 7, 'value' => 9, 'qr' => 88];
// Map preview px-ish spacing controls to pt-ish values for PDF.
$padPt = isset($style['pad']) ? max(7, min(12, (int) round(((int) $style['pad']) * 0.55))) : 9;
$gapPt = isset($style['gap']) ? max(4, min(10, (int) round(((int) $style['gap']) * 0.6))) : 7;
$stackPt = isset($style['stack']) ? max(1, min(4, (int) round(((int) $style['stack']) * 0.8))) : 2;
$barPt = isset($style['bar']) ? max(6, min(12, (int) round(((int) $style['bar']) * 0.6))) : 8;
$photoWPt = isset($style['photo_w']) ? max(54, min(88, (int) round(((int) $style['photo_w']) * 0.62))) : 76;
$photoHPt = isset($style['photo_h']) ? max(54, min(88, (int) round(((int) $style['photo_h']) * 0.62))) : 76;

$studentName = (string) ($student['student_fullname'] ?? '');
$studentId = (string) ($student['student_id'] ?? '');
$nic = (string) ($student['student_nic'] ?? '');
$dept = (string) (($enrollment['department_name'] ?? '') ?: '');
$course = (string) (($enrollment['course_name'] ?? '') ?: '');
?>

<style>
    @page { margin: 0; }
    body { margin: 0; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #0f172a; }
    .card { width: 100%; height: 100%; position: relative; background: #fff; overflow: hidden; }
    .pad { padding: <?php echo (int)$padPt; ?>pt <?php echo (int)$padPt; ?>pt <?php echo (int)($padPt - 1); ?>pt; }
    .round { border-radius: 16pt; border: 1pt solid rgba(15, 23, 42, .12); }

    .top-row { display: table; width: 100%; table-layout: fixed; }
    .top-row > div { display: table-cell; vertical-align: middle; }
    .cell-crest { width: 38pt; }
    .cell-logo { width: 88pt; text-align: right; }
    .crest img { width: 34pt; height: 34pt; }
    .brand { text-align: center; }
    .title { font-weight: 800; letter-spacing: .14em; font-size: 9pt; }
    .inst { font-weight: 900; font-size: 13.6pt; margin-top: 2pt; line-height: 1.06; }
    .sub { font-size: 7.2pt; color: #334155; margin-top: 2pt; line-height: 1.25; }
    .logo img { height: 18pt; width: auto; }

    .content { margin-top: 7pt; display: table; width: 100%; table-layout: fixed; }
    .content > div { display: table-cell; vertical-align: middle; }
    .cell-photo { width: <?php echo (int)($photoWPt + 6); ?>pt; }
    .photo { width: <?php echo (int)$photoWPt; ?>pt; height: <?php echo (int)$photoHPt; ?>pt; border-radius: 12pt; border: 3pt solid #dbeafe; background: #e2e8f0; }
    .info { padding-top: 1pt; }
    .name { font-weight: 900; font-size: <?php echo (int)($style['name'] ?? 13); ?>pt; margin: 0; line-height: 1.04; }
    .meta { margin-top: 1.2pt; font-size: 8.4pt; line-height: 1.12; }
    .label { color: #64748b; font-weight: 800; letter-spacing: .08em; font-size: <?php echo (int)($style['label'] ?? 7); ?>pt; text-transform: uppercase; }
    .val { font-weight: 900; font-size: <?php echo (int)($style['value'] ?? 9); ?>pt; word-break: break-word; }
    /* Dompdf doesn't support line-clamp well; use a fixed height cut-off */
    .val-clamp { display: block; overflow: hidden; line-height: 1.12; max-height: 21pt; }
    .stack { margin-top: <?php echo (int)$stackPt; ?>pt; }

    .bar { position: absolute; left: 9pt; right: 9pt; bottom: <?php echo (int)$barPt; ?>pt; height: 7pt; border-radius: 999pt; overflow: hidden; background: #e2e8f0; }
    .bar span { display: block; height: 7pt; float: left; }
    .bar .b1 { width: 24%; background: #0b0f16; }
    .bar .b2 { width: 38%; background: #e11d48; }
    .bar .b3 { width: 38%; background: #f59e0b; }

    /* Back */
    .back-grid { display: table; width: 100%; table-layout: fixed; margin-top: 6pt; }
    .back-grid > div { display: table-cell; vertical-align: top; }
    .cell-qr { width: 46%; }
    .scan { font-weight: 900; font-size: 14.6pt; margin-bottom: 5pt; line-height: 1.05; }
    .qr { width: <?php echo (int)($style['qr'] ?? 88); ?>pt; height: <?php echo (int)($style['qr'] ?? 88); ?>pt; border-radius: 10pt; border: 5pt solid #fff; background: #fff; }
    .sec h4 { margin: 0 0 4pt; font-weight: 900; color: #64748b; font-size: 10pt; }
    .sec p { margin: 0; font-size: 7.4pt; color: #334155; line-height: 1.33; }
    .valid { margin-top: 7pt; }
    .sig { margin-top: 8pt; text-align: center; }
    .sig img { max-height: 20pt; max-width: 80pt; }
    .sigline { height: 1pt; background: #0f172a; opacity: .55; margin: 6pt auto 4pt; width: 70%; }
    .role { font-weight: 900; font-size: 8.2pt; }
    .backbar { position: absolute; left: 9pt; right: 9pt; bottom: <?php echo (int)$barPt; ?>pt; height: 8pt; border-radius: 999pt; background: #e2e8f0; }
    .backbar::before { content: ""; display: block; width: 100%; height: 8pt; background: linear-gradient(90deg, #fde68a, #fecaca, #c7d2fe); opacity: .85; }

    .page-break { page-break-after: always; }
</style>

<!-- Front -->
<div class="card round page-break">
    <div class="pad">
        <div class="top-row">
            <div class="cell-crest">
                <div class="crest"><img src="<?php echo $e($crestDataUri ?? ''); ?>" alt=""></div>
            </div>
            <div>
                <div class="brand">
                    <div class="title">STUDENT IDENTITY CARD</div>
                    <div class="inst">Sri Lanka German Training Institute</div>
                    <div class="sub">Ministry of Education, Higher Education and Vocational Education<br>Vocational Education Division</div>
                </div>
            </div>
            <div class="cell-logo">
                <div class="logo"><img src="<?php echo $e($logoDataUri ?? ''); ?>" alt="SLGTI"></div>
            </div>
        </div>

        <div class="content">
            <div class="cell-photo">
                <?php if (!empty($profileDataUri)): ?>
                    <img class="photo" src="<?php echo $e($profileDataUri); ?>" alt="">
                <?php else: ?>
                    <div class="photo"></div>
                <?php endif; ?>
            </div>
            <div class="info">
                <p class="name"><?php echo $e($studentName); ?></p>
                <div class="meta"><span class="label">ID:</span> <span class="val"><?php echo $e($studentId); ?></span></div>
                <div class="stack"><span class="label">NIC</span><br><span class="val"><?php echo $e($nic); ?></span></div>
                <div class="stack"><span class="label">Department</span><br><span class="val val-clamp"><?php echo $e($dept); ?></span></div>
                <div class="stack"><span class="label">Course</span><br><span class="val val-clamp"><?php echo $e($course); ?></span></div>
            </div>
        </div>
    </div>
    <div class="bar"><span class="b1"></span><span class="b2"></span><span class="b3"></span></div>
</div>

<!-- Back -->
<div class="card round">
    <div class="pad">
        <div class="back-grid">
            <div class="cell-qr">
                <div class="scan">Scan to Verify</div>
                <img class="qr" src="<?php echo $e($qrDataUri ?? ''); ?>" alt="QR">
            </div>
            <div class="sec">
                <h4>Instructions</h4>
                <p>This ID card is the property of SLGTI. If you find it, please hand over to SLGTI. Do not fold, bend, or punch the QR code. It should be returned after completing the course.</p>
                <div class="valid">
                    <h4>Validity</h4>
                    <p>Enroll Date: <?php echo $e($enrollDateDmy ?? ''); ?><br>Expire Date: <?php echo $e($expiryDateDmy ?? ''); ?></p>
                </div>
                <div class="sig">
                    <?php if (!empty($principalSigDataUri)): ?>
                        <img src="<?php echo $e($principalSigDataUri); ?>" alt="Signature">
                    <?php else: ?>
                        <div style="height: 20pt;"></div>
                    <?php endif; ?>
                    <div class="sigline"></div>
                    <div class="role">Principal</div>
                </div>
            </div>
        </div>
    </div>
    <div class="backbar"></div>
</div>

