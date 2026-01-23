<?php
/**
 * Initialize Security Tables
 * This script creates the necessary tables for security features
 * Run this once or call from index.php on first load
 */

function initSecurityTables() {
    try {
        $db = Database::getInstance();
        
        // Initialize ActivityLogger to create activity_log table
        require_once BASE_PATH . '/core/ActivityLogger.php';
        $activityLogger = new ActivityLogger();
        
        // Initialize LoginAttemptModel to create login_attempts table
        require_once BASE_PATH . '/models/LoginAttemptModel.php';
        $loginAttemptModel = new LoginAttemptModel();
        $loginAttemptModel->createTableIfNotExists();
        
        // Initialize UserModel to add lock fields to user table
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userModel->addLockFieldsIfNotExists();
        
        return true;
    } catch (Exception $e) {
        error_log("Error initializing security tables: " . $e->getMessage());
        return false;
    }
}

// Auto-initialize on include if database is available
if (defined('BASE_PATH') && class_exists('Database')) {
    initSecurityTables();
}

