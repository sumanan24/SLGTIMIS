<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$nic = (string) ($nic ?? '');
$formAction = (string) ($formAction ?? (APP_URL . '/application-admission/interview-letter/download'));
$logoUrl = rtrim(APP_URL, '/') . '/assets/img/logo.png';
?>
<style>
.iv-letter-page { max-width: 32rem; margin: 0 auto 2rem; }
.iv-letter-card {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(12, 74, 110, 0.12);
    overflow: hidden;
}
.iv-letter-accent {
    height: 5px;
    background: linear-gradient(90deg, #0c4a6e 0%, #0369a1 45%, #d97706 100%);
}
.iv-letter-body { padding: 1.5rem 1.5rem 1.75rem; }
.iv-letter-brand { text-align: center; margin-bottom: 1.15rem; }
.iv-letter-brand img { height: 56px; width: auto; display: block; margin: 0 auto 0.65rem; }
.iv-letter-brand .iv-inst {
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0c4a6e;
    margin: 0;
}
.iv-letter-brand .iv-title {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0.45rem 0 0.25rem;
    color: #0f172a;
}
.iv-letter-brand .iv-sub { font-size: 0.9rem; color: #64748b; margin: 0; }
.iv-letter-card .form-label { font-weight: 600; font-size: 0.875rem; }
.iv-letter-card .form-control {
    min-height: 2.75rem;
    font-size: 1.05rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.iv-letter-note {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.85rem 0 0;
}
</style>

<div class="iv-letter-page">
    <div class="iv-letter-card">
        <div class="iv-letter-accent"></div>
        <div class="iv-letter-body">
            <div class="iv-letter-brand">
                <img src="<?php echo $e($logoUrl); ?>" alt="SLGTI" onerror="this.style.display='none'">
                <p class="iv-inst">Sri Lanka German Training Institute</p>
                <h1 class="iv-title">Interview letter</h1>
                <p class="iv-sub">Enter your NIC number to download your interview invitation letter.</p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo $e($formAction); ?>">
                <div class="mb-3">
                    <label class="form-label" for="student_nic_letter">NIC number</label>
                    <input type="text"
                           class="form-control"
                           id="student_nic_letter"
                           name="nic"
                           required
                           autocomplete="off"
                           maxlength="20"
                           placeholder="e.g. 200312345678 or 991234567V"
                           value="<?php echo $e($nic); ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-file-pdf me-2"></i>Download interview letter (PDF)
                </button>
            </form>
            <p class="iv-letter-note">Use the NIC exactly as on your application. The letter is available after your interview schedule is published.</p>
        </div>
    </div>
</div>
