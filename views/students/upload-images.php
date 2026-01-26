<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-images me-2"></i>Upload Student Profile Images</h5>
                        <a href="<?php echo APP_URL; ?>/students" class="btn btn-light btn-sm mt-2 mt-md-0">
                            <i class="fas fa-arrow-left me-1"></i>Back to Students
                        </a>
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
                            <li>Select one or more image files (JPG, JPEG, PNG, or GIF format)</li>
                            <li>Maximum file size: 10MB per image</li>
                            <li><strong>Large Batch Support:</strong> You can upload up to 500 images at once</li>
                            <li><strong>Important:</strong> Image filenames should contain or match the student ID</li>
                            <li>Examples of valid filenames: <code>2025_AUT_4AM082.jpg</code>, <code>2025/AUT/4AM082.png</code>, or <code>4AM082.jpg</code></li>
                            <li>The system will automatically match images to students based on the filename</li>
                            <li>Images will be saved to: <code>assets/img/Student_profile/</code></li>
                            <li><strong>Note:</strong> Large uploads may take several minutes. Please be patient and do not close the browser.</li>
                        </ul>
                    </div>
                    
                    <!-- Directory Status -->
                    <div class="card border mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fas fa-folder me-2"></i>Directory Status</h6>
                            <?php if ($dirExists): ?>
                                <div class="d-flex align-items-center text-success mb-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span>Image directory exists: <code><?php echo htmlspecialchars($targetDir ?? 'assets/img/Student_profile'); ?></code></span>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center text-warning mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <span>Image directory does not exist. It will be created automatically on first upload.</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($phpSettings)): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <h6 class="small fw-bold mb-2">PHP Upload Settings:</h6>
                                    <div class="row small">
                                        <div class="col-md-6">
                                            <strong>Max File Uploads:</strong> <?php echo $phpSettings['max_file_uploads']; ?><br>
                                            <strong>Max File Size:</strong> <?php echo $phpSettings['upload_max_filesize']; ?><br>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Post Max Size:</strong> <?php echo $phpSettings['post_max_size']; ?><br>
                                            <strong>Execution Time:</strong> <?php echo $phpSettings['max_execution_time']; ?>s<br>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            For large uploads (300+ images), these limits are automatically increased during processing.
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Upload Form -->
                    <form method="POST" action="<?php echo APP_URL; ?>/students/upload-images" enctype="multipart/form-data" id="uploadForm">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="fas fa-upload me-2"></i>Select Images</h6>
                                
                                <div class="mb-3">
                                    <label for="images" class="form-label fw-semibold">
                                        Choose Image Files <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="images" 
                                           name="images[]" 
                                           accept="image/jpeg,image/jpg,image/png,image/gif" 
                                           multiple 
                                           required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        You can select multiple images at once (Hold Ctrl/Cmd to select multiple files or click and drag to select a range)
                                        <br>
                                        <strong>Tip:</strong> To select all files in a folder, press Ctrl+A (Windows) or Cmd+A (Mac) in the file dialog
                                    </div>
                                </div>
                                
                                <div id="filePreview" class="mb-3"></div>
                                
                                <!-- Progress Bar -->
                                <div id="uploadProgress" class="d-none mt-3">
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" 
                                             style="width: 0%" 
                                             id="progressBar">
                                            0%
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <small id="progressText">Uploading images, please wait...</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?php echo APP_URL; ?>/students/import-images" class="btn btn-info">
                                <i class="fas fa-sync me-1"></i>Import Existing Images
                            </a>
                            <div>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-upload me-1"></i>Upload Images
                                </button>
                                <a href="<?php echo APP_URL; ?>/students" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('images');
    const filePreview = document.getElementById('filePreview');
    const submitBtn = document.getElementById('submitBtn');
    
    fileInput.addEventListener('change', function(e) {
        const files = e.target.files;
        filePreview.innerHTML = '';
        
        if (files.length === 0) {
            return;
        }
        
        // Show selected files
        const previewCard = document.createElement('div');
        previewCard.className = 'card border-info';
        previewCard.innerHTML = '<div class="card-header bg-info bg-opacity-10"><strong>Selected Files (' + files.length + ')</strong></div><div class="card-body"><ul class="list-group list-group-flush" id="fileList"></ul></div>';
        filePreview.appendChild(previewCard);
        
        const fileList = document.getElementById('fileList');
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
            
            const fileInfo = document.createElement('div');
            fileInfo.innerHTML = '<i class="fas fa-image me-2 text-primary"></i><strong>' + escapeHtml(file.name) + '</strong><br><small class="text-muted">Size: ' + formatFileSize(file.size) + '</small>';
            
            const status = document.createElement('div');
            const maxFileSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxFileSize) {
                status.innerHTML = '<span class="badge bg-danger">Too Large (' + formatFileSize(file.size) + ')</span>';
                submitBtn.disabled = true;
            } else {
                const ext = file.name.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    status.innerHTML = '<span class="badge bg-success">Ready</span>';
                } else {
                    status.innerHTML = '<span class="badge bg-warning">Invalid Format</span>';
                    submitBtn.disabled = true;
                }
            }
            
            listItem.appendChild(fileInfo);
            listItem.appendChild(status);
            fileList.appendChild(listItem);
        }
        
        // Enable submit button if all files are valid
        if (!submitBtn.disabled) {
            submitBtn.disabled = false;
        }
    });
    
    // Prevent form submission if files are invalid
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        const files = fileInput.files;
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const submitBtn = document.getElementById('submitBtn');
        
        if (files.length === 0) {
            e.preventDefault();
            alert('Please select at least one image file.');
            return false;
        }
        
        const maxFileSize = 10 * 1024 * 1024; // 10MB
        const tooLargeFiles = [];
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.size > maxFileSize) {
                tooLargeFiles.push(file.name);
            }
        }
        
        if (tooLargeFiles.length > 0) {
            e.preventDefault();
            alert('One or more files exceed the 10MB size limit:\n' + tooLargeFiles.slice(0, 5).join('\n') + (tooLargeFiles.length > 5 ? '\n...and ' + (tooLargeFiles.length - 5) + ' more' : ''));
            return false;
        }
        
        // Show progress bar for large uploads
        if (files.length >= 10) {
            uploadProgress.classList.remove('d-none');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
            progressText.textContent = `Uploading ${files.length} images. This may take several minutes. Please do not close this page...`;
            
            // Simulate progress (actual progress will be shown after upload completes)
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += 2;
                if (progress > 90) progress = 90; // Don't complete until actually done
                progressBar.style.width = progress + '%';
                progressBar.textContent = Math.round(progress) + '%';
            }, 500);
        }
    });
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
</script>

