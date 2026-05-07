<?php
/**
 * db.php — mysqli connection for Level 05 wizard (uses `student_applications` table).
 */
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/database.php';
if (!is_file($configPath)) {
    throw new RuntimeException('Configuration missing. Expected: config/database.php');
}

require_once $configPath;
require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__) . '/core/Database.php';

/** @var mysqli $conn */
$conn = Database::getInstance()->getConnection();

// Ensure the table exists (and apply small schema migrations if needed).
// If the DB user lacks privileges to create/alter tables, fail fast with a clear server-side log.
l05_ensure_student_applications_table($conn);
try {
    $chk = $conn->query("SHOW TABLES LIKE 'student_applications'");
    $exists = $chk && $chk->num_rows > 0;
    if ($chk) {
        $chk->free();
    }
    if (!$exists) {
        throw new RuntimeException("Table 'student_applications' does not exist (and could not be created).");
    }
} catch (Throwable $e) {
    error_log('level05application/db.php table check failed: ' . $e->getMessage());
    throw $e;
}
