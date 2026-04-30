<?php
/**
 * insert.php — New Level 05 row in student_applications + document uploads.
 * POST multipart/form-data (all fields + 6 files).
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
$errors = l05_validate_application($p, $_FILES, false);
if ($errors !== []) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
    exit;
}

$initialPaths = [];
foreach (L05_FILE_FIELDS as $dbCol) {
    $initialPaths[$dbCol] = null;
}

try {
    $row = l05_post_to_row($p, $initialPaths);
} catch (Throwable $e) {
    error_log('level05application/insert invalid data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid data: ' . $e->getMessage()]);
    exit;
}

$cols = l05_insert_column_order();
$vals = [];
foreach ($cols as $c) {
    $vals[] = $row[$c] ?? null;
}

$placeholders = implode(', ', array_fill(0, count($cols), '?'));
$sql = 'INSERT INTO `student_applications` (`' . implode('`,`', $cols) . '`) VALUES (' . $placeholders . ')';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed.']);
    exit;
}

$types = str_repeat('s', count($vals));
$params = array_merge([$types], $vals);
$refs = [];
foreach ($params as $key => $_) {
    $refs[$key] = &$params[$key];
}
call_user_func_array([$stmt, 'bind_param'], $refs);

try {
    $stmt->execute();
} catch (Throwable $e) {
    $stmt->close();
    $msg = $e->getMessage();
    if (stripos($msg, 'Duplicate') !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This NIC or email is already used for a Level 05 application.']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save application.']);
    exit;
}
$newId = (int) $conn->insert_id;
$stmt->close();

try {
    $merged = l05_process_uploads((string) $row['student_nic'], $_FILES, null);
    foreach (L05_FILE_FIELDS as $dbCol) {
        if (empty($merged[$dbCol])) {
            throw new RuntimeException('Missing upload');
        }
    }
} catch (Throwable $e) {
    $conn->query('DELETE FROM `student_applications` WHERE `application_id` = ' . (int) $newId . ' LIMIT 1');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
    exit;
}

$setParts = [];
$updVals = [];
foreach (L05_FILE_FIELDS as $dbCol) {
    $setParts[] = '`' . $dbCol . '` = ?';
    $updVals[] = $merged[$dbCol];
}
$updSql = 'UPDATE `student_applications` SET ' . implode(', ', $setParts) . ' WHERE `application_id` = ?';
$updStmt = $conn->prepare($updSql);
$typesU = str_repeat('s', count($updVals)) . 'i';
$bindU = array_merge([$typesU], $updVals, [$newId]);
$refsU = [];
foreach ($bindU as $k => $_) {
    $refsU[$k] = &$bindU[$k];
}
call_user_func_array([$updStmt, 'bind_param'], $refsU);
$updStmt->execute();
$updStmt->close();

echo json_encode(['success' => true, 'message' => 'Application submitted successfully.', 'application_id' => $newId]);
