<?php
/**
 * fetch.php — GET application by NIC (Level 05 only).
 */
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Use GET.']);
    exit;
}

$nic = nic_normalize((string) ($_GET['nic'] ?? ''));
if ($nic === '' || !nic_valid($nic)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid NIC parameter.']);
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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit;
}

if (!$row) {
    echo json_encode(['status' => 'not_found']);
    exit;
}

echo json_encode(['status' => 'ok', 'data' => l05_row_for_json($row)], JSON_UNESCAPED_UNICODE);
