<?php
/**
 * update.php — Update existing Level 05 application + optional new uploads.
 * POST multipart: application_id, student_nic, all fields; files optional (keeps existing if omitted).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST.']);
    exit;
}

$dbOk = true;
try {
    require_once __DIR__ . '/db.php';
} catch (Throwable $e) {
    $dbOk = false;
    error_log('level05application/update db load failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server database configuration error.']);
    exit;
}

$p = $_POST;

if (empty($p) && l05_multipart_exceeded_post_max()) {
    http_response_code(413);
    echo json_encode([
        'success' => false,
        'message' => 'Total upload size exceeds PHP post_max_size. Use smaller files or increase post_max_size and upload_max_filesize in php.ini.',
    ]);
    exit;
}

$appId = (int) ($p['application_id'] ?? 0);
$nic = nic_normalize((string) ($p['student_nic'] ?? ''));

if ($appId < 1 || !nic_valid($nic)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid application or NIC.']);
    exit;
}

$level = L05_APP_LEVEL;
try {
    $existing = l05_fetch_application_by_id_nic_level($conn, $appId, $nic, $level);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error while loading your application.']);
    exit;
}

if (!$existing) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Application not found.']);
    exit;
}

$existingPaths = [];
foreach (L05_FILE_FIELDS as $_fieldName => $dbCol) {
    $existingPaths[$dbCol] = $existing[$dbCol] ?? null;
}

$errors = l05_validate_application($p, $_FILES, true, $existingPaths);
if ($errors !== []) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
    exit;
}

try {
    $mergedPaths = l05_process_uploads($nic, $_FILES, $existingPaths);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $e->getMessage()]);
    exit;
}

try {
    $row = l05_post_to_row($p, $mergedPaths);
} catch (Throwable $e) {
    error_log('level05application/update invalid data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid data: ' . $e->getMessage()]);
    exit;
}

try {
    l05_execute_application_update($conn, $appId, $nic, $level, $row);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update application.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Application updated successfully.', 'application_id' => $appId]);
