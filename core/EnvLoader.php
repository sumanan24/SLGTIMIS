<?php
/**
 * Minimal .env loader (KEY=VALUE). Empty existing getenv values are overridden by .env.
 */
declare(strict_types=1);

class EnvLoader {
    private static bool $loaded = false;
    private static ?string $loadedPath = null;

    public static function load(?string $path = null): void {
        $file = $path ?? (defined('BASE_PATH') ? BASE_PATH . '/.env' : dirname(__DIR__) . '/.env');
        if (self::$loaded && self::$loadedPath === $file) {
            return;
        }
        self::$loaded = true;
        self::$loadedPath = $file;
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $i => $line) {
            $line = trim((string) $line);
            if ($i === 0) {
                // Strip UTF-8 BOM if present
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
            }
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
            $existing = getenv($name);
            // Apply .env when unset OR empty (empty server env must not block .env)
            if ($existing === false || $existing === '') {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string {
        if (!self::$loaded) {
            self::load();
        }
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? null;
        }
        if ($v === null || $v === '') {
            return $default;
        }
        return (string) $v;
    }

    /** Whether the .env file was found on the last load attempt. */
    public static function envFileExists(): bool {
        if (!self::$loaded) {
            self::load();
        }
        $file = self::$loadedPath ?? (defined('BASE_PATH') ? BASE_PATH . '/.env' : dirname(__DIR__) . '/.env');
        return is_file($file) && is_readable($file);
    }
}
