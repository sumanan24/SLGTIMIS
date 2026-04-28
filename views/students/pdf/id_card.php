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
 */
$student = $student ?? [];
$enrollment = $enrollment ?? null;
$e = $e ?? static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

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
    .pad { padding: 10pt 10pt 9pt; }
    .round { border-radius: 16pt; border: 1pt solid rgba(15, 23, 42, .12); }

    .top-row { display: table; width: 100%; table-layout: fixed; }
    .top-row > div { display: table-cell; vertical-align: middle; }
    .cell-crest { width: 38pt; }
    .cell-logo { width: 98pt; text-align: right; }
    .crest img { width: 34pt; height: 34pt; }
    .brand { text-align: center; }
    .title { font-weight: 800; letter-spacing: .14em; font-size: 9pt; }
    .inst { font-weight: 900; font-size: 14.5pt; margin-top: 2pt; line-height: 1.05; }
    .sub { font-size: 7.2pt; color: #334155; margin-top: 2pt; line-height: 1.25; }
    .logo img { height: 20pt; width: auto; }

    .content { margin-top: 7pt; display: table; width: 100%; table-layout: fixed; }
    .content > div { display: table-cell; vertical-align: middle; }
    .cell-photo { width: 86pt; }
    .photo { width: 80pt; height: 80pt; border-radius: 12pt; border: 3pt solid #dbeafe; background: #e2e8f0; }
    .name { font-weight: 900; font-size: 13.5pt; margin: 0; line-height: 1.05; }
    .meta { margin-top: 2pt; font-size: 8.4pt; }
    .label { color: #64748b; font-weight: 800; letter-spacing: .08em; font-size: 7pt; text-transform: uppercase; }
    .val { font-weight: 900; font-size: 9.2pt; }
    .stack { margin-top: 3pt; }

    .bar { position: absolute; left: 9pt; right: 9pt; bottom: 8pt; height: 7pt; border-radius: 999pt; overflow: hidden; background: #e2e8f0; }
    .bar span { display: block; height: 7pt; float: left; }
    .bar .b1 { width: 24%; background: #0b0f16; }
    .bar .b2 { width: 38%; background: #e11d48; }
    .bar .b3 { width: 38%; background: #f59e0b; }

    /* Back */
    .back-grid { display: table; width: 100%; table-layout: fixed; margin-top: 6pt; }
    .back-grid > div { display: table-cell; vertical-align: top; }
    .cell-qr { width: 50%; }
    .scan { font-weight: 900; font-size: 15.5pt; margin-bottom: 6pt; }
    .qr { width: 86pt; height: 86pt; border-radius: 10pt; border: 5pt solid #fff; background: #fff; }
    .sec h4 { margin: 0 0 4pt; font-weight: 900; color: #64748b; font-size: 10pt; }
    .sec p { margin: 0; font-size: 7.6pt; color: #334155; line-height: 1.35; }
    .valid { margin-top: 8pt; }
    .sig { margin-top: 10pt; text-align: center; }
    .sig img { max-height: 20pt; max-width: 80pt; }
    .sigline { height: 1pt; background: #0f172a; opacity: .55; margin: 6pt auto 4pt; width: 70%; }
    .role { font-weight: 900; font-size: 8.2pt; }
    .backbar { position: absolute; left: 9pt; right: 9pt; bottom: 8pt; height: 8pt; border-radius: 999pt; background: #e2e8f0; }
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
            <div>
                <p class="name"><?php echo $e($studentName); ?></p>
                <div class="meta"><span class="label">ID:</span> <span class="val"><?php echo $e($studentId); ?></span></div>
                <div class="stack"><span class="label">NIC</span><br><span class="val"><?php echo $e($nic); ?></span></div>
                <div class="stack"><span class="label">Department</span><br><span class="val"><?php echo $e($dept); ?></span></div>
                <div class="stack"><span class="label">Course</span><br><span class="val"><?php echo $e($course); ?></span></div>
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

