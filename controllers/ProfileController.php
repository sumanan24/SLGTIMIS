<?php
/**
 * Profile Controller
 */

class ProfileController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM `user` WHERE `user_id` = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                $_SESSION['error'] = 'User not found.';
                $this->redirect('dashboard');
                return;
            }
            
            $data = [
                'title' => 'Profile',
                'page' => 'profile',
                'user' => $user,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            
            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('profile/index', $data);
        } catch (Exception $e) {
            $data = [
                'title' => 'Profile',
                'page' => 'profile',
                'error' => 'Error loading profile: ' . $e->getMessage()
            ];
            return $this->view('profile/index', $data);
        }
    }
    
    public function update() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('profile');
            return;
        }
        
        try {
            $db = Database::getInstance();
            
            // Get current user data
            $stmt = $db->prepare("SELECT * FROM `user` WHERE `user_id` = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentUser = $result->fetch_assoc();
            
            if (!$currentUser) {
                $_SESSION['error'] = 'User not found.';
                $this->redirect('profile');
                return;
            }
            
            // Get form data
            $userName = trim($this->post('user_name', ''));
            $userEmail = trim($this->post('user_email', ''));
            $currentPassword = $this->post('current_password', '');
            $newPassword = $this->post('new_password', '');
            $confirmPassword = $this->post('confirm_password', '');
            
            // Validation
            if (empty($userName)) {
                $_SESSION['error'] = 'Username is required.';
                $this->redirect('profile');
                return;
            }
            
            if (empty($userEmail)) {
                $_SESSION['error'] = 'Email is required.';
                $this->redirect('profile');
                return;
            }
            
            if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Invalid email format.';
                $this->redirect('profile');
                return;
            }
            
            // Check if username already exists (excluding current user)
            $checkStmt = $db->prepare("SELECT user_id FROM `user` WHERE `user_name` = ? AND `user_id` != ?");
            $checkStmt->bind_param("si", $userName, $_SESSION['user_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $_SESSION['error'] = 'Username already exists.';
                $this->redirect('profile');
                return;
            }
            
            // Check if email already exists (excluding current user)
            $checkStmt = $db->prepare("SELECT user_id FROM `user` WHERE `user_email` = ? AND `user_id` != ?");
            $checkStmt->bind_param("si", $userEmail, $_SESSION['user_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $_SESSION['error'] = 'Email already exists.';
                $this->redirect('profile');
                return;
            }
            
            // Update password if provided
            $passwordUpdated = false;
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $_SESSION['error'] = 'Current password is required to change password.';
                    $this->redirect('profile');
                    return;
                }
                
                if ($newPassword !== $confirmPassword) {
                    $_SESSION['error'] = 'New password and confirm password do not match.';
                    $this->redirect('profile');
                    return;
                }
                
                if (strlen($newPassword) < 6) {
                    $_SESSION['error'] = 'New password must be at least 6 characters long.';
                    $this->redirect('profile');
                    return;
                }
                
                // Verify current password
                $isSha256 = (strlen($currentUser['user_password_hash']) === 64 && ctype_xdigit($currentUser['user_password_hash']));
                $passwordVerified = false;
                
                if ($isSha256) {
                    $hashedInput = hash('sha256', $currentPassword);
                    $passwordVerified = hash_equals($currentUser['user_password_hash'], $hashedInput);
                } else {
                    $passwordVerified = password_verify($currentPassword, $currentUser['user_password_hash']);
                }
                
                if (!$passwordVerified) {
                    $_SESSION['error'] = 'Current password is incorrect.';
                    $this->redirect('profile');
                    return;
                }
                
                // Hash new password
                $newPasswordHash = hash('sha256', $newPassword);
                $passwordUpdated = true;
            }
            
            // Update user data
            if ($passwordUpdated) {
                $updateStmt = $db->prepare("UPDATE `user` SET `user_name` = ?, `user_email` = ?, `user_password_hash` = ? WHERE `user_id` = ?");
                $updateStmt->bind_param("sssi", $userName, $userEmail, $newPasswordHash, $_SESSION['user_id']);
            } else {
                $updateStmt = $db->prepare("UPDATE `user` SET `user_name` = ?, `user_email` = ? WHERE `user_id` = ?");
                $updateStmt->bind_param("ssi", $userName, $userEmail, $_SESSION['user_id']);
            }
            
            $updateStmt->execute();
            
            // Update session
            $_SESSION['user_name'] = $userName;
            $_SESSION['user_email'] = $userEmail;
            
            $_SESSION['message'] = 'Profile updated successfully' . ($passwordUpdated ? ' (including password)' : '') . '.';
            $this->redirect('profile');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating profile: ' . $e->getMessage();
            $this->redirect('profile');
        }
    }
}

