<?php
/**
 * Resolve the app-relative route (e.g. "dashboard") from Apache/PHP server vars.
 */

class RequestPath {
    /**
     * @return string Route path with no leading/trailing slash
     */
    public static function resolve(): string {
        $path = self::rawPath();
        $path = str_replace('\\', '/', $path);
        $path = rawurldecode($path);

        foreach (self::basePrefixes() as $base) {
            if ($path === $base || strpos($path, $base . '/') === 0) {
                $path = substr($path, strlen($base));
                break;
            }
        }

        $path = trim((string) $path, '/');

        // Rewrite / ErrorDocument leftovers: SLGTIMIS/dashboard → dashboard
        while (stripos($path, 'slgtimis/') === 0) {
            $path = substr($path, 9);
        }
        if (strcasecmp($path, 'slgtimis') === 0) {
            $path = '';
        }

        if (stripos($path, 'index.php/') === 0) {
            $path = substr($path, 10);
        } elseif (strcasecmp($path, 'index.php') === 0) {
            $path = '';
        }

        return trim($path, '/');
    }

    private static function rawPath(): string {
        foreach (['REQUEST_URI', 'REDIRECT_URL'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $parsed = parse_url((string) $_SERVER[$key], PHP_URL_PATH);
            if (is_string($parsed) && $parsed !== '') {
                return $parsed;
            }
        }

        foreach (['PATH_INFO', 'ORIG_PATH_INFO'] as $key) {
            if (!empty($_SERVER[$key]) && $_SERVER[$key] !== '/') {
                return (string) $_SERVER[$key];
            }
        }

        return '/';
    }

    /**
     * @return list<string>
     */
    private static function basePrefixes(): array {
        $bases = [];
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && $scriptDir !== '.') {
            $bases[] = $scriptDir;
        }
        if (defined('APP_URL')) {
            $appPath = parse_url(APP_URL, PHP_URL_PATH);
            if (is_string($appPath) && $appPath !== '' && $appPath !== '/') {
                $bases[] = rtrim(str_replace('\\', '/', $appPath), '/');
            }
        }
        $bases[] = '/SLGTIMIS';

        return array_values(array_unique($bases));
    }
}
