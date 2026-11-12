<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$userName = trim((string) ($_SESSION['user_name'] ?? ''));
?>
<style>
.st-pw-page {
    width: 100%;
    max-width: 520px;
    margin: 0 auto;
    box-sizing: border-box;
}
.st-pw-page *,
.st-pw-page *::before,
.st-pw-page *::after { box-sizing: border-box; }

.st-pw-hero {
    background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
    color: #fff;
    border-radius: 14px;
    padding: 1.1rem 1.15rem;
    margin-bottom: 1rem;
    box-shadow: 0 6px 18px rgba(0, 31, 63, .18);
}
.st-pw-step {
    font-size: .75rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    opacity: .8;
    margin: 0 0 .25rem;
}
.st-pw-hero h1 {
    font-size: clamp(1.1rem, 4vw, 1.25rem);
    font-weight: 700;
    margin: 0 0 .35rem;
    line-height: 1.3;
}
.st-pw-hero p {
    margin: 0;
    font-size: .88rem;
    line-height: 1.45;
    opacity: .9;
}
.st-pw-hero .st-pw-user {
    margin-top: .65rem;
    font-size: .8rem;
    opacity: .85;
    overflow-wrap: anywhere;
}

.st-pw-card {
    background: #fff;
    border: 1px solid #e6eaf0;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0, 31, 63, .06);
    padding: 1.15rem 1.15rem 1.25rem;
}

.st-pw-alert {
    border-radius: 10px;
    font-size: .9rem;
    display: flex;
    gap: .55rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}
.st-pw-alert i { margin-top: .15rem; flex-shrink: 0; }

.st-pw-field { margin-bottom: 1rem; }
.st-pw-field > label {
    display: block;
    font-size: .88rem;
    font-weight: 600;
    color: #1c2b3a;
    margin-bottom: .4rem;
}

