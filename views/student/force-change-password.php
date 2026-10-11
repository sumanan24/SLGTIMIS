<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<style>
.st-pw-wrap .form-control { border-right: 0; }
.st-pw-wrap .btn-outline-secondary {
    border-color: #ced4da;
    color: #495057;
}
.st-pw-wrap .form-control:focus + .btn-outline-secondary,
.st-pw-wrap:focus-within .btn-outline-secondary {
    border-color: #86b7fe;
}
</style>
<div class="container py-4" style="max-width: 520px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="small text-uppercase text-muted mb-1" style="letter-spacing:.04em;">Step 1 of 2</div>
            <h1 class="h4 mb-2">Change your password</h1>
            <p class="text-muted small">Your first login uses a temporary password (your NIC). After this you will complete personal and parent details.</p>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo APP_URL; ?>/student/change-password" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="old_password">Current password (NIC)</label>
                    <div class="input-group st-pw-wrap">
                        <input type="password" class="form-control" id="old_password" name="old_password" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="old_password" aria-label="Show password" title="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New password</label>
                    <div class="input-group st-pw-wrap">
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="new_password" aria-label="Show password" title="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">At least 8 characters, with 1 capital letter, 1 small letter, and 1 number.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm new password</label>
                    <div class="input-group st-pw-wrap">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="confirm_password" aria-label="Show password" title="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Save new password</button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    document.querySelectorAll('.st-pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            }
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.setAttribute('title', show ? 'Hide password' : 'Show password');
        });
    });
})();
</script>
