<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Timetable Entry</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                        <div>
                            <strong>Warning!</strong> Are you sure you want to delete this timetable entry? This action cannot be undone.
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Weekday:</th>
                                    <td><?php echo htmlspecialchars($timetable['weekday']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Period:</th>
                                    <td><?php echo htmlspecialchars($timetable['period']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Module ID:</th>
                                    <td><?php echo htmlspecialchars($timetable['module_id'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Staff:</th>
                                    <td><?php echo htmlspecialchars($timetable['staff_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Classroom:</th>
                                    <td><?php echo htmlspecialchars($timetable['classroom'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Group:</th>
                                    <td><?php echo htmlspecialchars($timetable['group_name'] ?? 'N/A'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/group-timetable/delete?id=<?php echo urlencode($timetable['timetable_id']); ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Yes, Delete Entry
                            </button>
                            <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($timetable['group_id']); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

