<?php
/**
 * wizard_save_progress.php — Auto-save on wizard Next/Previous (no full validation).
 * Merges empty POST fields with existing DB values; applies new uploads when provided.
 */
declare(strict_types=1);

// Always return valid JSON (avoid frontend "invalid response" when PHP warnings/fatals occur).
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if (!$err) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'] ?? 0, $fatalTypes, true)) {
        return;
    }
    // Clean any partial output and return JSON.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    error_log('wizard_save_progress fatal: ' . ($err['message'] ?? 'fatal'));
    echo json_encode(['success' => false, 'message' => 'Could not save progress.']);
});

header('Content-Type: application/json; charset=utf-8');

/** Ensure response body is pure JSON (strip BOM/whitespace/warnings). */
$jsonOut = static function (array $payload, int $status = 200): void {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

try {
    require_once __DIR__ . '/db.php';
} catch (Throwable $e) {
    error_log('wizard_save_progress db load failed: ' . $e->getMessage());
    $jsonOut(['success' => false, 'message' => 'Server database configuration error.'], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $jsonOut(['success' => false, 'message' => 'Use POST.'], 405);
}

$p = $_POST;

if (empty($p) && l05_multipart_exceeded_post_max()) {
    $jsonOut([
        'success' => false,
        'message' => 'Total upload size exceeds PHP post_max_size. Use smaller files (max 5 MB each) or increase post_max_size and upload_max_filesize in php.ini (e.g. WAMP: PHP menu → php.ini).',
    ], 413);
}

$appId = (int) ($p['application_id'] ?? 0);
$nic = nic_normalize((string) ($p['student_nic'] ?? ''));

if ($appId < 1 || !nic_valid($nic)) {
    $hint = empty($p)
        ? ' No form data was received — often caused by uploads exceeding server limits.'
        : '';
    $jsonOut(['success' => false, 'message' => 'Invalid application or NIC.' . $hint], 422);
}

$level = L05_APP_LEVEL;
try {
    $existing = l05_fetch_application_by_id_nic_level($conn, $appId, $nic, $level);
} catch (Throwable $e) {
    $jsonOut(['success' => false, 'message' => 'Database error.'], 500);
}

if (!$existing) {
    $jsonOut(['success' => false, 'message' => 'Application not found.'], 404);
}

try {
    $blockMsg = l05_other_level_application_blocks($conn, $nic, $level);
    if ($blockMsg !== null) {
        $jsonOut(['success' => false, 'message' => $blockMsg], 422);
    }
} catch (Throwable $e) {
    $jsonOut(['success' => false, 'message' => 'Could not verify NIC against existing applications.'], 500);
}

$existingPaths = [];
foreach (L05_FILE_FIELDS as $_fieldName => $dbCol) {
    $existingPaths[$dbCol] = $existing[$dbCol] ?? null;
}

try {
    $mergedPaths = l05_process_uploads($nic, $_FILES, $existingPaths);
} catch (Throwable $e) {
    $jsonOut(['success' => false, 'message' => 'Upload error: ' . $e->getMessage()], 500);
}

try {
    $row = l05_post_to_row($p, $mergedPaths);
    $row = l05_merge_post_row_with_existing($row, $existing);
} catch (Throwable $e) {
    error_log('wizard_save_progress invalid data: ' . $e->getMessage());
    $jsonOut(['success' => false, 'message' => 'Invalid data: ' . $e->getMessage()], 500);
}

try {
    l05_execute_application_update($conn, $appId, $nic, $level, $row);
} catch (Throwable $e) {
    $jsonOut(['success' => false, 'message' => 'Could not save progress.'], 500);
}

$pathsOut = [];
foreach (L05_FILE_FIELDS as $_fieldName => $dbCol) {
    $pathsOut[$dbCol] = $row[$dbCol] ?? null;
}

$jsonOut([
    'success' => true,
    'application_id' => $appId,
    'paths' => $pathsOut,
], 200);
