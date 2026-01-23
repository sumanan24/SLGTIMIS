<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-sync me-2"></i>Import Existing Student Images</h5>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <a href="<?php echo APP_URL; ?>/students/upload-images" class="btn btn-light btn-sm">
                                <i class="fas fa-upload me-1"></i>Upload New Images
                            </a>
                            <a href="<?php echo APP_URL; ?>/students" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Students
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start" role="alert">
                            <i class="fas fa-exclamation-circle me-2 mt-1"></i>
                            <div><?php echo $error; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-start" role="alert">
                            <i class="fas fa-check-circle me-2 mt-1"></i>
                            <div><?php echo $message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Instructions -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>Instructions:</h6>
                        <ul class="mb-0">
                            <li>Select images from the directory below to import them into student records</li>
                            <li>Images are matched to students based on filename containing the student ID</li>
                            <li>Already imported images are marked and can be re-imported if needed</li>
                            <li>Images that cannot be matched to any student are shown with a warning</li>
                        </ul>
                    </div>
                    
                    <!-- Directory Status -->
                    <?php if ($targetDir): ?>
                        <div class="card border mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="fas fa-folder me-2"></i>Directory</h6>
                                <div class="d-flex align-items-center text-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span><code><?php echo htmlspecialchars($targetDir); ?></code></span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Image directory does not exist. Please upload images first or create the directory.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($existingImages)): ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/students/import-images" id="importForm">
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <h6 class="mb-0 fw-bold"><i class="fas fa-images me-2"></i>Existing Images (<?php echo count($existingImages); ?>)</h6>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">
                                                <i class="fas fa-check-square me-1"></i>Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectNoneBtn">
                                                <i class="fas fa-square me-1"></i>Select None
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3" id="imageGrid">
                                        <?php foreach ($existingImages as $index => $image): ?>
                                            <div class="col-md-4 col-lg-3">
                                                <div class="card h-100 border <?php echo $image['match_status'] === 'matched' ? 'border-success' : ($image['match_status'] === 'already_imported' ? 'border-info' : 'border-warning'); ?>">
                                                    <div class="card-body p-2">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input image-checkbox" 
                                                                   type="checkbox" 
                                                                   name="selected_files[]" 
                                                                   value="<?php echo htmlspecialchars($image['filename']); ?>"
                                                                   id="img_<?php echo $index; ?>"
                                                                   <?php echo $image['match_status'] === 'matched' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label fw-bold" for="img_<?php echo $index; ?>">
                                                                <?php echo htmlspecialchars($image['filename']); ?>
                                                            </label>
                                                        </div>
                                                        
                                                        <div class="text-center mb-2">
                                                            <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($image['filename']); ?>"
                                                                 class="img-thumbnail" 
                                                                 style="max-width: 100%; max-height: 150px; object-fit: cover; cursor: pointer;"
                                                                 onclick="document.getElementById('img_<?php echo $index; ?>').click();">
                                                        </div>
                                                        
                                                        <div class="small">
                                                            <div class="mb-1">
                                                                <strong>Size:</strong> <?php echo number_format($image['size'] / 1024, 2); ?> KB
                                                            </div>
                                                            
                                                            <?php if ($image['match_status'] === 'matched'): ?>
                                                                <div class="text-success">
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                    <strong>Student:</strong> <?php echo htmlspecialchars($image['student_id']); ?>
                                                                    <?php if ($image['student_name']): ?>
                                                                        <br><small>(<?php echo htmlspecialchars($image['student_name']); ?>)</small>
                                                                    <?php endif; ?>
                                                                    <?php if ($image['is_imported']): ?>
                                                                        <br><span class="badge bg-info mt-1">Already Imported</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php elseif ($image['match_status'] === 'already_imported'): ?>
                                                                <div class="text-info">
                                                                    <i class="fas fa-info-circle me-1"></i>
                                                                    <strong>Already Imported</strong>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="text-warning">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                                    <strong>No Match Found</strong>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    <span id="selectedCount">0</span> image(s) selected
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary" id="importBtn">
                                        <i class="fas fa-sync me-1"></i>Import Selected Images
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/students/upload-images" class="btn btn-secondary ms-2">
                                        <i class="fas fa-upload me-1"></i>Upload New Images
                                    </a>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No images found in the directory.</p>
                            <a href="<?php echo APP_URL; ?>/students/upload-images" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>Upload Images
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllBtn');
    const selectNoneBtn = document.getElementById('selectNoneBtn');
    const checkboxes = document.querySelectorAll('.image-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const importBtn = document.getElementById('importBtn');
    const importForm = document.getElementById('importForm');
    
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.image-checkbox:checked').length;
        selectedCount.textContent = selected;
        importBtn.disabled = selected === 0;
    }
    
    // Select All
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();
        });
    }
    
    // Select None
    if (selectNoneBtn) {
        selectNoneBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
        });
    }
    
    // Update count on checkbox change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    // Initial count
    updateSelectedCount();
    
    // Form submission
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.image-checkbox:checked').length;
            if (selected === 0) {
                e.preventDefault();
                alert('Please select at least one image to import.');
                return false;
            }
            
            if (!confirm('This will update student records with the selected images. Continue?')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<style>
.image-checkbox:checked + label {
    color: var(--primary-navy);
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

#importBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

