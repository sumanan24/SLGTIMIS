<style>
.srf {
    --srf-text: #0f172a;
    --srf-muted: #64748b;
    --srf-border: #e2e8f0;
    --srf-label: #334155;
    --srf-navy: #001f3f;
}
.srf-shell { width: 100%; max-width: 760px; margin: 0 auto; }

.srf-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.srf-title { font-size: 1.25rem; font-weight: 700; color: var(--srf-text); margin: 0 0 0.25rem; line-height: 1.3; }
.srf-title i { color: #0d6efd; }
.srf-lead { font-size: 0.875rem; color: var(--srf-muted); margin: 0; line-height: 1.45; }
.srf-head .btn {
    flex-shrink: 0;
    min-height: 40px;
    min-width: 96px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.srf-panel {
    background: #fff;
    border: 1px solid var(--srf-border);
    border-radius: 0.65rem;
    overflow: hidden;
}
.srf-panel-body { padding: 1.35rem 1.4rem 1.4rem; }
.srf-section {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--srf-muted);
    margin: 0 0 0.9rem;
}

.srf-grid {
    display: grid;
    grid-template-columns: 200px minmax(0, 1fr);
    gap: 1rem 1.25rem;
    align-items: start;
}
.srf-field { display: flex; flex-direction: column; min-width: 0; }
.srf .form-label {
    display: block;
    margin: 0 0 0.4rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.3;
    color: var(--srf-label);
}
.srf .req { color: #dc3545; }
.srf .form-control {
    display: block;
    width: 100%;
    max-width: 100%;
    height: 42px;
    min-height: 42px;
    box-sizing: border-box;
    border-color: #cbd5e1;
    font-size: 0.9375rem;
    border-radius: 8px;
}
.srf .form-control:focus {
    border-color: var(--srf-navy);
    box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.12);
}
.srf .srf-id-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.srf .help-mini { margin: 0.4rem 0 0; font-size: 0.8rem; line-height: 1.4; color: var(--srf-muted); }

.srf-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--srf-border);
}
.srf-actions .btn {
    min-height: 40px;
    min-width: 128px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 767.98px) {
    .srf.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .srf-head { flex-direction: column; align-items: stretch; }
    .srf-head .btn, .srf-actions .btn { width: 100%; }
    .srf-actions { flex-direction: column-reverse; }
    .srf-grid { grid-template-columns: minmax(0, 1fr); gap: 1rem; }
    .srf-panel-body { padding: 1.1rem 1rem 1.15rem; }
    .srf .form-control { font-size: 16px; }
}
</style>

<div class="container-fluid px-4 py-3 srf">
    <div class="srf-shell">
        <div class="srf-head">
            <div>
                <h1 class="srf-title"><i class="fas fa-plus-circle me-2"></i>Create staff role</h1>
                <p class="srf-lead">Add a short role code and the position name used for staff and login access.</p>
            </div>
            <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="srf-panel">
            <div class="srf-panel-body">
                <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/create">
                    <div class="srf-section">Role details</div>
                    <div class="srf-grid">
                        <div class="srf-field">
                            <label class="form-label" for="staff_position_type_id">Role ID <span class="req">*</span></label>
                            <input type="text" class="form-control srf-id-input" id="staff_position_type_id"
                                   name="staff_position_type_id" maxlength="11" required placeholder="HOD" autocomplete="off">
                            <div class="help-mini">Up to 11 letters or numbers, stored in uppercase.</div>
                        </div>
                        <div class="srf-field">
                            <label class="form-label" for="staff_position_type_name">Role name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="staff_position_type_name"
                                   name="staff_position_type_name" maxlength="64" required placeholder="e.g. Head of Department">
                            <div class="help-mini">Official position name, maximum 64 characters.</div>
                        </div>
                    </div>
                    <div class="srf-actions">
                        <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var id = document.getElementById('staff_position_type_id');
    if (id) {
        id.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 11);
        });
    }
})();
</script>
