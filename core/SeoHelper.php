<?php
/**
 * SEO helpers — titles and meta built around head word SLGTI
 */

class SeoHelper {
    private static ?array $config = null;

    public static function config(): array {
        if (self::$config === null) {
            self::$config = require BASE_PATH . '/config/seo.php';
        }
        return self::$config;
    }

    public static function headWord(): string {
        return self::config()['head_word'];
    }

    /**
     * Page title: "SLGTI | {page}" or default site title
     */
    public static function title(?string $pageTitle = null): string {
        $cfg = self::config();
        $hw = $cfg['head_word'];
        $pageTitle = trim((string) $pageTitle);
        if ($pageTitle === '') {
            return $cfg['site_name'];
        }
        if (stripos($pageTitle, $hw) === 0) {
            return $pageTitle;
        }
        return $hw . ' | ' . $pageTitle;
    }

    public static function canonical(?string $path = null): string {
        if ($path === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        }
        $path = '/' . ltrim($path, '/');
        return rtrim(APP_URL, '/') . $path;
    }

    public static function absoluteUrl(string $relativePath): string {
        $relativePath = '/' . ltrim($relativePath, '/');
        return rtrim(APP_URL, '/') . $relativePath;
    }

    public static function ogImageUrl(): string {
        $cfg = self::config();
        return self::absoluteUrl($cfg['og_image'] ?? '/assets/img/logo.png');
    }
}
