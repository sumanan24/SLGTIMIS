<?php
$y = $year ?? [];
$usage = $usage ?? ['total' => 0, 'details' => []];
$blocked = ((int) ($usage['total'] ?? 0)) > 0;
?>
<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Academic Year</h5>
                </div>
                <div class="card-body">
                    <?php if ($blocked): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="fas fa-ban me-2"></i>
                            <div>This year cannot be deleted because it is used by <?php echo htmlspecialchars(implode(', ', $usage['details'])); ?>.</div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div><strong>Warning!</strong> This cannot be undone.</div>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Academic year</th>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($y['academic_year'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status</th>
                                    <td><?php echo htmlspecialchars($y['academic_year_status'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">1st semester</th>
                                    <td><?php echo htmlspecialchars(AcademicYearModel::formatDate($y['first_semi_start_date'] ?? '') . ' – ' . AcademicYearModel::formatDate($y['first_semi_end_date'] ?? '')); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">2nd semester</th>
                                    <td><?php echo htmlspecialchars(AcademicYearModel::formatDate($y['second_semi_start_date'] ?? '') . ' – ' . AcademicYearModel::formatDate($y['second_semi_end_date'] ?? '')); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($blocked): ?>
                        <a href="<?php echo APP_URL; ?>/academic-years" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
                    <?php else: ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/academic-years/delete?id=<?php echo urlencode($y['academic_year'] ?? ''); ?>">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Yes, delete</button>
                                <a href="<?php echo APP_URL; ?>/academic-years" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
