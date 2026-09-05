<style>
.dept-form-wrap {
    --dept-text: #0f172a;
    --dept-muted: #64748b;
    --dept-border: #e2e8f0;
    --dept-label: #334155;
}
.dept-form-shell {
    width: 100%;
    max-width: 760px;
    margin: 0;
}
.dept-form-wrap .dept-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.dept-form-wrap .dept-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dept-text);
    margin: 0 0 0.25rem;
    line-height: 1.3;
}
.dept-form-wrap .dept-lead {
    font-size: 0.875rem;
    color: var(--dept-muted);
    margin: 0;
    line-height: 1.45;
}
.dept-form-wrap .dept-head .btn {
    flex-shrink: 0;
    min-height: 40px;
    min-width: 96px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.dept-form-wrap .dept-panel {
    background: #fff;
    border: 1px solid var(--dept-border);
    border-radius: 0.65rem;
    overflow: hidden;
}
.dept-form-wrap .dept-panel-body {
    padding: 1.35rem 1.4rem 1.4rem;
}
.dept-form-wrap .dept-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--dept-muted);
    margin: 0 0 0.9rem;
}
.dept-form-grid {
    display: grid;
    grid-template-columns: 180px minmax(0, 1fr);
    gap: 1rem 1.25rem;
    align-items: start;
}
.dept-field {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.dept-form-wrap .form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--dept-label);
    margin: 0 0 0.4rem;
    line-height: 1.3;
}
.dept-form-wrap .req { color: #dc3545; }
.dept-form-wrap .help-mini {
    font-size: 0.8rem;
    color: var(--dept-muted);
    margin: 0.4rem 0 0;
    line-height: 1.4;
}
.dept-form-wrap .form-control {
    display: block;
    width: 100%;
    max-width: 100%;
    min-height: 42px;
    box-sizing: border-box;
    border-color: #cbd5e1;
}
.dept-form-wrap .dept-id-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: 0.06em;
    font-weight: 700;
    text-transform: uppercase;
}
.dept-form-wrap .dept-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--dept-border);
}
.dept-form-wrap .dept-actions .btn {
    min-height: 40px;
    min-width: 128px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
@media (max-width: 767.98px) {
    .dept-form-wrap .dept-head {
        flex-direction: column;
        align-items: stretch;
    }
    .dept-form-wrap .dept-head .btn,
    .dept-form-wrap .dept-actions .btn {
        width: 100%;
    }
    .dept-form-wrap .dept-actions {
        flex-direction: column-reverse;
    }
    .dept-form-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
    }
    .dept-form-wrap .dept-panel-body {
        padding: 1.1rem 1rem 1.15rem;
    }
}
</style>

<div class="container-fluid px-4 py-3 dept-form-wrap">
    <div class="dept-form-shell">
        <div class="dept-head">
            <div>
                <h1 class="dept-title"><i class="fas fa-plus-circle text-primary me-2"></i>Create department</h1>
                <p class="dept-lead">Add a short code and the official department name used across the system.</p>
            </div>
            <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="dept-panel">
            <div class="dept-panel-body">
                <form method="POST" action="<?php echo APP_URL; ?>/departments/create" class="dept-form" id="deptCreateForm">
                    <div class="dept-section-title">Department details</div>
                    <div class="dept-form-grid">
                        <div class="dept-field">
                            <label for="department_id" class="form-label">Department ID <span class="req">*</span></label>
                            <input type="text" class="form-control dept-id-input" id="department_id" name="department_id"
                                   maxlength="6" required placeholder="ICT" autocomplete="off">
                            <div class="help-mini">Up to 6 letters or numbers.</div>
                        </div>
                        <div class="dept-field">
                            <label for="department_name" class="form-label">Department name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="department_name" name="department_name"
                                   maxlength="60" required placeholder="e.g. Information and Communication Technology">
                            <div class="help-mini">Official name, maximum 60 characters.</div>
                        </div>
                    </div>
                    <div class="dept-actions">
                        <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var idInput = document.getElementById('department_id');
    if (!idInput) return;
    idInput.addEventListener('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
    });
});
</script>
