<?php
/**
 * check_nic.php — AJAX: lookup Level 05 application by NIC (student_applications).
 * POST JSON: { "nic": "..." }
 */
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Use POST.']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$nic = nic_normalize((string) ($input['nic'] ?? ''));
if ($nic === '' || !nic_valid($nic)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Enter a valid NIC (9 digits + V or X, or 12 digits).']);
    exit;
}

try {
    require_once __DIR__ . '/db.php';
    $blockMsg = l05_other_level_application_blocks($conn, $nic, L05_APP_LEVEL);
    if ($blockMsg !== null) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => $blockMsg]);
        exit;
    }
    $row = l05_fetch_application_by_nic_level($conn, $nic, L05_APP_LEVEL);
} catch (Throwable $e) {
    // Log the underlying cause server-side (connection, missing table/column, permissions, etc.)
    $code = (int) $e->getCode();
    $extra = $code !== 0 ? ' (code=' . (string) $code . ')' : '';
    error_log('level05application/check_nic.php NIC verify failed' . $extra . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not verify NIC. Check the database connection and that the table exists.']);
    exit;
}

if ($row) {
    echo json_encode(['status' => 'exists', 'data' => l05_row_for_json($row)], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => 'new']);
