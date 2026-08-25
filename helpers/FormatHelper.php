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

    /**
     * Name with initials for exam/admission displays (student.student_ininame, else full name).
     */
    public static function studentInitialsName(array $student): string {
        $ininame = trim((string) ($student['student_ininame'] ?? ''));
        if ($ininame !== '') {
            // Keep stored initials (e.g. R.A.D. Dharsan, A.Infas Ahamed) — do not title-case abbreviations.
            if (preg_match('/\.[A-Za-z]/', $ininame)) {
                return $ininame;
            }

            return self::personName($ininame);
        }

        return self::personName(trim((string) ($student['student_fullname'] ?? '')));
    }
}
