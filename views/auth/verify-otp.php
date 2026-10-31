<style>
    body:not(.logged-in) {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        height: auto !important;
        min-height: 100vh;
        background: #eef3f8 !important;
    }
    body:not(.logged-in) .main-content {
        overflow: visible !important;
        height: auto !important;
        min-height: calc(100vh - 52px);
        background: transparent !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    body:not(.logged-in) .footer {
        height: auto !important;
        min-height: 52px;
        background: #eef3f8 !important;
        border-top: 0 !important;
    }
    .slgti-login { width: 100%; padding: 1.25rem 1rem 1.5rem; box-sizing: border-box; }
    .slgti-login-card {
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #d9e2ec;
        border-radius: 16px;
        box-shadow: 0 12px 32px rgba(0, 31, 63, 0.08);
        overflow: hidden;
    }
    .slgti-login-brand { padding: 1.75rem 1.5rem 1.25rem; text-align: center; border-bottom: 1px solid #e6edf5; }
    .slgti-login-brand img { height: 72px; width: auto; max-width: 100%; margin-bottom: 0.85rem; }
    .slgti-login-brand h1 { margin: 0; color: #001f3f; font-size: 1.45rem; font-weight: 700; }
    .slgti-login-brand p { margin: 0.35rem 0 0; color: #5c6b7a; font-size: 0.95rem; }
    .slgti-login-body { padding: 1.5rem; }
    .slgti-login-body .alert { border-radius: 10px; font-size: 0.92rem; }
    .slgti-note { margin: 0 0 0.35rem; color: #1c2b3a; font-size: 0.95rem; }
    .slgti-timer { margin: 0 0 1rem; color: #5c6b7a; font-size: 0.95rem; }
    .otp-boxes { display: flex; gap: 0.4rem; justify-content: space-between; margin-bottom: 1.15rem; }
    .otp-boxes input {
        flex: 1 1 0;
        min-width: 0;
        width: 100%;
        height: 48px;
        text-align: center;
        font-size: 1.2rem;
        font-weight: 700;
        color: #102033;
        border: 1px solid #cfd8e3;
        border-radius: 10px;
        background: #f8fafc;
    }
    .otp-boxes input:focus { border-color: #001f3f; background: #ffffff; outline: 0; box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.12); }
    .slgti-login-submit, .slgti-login-secondary {
        width: 100%;
        min-height: 48px;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
    }
    .slgti-login-submit { border: 0; background: #001f3f; color: #ffffff; margin-bottom: 0.75rem; }
    .slgti-login-submit:hover, .slgti-login-submit:focus { background: #003366; color: #ffffff; }
    .slgti-login-secondary { border: 1px solid #001f3f; background: #ffffff; color: #001f3f; }
    .slgti-login-secondary:hover, .slgti-login-secondary:focus { background: #f4f7fb; color: #001f3f; }
    .slgti-login-back { margin-top: 1rem; text-align: center; }
    .slgti-login-back a { color: #0b4f8a; font-weight: 600; text-decoration: none; }
    .slgti-login-back a:hover { text-decoration: underline; }
    @media (max-width: 575.98px) {
        .slgti-login { padding: 0.75rem 0.75rem 1rem; }
        .slgti-login-card { border-radius: 12px; }
        .slgti-login-brand { padding: 1.35rem 1rem 1rem; }
        .slgti-login-brand img { height: 60px; }
        .slgti-login-brand h1 { font-size: 1.3rem; }
        .slgti-login-body { padding: 1.1rem 1rem 1.15rem; }
        .otp-boxes { gap: 0.3rem; }
        .otp-boxes input { height: 44px; font-size: 1.05rem; }
    }
</style>
<div class="slgti-login">
    <div class="slgti-login-card">
        <div class="slgti-login-brand">
            <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="SLGTI">
            <h1>SLGTI MIS</h1>
            <p>Enter the verification code</p>
        </div>
        <div class="slgti-login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <p class="slgti-note">Registered mobile: <strong><?php echo htmlspecialchars((string) ($masked_mobile ?? '')); ?></strong></p>
            <p class="slgti-timer">Code expires in <strong id="otpTimer">05:00</strong></p>
            <form method="POST" action="<?php echo APP_URL; ?>/forgot-password/verify" id="verifyForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="otp" id="otpValue">
                <noscript>
                    <label class="slgti-note" for="otpPlain">Verification code</label>
                    <input type="text" name="otp" id="otpPlain" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder="6-digit code" style="width:100%;min-height:48px;margin-bottom:1rem;font-size:16px;">
                </noscript>
                <div class="otp-boxes" id="otpBoxes">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]*" class="otp-digit" aria-label="Digit <?php echo $i + 1; ?>" autocomplete="<?php echo $i === 0 ? 'one-time-code' : 'off'; ?>">
                    <?php endfor; ?>
                </div>
                <button type="submit" class="slgti-login-submit" id="verifyBtn">Verify OTP</button>
            </form>
            <form method="POST" action="<?php echo APP_URL; ?>/forgot-password/verify" id="resendForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="resend" value="1">
                <button type="submit" class="slgti-login-secondary" id="resendBtn">Resend OTP</button>
            </form>
            <div class="slgti-login-back">
                <a href="<?php echo APP_URL; ?>/login"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back to Login</a>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var digits = Array.prototype.slice.call(document.querySelectorAll('.otp-digit'));
    var hidden = document.getElementById('otpValue');
    function sync() {
        hidden.value = digits.map(function (el) { return el.value.replace(/\D/g, ''); }).join('');
    }
    digits.forEach(function (input, index) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            if (input.value && digits[index + 1]) digits[index + 1].focus();
            sync();
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !input.value && digits[index - 1]) digits[index - 1].focus();
        });
        input.addEventListener('paste', function (event) {
            var text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!text) return;
            event.preventDefault();
            text.split('').forEach(function (ch, i) { if (digits[i]) digits[i].value = ch; });
            sync();
            (digits[Math.min(text.length, 5)] || digits[0]).focus();
        });
    });
    document.getElementById('verifyForm').addEventListener('submit', function (event) {
        sync();
        if (!/^\d{6}$/.test(hidden.value)) {
            event.preventDefault();
            return;
        }
        var btn = document.getElementById('verifyBtn');
        setTimeout(function () { btn.disabled = true; btn.textContent = 'Verifying...'; }, 0);
    });
    document.getElementById('resendForm').addEventListener('submit', function () {
        var btn = document.getElementById('resendBtn');
        setTimeout(function () { btn.disabled = true; btn.textContent = 'Sending...'; }, 0);
    });
    var end = <?php echo (int) ($expires_at ?? 0); ?> * 1000;
    var timer = document.getElementById('otpTimer');
    function tick() {
        var left = Math.max(0, Math.floor((end - Date.now()) / 1000));
        var min = String(Math.floor(left / 60)).padStart(2, '0');
        var sec = String(left % 60).padStart(2, '0');
        timer.textContent = min + ':' + sec;
        if (left === 0) timer.textContent = 'Expired';
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
