<?php
namespace local_slgtiacademic;

defined('MOODLE_INTERNAL') || die();

/**
 * The six SLGTI academic departments. Codes match the SIS department ids.
 */
class departments {
    /**
     * @return array<string,string>
     */
    public static function all(): array {
        return [
            'AUT' => 'Automotive & Agriculture Technology',
            'EET' => 'Electrical & Electronic Technology',
            'MEC' => 'Mechanical Technology',
            'FDT' => 'Food Technology',
            'ICT' => 'Information & Communication Technology',
            'COT' => 'Construction Technology',
        ];
    }

    public static function name(string $code): string {
        $code = strtoupper(trim($code));
        $all = self::all();
        if ($code === '') {
            return get_string('unassigned', 'local_slgtiacademic');
        }
        return $all[$code] ?? $code;
    }

    public static function valid(string $code): bool {
        return array_key_exists(strtoupper($code), self::all());
    }
}
