<?php
/**
 * wizard_save_progress.php — Auto-save on wizard Next/Previous (no full validation).
 * Merges empty POST fields with existing DB values; applies new uploads when provided.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST.']);
    exit;
}

$p = $_POST;

if (empty($p) && l05_multipart_exceeded_post_max()) {
    http_response_code(413);
    echo json_encode([
        'success' => false,
        'message' => 'Total upload size exceeds PHP post_max_size. Use smaller files (max 5 MB each) or increase post_max_size and upload_max_filesize in php.ini (e.g. WAMP: PHP menu → php.ini).',
    ]);
    exit;
}

$appId = (int) ($p['application_id'] ?? 0);
$nic = nic_normalize((string) ($p['student_nic'] ?? ''));

if ($appId < 1 || !nic_valid($nic)) {
    http_response_code(422);
    $hint = empty($p)
        ? ' No form data was received — often caused by uploads exceeding server limits.'
        : '';
    echo json_encode(['success' => false, 'message' => 'Invalid application or NIC.' . $hint]);
    exit;
}

$level = L05_APP_LEVEL;
try {
    $existing = l05_fetch_application_by_id_nic_level($conn, $appId, $nic, $level);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
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

try {
    $mergedPaths = l05_process_uploads($nic, $_FILES, $existingPaths);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $e->getMessage()]);
    exit;
}

try {
    $row = l05_post_to_row($p, $mergedPaths);
    $row = l05_merge_post_row_with_existing($row, $existing);
} catch (Throwable $e) {
    error_log('wizard_save_progress invalid data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid data: ' . $e->getMessage()]);
    exit;
}

try {
    l05_execute_application_update($conn, $appId, $nic, $level, $row);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save progress.']);
    exit;
}

$pathsOut = [];
foreach (L05_FILE_FIELDS as $_fieldName => $dbCol) {
    $pathsOut[$dbCol] = $row[$dbCol] ?? null;
}

echo json_encode([
    'success' => true,
    'application_id' => $appId,
    'paths' => $pathsOut,
], JSON_UNESCAPED_UNICODE);