.st-pw-input {
    display: flex;
    align-items: center;
    gap: .55rem;
    min-height: 48px;
    padding: 0 .3rem 0 .85rem;
    border: 1px solid #cfd8e3;
    border-radius: 10px;
    background: #f8fafc;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.st-pw-input:focus-within {
    border-color: #001f3f;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(0, 31, 63, .12);
}
.st-pw-input.is-bad { border-color: #dc3545; }
.st-pw-input.is-good { border-color: #198754; }
.st-pw-input > i.st-pw-lead {
    color: #5c6b7a;
    width: 1rem;
    text-align: center;
    flex: 0 0 auto;
}
.st-pw-input input {
    flex: 1 1 auto;
    min-width: 0;
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    font-size: 16px; /* avoid iOS zoom */
    line-height: 1.4;
    padding: .75rem 0;
    color: #102033;
}
.st-pw-eye {
    border: 0;
    background: transparent;
    color: #5c6b7a;
    min-width: 44px;
    min-height: 44px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 0;
    cursor: pointer;
}
.st-pw-eye:hover,
.st-pw-eye:focus-visible {
    color: #001f3f;
    background: rgba(0, 31, 63, .06);
    outline: none;
}

.st-pw-meter {
    display: flex;
    gap: 5px;
    margin-top: .55rem;
}
.st-pw-meter span {
    flex: 1;
    height: 5px;
    border-radius: 4px;
    background: #e2e8f0;
    transition: background .2s;
}
.st-pw-meter[data-level="1"] span:nth-child(-n+1) { background: #dc3545; }
.st-pw-meter[data-level="2"] span:nth-child(-n+2) { background: #fd7e14; }
.st-pw-meter[data-level="3"] span:nth-child(-n+3) { background: #ffc107; }
.st-pw-meter[data-level="4"] span:nth-child(-n+4) { background: #198754; }
.st-pw-meter-label {
    font-size: .78rem;
    color: #64748b;
    margin-top: .3rem;
    min-height: 1.1em;
}

.st-pw-rules {
    list-style: none;
    padding: 0;
    margin: .65rem 0 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .4rem .75rem;
    font-size: .82rem;
    color: #64748b;
}
.st-pw-rules li {
    display: flex;
    align-items: flex-start;
    gap: .4rem;
    line-height: 1.35;
    min-width: 0;
}
.st-pw-rules li i {
    font-size: .75rem;
    color: #cbd5e1;
    margin-top: .2rem;
    flex-shrink: 0;
    transition: color .15s;
}
.st-pw-rules li.ok { color: #0f6b3f; }
.st-pw-rules li.ok i { color: #198754; }

.st-pw-match {
    font-size: .82rem;
    margin-top: .4rem;
    min-height: 1.1em;
}
.st-pw-match.bad { color: #dc3545; }
.st-pw-match.good { color: #198754; }

.st-pw-submit {
    width: 100%;
    min-height: 50px;
    border: 0;
    border-radius: 10px;
    background: #001f3f;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    margin-top: .35rem;
    transition: background .15s, opacity .15s;
    cursor: pointer;
}
.st-pw-submit:hover:not(:disabled) { background: #003366; }
.st-pw-submit:disabled { opacity: .55; cursor: not-allowed; }
.st-pw-note {
    text-align: center;
    font-size: .8rem;
    color: #64748b;
    margin: .85rem 0 0;
    line-height: 1.4;
}

@media (max-width: 576px) {
    .st-pw-hero {
        border-radius: 12px;
        padding: 1rem .95rem;
        margin-bottom: .85rem;
    }
    .st-pw-card {
        padding: 1rem .95rem 1.1rem;
        border-radius: 12px;
    }
    .st-pw-rules {
        grid-template-columns: 1fr;
        gap: .35rem;
    }
    .st-pw-field { margin-bottom: .9rem; }
}
@media (max-width: 380px) {
    .st-pw-hero p { font-size: .84rem; }
    .st-pw-input { padding-left: .7rem; }
}
</style>

<div class="st-pw-page">
    <div class="st-pw-hero">
        <p class="st-pw-step">Step 1 of 2</p>
        <h1>Change your password</h1>
        <p>Your first login uses a temporary password (your NIC). After this you will complete personal and parent details.</p>
        <?php if ($userName !== ''): ?>
            <div class="st-pw-user"><i class="fas fa-user me-1" aria-hidden="true"></i><?php echo $e($userName); ?></div>
        <?php endif; ?>
    </div>

    <div class="st-pw-card">
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger st-pw-alert py-2 mb-3" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <div><?php echo $e((string) $_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo APP_URL; ?>/student/change-password" id="stPwForm" novalidate>
            <div class="st-pw-field">
                <label for="old_password">Current password (NIC)</label>
                <div class="st-pw-input">
                    <i class="fas fa-lock st-pw-lead" aria-hidden="true"></i>
                    <input type="password" id="old_password" name="old_password" required autocomplete="current-password" autofocus placeholder="Enter your NIC / temporary password">
                    <button type="button" class="st-pw-eye" data-target="old_password" aria-label="Show password" title="Show password">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="st-pw-field">
                <label for="new_password">New password</label>
                <div class="st-pw-input" id="stPwNewBox">
                    <i class="fas fa-key st-pw-lead" aria-hidden="true"></i>
                    <input type="password" id="new_password" name="new_password" minlength="8" required autocomplete="new-password" placeholder="Create a strong password">
                    <button type="button" class="st-pw-eye" data-target="new_password" aria-label="Show password" title="Show password">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="st-pw-meter" id="stPwMeter" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                <div class="st-pw-meter-label" id="stPwMeterLabel"></div>
                <ul class="st-pw-rules" id="stPwRules">
                    <li data-rule="len"><i class="fas fa-circle" aria-hidden="true"></i>At least 8 characters</li>
                    <li data-rule="upper"><i class="fas fa-circle" aria-hidden="true"></i>One capital letter</li>
                    <li data-rule="lower"><i class="fas fa-circle" aria-hidden="true"></i>One small letter</li>
                    <li data-rule="digit"><i class="fas fa-circle" aria-hidden="true"></i>One number</li>
                </ul>
            </div>

            <div class="st-pw-field">
                <label for="confirm_password">Confirm new password</label>
                <div class="st-pw-input" id="stPwConfirmBox">
                    <i class="fas fa-check-double st-pw-lead" aria-hidden="true"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Re-enter the new password">
                    <button type="button" class="st-pw-eye" data-target="confirm_password" aria-label="Show password" title="Show password">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="st-pw-match" id="stPwMatch" aria-live="polite"></div>
            </div>

            <button type="submit" class="st-pw-submit" id="stPwSubmit" disabled>
                <span class="spinner-border spinner-border-sm d-none" id="stPwSpin" aria-hidden="true"></span>
                <span id="stPwSubmitText"><i class="fas fa-save me-1" aria-hidden="true"></i>Save new password</span>
            </button>
            <p class="st-pw-note">Next: complete personal and parent details.</p>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('stPwForm');
    if (!form) return;

    var oldP = document.getElementById('old_password');
    var nw = document.getElementById('new_password');
    var cf = document.getElementById('confirm_password');
    var meter = document.getElementById('stPwMeter');
    var meterLabel = document.getElementById('stPwMeterLabel');
    var match = document.getElementById('stPwMatch');
    var submit = document.getElementById('stPwSubmit');
    var newBox = document.getElementById('stPwNewBox');
    var confirmBox = document.getElementById('stPwConfirmBox');
    var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

    document.querySelectorAll('.st-pw-eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.setAttribute('title', show ? 'Hide password' : 'Show password');
            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
            }
        });
    });

    function evaluate() {
        var v = nw.value;
        var o = oldP.value;
        var c = cf.value;
        var res = {
            len: v.length >= 8,
            upper: /[A-Z]/.test(v),
            lower: /[a-z]/.test(v),
            digit: /\d/.test(v)
        };

        document.querySelectorAll('#stPwRules li').forEach(function (li) {
            var ok = !!res[li.getAttribute('data-rule')];
            li.classList.toggle('ok', ok);
            var icon = li.querySelector('i');
            if (icon) {
                icon.className = ok ? 'fas fa-circle-check' : 'fas fa-circle';
            }
        });

        var score = 0;
        if (v) {
            score = (res.len ? 1 : 0)
                + (res.upper && res.lower ? 1 : 0)
                + (res.digit ? 1 : 0)
                + (/[^A-Za-z0-9]/.test(v) || v.length >= 12 ? 1 : 0);
            if (score === 0) score = 1;
        }
        meter.setAttribute('data-level', String(score));
        meterLabel.textContent = v ? ('Strength: ' + labels[score]) : '';

        var rulesOk = res.len && res.upper && res.lower && res.digit;
        newBox.classList.toggle('is-good', rulesOk && !!v);
        newBox.classList.toggle('is-bad', !!v && !rulesOk && v.length >= 8);

        var matches = c !== '' && c === v;
        if (!c) {
            match.textContent = '';
            match.className = 'st-pw-match';
            confirmBox.classList.remove('is-good', 'is-bad');
        } else if (matches) {
            match.innerHTML = '<i class="fas fa-circle-check me-1" aria-hidden="true"></i>Passwords match';
            match.className = 'st-pw-match good';
            confirmBox.classList.add('is-good');
            confirmBox.classList.remove('is-bad');
        } else {
            match.innerHTML = '<i class="fas fa-circle-xmark me-1" aria-hidden="true"></i>Passwords do not match';
            match.className = 'st-pw-match bad';
            confirmBox.classList.add('is-bad');
            confirmBox.classList.remove('is-good');
        }

        submit.disabled = !(o !== '' && rulesOk && matches);
    }

    [oldP, nw, cf].forEach(function (el) {
        el.addEventListener('input', evaluate);
        el.addEventListener('change', evaluate);
    });
    evaluate();
    setTimeout(evaluate, 400);

    form.addEventListener('submit', function (ev) {
        if (submit.disabled) {
            ev.preventDefault();
            return;
        }
        submit.disabled = true;
        document.getElementById('stPwSpin').classList.remove('d-none');
        document.getElementById('stPwSubmitText').textContent = 'Saving...';
    });
})();
</script>
