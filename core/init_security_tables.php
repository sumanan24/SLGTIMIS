<?php
/**
 * Initialize Security Tables
 * Creates tables required for security and navigation features.
 * Called from index.php on application bootstrap.
 */

function initSecurityTables() {
    try {
        $db = Database::getInstance();

        require_once BASE_PATH . '/core/ActivityLogger.php';
        new ActivityLogger();

        require_once BASE_PATH . '/models/LoginAttemptModel.php';
        $loginAttemptModel = new LoginAttemptModel();
        $loginAttemptModel->createTableIfNotExists();

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userModel->addLockFieldsIfNotExists();

        require_once BASE_PATH . '/models/NavMenuModel.php';
        require_once BASE_PATH . '/models/SystemSettingModel.php';
        (new NavMenuModel())->ensureTable();
        (new SystemSettingModel())->ensureTable();

        require_once BASE_PATH . '/models/StaffNavAssignmentModel.php';
        (new StaffNavAssignmentModel())->ensureTable();

        return true;
    } catch (Exception $e) {
        error_log('Error initializing security tables: ' . $e->getMessage());
        return false;
    }
}
