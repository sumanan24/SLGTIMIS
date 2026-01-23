<style>
    /* CSS Cache Version: <?php echo time(); ?> */
    /* Video Background */
    .video-background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        overflow: hidden;
    }
    
    .video-background video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        min-width: 100%;
        min-height: 100%;
        object-fit: cover;
        z-index: 1;
    }
    
    /* Overlay for better readability */
    .video-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        z-index: 1;
    }
    
    /* Welcome Card - Professional Navy Blue, White, Soft Gray Theme */
    .welcome-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 2.5rem 2rem;
        box-shadow: 0 8px 24px rgba(0, 31, 63, 0.2);
        border: 2px solid #001f3f;
        position: relative;
        overflow: visible;
    }
    
    /* Footer */
    .home-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        padding: 1rem;
        text-align: center;
        z-index: 10;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .home-footer p {
        margin: 0;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.875rem;
    }
    
    .home-footer .copyright {
        color: rgba(255, 255, 255, 0.95);
        font-weight: 600;
    }
    
    .home-footer .developer {
        color: rgba(255, 206, 0, 0.95);
        margin-top: 0.25rem;
    }
</style>

<div style="position: relative; width: 100%; min-height: calc(100vh - 50px); overflow: hidden;">
    <!-- Video Background -->
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="<?php echo APP_URL; ?>/assets/video/desktop.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="video-overlay"></div>
    </div>
    
    <!-- Content Container with White Card -->
    <div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 50px); padding: 2rem 1rem; position: relative; z-index: 2;">
        <div class="container text-center" style="max-width: 600px; margin: 0 auto;">
            <div class="welcome-card">
                <div class="mb-2 d-flex justify-content-center" style="position: relative; z-index: 1;">
                    <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="SLGTI Logo" class="img-fluid" style="max-height: 60px; width: auto;">
                </div>
                <h1 class="h3 fw-bold mb-1" style="color: #000000; font-size: 1.75rem; margin-left: auto; margin-right: auto; position: relative; z-index: 1;">Sri Lanka German Training Institute</h1>
                <p class="mb-2" style="color: #333333; font-size: 0.95rem; max-width: 500px; margin-left: auto; margin-right: auto; padding: 0 1rem; line-height: 1.4; position: relative; z-index: 1;">Welcome to the SLGTI MIS

Welcome to the SLGTI Management Information System. This system helps manage student records, courses, and institute activities in an easy and organized way, making daily work faster and more efficient.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="mt-2 d-flex justify-content-center gap-3 flex-wrap" style="position: relative; z-index: 1;">
                        <a href="<?php echo APP_URL; ?>/login" class="btn px-4 py-2 shadow" style="background: #001f3f; border: none; color: #ffffff; font-size: 0.9rem; font-weight: 600; box-shadow: 0 4px 12px rgba(0, 31, 63, 0.3); transition: all 0.3s ease;">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to System
                        </a>
                        <a href="<?php echo APP_URL; ?>/students" class="btn px-4 py-2 shadow" style="background: #001f3f; border: none; color: #ffffff; font-size: 0.9rem; font-weight: 600; box-shadow: 0 4px 12px rgba(0, 31, 63, 0.3); transition: all 0.3s ease;">
                            <i class="fas fa-search me-2"></i>Search Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="home-footer">
        <p class="copyright">&copy; <?php echo date('Y'); ?> Copyright SLGTI</p>
        <p class="developer">Developed by sicode</p>
    </footer>
</div>

