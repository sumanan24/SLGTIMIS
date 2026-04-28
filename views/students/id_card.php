<?php
/** @var array $student */
/** @var array|null $enrollment */
/** @var string|null $profileImageUrl */
/** @var string $qrDataUri */
/** @var string $verifyUrl */
/** @var string $enrollDateDmy */
/** @var string $expiryDateDmy */
/** @var string $downloadUrl */
/** @var string $downloadZipUrl */
/** @var array $style */
$student = $student ?? [];
$enrollment = $enrollment ?? null;
$profileImageUrl = $profileImageUrl ?? null;
$qrDataUri = (string) ($qrDataUri ?? '');
$verifyUrl = (string) ($verifyUrl ?? '');
$downloadUrl = (string) ($downloadUrl ?? '');
$downloadZipUrl = (string) ($downloadZipUrl ?? '');
$style = is_array($style ?? null) ? $style : ['name' => 20, 'label' => 10, 'value' => 15, 'qr' => 176];

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
    :root{
        --card-pad-x: <?php echo (int)($style['pad'] ?? 16); ?>px;
        --card-pad-y: <?php echo (int)($style['pad'] ?? 16); ?>px;
        --card-text: #0f172a;
        --card-muted: #64748b;
        --card-sub: #334155;
        --fs-name: <?php echo (int)($style['name'] ?? 20); ?>px;
        --fs-label: <?php echo (int)($style['label'] ?? 10); ?>px;
        --fs-value: <?php echo (int)($style['value'] ?? 15); ?>px;
        --qr-size: <?php echo (int)($style['qr'] ?? 176); ?>px;
        --content-gap: <?php echo (int)($style['gap'] ?? 12); ?>px;
        --stack-gap: <?php echo (int)($style['stack'] ?? 3); ?>px;
        --bar-bottom: <?php echo (int)($style['bar'] ?? 14); ?>px;
        --photo-w: <?php echo (int)($style['photo_w'] ?? 124); ?>px;
        --photo-h: <?php echo (int)($style['photo_h'] ?? 124); ?>px;
    }
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
    .id-card .pad { padding: var(--card-pad-y) var(--card-pad-x) 24px; }
    .id-card .top-row { display: grid; grid-template-columns: 44px 1fr 118px; gap: 10px; align-items: center; }
    .id-card .crest { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #ef4444); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .id-card .brand { text-align: center; }
    .id-card .brand .title { font-weight: 800; letter-spacing: .12em; font-size: 12px; color: var(--card-text); }
    .id-card .brand .inst { font-weight: 900; font-size: 18px; margin-top: 2px; color: var(--card-text); line-height: 1.06; }
    .id-card .brand .sub { margin-top: 2px; font-size: 10px; color: var(--card-sub); line-height: 1.25; }
    .id-card .logo { text-align: right; font-weight: 900; color: #111827; font-size: 16px; line-height: 1.05; }
    .id-card .logo small { display:block; font-weight: 800; letter-spacing: .08em; font-size: 8px; color: #ef4444; margin-top: 2px; }

    .id-card .content { display: grid; grid-template-columns: var(--photo-w) 1fr; gap: var(--content-gap); margin-top: 10px; align-items: start; }
    .id-card .content .info { padding-top: 2px; min-width: 0; }
    .id-card .photo {
        width: var(--photo-w); height: var(--photo-h); border-radius: 16px;
        border: 4px solid #dbeafe;
        background: #e2e8f0;
        object-fit: cover;
        box-shadow: 0 10px 22px rgba(2, 6, 23, .12);
    }
    .id-card .photo-placeholder { width: var(--photo-w); height: var(--photo-h); border-radius: 16px; border: 4px solid #dbeafe; background: #e2e8f0; display:flex; align-items:center; justify-content:center; color:#475569; font-size: 40px; }
    .id-card .name {
        font-weight: 900;
        font-size: var(--fs-name);
        margin: 0;
        color: var(--card-text);
        line-height: 1.04;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .id-card .meta { margin-top: 2px; color: var(--card-text); font-size: 13px; line-height: 1.15; }
    .id-card .meta .label { color: var(--card-muted); font-weight: 900; text-transform: uppercase; letter-spacing: .08em; font-size: var(--fs-label); }
    .id-card .meta .val { font-weight: 800; }
    .id-card .stack { margin-top: var(--stack-gap); }
    .id-card .stack .label { color:var(--card-muted); font-weight: 900; text-transform: uppercase; letter-spacing: .08em; font-size: var(--fs-label); display:block; }
    .id-card .stack .val { font-weight: 800; font-size: var(--fs-value); color: var(--card-text); line-height: 1.08; }
    .id-card .stack .val { word-break: break-word; }
    .id-card .stack .val.clamp-2{
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .id-card .bar {
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: var(--bar-bottom);
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
    .id-card.back .pad { padding: var(--card-pad-y) var(--card-pad-x) 22px; }
    .id-card .back-grid { display: grid; grid-template-columns: 200px 1fr; gap: 14px; align-items: start; margin-top: 6px; }
    .id-card .scan-title { font-weight: 900; font-size: 24px; color: var(--card-text); line-height: 1.05; }
    .id-card .qr { width: var(--qr-size); height: var(--qr-size); border-radius: 12px; border: 6px solid #fff; box-shadow: 0 10px 22px rgba(2, 6, 23, .12); background: #fff; display: block; }
    .id-card .back h4 { margin: 0 0 6px; font-weight: 900; color:var(--card-muted); font-size: 14px; }
    .id-card .back p { margin: 0; font-size: 11.5px; color:var(--card-sub); line-height: 1.35; }
    .id-card .valid { margin-top: 10px; }
    .id-card .sig { margin-top: 14px; text-align: center; }
    .id-card .sig .line { height: 1px; background: #0f172a; opacity: .6; margin: 8px auto 6px; width: 70%; }
    .id-card .sig .role { font-weight: 800; color:#0f172a; }
    .id-card .backbar {
        position: absolute; left: 16px; right: 16px; bottom: var(--bar-bottom);
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
            <button class="btn btn-outline-primary" type="button" id="btnPngFront">
                <i class="fas fa-image me-1"></i> Front PNG
            </button>
            <button class="btn btn-outline-primary" type="button" id="btnPngBack">
                <i class="fas fa-image me-1"></i> Back PNG
            </button>
            <button class="btn btn-outline-primary" type="button" id="btnPngZip">
                <i class="fas fa-file-zipper me-1"></i> PNG ZIP
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="max-width: 1020px;">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Name size</label>
                    <input id="ctlName" type="range" class="form-range" min="14" max="26" step="1" value="<?php echo (int)($style['name'] ?? 20); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Label size</label>
                    <input id="ctlLabel" type="range" class="form-range" min="8" max="14" step="1" value="<?php echo (int)($style['label'] ?? 10); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Value size</label>
                    <input id="ctlValue" type="range" class="form-range" min="10" max="18" step="1" value="<?php echo (int)($style['value'] ?? 15); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">QR size</label>
                    <input id="ctlQr" type="range" class="form-range" min="140" max="220" step="2" value="<?php echo (int)($style['qr'] ?? 176); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Photo width</label>
                    <input id="ctlPhotoW" type="range" class="form-range" min="90" max="150" step="2" value="<?php echo (int)($style['photo_w'] ?? 124); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Photo height</label>
                    <input id="ctlPhotoH" type="range" class="form-range" min="90" max="150" step="2" value="<?php echo (int)($style['photo_h'] ?? 124); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Card padding</label>
                    <input id="ctlPad" type="range" class="form-range" min="12" max="20" step="1" value="<?php echo (int)($style['pad'] ?? 16); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Photo ↔ text gap</label>
                    <input id="ctlGap" type="range" class="form-range" min="8" max="16" step="1" value="<?php echo (int)($style['gap'] ?? 12); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Row spacing</label>
                    <input id="ctlStack" type="range" class="form-range" min="1" max="6" step="1" value="<?php echo (int)($style['stack'] ?? 3); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Bottom bar margin</label>
                    <input id="ctlBar" type="range" class="form-range" min="10" max="18" step="1" value="<?php echo (int)($style['bar'] ?? 14); ?>">
                </div>
            </div>
            <div class="d-flex gap-2 mt-2">
                <button id="ctlApply" type="button" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-wand-magic-sparkles me-1"></i> Apply to downloads
                </button>
                <span class="text-muted small align-self-center">Downloads will use these sizes.</span>
            </div>
        </div>
    </div>

    <div class="idcard-stage">
        <!-- Front -->
        <div class="id-card" id="idcardFront">
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

                    <div class="info">
                        <p class="name"><?php echo $e($studentName); ?></p>
                        <div class="meta">
                            <span class="label">ID:</span>
                            <span class="val"><?php echo $e($studentId); ?></span>
                        </div>
                        <div class="stack stack-nic">
                            <span class="label">NIC</span>
                            <span class="val"><?php echo $e($nic); ?></span>
                        </div>
                        <div class="stack stack-dept">
                            <span class="label">Department</span>
                            <span class="val clamp-2"><?php echo $e($dept); ?></span>
                        </div>
                        <div class="stack stack-course">
                            <span class="label">Course</span>
                            <span class="val clamp-2"><?php echo $e($course); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bar"><span></span><span></span><span></span></div>
        </div>

        <!-- Back -->
        <div class="id-card back" id="idcardBack">
            <div class="pad">
                <div class="back-grid">
                    <div>
                        <div class="scan-title">Scan to Verify</div>
                        <img class="qr mt-2" src="<?php echo $e($qrDataUri); ?>" alt="QR">
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

<script>
(() => {
    const root = document.documentElement;
    const ctlName = document.getElementById('ctlName');
    const ctlLabel = document.getElementById('ctlLabel');
    const ctlValue = document.getElementById('ctlValue');
    const ctlQr = document.getElementById('ctlQr');
    const ctlPhotoW = document.getElementById('ctlPhotoW');
    const ctlPhotoH = document.getElementById('ctlPhotoH');
    const ctlPad = document.getElementById('ctlPad');
    const ctlGap = document.getElementById('ctlGap');
    const ctlStack = document.getElementById('ctlStack');
    const ctlBar = document.getElementById('ctlBar');
    const apply = document.getElementById('ctlApply');

    function syncVars() {
        root.style.setProperty('--fs-name', ctlName.value + 'px');
        root.style.setProperty('--fs-label', ctlLabel.value + 'px');
        root.style.setProperty('--fs-value', ctlValue.value + 'px');
        root.style.setProperty('--qr-size', ctlQr.value + 'px');
        root.style.setProperty('--photo-w', ctlPhotoW.value + 'px');
        root.style.setProperty('--photo-h', ctlPhotoH.value + 'px');
        root.style.setProperty('--card-pad-x', ctlPad.value + 'px');
        root.style.setProperty('--card-pad-y', ctlPad.value + 'px');
        root.style.setProperty('--content-gap', ctlGap.value + 'px');
        root.style.setProperty('--stack-gap', ctlStack.value + 'px');
        root.style.setProperty('--bar-bottom', ctlBar.value + 'px');
    }
    [ctlName, ctlLabel, ctlValue, ctlQr, ctlPhotoW, ctlPhotoH, ctlPad, ctlGap, ctlStack, ctlBar].forEach(el => el.addEventListener('input', syncVars));
    syncVars();

    function withStyleParams(url) {
        const u = new URL(url, window.location.origin);
        u.searchParams.set('name_fs', ctlName.value);
        u.searchParams.set('label_fs', ctlLabel.value);
        u.searchParams.set('value_fs', ctlValue.value);
        u.searchParams.set('qr_px', ctlQr.value);
        u.searchParams.set('photo_w_px', ctlPhotoW.value);
        u.searchParams.set('photo_h_px', ctlPhotoH.value);
        u.searchParams.set('pad_px', ctlPad.value);
        u.searchParams.set('gap_px', ctlGap.value);
        u.searchParams.set('stack_px', ctlStack.value);
        u.searchParams.set('bar_px', ctlBar.value);
        return u.toString();
    }

    function updateLinks() {
        const aJpg = document.querySelector('a[href*="/students/id-card-download-zip"], a[href*=\"/students/id-card-download-jpg\"]');
        if (aJpg) aJpg.href = withStyleParams(aJpg.getAttribute('href'));
    }
    updateLinks();
    apply.addEventListener('click', () => {
        updateLinks();
        apply.textContent = 'Applied';
        setTimeout(() => apply.textContent = 'Apply to downloads', 900);
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js" crossorigin="anonymous"></script>
<script>
(() => {
    const studentId = <?php echo json_encode($studentId, JSON_UNESCAPED_SLASHES); ?>;
    const frontEl = document.getElementById('idcardFront');
    const backEl = document.getElementById('idcardBack');
    const btnFront = document.getElementById('btnPngFront');
    const btnBack = document.getElementById('btnPngBack');
    const btnZip = document.getElementById('btnPngZip');

    function safeName(s) {
        return String(s || 'student').replace(/[^a-zA-Z0-9_-]+/g, '_');
    }

    async function capturePngBlob(el) {
        // Scale for better quality
        const canvas = await html2canvas(el, {
            backgroundColor: '#ffffff',
            scale: 3,
            useCORS: true,
            logging: false,
        });
        return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    async function withBusy(btn, fn) {
        const old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Processing…';
        try { await fn(); }
        finally { btn.disabled = false; btn.innerHTML = old; }
    }

    btnFront.addEventListener('click', () => withBusy(btnFront, async () => {
        const blob = await capturePngBlob(frontEl);
        if (!blob) return;
        downloadBlob(blob, safeName(studentId) + '_front.png');
    }));

    btnBack.addEventListener('click', () => withBusy(btnBack, async () => {
        const blob = await capturePngBlob(backEl);
        if (!blob) return;
        downloadBlob(blob, safeName(studentId) + '_back.png');
    }));

    btnZip.addEventListener('click', () => withBusy(btnZip, async () => {
        const zip = new JSZip();
        const [frontBlob, backBlob] = await Promise.all([capturePngBlob(frontEl), capturePngBlob(backEl)]);
        if (!frontBlob || !backBlob) return;
        const base = safeName(studentId);
        zip.file(base + '_front.png', frontBlob);
        zip.file(base + '_back.png', backBlob);
        const out = await zip.generateAsync({ type: 'blob' });
        downloadBlob(out, 'student_id_' + base + '_png.zip');
    }));
})();
</script>

