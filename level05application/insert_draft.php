<?php
/**
 * insert_draft.php — After NIC check (new applicant): create a minimal Level 05 row (NIC + placeholder name).
 * Wizard then uses update.php for final submit. POST: student_nic (multipart or x-www-form-urlencoded).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST.']);
    exit;
}

try {
    require_once __DIR__ . '/db.php';
} catch (Throwable $e) {
    error_log('level05application/insert_draft db load failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server database configuration error.']);
    exit;
}

$nic = nic_normalize((string) ($_POST['student_nic'] ?? ''));
if (!nic_valid($nic)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid NIC.']);
    exit;
}

try {
    $blockMsg = l05_other_level_application_blocks($conn, $nic, L05_APP_LEVEL);
    if ($blockMsg !== null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $blockMsg]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

try {
    $row = l05_fetch_application_by_nic_level($conn, $nic, L05_APP_LEVEL);
    if ($row) {
        echo json_encode([
            'success' => true,
            'application_id' => (int) $row['application_id'],
            'already_existed' => true,
        ]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

$level = L05_APP_LEVEL;
$placeholder = L05_DRAFT_FULL_NAME_PLACEHOLDER;
$sql = 'INSERT INTO `student_applications` (`application_level`, `student_nic`, `student_full_name`) VALUES (?, ?, ?)';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed.']);
    exit;
}
$stmt->bind_param('sss', $level, $nic, $placeholder);
try {
    $stmt->execute();
} catch (Throwable $e) {
    $stmt->close();
    if (stripos($e->getMessage(), 'Duplicate') !== false) {
        try {
            $again = l05_fetch_application_by_nic_level($conn, $nic, L05_APP_LEVEL);
            if ($again) {
                echo json_encode([
                    'success' => true,
                    'application_id' => (int) $again['application_id'],
                    'already_existed' => true,
                ]);
                exit;
            }
        } catch (Throwable $e2) {
        }
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This NIC is already registered for Level 05.']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create application.']);
    exit;
}
$newId = (int) $conn->insert_id;
$stmt->close();

echo json_encode(['success' => true, 'application_id' => $newId, 'already_existed' => false]);
