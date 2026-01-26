<?php
/**
 * Authentication Controller
 */

class AuthController extends Controller {
    
    private $maxLoginAttempts = 3; // Maximum failed login attempts before lock
    private $sessionTimeout = 1800; // 30 minutes in seconds
    
    public function login() {
        // If timeout parameter exists, ensure we're not logged in
        // (session should already be cleared by index.php, but double-check)
        if (isset($_GET['timeout'])) {
            // Clear any remaining session data
            if (isset($_SESSION['user_id'])) {
                $_SESSION = [];
            }
        }
        
        // If already logged in (and no timeout parameter), redirect to appropriate dashboard
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] && !isset($_GET['timeout'])) {
            $userTable = $_SESSION['user_table'] ?? 'student';
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            
            if ($userTable === 'student') {
                $this->redirect('student/dashboard');
            } elseif ($userModel->isHOD($_SESSION['user_id'])) {
                $this->redirect('hod/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Load required models and logger
            require_once BASE_PATH . '/core/ActivityLogger.php';
            require_once BASE_PATH . '/models/LoginAttemptModel.php';
            require_once BASE_PATH . '/models/UserModel.php';
            
            $loginAttemptModel = new LoginAttemptModel();
            $userModel = new UserModel();
            $activityLogger = new ActivityLogger();
            
            if (empty($username) || empty($password)) {
                $activityLogger->log('login_attempt', 'Login attempt with empty fields', 'failed', null, $username);
                $data = [
                    'title' => 'Login',
                    'error' => 'Please enter both username and password'
                ];
                return $this->view('auth/login', $data);
            }
            
            // Check if account is locked
            if ($userModel->isAccountLockedByUsername($username)) {
                $user = $userModel->getUserByUsername($username);
                $loginAttemptModel->recordAttempt($username, 'failed');
                $activityLogger->log('login_attempt', 'Login attempt on locked account', 'failed', $user['user_id'] ?? null, $username);
                
                $data = [
                    'title' => 'Login',
                    'error' => 'This account has been locked due to multiple failed login attempts. Please contact the administrator to unlock your account.'
                ];
                return $this->view('auth/login', $data);
            }
            
            // Check failed login attempts
            $failedAttempts = $loginAttemptModel->getFailedAttemptsCount($username, 60);
            if ($failedAttempts >= $this->maxLoginAttempts) {
                // Lock the account
                $user = $userModel->getUserByUsername($username);
                if ($user) {
                    $userModel->lockAccountByUsername($username, 'Too many failed login attempts (3 attempts)');
                    $activityLogger->log('account_locked', "Account locked due to {$failedAttempts} failed login attempts", 'success', $user['user_id'], $username);
                }
                
                $data = [
                    'title' => 'Login',
                    'error' => 'This account has been locked due to multiple failed login attempts. Please contact the administrator to unlock your account.'
                ];
                return $this->view('auth/login', $data);
            }
            
            // Authentication
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT * FROM `user` WHERE `user_name` = ? AND `user_active` = 1");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (!$user) {
                    $loginAttemptModel->recordAttempt($username, 'failed');
                    $activityLogger->log('login_attempt', 'Login attempt with invalid username', 'failed', null, $username);
                    
                    $remainingAttempts = $this->maxLoginAttempts - $failedAttempts - 1;
                    $errorMsg = 'Invalid username or password';
                    if ($remainingAttempts > 0) {
                        $errorMsg .= ". You have {$remainingAttempts} attempt(s) remaining before your account will be locked.";
                    }
                    
                    $data = [
                        'title' => 'Login',
                        'error' => $errorMsg
                    ];
                    return $this->view('auth/login', $data);
                }
                
                // Check if password hash exists
                if (empty($user['user_password_hash'])) {
                    $loginAttemptModel->recordAttempt($username, 'failed');
                    $activityLogger->log('login_attempt', 'Login attempt - account not properly configured', 'failed', $user['user_id'], $username);
                    
                    $data = [
                        'title' => 'Login',
                        'error' => 'User account not properly configured. Please contact administrator.'
                    ];
                    return $this->view('auth/login', $data);
                }
                
                // Verify password - Database uses SHA2-256 hashes (64 hex characters)
                // The system stores passwords as SHA2(NIC, 256) for students/staff
                $passwordVerified = false;
                
                // Check if it's SHA2-256 hash format (64 hex characters)
                $isSha256 = (strlen($user['user_password_hash']) === 64 && ctype_xdigit($user['user_password_hash']));
                
                if ($isSha256) {
                    // It's SHA2-256 format - hash the input password and compare
                    $hashedInput = hash('sha256', $password);
                    $passwordVerified = hash_equals($user['user_password_hash'], $hashedInput);
                } else {
                    // Try PHP password_verify (for newer password_hash format if any)
                    $passwordVerified = password_verify($password, $user['user_password_hash']);
                }
                
                if ($passwordVerified) {
                    // Clear failed login attempts
                    $loginAttemptModel->clearFailedAttempts($username);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['user_name'];
                    $_SESSION['user_email'] = $user['user_email'];
                    $_SESSION['user_table'] = $user['user_table'] ?? 'student';
                    $_SESSION['last_activity'] = time(); // Set last activity time for session timeout
                    
                    // Update last login timestamp
                    try {
                        $updateStmt = $db->prepare("UPDATE `user` SET `user_last_login_timestamp` = ? WHERE `user_id` = ?");
                        $timestamp = time();
                        $updateStmt->bind_param("ii", $timestamp, $user['user_id']);
                        $updateStmt->execute();
                    } catch (Exception $e) {
                        // Ignore update errors, login should still work
                    }
                    
                    // Log successful login
                    $loginAttemptModel->recordAttempt($username, 'success');
                    $activityLogger->log('login_success', 'User successfully logged in', 'success', $user['user_id'], $username);
                    
                    // Redirect based on user type
                    $userTable = $user['user_table'] ?? 'student';
                    require_once BASE_PATH . '/models/UserModel.php';
                    $userModelCheck = new UserModel();
                    
                    if ($userTable === 'student') {
                        header("Location: " . APP_URL . "/student/dashboard");
                    } elseif ($userModelCheck->isHOD($user['user_id'])) {
                        // Redirect HOD users to HOD dashboard
                        header("Location: " . APP_URL . "/hod/dashboard");
                    } else {
                        header("Location: " . APP_URL . "/dashboard");
                    }
                    exit();
                } else {
                    // Record failed attempt
                    $loginAttemptModel->recordAttempt($username, 'failed');
                    $activityLogger->log('login_attempt', 'Login attempt with invalid password', 'failed', $user['user_id'], $username);
                    
                    $failedAttempts = $loginAttemptModel->getFailedAttemptsCount($username, 60);
                    $remainingAttempts = $this->maxLoginAttempts - $failedAttempts;
                    
                    // Check if we should lock the account now
                    if ($failedAttempts >= $this->maxLoginAttempts) {
                        $userModel->lockAccountByUsername($username, 'Too many failed login attempts (3 attempts)');
                        $activityLogger->log('account_locked', "Account locked due to {$failedAttempts} failed login attempts", 'success', $user['user_id'], $username);
                        
                        $data = [
                            'title' => 'Login',
                            'error' => 'This account has been locked due to multiple failed login attempts. Please contact the administrator to unlock your account.'
                        ];
                    } else {
                        $errorMsg = 'Invalid username or password';
                        if ($remainingAttempts > 0) {
                            $errorMsg .= ". You have {$remainingAttempts} attempt(s) remaining before your account will be locked.";
                        }
                        
                        $data = [
                            'title' => 'Login',
                            'error' => $errorMsg
                        ];
                    }
                    
                    return $this->view('auth/login', $data);
                }
            } catch (Exception $e) {
                $activityLogger->log('login_error', 'Database error during login: ' . $e->getMessage(), 'error', null, $username);
                
                $data = [
                    'title' => 'Login',
                    'error' => 'Database error: ' . $e->getMessage()
                ];
                return $this->view('auth/login', $data);
            }
        }
        
        $data = [
            'title' => 'Login',
            'page' => 'login'
        ];
        
        return $this->view('auth/login', $data);
    }
    
    public function logout() {
        // Log logout activity
        if (isset($_SESSION['user_id'])) {
            require_once BASE_PATH . '/core/ActivityLogger.php';
            $activityLogger = new ActivityLogger();
            $activityLogger->log('logout', 'User logged out', 'success', $_SESSION['user_id'], $_SESSION['user_name'] ?? null);
        }
        
        session_destroy();
        $_SESSION = [];
        $this->redirect('home');
    }
    
    /**
     * Check if session has timed out
     * Returns true if session is valid, false if timed out or invalid
     */
    public static function checkSessionTimeout() {
        // If no session data exists, return false (not logged in)
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        $timeout = 1800; // 30 minutes
        $timeSinceLastActivity = time() - $_SESSION['last_activity'];
        
        if ($timeSinceLastActivity > $timeout) {
            // Session has timed out - log it first before clearing
            require_once BASE_PATH . '/core/ActivityLogger.php';
            $userId = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['user_name'] ?? null;
            
            if ($userId) {
                try {
                    $activityLogger = new ActivityLogger();
                    $activityLogger->log('session_timeout', 'Session timed out due to inactivity', 'success', $userId, $username);
                } catch (Exception $e) {
                    // Ignore logging errors
                }
            }
            
            // Clear session data - let the caller handle session destruction/redirect
            // Don't destroy session here to avoid issues with active session
            $_SESSION['user_id'] = null;
            unset($_SESSION['user_id']);
            unset($_SESSION['user_name']);
            unset($_SESSION['user_email']);
            unset($_SESSION['user_table']);
            unset($_SESSION['last_activity']);
            
            return false;
        } else {
            // Update last activity time
            $_SESSION['last_activity'] = time();
            return true;
        }
    }
}

