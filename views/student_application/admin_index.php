<?php
/** @var list<array<string, mixed>> $applications */
$applications = $applications ?? [];
?>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Online applications</h1>
        <div class="small text-muted">Level 04 &amp; 05 (from public form)</div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover w-100" id="studentApplicationsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Level</th>
                            <th>Full name</th>
                            <th>NIC</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date sent</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $r): ?>
                        <tr>
                            <td><?php echo (int) ($r['application_id'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($r['application_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['student_full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['student_nic'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['student_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['student_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-order="<?php echo htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo !empty($r['created_at']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at'])), ENT_QUOTES, 'UTF-8') : ''; ?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/student-applications/view?id=' . (int) ($r['application_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && jQuery.fn.DataTable) {
        jQuery('#studentApplicationsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']]
        });
    }
});
</script>
