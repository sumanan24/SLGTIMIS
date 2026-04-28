<?php
/**
 * Public student ID verification (QR on ID cards).
 * URL: search_student.php?mode=id&q=2025%2FICT%2F4TE025
 *
 * Served as a real file so Apache/WAMP serves it directly (see .htaccess RewriteCond %{REQUEST_FILENAME} -f).
 */

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/models/StudentModel.php';
require_once BASE_PATH . '/models/StudentEnrollmentModel.php';

/**
 * @return string|null Normalized student_id or null if invalid
 */
function verify_page_parse_student_id(): ?string {
    $mode = isset($_GET['mode']) ? strtolower(trim((string)$_GET['mode'])) : '';
    if ($mode !== 'id') {
        return null;
    }
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($q === '' || strlen($q) > 120) {
        return null;
    }
    if (!preg_match('/^[A-Za-z0-9\/_\-]+$/', $q)) {
        return null;
    }
    return $q;
}

$studentId = verify_page_parse_student_id();
$title = 'Student verification';
$invalidRequest = false;
$message = null;
$student = null;
$enrollment = null;
$profileImageUrl = null;

if ($studentId === null) {
    $invalidRequest = true;
    $message = 'This link is not valid. Use the QR code or link provided on the student ID card.';
} else {
    $studentModel = new StudentModel();
    $student = $studentModel->find($studentId);
    if (!$student) {
        $message = 'No student was found for this registration number.';
    } else {
        $enrollmentModel = new StudentEnrollmentModel();
        $enrollment = $enrollmentModel->getCurrentEnrollment($studentId);
        if (!$enrollment) {
            $enrollment = $enrollmentModel->getLatestEnrollment($studentId);
        }
        $profileImageUrl = $studentModel->getProfileImagePath($student);
    }
}

header('X-Robots-Tag: noindex, nofollow');
include BASE_PATH . '/views/public/student_id_verify.php';
