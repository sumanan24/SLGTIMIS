<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; text-align: center;">
    <div>
        <h1 style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;">Unable to load page</h1>
        <p style="color: var(--text-gray); margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($error ?? 'Something went wrong. Please try again.'); ?>
        </p>
        <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-primary">Go to Dashboard</a>
        <a href="<?php echo APP_URL; ?>/home" class="btn btn-outline-secondary">Go to Home</a>
    </div>
</div>
