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
.dept-form-wrap .dept-identity {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 0.9rem 1rem;
    background: #f8fafc;
    border: 1px solid #eef2f6;
    border-radius: 0.6rem;
    margin-bottom: 1.15rem;
}
.dept-form-wrap .dept-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 0.7rem;
    background: #dbe7f3;
    color: #001f3f;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    flex-shrink: 0;
}
.dept-form-wrap .dept-identity h2 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 0.15rem;
    color: var(--dept-text);
}
.dept-form-wrap .dept-id-chip {
    display: inline-flex;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #001f3f;
    background: #eef3f8;
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
}
.dept-form-wrap .dept-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.15rem;
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
    .dept-form-wrap .dept-actions .btn { width: 100%; }
    .dept-form-wrap .dept-actions { flex-direction: column; }
}
</style>

<div class="container-fluid px-4 py-3 dept-form-wrap">
    <div class="dept-head d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <h1 class="dept-title"><i class="fas fa-trash text-danger me-2"></i>Delete department</h1>
            <p class="dept-lead">This cannot be undone. Departments linked to courses cannot be deleted.</p>
        </div>
        <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7 col-xl-6">
            <div class="dept-panel">
                <div class="dept-panel-body">
                    <div class="alert alert-warning d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                        <div>Are you sure you want to delete this department?</div>
                    </div>

                    <div class="dept-identity">
                        <span class="dept-avatar"><?php echo htmlspecialchars(strtoupper(substr($department['department_id'], 0, 3))); ?></span>
                        <div>
                            <h2><?php echo htmlspecialchars($department['department_name']); ?></h2>
                            <span class="dept-id-chip"><?php echo htmlspecialchars($department['department_id']); ?></span>
                        </div>
                    </div>

                    <form method="POST" action="<?php echo APP_URL; ?>/departments/delete?id=<?php echo urlencode($department['department_id']); ?>">
                        <div class="dept-actions">
                            <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Delete department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
