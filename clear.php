<?php
/**
 * Clear OPcache + stale machine face JPEG cache.
 * Protect with a basic token so it's not publicly callable by anyone.
 *
 * TODO: Change this token value before using on a public server.
 */
$token = 'CHANGE_ME_SECRET_TOKEN';

if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$messages = [];

$faceDir = __DIR__ . '/assets/img/machine_faces';
$facesDeleted = 0;
if (is_dir($faceDir)) {
    foreach (scandir($faceDir) ?: [] as $name) {
        if ($name === '.' || $name === '..' || $name === '.gitkeep') {
            continue;
        }
        if (!preg_match('/\.(jpe?g)$/i', $name)) {
            continue;
        }
        $path = $faceDir . DIRECTORY_SEPARATOR . $name;
        if (is_file($path) && @unlink($path)) {
            $facesDeleted++;
        }
    }
}
$messages[] = "Face cache files deleted: {$facesDeleted}";

if (function_exists('opcache_reset')) {
    $messages[] = opcache_reset() ? 'OPcache cleared.' : 'Failed to clear OPcache.';
} else {
    $messages[] = 'OPcache is not enabled or opcache_reset() is unavailable.';
}

clearstatcache(true);
header('Content-Type: text/plain; charset=UTF-8');
echo implode("\n", $messages);
