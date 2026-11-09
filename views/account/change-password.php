<?php
$e = static fn ($s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$userName = (string) ($_SESSION['user_name'] ?? '');
?>
<style>
.cp-page { padding: 2rem 1rem; }
.cp-card {
    max-width: 960px; margin: 0 auto; background: #fff; border: 1px solid #d9e2ec; border-radius: 18px;
    box-shadow: 0 14px 36px rgba(0, 31, 63, .10); overflow: hidden; display: flex;
}
.cp-aside {
    flex: 0 0 340px; padding: 2.25rem 2rem; color: #e2e8f0;
    background: linear-gradient(160deg, #001f3f 0%, #003366 70%, #0b4f8a 100%);
    display: flex; flex-direction: column;
}
.cp-aside .cp-shield {
    width: 64px; height: 64px; border-radius: 16px; background: rgba(255, 255, 255, .14);
    display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #fff; margin-bottom: 1.25rem;
}
.cp-aside h1 { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 .5rem; }
.cp-aside p { font-size: .92rem; line-height: 1.55; color: #cbd5e1; margin: 0 0 1.5rem; }
.cp-tips { list-style: none; padding: 0; margin: 0; display: grid; gap: .85rem; font-size: .88rem; }
.cp-tips li { display: flex; gap: .7rem; align-items: flex-start; color: #e2e8f0; }
.cp-tips i { color: #7dd3fc; margin-top: .2rem; width: 1rem; text-align: center; }
.cp-aside .cp-foot { margin-top: auto; padding-top: 1.5rem; font-size: .8rem; color: #94a3b8; }

.cp-main { flex: 1 1 auto; padding: 2.25rem 2.25rem 2rem; min-width: 0; }
.cp-hello { font-size: .85rem; color: #64748b; margin-bottom: .15rem; }
.cp-main h2 { font-size: 1.35rem; font-weight: 700; color: #001f3f; margin: 0 0 1.25rem; }
.cp-alert { border-radius: 10px; font-size: .9rem; display: flex; gap: .6rem; align-items: flex-start; }

.cp-field { margin-bottom: 1.1rem; }
.cp-field > label { display: block; font-size: .88rem; font-weight: 600; color: #1c2b3a; margin-bottom: .35rem; }
.cp-input {
    display: flex; align-items: center; gap: .65rem; min-height: 48px; padding: 0 .35rem 0 .85rem;
    border: 1px solid #cfd8e3; border-radius: 10px; background: #f8fafc; transition: border-color .15s, box-shadow .15s, background .15s;
}
.cp-input:focus-within { border-color: #001f3f; background: #fff; box-shadow: 0 0 0 3px rgba(0, 31, 63, .12); }
.cp-input.is-bad { border-color: #dc3545; }
.cp-input.is-good { border-color: #198754; }
.cp-input > i.cp-lead { color: #5c6b7a; width: 1rem; text-align: center; flex: 0 0 auto; }
.cp-input input { flex: 1 1 auto; min-width: 0; border: 0; outline: 0; background: transparent; font-size: 16px; padding: .7rem 0; color: #102033; }
.cp-eye { border: 0; background: transparent; color: #5c6b7a; min-width: 44px; min-height: 44px; border-radius: 8px; }
.cp-eye:hover { color: #001f3f; background: rgba(0, 31, 63, .06); }

.cp-meter { display: flex; gap: 5px; margin-top: .55rem; }
.cp-meter span { flex: 1; height: 5px; border-radius: 4px; background: #e2e8f0; transition: background .2s; }
.cp-meter[data-level="1"] span:nth-child(-n+1) { background: #dc3545; }
.cp-meter[data-level="2"] span:nth-child(-n+2) { background: #fd7e14; }
.cp-meter[data-level="3"] span:nth-child(-n+3) { background: #ffc107; }
.cp-meter[data-level="4"] span:nth-child(-n+4) { background: #198754; }
.cp-meter-label { font-size: .78rem; color: #64748b; margin-top: .3rem; min-height: 1.1em; }

.cp-rules { list-style: none; padding: 0; margin: .65rem 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: .35rem .9rem; font-size: .82rem; color: #64748b; }
.cp-rules li { display: flex; align-items: center; gap: .45rem; }
.cp-rules li i { font-size: .75rem; color: #cbd5e1; transition: color .15s; }
.cp-rules li.ok { color: #0f6b3f; }
.cp-rules li.ok i { color: #198754; }

.cp-match { font-size: .82rem; margin-top: .4rem; min-height: 1.1em; }
.cp-match.bad { color: #dc3545; }
.cp-match.good { color: #198754; }

.cp-submit {
    width: 100%; min-height: 50px; border: 0; border-radius: 10px; background: #001f3f; color: #fff; font-weight: 700; font-size: 1rem;
    display: flex; align-items: center; justify-content: center; gap: .6rem; margin-top: .5rem; transition: background .15s, opacity .15s;
}
.cp-submit:hover:not(:disabled) { background: #003366; }
.cp-submit:disabled { opacity: .55; cursor: not-allowed; }
.cp-note { text-align: center; font-size: .8rem; color: #64748b; margin-top: .9rem; }

@media (max-width: 860px) {
    .cp-card { flex-direction: column; max-width: 520px; }
    .cp-aside { flex: none; padding: 1.5rem 1.4rem; }
    .cp-aside p { margin-bottom: 1rem; }
    .cp-tips, .cp-aside .cp-foot { display: none; }
    .cp-main { padding: 1.5rem 1.25rem; }
}
@media (max-width: 480px) {
    .cp-page { padding: 1rem .5rem; }
    .cp-rules { grid-template-columns: 1fr; }
}
</style>

<div class="cp-page">
    <div class="cp-card">
        <aside class="cp-aside">
            <div class="cp-shield"><i class="fas fa-shield-halved"></i></div>
            <h1>Secure your account</h1>
            <p>You are signed in with a temporary (default) password. Choose your own password to continue to SLGTI MIS.</p>
            <ul class="cp-tips">
                <li><i class="fas fa-check-circle"></i><span>Use a password you do not use anywhere else.</span></li>
                <li><i class="fas fa-check-circle"></i><span>Mix capital and small letters with numbers; add symbols for extra strength.</span></li>
                <li><i class="fas fa-check-circle"></i><span>Never share your password, even with IT or admin staff.</span></li>
            </ul>
            <div class="cp-foot"><i class="fas fa-lock me-1"></i>You will only be asked to do this once.</div>
        </aside>

        <section class="cp-main">
            <?php if ($userName !== ''): ?><div class="cp-hello">Hello, <?php echo $e($userName); ?></div><?php endif; ?>
            <h2>Set a new password</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger cp-alert py-2" role="alert">
                    <i class="fas fa-exclamation-circle mt-1"></i><div><?php echo $e($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo APP_URL; ?>/account/change-password" id="cpForm" novalidate>
                <div class="cp-field">
                    <label for="old_password">Current (default) password</label>
                    <div class="cp-input">
                        <i class="fas fa-lock cp-lead" aria-hidden="true"></i>
                        <input type="password" id="old_password" name="old_password" required autocomplete="current-password" autofocus placeholder="Enter your current password">
                        <button type="button" class="cp-eye" data-target="old_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                </div>

                <div class="cp-field">
                    <label for="new_password">New password</label>
                    <div class="cp-input" id="cpNewBox">
                        <i class="fas fa-key cp-lead" aria-hidden="true"></i>
                        <input type="password" id="new_password" name="new_password" minlength="8" required autocomplete="new-password" placeholder="Create a strong password">
                        <button type="button" class="cp-eye" data-target="new_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="cp-meter" id="cpMeter" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                    <div class="cp-meter-label" id="cpMeterLabel"></div>
                    <ul class="cp-rules" id="cpRules">
                        <li data-rule="len"><i class="fas fa-circle"></i>At least 8 characters</li>
                        <li data-rule="upper"><i class="fas fa-circle"></i>One capital letter</li>
                        <li data-rule="lower"><i class="fas fa-circle"></i>One small letter</li>
                        <li data-rule="digit"><i class="fas fa-circle"></i>One number</li>
                        <li data-rule="diff"><i class="fas fa-circle"></i>Different from current password</li>
                    </ul>
                </div>

                <div class="cp-field">
                    <label for="confirm_password">Confirm new password</label>
                    <div class="cp-input" id="cpConfirmBox">
                        <i class="fas fa-check-double cp-lead" aria-hidden="true"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Re-enter the new password">
                        <button type="button" class="cp-eye" data-target="confirm_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="cp-match" id="cpMatch" aria-live="polite"></div>
                </div>

                <button type="submit" class="cp-submit" id="cpSubmit" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="cpSpin" aria-hidden="true"></span>
                    <span id="cpSubmitText"><i class="fas fa-save me-2"></i>Save new password</span>
                </button>
                <div class="cp-note">After saving, you will be taken to your dashboard.</div>
            </form>
        </section>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('cpForm');
    var oldP = document.getElementById('old_password');
    var nw = document.getElementById('new_password');
    var cf = document.getElementById('confirm_password');
    var meter = document.getElementById('cpMeter');
    var meterLabel = document.getElementById('cpMeterLabel');
    var match = document.getElementById('cpMatch');
    var submit = document.getElementById('cpSubmit');
    var newBox = document.getElementById('cpNewBox');
    var confirmBox = document.getElementById('cpConfirmBox');
    var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

    document.querySelectorAll('.cp-eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    });

    function evaluate() {
        var v = nw.value, o = oldP.value, c = cf.value;
        var res = {
            len: v.length >= 8,
            upper: /[A-Z]/.test(v),
            lower: /[a-z]/.test(v),
            digit: /\d/.test(v),
            diff: v !== '' && v !== o
        };
        document.querySelectorAll('#cpRules li').forEach(function (li) {
            var ok = !!res[li.getAttribute('data-rule')];
            li.classList.toggle('ok', ok);
            li.querySelector('i').className = ok ? 'fas fa-circle-check' : 'fas fa-circle';
        });

        var score = 0;
        if (v) {
            score = (res.len ? 1 : 0) + (res.upper && res.lower ? 1 : 0) + (res.digit ? 1 : 0) + (/[^A-Za-z0-9]/.test(v) || v.length >= 12 ? 1 : 0);
            if (score === 0) score = 1;
        }
        meter.setAttribute('data-level', score);
        meterLabel.textContent = v ? 'Strength: ' + labels[score] : '';

        var rulesOk = res.len && res.upper && res.lower && res.digit && res.diff;
        newBox.classList.toggle('is-good', rulesOk && !!v);
        newBox.classList.toggle('is-bad', !!v && !rulesOk && v.length >= 8);

        var matches = c !== '' && c === v;
        if (!c) {
            match.textContent = ''; match.className = 'cp-match'; confirmBox.classList.remove('is-good', 'is-bad');
        } else if (matches) {
            match.innerHTML = '<i class="fas fa-circle-check me-1"></i>Passwords match'; match.className = 'cp-match good';
            confirmBox.classList.add('is-good'); confirmBox.classList.remove('is-bad');
        } else {
            match.innerHTML = '<i class="fas fa-circle-xmark me-1"></i>Passwords do not match'; match.className = 'cp-match bad';
            confirmBox.classList.add('is-bad'); confirmBox.classList.remove('is-good');
        }

        submit.disabled = !(o !== '' && rulesOk && matches);
    }

    [oldP, nw, cf].forEach(function (el) { el.addEventListener('input', evaluate); });
    [oldP, nw, cf].forEach(function (el) { el.addEventListener('change', evaluate); });
    evaluate();
    setTimeout(evaluate, 400); // browser autofill

    form.addEventListener('submit', function (ev) {
        if (submit.disabled) { ev.preventDefault(); return; }
        submit.disabled = true;
        document.getElementById('cpSpin').classList.remove('d-none');
        document.getElementById('cpSubmitText').textContent = 'Saving...';
    });
})();
</script>
