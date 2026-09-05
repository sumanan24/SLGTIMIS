<style>
.dept-form-wrap {
    --dept-text: #0f172a;
    --dept-muted: #64748b;
    --dept-border: #e2e8f0;
}
.dept-form-wrap .dept-head { margin-bottom: 1.25rem; }
.dept-form-wrap .dept-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dept-text);
    margin: 0 0 0.25rem;
    line-height: 1.3;
}
.dept-form-wrap .dept-lead { font-size: 0.875rem; color: var(--dept-muted); margin: 0; }
.dept-form-wrap .dept-head .btn {
    min-height: 38px;
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
.dept-form-wrap .dept-panel-body { padding: 1.35rem 1.4rem 1.4rem; }
.dept-form-wrap .dept-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--dept-muted);
    margin: 0 0 0.9rem;
}
.dept-form-wrap .form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.35rem;
}
.dept-form-wrap .req { color: #dc3545; }
.dept-form-wrap .help-mini { font-size: 0.8rem; color: var(--dept-muted); margin-top: 0.35rem; }
.dept-form-wrap .form-control {
    min-height: 42px;
    border-color: #cbd5e1;
}
.dept-form-wrap .dept-id-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: 0.06em;
    font-weight: 700;
    max-width: 220px;
}
.dept-form-wrap .dept-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--dept-border);
}
.dept-form-wrap .dept-actions .btn {
    min-height: 40px;
    min-width: 120px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
@media (max-width: 767.98px) {
    .dept-form-wrap.container-fluid {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    .dept-form-wrap .dept-head .btn,
    .dept-form-wrap .dept-id-input,
    .dept-form-wrap .dept-actions .btn { width: 100%; max-width: none; }
    .dept-form-wrap .dept-actions { flex-direction: column; }
}
</style>

<div class="container-fluid px-4 py-3 dept-form-wrap">
    <div class="dept-head d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <h1 class="dept-title"><i class="fas fa-edit text-primary me-2"></i>Edit department</h1>
            <p class="dept-lead">Update the official name. The department ID cannot be changed.</p>
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

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">
            <div class="dept-panel">
                <div class="dept-panel-body">
                    <form method="POST" action="<?php echo APP_URL; ?>/departments/edit?id=<?php echo urlencode($department['department_id']); ?>">
                        <div class="dept-section-title">Department details</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="department_id" class="form-label">Department ID</label>
                                <input type="text" class="form-control dept-id-input" id="department_id"
                                       value="<?php echo htmlspecialchars($department['department_id']); ?>" disabled>
                                <div class="help-mini">This code is used in courses and staff records.</div>
                            </div>
                            <div class="col-12 col-md-8">
                                <label for="department_name" class="form-label">Department name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="department_name" name="department_name"
                                       value="<?php echo htmlspecialchars($department['department_name']); ?>"
                                       maxlength="60" required>
                                <div class="help-mini">Official name, maximum 60 characters.</div>
                            </div>
                        </div>
                        <div class="dept-actions">
                            <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
