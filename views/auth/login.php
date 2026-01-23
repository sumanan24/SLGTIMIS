<br>
<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="card shadow-lg border-0" style="max-width: 450px; width: 100%;">
        <div class=" text-center py-4" style="background-color: #ffffff; border-bottom: 2px solid #e0e0e0;">
            <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="Logo" class="mb-3" style="height: 70px; width: auto;">
            <h4 class="mb-0 fw-bold" style="color: #000000;">Login to System</h4>
        </div>
        <div class="card-body p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Your session has expired due to inactivity. Please log in again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo APP_URL; ?>/login">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control form-control-lg" required autofocus placeholder="Enter your username">
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control form-control-lg" required placeholder="Enter your password">
                    <div class="form-text">
                       
                        Forgot your password? <br>
                        Please contact your system administrator for assistance.
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="text-center">
                <a href="<?php echo APP_URL; ?>/home" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

