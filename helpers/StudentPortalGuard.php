<?php
/**
 * First-login gates for the student portal: password change, then personal + parent details.
 */
class StudentPortalGuard
{
    /**
     * @param string $uri App-relative path from RequestPath::resolve()
     */
    public static function enforce(string $uri): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_table'] ?? '') !== 'student') {
            return;
        }

        $norm = strtolower(trim($uri, '/'));
        if (in_array($norm, ['logout', 'login'], true)) {
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $users = new UserModel();
        if ($users->mustChangePassword((int) $_SESSION['user_id'])) {
            if ($norm !== 'student/change-password') {
                header('Location: ' . APP_URL . '/student/change-password');
                exit();
            }
            return;
        }

        if ($norm === 'student/complete-profile' || $norm === 'student/change-password') {
            return;
        }

        $student = self::currentStudent();
        if ($student === null) {
            return;
        }

        require_once BASE_PATH . '/models/StudentModel.php';
        if ((new StudentModel())->isPortalProfileIncomplete($student)) {
            header('Location: ' . APP_URL . '/student/complete-profile');
            exit();
        }
    }

    public static function currentStudent(): ?array
    {
        require_once BASE_PATH . '/helpers/AdmissionRegistrationService.php';
        require_once BASE_PATH . '/models/StudentModel.php';
        $studentId = (new AdmissionRegistrationService())->resolveStudentPortalId((string) ($_SESSION['user_name'] ?? ''));
        if ($studentId) {
            $_SESSION['user_name'] = $studentId;
            $_SESSION['student_id'] = $studentId;
        }
        if (!$studentId) {
            return null;
        }
        $row = (new StudentModel())->find($studentId);
        return is_array($row) ? $row : null;
    }

    public static function nextAfterPassword(): string
    {
        $student = self::currentStudent();
        require_once BASE_PATH . '/models/StudentModel.php';
        if ($student && !(new StudentModel())->isPortalProfileIncomplete($student)) {
            return 'student/dashboard';
        }
        return 'student/complete-profile';
    }
}
