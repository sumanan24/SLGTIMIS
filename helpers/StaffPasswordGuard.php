<?php
/**
 * Keeps staff (non-student) accounts on the "choose a new password" page while
 * `user.must_change_password` is set (new account or password reset to the default).
 */
class StaffPasswordGuard
{
    /**
     * @param string $uri App-relative path from RequestPath::resolve()
     */
    public static function enforce(string $uri): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_table'] ?? '') === 'student') {
            return;
        }

        $norm = strtolower(trim($uri, '/'));
        if (in_array($norm, ['logout', 'login', 'account/change-password'], true)) {
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        if (!(new UserModel())->mustChangePassword((int) $_SESSION['user_id'])) {
            return;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Please change your password first.']);
            exit();
        }
        header('Location: ' . APP_URL . '/account/change-password');
        exit();
    }
}
