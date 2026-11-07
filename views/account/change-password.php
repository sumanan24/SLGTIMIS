<?php
$e = static fn ($s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<style>
.acc-pw-wrap .form-control { border-right: 0; }
.acc-pw-wrap .btn-outline-secondary { border-color: #ced4da; color: #495057; }
.acc-pw-rules { font-size: .8rem; color: #64748b; }
.acc-pw-rules span { display: inline-block; margin-right: .75rem; }
.acc-pw-rules .ok { color: #198754; }
@media (max-width: 576px) { .acc-pw-card .card-body { padding: 1.25rem !important; } }
</style>
<div class="container py-4 py-md-5" style="max-width: 520px;">
    <div class="card shadow-sm border-0 acc-pw-card">
        <div class="card-body p-4">
            <h1 class="h4 mb-2"><i class="fas fa-key text-primary me-2"></i>Choose a new password</h1>
            <p class="text-muted small mb-3">
                You signed in with a temporary (default) password, <strong>Slgti@1234</strong>.
                For your security, set your own password before you continue.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo $e($error); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo APP_URL; ?>/account/change-password" id="accPwForm" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="old_password">Current (default) password</label>
                    <div class="input-group acc-pw-wrap">
                        <input type="password" class="form-control" id="old_password" name="old_password" required autocomplete="current-password" autofocus>
                        <button type="button" class="btn btn-outline-secondary acc-pw-toggle" data-target="old_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New password</label>
                    <div class="input-group acc-pw-wrap">
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary acc-pw-toggle" data-target="new_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="acc-pw-rules mt-1" id="accPwRules">
                        <span data-rule="len">8+ characters</span>
                        <span data-rule="upper">1 capital</span>
                        <span data-rule="lower">1 small letter</span>
                        <span data-rule="digit">1 number</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm new password</label>
                    <div class="input-group acc-pw-wrap">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary acc-pw-toggle" data-target="confirm_password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="small text-danger mt-1 d-none" id="accPwMismatch">Passwords do not match.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Save new password</button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    document.querySelectorAll('.acc-pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) { icon.classList.toggle('fa-eye', !show); icon.classList.toggle('fa-eye-slash', show); }
        });
    });
    var nw = document.getElementById('new_password');
    var cf = document.getElementById('confirm_password');
    var mis = document.getElementById('accPwMismatch');
    function rules() {
        var v = nw.value;
        var res = { len: v.length >= 8, upper: /[A-Z]/.test(v), lower: /[a-z]/.test(v), digit: /\d/.test(v) };
        document.querySelectorAll('#accPwRules [data-rule]').forEach(function (el) {
            el.classList.toggle('ok', !!res[el.getAttribute('data-rule')]);
        });
        mis.classList.toggle('d-none', !cf.value || cf.value === v);
    }
    nw.addEventListener('input', rules);
    cf.addEventListener('input', rules);
})();
</script>
