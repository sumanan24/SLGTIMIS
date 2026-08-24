<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
$c = $complaint ?? [];
$id = (int) ($c['id'] ?? 0);
$canManage = !empty($canManage);
$readOnly = !empty($readOnly);
?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><?php echo $e($c['reference_no'] ?? 'Complaint Letter'); ?></h1>
            <p class="text-muted small mb-0"><?php echo $e($c['subject'] ?? ''); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a href="<?php echo APP_URL; ?>/complaint-letters" class="btn btn-outline-secondary btn-sm">Back</a>
            <a href="<?php echo APP_URL; ?>/complaint-letters/preview?id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="fas fa-file-alt me-1"></i> Preview</a>
            <?php if ($canManage): ?>
            <a href="<?php echo APP_URL; ?>/complaint-letters/edit?id=<?php echo $id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i> Edit</a>
            <a href="<?php echo APP_URL; ?>/complaint-letters/pdf?id=<?php echo $id; ?>" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf me-1"></i> Generate PDF</a>
            <a href="<?php echo APP_URL; ?>/complaint-letters/print?id=<?php echo $id; ?>" class="btn btn-info btn-sm" target="_blank"><i class="fas fa-print me-1"></i> Print</a>
            <?php else: ?>
            <a href="<?php echo APP_URL; ?>/complaint-letters/pdf?id=<?php echo $id; ?>" class="btn btn-outline-danger btn-sm"><i class="fas fa-file-pdf me-1"></i> View PDF</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <style><?php echo ComplaintLetterPdfHelper::complaintLetterStylesheet(); ?> .cl-letter-rich{font-size:14px;}</style>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Letter Information</strong></div>
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted">Date:</span> <?php echo $e($c['letter_date'] ?? ''); ?></div>
                        <div class="col-md-4"><span class="text-muted">Department:</span> <?php echo $e($c['department_name'] ?? $c['department_id'] ?? ''); ?></div>
                        <div class="col-md-4"><span class="text-muted">Course:</span> <?php echo $e($c['course_name'] ?? '—'); ?></div>
                        <div class="col-md-4"><span class="text-muted">Academic Year:</span> <?php echo $e($c['academic_year'] ?? ''); ?></div>
                        <div class="col-md-4"><span class="text-muted">Status:</span> <span class="badge bg-<?php echo ($c['status'] ?? '') === 'final' ? 'success' : 'secondary'; ?>"><?php echo $e(ucfirst((string)($c['status'] ?? 'draft'))); ?></span></div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Complaint Details</strong></div>
                <div class="card-body"><div class="small cl-letter-rich cl-body"><?php echo ComplaintLetterPdfHelper::formatLetterContent($c['complaint_body'] ?? ''); ?></div></div>
            </div>
            <div class="card">
                <div class="card-header py-2"><strong>Students</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Student ID</th><th>Name</th><th>Course</th></tr></thead>
                        <tbody>
                        <?php if (empty($students)): ?>
                        <tr><td colspan="3" class="text-muted text-center py-3">No students linked.</td></tr>
                        <?php else: foreach ($students as $s): ?>
                        <tr>
                            <td><?php echo $e($s['student_id'] ?? ''); ?></td>
                            <td><?php echo $e($s['student_name'] ?? ''); ?></td>
                            <td><?php echo $e($s['course_name'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Audit Trail</strong></div>
                <div class="card-body small">
                    <?php if (empty($auditLogs)): ?>
                    <p class="text-muted mb-0">No audit entries yet.</p>
                    <?php else: foreach ($auditLogs as $log): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div><strong><?php echo $e(ucfirst(str_replace('_', ' ', (string)($log['action'] ?? '')))); ?></strong></div>
                        <div class="text-muted"><?php echo $e($log['created_at'] ?? ''); ?> · <?php echo $e($log['user_name'] ?? ''); ?> (<?php echo $e($log['user_role'] ?? ''); ?>)</div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <?php if ($canManage): ?>
            <form method="post" action="<?php echo APP_URL; ?>/complaint-letters/delete" onsubmit="return confirm('Delete this complaint letter?\n\nThe record will be soft-deleted and kept in audit history.');">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Delete Complaint Letter</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
