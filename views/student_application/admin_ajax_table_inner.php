<?php
/**
 * Inner HTML for student applications table area (initial page + AJAX NIC refresh).
 *
 * Expected variables: $active_tab, $filterContextSuffix, $count_new, $count_approved, $count_rejected,
 * $applications_new, $applications_approved, $applications_rejected,
 * $page_new, $page_approved, $page_rejected, $max_page_new, $max_page_approved, $max_page_rejected,
 * $per_page, $ajax_pagination, $filter_level, $esc, $viewUrl, $editUrl, $deleteAction, $can_delete, $can_edit, $formatSubmitted, $buildListUrl
 */
if (!function_exists('sa_admin_student_applications_render_table')) {
    require_once __DIR__ . '/partials/sa_admin_app_table.php';
}
if (!function_exists('sa_admin_student_applications_pagination')) {
    require_once __DIR__ . '/partials/sa_admin_app_pagination.php';
}
?>
<div class="sa-apps-table-mount-inner">
    <?php if ($active_tab === 'new'): ?>
    <div class="sa-apps-panel-lead px-3 py-3 mb-0 border-bottom bg-white" id="sa-panel-desc">
        <p class="small text-secondary mb-0"><span class="fw-semibold text-dark"><?php echo (int) $count_new; ?></span> to review<?php echo $filterContextSuffix; ?>.</p>
    </div>
    <?php
    sa_admin_student_applications_render_table(
        $applications_new,
        $esc,
        $viewUrl,
        $editUrl,
        $deleteAction,
        $can_delete,
        $can_edit,
        $formatSubmitted,
        'new',
        'bg-secondary',
        ($page_new - 1) * $per_page,
        (int) ($filter_course_priority ?? 1)
    );
    ?>
    <?php if ($max_page_new > 1): ?>
    <?php sa_admin_student_applications_pagination('new', $page_new, $max_page_new, $ajax_pagination, $filter_level, $buildListUrl, $esc); ?>
    <?php endif; ?>
    <?php elseif ($active_tab === 'approved'): ?>
    <div class="sa-apps-panel-lead px-3 py-3 mb-0 border-bottom bg-white" id="sa-panel-desc">
        <p class="small text-secondary mb-0"><span class="fw-semibold text-dark"><?php echo (int) $count_approved; ?></span> approved<?php echo $filterContextSuffix; ?>.</p>
    </div>
    <?php
    sa_admin_student_applications_render_table(
        $applications_approved,
        $esc,
        $viewUrl,
        $editUrl,
        $deleteAction,
        $can_delete,
        $can_edit,
        $formatSubmitted,
        'approved',
        'bg-success',
        ($page_approved - 1) * $per_page,
        (int) ($filter_course_priority ?? 1)
    );
    ?>
    <?php if ($max_page_approved > 1): ?>
    <?php sa_admin_student_applications_pagination('approved', $page_approved, $max_page_approved, $ajax_pagination, $filter_level, $buildListUrl, $esc); ?>
    <?php endif; ?>
    <?php else: ?>
    <div class="sa-apps-panel-lead sa-apps-panel-lead--rejected px-3 py-3 mb-0 border-bottom bg-white" id="sa-panel-desc">
        <p class="small text-secondary mb-0"><span class="fw-semibold text-dark"><?php echo (int) $count_rejected; ?></span> rejected<?php echo $filterContextSuffix; ?>.</p>
    </div>
    <?php
    sa_admin_student_applications_render_table(
        $applications_rejected,
        $esc,
        $viewUrl,
        $editUrl,
        $deleteAction,
        $can_delete,
        $can_edit,
        $formatSubmitted,
        'rejected',
        'bg-danger',
        ($page_rejected - 1) * $per_page,
        (int) ($filter_course_priority ?? 1)
    );
    ?>
    <?php if ($max_page_rejected > 1): ?>
    <?php sa_admin_student_applications_pagination('rejected', $page_rejected, $max_page_rejected, $ajax_pagination, $filter_level, $buildListUrl, $esc); ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
