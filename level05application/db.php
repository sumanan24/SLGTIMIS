<?php
/**
 * db.php — mysqli connection for Level 05 wizard (uses `student_applications` table).
 */
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/database.php';
if (!is_file($configPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Configuration missing. Expected: config/database.php';
    exit;
}

require_once $configPath;
require_once __DIR__ . '/helpers.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/** @var mysqli $conn */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset(defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');

l05_ensure_student_applications_table($conn);
