<?php
// Simple OPcache clear script.
// Protect with a basic token so it's not publicly callable by anyone.

// TODO: Change this token value before using on a public server.
$token = 'CHANGE_ME_SECRET_TOKEN';

if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (!function_exists('opcache_reset')) {
    echo 'OPcache is not enabled or opcache_reset() is unavailable.';
    exit;
}

if (opcache_reset()) {
    echo 'OPcache cleared!';
} else {
    echo 'Failed to clear OPcache.';
}

