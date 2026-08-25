<?php
/**
 * Examination roll / index numbers (SLGTI/KA/ICT/05/E/001).
 */

class ExamRollHelper {
    /**
     * @param list<array<string, mixed>> $students
     * @return list<array<string, mixed>>
     */
    public static function assignRollNumbersToStudents(array $exam, array $students): array {
        $seq = 0;
        foreach ($students as &$row) {
            $seq++;
            $roll = self::formatRollNumberForExam($exam, $seq);
            $lines = self::splitRollLines($roll);
            $row['roll_number'] = $roll;
            $row['roll_line1'] = $lines['line1'];
            $row['roll_line2'] = $lines['line2'];
        }
        unset($row);

        return $students;
    }

    /**
     * @param list<string> $studentIds Ordered student IDs (same order as exam registration list)
     * @return array<string, string> student_id => full roll number
     */
    public static function buildRollMapForExam(array $exam, array $studentIds): array {
        $map = [];
        $seq = 0;
        foreach ($studentIds as $sid) {
            $sid = trim((string) $sid);
            if ($sid === '') {
                continue;
            }
            $seq++;
            $map[$sid] = self::formatRollNumberForExam($exam, $seq);
        }

        return $map;
    }

    public static function formatRollNumberForExam(array $exam, int $sequence): string {
        $sequence = max(1, $sequence);
        $num = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        return self::rollPrefixFromExam($exam) . '/' . $num;
    }

    public static function rollPrefixFromExam(array $exam): string {
        return 'SLGTI/'
            . self::venueCodeFromExam($exam) . '/'
            . self::departmentCodeFromExam($exam) . '/'
            . self::nvqLevelFromExam($exam) . '/'
            . self::mediumLetterForExam();
    }

    /**
     * Split roll for sticker / table display (matches assets/js/student-barcode-sticker.js).
     *
     * @return array{line1: string, line2: string}
     */
    public static function splitRollLines(string $rollNumber): array {
        $code = trim($rollNumber);
        $code = trim($code, '/');
        if ($code === '') {
            return ['line1' => '', 'line2' => ''];
        }

        $parts = array_values(array_filter(array_map('trim', explode('/', $code)), static function ($p) {
            return $p !== '';
        }));

        if (count($parts) >= 4) {
            return [
                'line1' => implode('/', array_slice($parts, 0, 3)),
                'line2' => implode('/', array_slice($parts, 3)),
            ];
        }
        if (count($parts) === 3) {
            return [
                'line1' => implode('/', array_slice($parts, 0, 2)),
                'line2' => $parts[2],
            ];
        }

        return ['line1' => $code, 'line2' => ''];
    }

    public static function venueCodeFromExam(array $exam): string {
        $raw = strtoupper(trim((string) ($exam['location'] ?? '')));
        $compact = preg_replace('/[^A-Z0-9]+/', '', $raw) ?? '';
        if ($compact === '' || $compact === 'SLGTI' || str_contains($compact, 'KILINOCHCHI')) {
            return 'KA';
        }
        if (strlen($compact) >= 2) {
            return substr($compact, 0, 2);
        }

        return $compact !== '' ? $compact : 'KA';
    }

    public static function departmentCodeFromExam(array $exam): string {
        $dept = strtoupper(preg_replace('/[^A-Za-z0-9._-]+/', '', trim((string) ($exam['department_id'] ?? ''))) ?? '');

        return $dept !== '' ? $dept : 'GEN';
    }

    public static function nvqLevelFromExam(array $exam): string {
        $nvq = trim((string) ($exam['course_nvq_level'] ?? ''));
        if ($nvq === '') {
            return '00';
        }
        $digits = preg_replace('/\D/', '', $nvq);
        if ($digits === null || $digits === '') {
            return '00';
        }

        return str_pad((string) (int) $digits, 2, '0', STR_PAD_LEFT);
    }

    public static function mediumLetterForExam(): string {
        return 'E';
    }
}
