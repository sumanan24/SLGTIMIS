<?php $entry = $entry ?? []; ?>
<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Timetable Entry</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong>Are you sure you want to delete this entry?</strong> This cannot be undone.
                    </div>
                    <table class="table table-bordered">
                        <tr><th class="bg-light" style="width: 140px;">Day</th><td><?php echo htmlspecialchars($entry['day'] ?? '—'); ?></td></tr>
                        <tr><th class="bg-light">Time Slot</th><td><?php echo htmlspecialchars($entry['time_slot'] ?? '—'); ?></td></tr>
                        <tr><th class="bg-light">Module / Subject</th><td><?php echo htmlspecialchars($entry['module_name'] ?? $entry['subject'] ?? '—'); ?></td></tr>
                        <tr><th class="bg-light">Type</th><td><?php echo htmlspecialchars($entry['session_type'] ?? '—'); ?></td></tr>
                        <tr><th class="bg-light">Lecturer</th><td><?php echo htmlspecialchars($entry['staff_name'] ?? $entry['lecturer'] ?? '—'); ?></td></tr>
                        <tr><th class="bg-light">Room</th><td><?php echo htmlspecialchars($entry['room'] ?? '—'); ?></td></tr>
                    </table>
                    <form method="post" action="<?php echo APP_URL; ?>/group-timetable/delete?id=<?php echo urlencode($entry['id'] ?? ''); ?>" class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Yes, Delete</button>
                        <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($entry['group_id'] ?? ''); ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
