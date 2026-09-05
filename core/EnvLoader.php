<?php
/**
 * Minimal .env loader (KEY=VALUE). Does not override existing getenv/$_ENV.
 */
declare(strict_types=1);

class EnvLoader {
    private static bool $loaded = false;

    public static function load(?string $path = null): void {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        $file = $path ?? (defined('BASE_PATH') ? BASE_PATH . '/.env' : dirname(__DIR__) . '/.env');
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '') {
                continue;
            }
            if (
                (strlen($value) >= 2)
                && (($value[0] === '"' && substr($value, -1) === '"')
                    || ($value[0] === "'" && substr($value, -1) === "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string {
        self::load();
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? null;
        }
        if ($v === null || $v === '') {
            return $default;
        }
        return (string) $v;
    }
}
