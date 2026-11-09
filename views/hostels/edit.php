<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Hostel</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/hostels/edit?id=<?php echo urlencode($hostel['id']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Hostel Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($hostel['name'] ?? ''); ?>" 
                                       required maxlength="100">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label fw-semibold">
                                    Address / Location <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($hostel['address'] ?? ''); ?>" 
                                       required maxlength="255">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label fw-semibold">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($hostel['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($hostel['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Mixed" <?php echo ($hostel['gender'] ?? '') === 'Mixed' ? 'selected' : ''; ?>>Mixed</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo !empty($hostel['active']) ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo empty($hostel['active']) ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                <h6 class="fw-bold mb-0"><i class="fas fa-door-open me-2"></i>Current Flats</h6>
                                <span class="small text-muted">
                                    Capacity: <strong><?php echo number_format($hostel['capacity']); ?></strong> beds
                                    &middot; Occupied: <strong><?php echo number_format($hostel['occupied']); ?></strong>
                                    &middot; Available: <strong><?php echo number_format($hostel['available']); ?></strong>
                                </span>
                            </div>
                            <?php if (!empty($flats)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-2" id="existingFlatsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="min-width:140px">Flat Name / No.</th>
                                                <th style="width:110px">Rooms</th>
                                                <th style="width:140px">Beds per Room</th>
                                                <th class="text-end">Total Beds</th>
                                                <th class="text-end">Occupied</th>
                                                <th class="text-center" style="width:80px">Remove</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($flats as $flat): ?>
                                                <tr class="existing-flat-row">
                                                    <td>
                                                        <input type="hidden" name="edit_flat_id[]" value="<?php echo (int) $flat['id']; ?>">
                                                        <input type="text" class="form-control form-control-sm" name="edit_flat_name[]" maxlength="50"
                                                               value="<?php echo htmlspecialchars($flat['name']); ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" name="edit_flat_rooms[]" min="1" max="100"
                                                               value="<?php echo (int) $flat['room_count']; ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" name="edit_flat_beds[]" min="1" max="50"
                                                               value="<?php echo $flat['beds_per_room'] !== null ? (int) $flat['beds_per_room'] : ''; ?>"
                                                               placeholder="<?php echo $flat['beds_per_room'] === null ? 'mixed' : ''; ?>"
                                                               title="<?php echo $flat['beds_per_room'] === null ? 'Rooms have different sizes. Leave blank to keep them, or enter a number to set all rooms.' : 'Beds in every room'; ?>">
                                                    </td>
                                                    <td class="text-end"><?php echo number_format($flat['capacity']); ?></td>
                                                    <td class="text-end"><?php echo number_format($flat['occupied']); ?></td>
                                                    <td class="text-center">
                                                        <input type="checkbox" class="form-check-input remove-flat-check" name="remove_flat[]"
                                                               value="<?php echo (int) $flat['id']; ?>" title="Delete this flat and its rooms">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-muted small mb-0">
                                    Change the name, rooms or beds and press <strong>Update Hostel</strong>. Adding rooms creates the next room numbers;
                                    removing rooms or a whole flat is only possible when no student is currently allocated to them (their past allocation records are deleted with them).
                                    Beds per room cannot go below the students currently in a room.
                                </p>
                                <script>
                                document.querySelectorAll('.remove-flat-check').forEach(function (box) {
                                    box.addEventListener('change', function () {
                                        var row = box.closest('tr');
                                        row.classList.toggle('table-danger', box.checked);
                                        row.querySelectorAll('input[type=text],input[type=number]').forEach(function (i) { i.readOnly = box.checked; });
                                    });
                                });
                                document.getElementById('existingFlatsTable').closest('form').addEventListener('submit', function (ev) {
                                    var n = document.querySelectorAll('.remove-flat-check:checked').length;
                                    if (n && !confirm('Delete ' + n + ' flat(s) and all their rooms?')) { ev.preventDefault(); }
                                });
                                </script>
                            <?php else: ?>
                                <p class="text-muted small mb-0">This hostel has no flats yet. Add them below.</p>
                            <?php endif; ?>
                        </div>
                        <?php $initialFlatRows = 0; include BASE_PATH . '/views/hostels/partials/flats_form.php'; ?>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/hostels" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Hostel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>