<?php
define('BASE_PATH', __DIR__);
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/SLGTIMIS');
}
require_once BASE_PATH . '/core/RequestPath.php';
require_once BASE_PATH . '/core/Router.php';

$cases = [
    [
        'REQUEST_URI' => '/SLGTIMIS/dashboard',
        'SCRIPT_NAME' => '/SLGTIMIS/index.php',
    ],
    [
        'REQUEST_URI' => '/SLGTIMIS/SLGTIMIS/dashboard',
        'SCRIPT_NAME' => '/SLGTIMIS/index.php',
    ],
    [
        'REQUEST_URI' => '/SLGTIMIS/index.php/dashboard',
        'SCRIPT_NAME' => '/SLGTIMIS/index.php',
    ],
    [
        'REQUEST_URI' => '/dashboard',
        'SCRIPT_NAME' => '/SLGTIMIS/index.php',
    ],
    [
        'REQUEST_URI' => '/SLGTIMIS/Dashboard',
        'SCRIPT_NAME' => '/SLGTIMIS/index.php',
    ],
];

$failed = 0;
foreach ($cases as $i => $server) {
    $_SERVER['REQUEST_URI'] = $server['REQUEST_URI'];
    $_SERVER['SCRIPT_NAME'] = $server['SCRIPT_NAME'];
    unset($_SERVER['REDIRECT_URL'], $_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);
    $uri = RequestPath::resolve();
    $ok = strcasecmp($uri, 'dashboard') === 0;
    echo ($ok ? 'OK ' : 'FAIL ') . $server['REQUEST_URI'] . ' => ' . $uri . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

$router = new Router();
ob_start();
// Don't dispatch real controllers — just confirm route key resolution via reflection
$ref = new ReflectionClass($router);
$prop = $ref->getProperty('routes');
$prop->setAccessible(true);
$routes = $prop->getValue($router);
echo isset($routes['dashboard']) ? "route dashboard exists\n" : "route dashboard MISSING\n";
ob_end_clean();

exit($failed > 0 ? 1 : 0);
