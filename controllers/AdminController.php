<?php
/**
 * Admin Controller
 * Handles admin-only security features: unlocking accounts, resetting passwords, viewing activity logs
 */

class AdminController extends Controller {
    
    /**
     * Check if current user is admin
     */
    private function checkAdmin() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        
        if (!$userModel->isAdmin($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * User Management - List all users with lock status and filters
     */
    public function users() {
        if (!$this->checkAdmin()) {
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/core/ActivityLogger.php';
        
        $userModel = new UserModel();
        $activityLogger = new ActivityLogger();
        
        try {
            // Get filter parameters
            $filters = [
                'search' => $this->get('search', ''),
                'status' => $this->get('status', ''),
                'lock_status' => $this->get('lock_status', ''),
                'has_login' => $this->get('has_login', ''),
                'order_by' => $this->get('order_by', 'user_name'),
                'order_dir' => $this->get('order_dir', 'ASC')
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== '';
            });
            
            $users = $userModel->getAllUsersWithLockStatus($filters);
            $lockedCount = count($userModel->getLockedAccounts());
            $totalUsers = count($userModel->getAllUsersWithLockStatus([])); // Get total without filters
            
            $data = [
                'title' => 'User Management',
                'page' => 'admin-users',
                'users' => $users,
                'lockedCount' => $lockedCount,
                'totalUsers' => $totalUsers,
                'filters' => $filters,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            
            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('admin/users', $data);
        } catch (Exception $e) {
            $data = [
                'title' => 'User Management',
                'error' => 'Error loading users: ' . $e->getMessage()
            ];
            return $this->view('admin/users', $data);
        }
    }
    
    /**
     * Unlock user account (AJAX)
     */
    public function unlockAccount() {
        if (!$this->checkAdmin()) {
            $this->json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $userId = $this->post('user_id');
        
        if (empty($userId)) {
            $this->json(['success' => false, 'message' => 'User ID is required'], 400);
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/core/ActivityLogger.php';
        require_once BASE_PATH . '/models/LoginAttemptModel.php';
        
        $userModel = new UserModel();
        $activityLogger = new ActivityLogger();
        $loginAttemptModel = new LoginAttemptModel();
        
        try {
            $user = $userModel->find($userId);
            
            if (!$user) {
                $this->json(['success' => false, 'message' => 'User not found'], 404);
                return;
            }
            
            // Unlock account
            $result = $userModel->unlockAccount($userId, $_SESSION['user_id']);
            
            if ($result) {
                // Clear failed login attempts
                $loginAttemptModel->clearFailedAttempts($user['user_name']);
                
                // Log the action
                $activityLogger->log(
                    'account_unlocked',
                    "Account unlocked by admin",
                    'success',
                    $userId,
                    $user['user_name']
                );
                
                $this->json([
                    'success' => true,
                    'message' => 'Account unlocked successfully'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to unlock account'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Reset user password (AJAX)
     */
    public function resetPassword() {
        if (!$this->checkAdmin()) {
            $this->json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $userId = $this->post('user_id');
        $newPassword = $this->post('new_password');
        
        if (empty($userId) || empty($newPassword)) {
            $this->json(['success' => false, 'message' => 'User ID and new password are required'], 400);
            return;
        }
        
        // Validate password strength (minimum 6 characters)
        if (strlen($newPassword) < 6) {
            $this->json(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/core/ActivityLogger.php';
        
        $userModel = new UserModel();
        $activityLogger = new ActivityLogger();
        
        try {
            $user = $userModel->find($userId);
            
            if (!$user) {
                $this->json(['success' => false, 'message' => 'User not found'], 404);
                return;
            }
            
            // Hash the new password using SHA256 (matching system format)
            $passwordHash = hash('sha256', $newPassword);
            
            // Reset password
            $result = $userModel->resetPassword($userId, $passwordHash, $_SESSION['user_id']);
            
            if ($result) {
                // Log the action
                $activityLogger->log(
                    'password_reset',
                    "Password reset by admin",
                    'success',
                    $userId,
                    $user['user_name']
                );
                
                $this->json([
                    'success' => true,
                    'message' => 'Password reset successfully'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to reset password'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Locked Accounts - View only locked accounts
     */
    public function lockedAccounts() {
        if (!$this->checkAdmin()) {
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/core/ActivityLogger.php';
        
        $userModel = new UserModel();
        $activityLogger = new ActivityLogger();
        
        try {
            // Get filter parameters
            $search = $this->get('search', '');
            $status = $this->get('status', '');
            $hasLogin = $this->get('has_login', '');
            $orderBy = $this->get('order_by', 'locked_at');
            $orderDir = $this->get('order_dir', 'DESC');
            
            // Build filters - always filter for locked accounts
            $filters = [
                'lock_status' => 'locked',
                'order_by' => $orderBy,
                'order_dir' => $orderDir
            ];
            
            // Add optional filters
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            if (!empty($status)) {
                $filters['status'] = $status;
            }
            if (!empty($hasLogin)) {
                $filters['has_login'] = $hasLogin;
            }
            
            $users = $userModel->getAllUsersWithLockStatus($filters);
            $lockedCount = count($userModel->getLockedAccounts());
            
            $data = [
                'title' => 'Locked Accounts',
                'page' => 'admin-locked-accounts',
                'users' => $users,
                'lockedCount' => $lockedCount,
                'filters' => $filters,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            
            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('admin/locked-accounts', $data);
        } catch (Exception $e) {
            $data = [
                'title' => 'Locked Accounts',
                'page' => 'admin-locked-accounts',
                'error' => 'Error loading locked accounts: ' . $e->getMessage()
            ];
            return $this->view('admin/locked-accounts', $data);
        }
    }
    
    /**
     * Activity Logs - View all activity logs
     */
    public function activityLogs() {
        if (!$this->checkAdmin()) {
            return;
        }
        
        require_once BASE_PATH . '/core/ActivityLogger.php';
        
        $activityLogger = new ActivityLogger();
        
        try {
            $page = (int)($this->get('page', 1));
            $perPage = 50;
            
            // Get filters
            $filters = [
                'username' => $this->get('username', ''),
                'activity_type' => $this->get('activity_type', ''),
                'status' => $this->get('status', ''),
                'date_from' => $this->get('date_from', ''),
                'date_to' => $this->get('date_to', '')
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== '';
            });
            
            $logs = $activityLogger->getLogs($filters, $page, $perPage);
            $totalLogs = $activityLogger->getLogsCount($filters);
            $totalPages = ceil($totalLogs / $perPage);
            $activityTypes = $activityLogger->getActivityTypes();
            
            $data = [
                'title' => 'Activity Logs',
                'page' => 'admin-activity-logs',
                'logs' => $logs,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalLogs' => $totalLogs,
                'filters' => $filters,
                'activityTypes' => $activityTypes
            ];
            
            return $this->view('admin/activity-logs', $data);
        } catch (Exception $e) {
            $data = [
                'title' => 'Activity Logs',
                'error' => 'Error loading activity logs: ' . $e->getMessage()
            ];
            return $this->view('admin/activity-logs', $data);
        }
    }

    /**
     * Database Backup - Download full SQL file including data, triggers, and functions
     */
    public function backupDb() {
        if (!$this->checkAdmin()) {
            return;
        }

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $tables = [];
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            $return = "-- SLGTI MIS Database Backup\n";
            $return .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $return .= "-- MySQL Version: " . $conn->server_info . "\n\n";
            $return .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $return .= "SET time_zone = \"+00:00\";\n\n";

            // 1. Export Tables and Data
            foreach ($tables as $table) {
                // Table structure
                $result = $conn->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch_row();
                $return .= "\n\n" . $row[1] . ";\n\n";

                // Table data
                $result = $conn->query("SELECT * FROM `$table`");
                $num_fields = $result->field_count;

                while ($row = $result->fetch_row()) {
                    $return .= "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $num_fields; $j++) {
                        if (isset($row[$j])) {
                            // Escape special characters
                            $escapedValue = $conn->real_escape_string($row[$j]);
                            $return .= '"' . $escapedValue . '"';
                        } else {
                            $return .= 'NULL';
                        }
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }

            // 2. Export Triggers
            $result = $conn->query("SHOW TRIGGERS");
            if ($result && $result->num_rows > 0) {
                $return .= "\n\n-- TRIGGERS --\n";
                while ($row = $result->fetch_assoc()) {
                    $return .= "DELIMITER //\n";
                    $return .= "CREATE TRIGGER `{$row['Trigger']}` {$row['Timing']} {$row['Event']} ON `{$row['Table']}` FOR EACH ROW {$row['Statement']}//\n";
                    $return .= "DELIMITER ;\n";
                }
            }

            // 3. Export Functions and Procedures
            $routines = $conn->query("SHOW PROCEDURE STATUS WHERE Db = '" . DB_NAME . "'");
            while ($row = $routines->fetch_assoc()) {
                $res = $conn->query("SHOW CREATE PROCEDURE `{$row['Name']}`");
                $create = $res->fetch_row();
                $return .= "\n\n-- PROCEDURE: {$row['Name']} --\n";
                $return .= "DELIMITER //\n" . $create[2] . "//\nDELIMITER ;\n";
            }

            $functions = $conn->query("SHOW FUNCTION STATUS WHERE Db = '" . DB_NAME . "'");
            while ($row = $functions->fetch_assoc()) {
                $res = $conn->query("SHOW CREATE FUNCTION `{$row['Name']}`");
                $create = $res->fetch_row();
                $return .= "\n\n-- FUNCTION: {$row['Name']} --\n";
                $return .= "DELIMITER //\n" . $create[2] . "//\nDELIMITER ;\n";
            }

            $return .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

            // Log activity
            require_once BASE_PATH . '/core/ActivityLogger.php';
            $activityLogger = new ActivityLogger();
            $activityLogger->log('database_backup', "Full database backup downloaded by admin", 'success');

            // Set headers for download
            $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $filename . "\"");
            echo $return;
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Backup failed: ' . $e->getMessage();
            $this->redirect('admin/users');
        }
    }
}

