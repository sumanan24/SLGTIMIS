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
    .slgti-note { margin: 0 0 1rem; color: #5c6b7a; font-size: 0.95rem; line-height: 1.45; }
    .slgti-field { margin-bottom: 1.15rem; }
    .slgti-field label { display: block; margin-bottom: 0.4rem; color: #1c2b3a; font-size: 0.92rem; font-weight: 600; }
    .slgti-input {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-height: 48px;
        padding: 0 0.85rem;
        border: 1px solid #cfd8e3;
        border-radius: 10px;
        background: #f8fafc;
    }
    .slgti-input:focus-within { border-color: #001f3f; background: #ffffff; box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.12); }
    .slgti-input i { color: #5c6b7a; width: 1rem; text-align: center; flex: 0 0 auto; }
    .slgti-input input {
        flex: 1 1 auto;
        width: 100%;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: #102033;
        font-size: 16px;
        line-height: 1.4;
        padding: 0.7rem 0;
    }
    .slgti-input input::placeholder { color: #8b98a5; }
    .slgti-login-submit, .slgti-login-secondary {
        width: 100%;
        min-height: 48px;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
    }
    .slgti-login-submit { border: 0; background: #001f3f; color: #ffffff; }
    .slgti-login-submit:hover, .slgti-login-submit:focus { background: #003366; color: #ffffff; }
    .slgti-login-submit:disabled { opacity: 0.75; }
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
    }
</style>
<div class="slgti-login">
    <div class="slgti-login-card">
        <div class="slgti-login-brand">
            <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="SLGTI">
            <h1>SLGTI MIS</h1>
            <p>Reset your password</p>
        </div>
        <div class="slgti-login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($info)): ?>
                <div class="alert alert-info" role="alert"><?php echo htmlspecialchars($info); ?></div>
            <?php endif; ?>
            <p class="slgti-note">Enter the username or NIC on your account. The code is sent only to the mobile number already registered for that account.</p>
            <form method="POST" action="<?php echo APP_URL; ?>/forgot-password" id="forgotForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="slgti-field">
                    <label for="identifier">Username or NIC</label>
                    <div class="slgti-input">
                        <i class="fas fa-id-card" aria-hidden="true"></i>
                        <input type="text" id="identifier" name="identifier" required autofocus maxlength="64" autocomplete="username" placeholder="Username or NIC">
                    </div>
                </div>
                <button type="submit" class="slgti-login-submit" id="sendOtpBtn">Send OTP</button>
            </form>
            <div class="slgti-login-back">
                <a href="<?php echo APP_URL; ?>/login"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back to Login</a>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('forgotForm').addEventListener('submit', function () {
    var btn = document.getElementById('sendOtpBtn');
    setTimeout(function () {
        btn.disabled = true;
        btn.textContent = 'Sending OTP...';
    }, 0);
});
</script>
