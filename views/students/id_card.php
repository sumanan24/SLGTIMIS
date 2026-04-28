<?php
/** @var array $student */
/** @var array|null $enrollment */
/** @var string|null $profileImageUrl */
/** @var string $qrDataUri */
/** @var string $verifyUrl */
/** @var string $enrollDateDmy */
/** @var string $expiryDateDmy */
/** @var string $downloadUrl */
$student = $student ?? [];
$enrollment = $enrollment ?? null;
$profileImageUrl = $profileImageUrl ?? null;
$qrDataUri = (string) ($qrDataUri ?? '');
$verifyUrl = (string) ($verifyUrl ?? '');
$downloadUrl = (string) ($downloadUrl ?? '');

$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$studentName = (string) ($student['student_fullname'] ?? '');
$studentId = (string) ($student['student_id'] ?? '');
$nic = (string) ($student['student_nic'] ?? '');
$dept = (string) (($enrollment['department_name'] ?? '') ?: '');
$course = (string) (($enrollment['course_name'] ?? '') ?: '');
?>

<style>
    .idcard-wrap { max-width: 1020px; margin: 0 auto; }
    .idcard-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .idcard-toolbar .btn { border-radius: 999px; }
    .idcard-stage { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }

    /* Card (screen preview) */
    .id-card {
        width: 100%;
        max-width: 520px;
        aspect-ratio: 85.6 / 54;
        background: #fff;
        border-radius: 22px;
        border: 2px solid rgba(15, 23, 42, .08);
        box-shadow: 0 16px 40px rgba(2, 6, 23, .12);
        overflow: hidden;
        position: relative;
        margin: 0 auto;
    }
    .id-card .pad { padding: 18px 18px 14px; }
    .id-card .top-row { display: grid; grid-template-columns: 46px 1fr 130px; gap: 10px; align-items: center; }
    .id-card .crest { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #ef4444); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .id-card .brand { text-align: center; }
    .id-card .brand .title { font-weight: 800; letter-spacing: .12em; font-size: 13px; color: #0f172a; }
    .id-card .brand .inst { font-weight: 800; font-size: 20px; margin-top: 3px; color: #0f172a; line-height: 1.05; }
    .id-card .brand .sub { margin-top: 3px; font-size: 11px; color: #334155; line-height: 1.25; }
    .id-card .logo { text-align: right; font-weight: 800; color: #111827; font-size: 18px; }
    .id-card .logo small { display:block; font-weight: 700; letter-spacing: .08em; font-size: 9px; color: #ef4444; margin-top: 2px; }

    .id-card .content { display: grid; grid-template-columns: 132px 1fr; gap: 14px; margin-top: 12px; align-items: center; }
    .id-card .photo {
        width: 132px; height: 132px; border-radius: 16px;
        border: 4px solid #dbeafe;
        background: #e2e8f0;
        object-fit: cover;
        box-shadow: 0 10px 22px rgba(2, 6, 23, .12);
    }
    .id-card .photo-placeholder { width: 132px; height: 132px; border-radius: 16px; border: 4px solid #dbeafe; background: #e2e8f0; display:flex; align-items:center; justify-content:center; color:#475569; font-size: 42px; }
    .id-card .name { font-weight: 900; font-size: 22px; margin: 0; color: #0f172a; line-height: 1.05; }
    .id-card .meta { margin-top: 4px; color: #0f172a; font-size: 14px; }
    .id-card .meta .label { color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; font-size: 11px; }
    .id-card .meta .val { font-weight: 800; }
    .id-card .stack { margin-top: 6px; }
    .id-card .stack .label { color:#64748b; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; font-size: 11px; display:block; }
    .id-card .stack .val { font-weight: 800; font-size: 16px; color: #0f172a; }

    .id-card .bar {
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: 14px;
        height: 10px;
        border-radius: 999px;
        overflow: hidden;
        background: #e2e8f0;
        display: grid;
        grid-template-columns: 24% 38% 38%;
    }
    .id-card .bar span:nth-child(1) { background: #0b0f16; }
    .id-card .bar span:nth-child(2) { background: #e11d48; }
    .id-card .bar span:nth-child(3) { background: #f59e0b; }

    /* Back */
    .id-card.back .pad { padding: 18px 18px 14px; }
    .id-card .back-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start; margin-top: 8px; }
    .id-card .scan-title { font-weight: 900; font-size: 26px; color: #0f172a; }
    .id-card .qr { width: 182px; height: 182px; border-radius: 12px; border: 6px solid #fff; box-shadow: 0 10px 22px rgba(2, 6, 23, .12); background: #fff; }
    .id-card .back h4 { margin: 0 0 8px; font-weight: 900; color:#64748b; }
    .id-card .back p { margin: 0; font-size: 12px; color:#334155; line-height: 1.35; }
    .id-card .valid { margin-top: 14px; }
    .id-card .sig { margin-top: 18px; text-align: center; }
    .id-card .sig .line { height: 1px; background: #0f172a; opacity: .6; margin: 8px auto 6px; width: 70%; }
    .id-card .sig .role { font-weight: 800; color:#0f172a; }
    .id-card .backbar {
        position: absolute; left: 16px; right: 16px; bottom: 14px;
        height: 12px; border-radius: 999px; background: linear-gradient(90deg, #fde68a, #fecaca, #c7d2fe);
        opacity: .85;
    }
</style>

<div class="idcard-wrap">
    <div class="idcard-toolbar">
        <div>
            <div class="h5 mb-0 fw-bold">Student ID Card</div>
            <div class="text-muted small">Front & back preview — QR links to verification page</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($studentId); ?>">
                <i class="fas fa-arrow-left me-1"></i> Back to student
            </a>
            <a class="btn btn-primary" href="<?php echo $e($downloadUrl); ?>">
                <i class="fas fa-download me-1"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="idcard-stage">
        <!-- Front -->
        <div class="id-card">
            <div class="pad">
                <div class="top-row">
                    <div class="crest">SL</div>
                    <div class="brand">
                        <div class="title">STUDENT IDENTITY CARD</div>
                        <div class="inst">Sri Lanka German Training Institute</div>
                        <div class="sub">Ministry of Education, Higher Education and Vocational Education<br>Vocational Education Division</div>
                    </div>
                    <div class="logo">SLGTI<small>Sri Lanka German Training Institute</small></div>
                </div>

                <div class="content">
                    <?php if ($profileImageUrl): ?>
                        <img class="photo" src="<?php echo $e($profileImageUrl); ?>" alt="">
                    <?php else: ?>
                        <div class="photo-placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>

                    <div>
                        <p class="name"><?php echo $e($studentName); ?></p>
                        <div class="meta">
                            <span class="label">ID:</span>
                            <span class="val"><?php echo $e($studentId); ?></span>
                        </div>
                        <div class="stack">
                            <span class="label">NIC</span>
                            <span class="val"><?php echo $e($nic); ?></span>
                        </div>
                        <div class="stack">
                            <span class="label">Department</span>
                            <span class="val"><?php echo $e($dept); ?></span>
                        </div>
                        <div class="stack">
                            <span class="label">Course</span>
                            <span class="val"><?php echo $e($course); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bar"><span></span><span></span><span></span></div>
        </div>

        <!-- Back -->
        <div class="id-card back">
            <div class="pad">
                <div class="back-grid">
                    <div>
                        <div class="scan-title">Scan to Verify</div>
                        <img class="qr mt-2" src="<?php echo $e($qrDataUri); ?>" alt="QR">
                        <div class="small text-muted mt-2" style="word-break: break-all;">
                            <a href="<?php echo $e($verifyUrl); ?>" target="_blank" rel="noopener"><?php echo $e($verifyUrl); ?></a>
                        </div>
                    </div>
                    <div class="back">
                        <h4>Instructions</h4>
                        <p>This ID card is the property of SLGTI. If you find it, please hand over to SLGTI. Do not fold, bend, or punch the QR code. It should be returned after completing the course.</p>
                        <div class="valid">
                            <h4>Validity</h4>
                            <p>Enroll Date: <?php echo $e($enrollDateDmy); ?><br>Expire Date: <?php echo $e($expiryDateDmy); ?></p>
                        </div>
                        <div class="sig">
                            <div style="height: 38px;"></div>
                            <div class="line"></div>
                            <div class="role">Principal</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="backbar"></div>
        </div>
    </div>
</div>

