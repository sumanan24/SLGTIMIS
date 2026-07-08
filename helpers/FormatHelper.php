<?php
/**
 * Display formatting helpers.
 */

class FormatHelper {
    /**
     * Format a person's name: first letter of each word capital, rest lowercase.
     * e.g. "KUMARASINGHE ARACHCHIGE DON SAMAN" → "Kumarasinghe Arachchige Don Saman"
     */
    public static function personName(?string $name): string {
        if ($name === null) {
            return '';
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            return '';
        }

        if (function_exists('mb_convert_case') && function_exists('mb_strtolower')) {
            return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($name));
    }
}
