<?php
/**
 * Account Controller
 *
 * Forced first-login password change for staff (and other non-student) accounts.
 * A staff account gets `user.must_change_password = 1` when it is created with, or reset to,
 * the default password. StaffPasswordGuard keeps the user on this page until a new password is saved.
 */

class AccountController extends Controller {

    const DEFAULT_PASSWORD = 'Slgti@1234';

    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        // Students have their own onboarding flow.
        if (($_SESSION['user_table'] ?? '') === 'student') {
            $this->redirect('student/dashboard');
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userId = (int) $_SESSION['user_id'];

        if (!$userModel->mustChangePassword($userId)) {
            $this->redirect('dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $data = [
                'title' => 'Change password',
                'page' => 'account-change-password',
                'use_bare_layout' => true,
                'error' => $_SESSION['error'] ?? null,
            ];
            unset($_SESSION['error']);
            return $this->view('account/change-password', $data);
        }

        $old = (string) $this->post('old_password', '');
        $new = (string) $this->post('new_password', '');
        $confirm = (string) $this->post('confirm_password', '');
        $back = 'account/change-password';

        if ($old === '' || $new === '' || $confirm === '') {
            $_SESSION['error'] = 'Current password, new password and confirm password are required.';
            $this->redirect($back);
            return;
        }
        if ($new !== $confirm) {
            $_SESSION['error'] = 'New password and confirm password do not match.';
            $this->redirect($back);
            return;
        }
        if (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/\d/', $new)) {
            $_SESSION['error'] = 'Password must be at least 8 characters and include 1 capital letter, 1 small letter and 1 number.';
            $this->redirect($back);
            return;
        }
        if ($new === $old || $new === self::DEFAULT_PASSWORD) {
            $_SESSION['error'] = 'Choose a new password that is different from the default password.';
            $this->redirect($back);
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT `user_password_hash` FROM `user` WHERE `user_id` = ? AND `user_active` = 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $current = (string) ($row['user_password_hash'] ?? '');
            if ($current === '') {
                $_SESSION['error'] = 'Your account is not set up correctly. Please contact the administrator.';
                $this->redirect($back);
                return;
            }

            $isSha256 = strlen($current) === 64 && ctype_xdigit($current);
            $verified = $isSha256 ? hash_equals($current, hash('sha256', $old)) : password_verify($old, $current);
            if (!$verified) {
                $_SESSION['error'] = 'Current password is incorrect.';
                $this->redirect($back);
                return;
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE `user` SET `user_password_hash` = ?, `must_change_password` = 0 WHERE `user_id` = ?");
            $update->bind_param('si', $newHash, $userId);
            if (!$update->execute()) {
                $_SESSION['error'] = 'Could not save the new password. Please try again.';
                $this->redirect($back);
                return;
            }

            $this->logActivity('UPDATE', 'user_password', (string) $userId, 'Password changed after first login / reset', null, null);
            $_SESSION['message'] = 'Password changed. Welcome!';
            $this->redirect($userModel->isHOD($userId) ? 'hod/dashboard' : 'dashboard');
        } catch (Throwable $e) {
            error_log('AccountController::changePassword - ' . $e->getMessage());
            $_SESSION['error'] = 'Error changing password. Please try again.';
            $this->redirect($back);
        }
    }
}
